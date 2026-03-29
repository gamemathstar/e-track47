<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\CommitmentBudget;
use App\Models\Deliverable;
use App\Models\SectorBudget;
use App\Traits\ChecksDataEntryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CommitmentController extends Controller
{
    use ChecksDataEntryAccess;

    //
    public function __construct()
    {
        $this->middleware("auth");
    }


    public function storeBudget(Request $request)
    {
//        return $request;
        $request->validate([
            'commitment_id' => 'required|exists:commitments,id',
            'amount' => 'required|max:255',
            'year' => 'required|integer',
            // Add other validation rules as needed
        ]);

        $bdg = new CommitmentBudget();
        $bdg->year = $request->year;
        $bdg->commitment_id = $request->commitment_id;
        $bdg->amount = $request->amount;
        $bdg->save();
        return back();
    }

    public function store(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Only PDCU users can create commitments
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can create commitments.');
        }

        // Check data entry access
        $request->validate([
            'sector_id' => "required",
        ]);
        
        $accessCheck = $this->checkDataEntryAccess($request->sector_id);
        if ($accessCheck) {
            return $accessCheck;
        }

//        return $request;
        $request->validate([
            'sector_id' => "required",
            'name' => "required",
            'type' => "required",
            'description' => "required",
            'status' => 'required',
//            'budget' => 'required',
            'img_url' => 'required|file|mimes:jpg,png|max:2048'
        ]);

//        return [];
//        if ($request->file('img_url')->isValid()) {
        $file = $request->file('img_url');
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads'), $fileName); // Move the file to a directory (here, 'uploads')

//        }

        $commitment = new Commitment();
        $commitment->sector_id = $request->sector_id;
        $commitment->name = $request->name;
        $commitment->type = $request->type;
        $commitment->description = $request->description;
        $commitment->status = $request->status;
//        $commitment->budget = $request->budget;
        $commitment->img_url = $fileName;
        $commitment->save();

        return redirect()->back()->with('success', 'Commitment created successfully');
    }

    public function changePhoto(Request $request)
    {
        $request->validate([
            'commitment_id' => "required",
            'img_url' => 'required|file|mimes:jpg,png|max:2048'
        ]);

        $file = $request->file('img_url');
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads'), $fileName);

        $commitment = Commitment::where(['id' => $request->commitment_id])->first();
        $commitment->img_url = $fileName;
        $commitment->save();

        return redirect()->back()->with('success', 'Commitment photo changed successfully');
    }

    /**
     * Commitment deliverables via encrypted query param (canonical URL).
     */
    public function deliverablesFromEncrypted(Request $request)
    {
        if (!$request->filled('e')) {
            return redirect()->route('dashboard')->with('failure', 'Invalid commitment link.');
        }
        try {
            $payload = Crypt::decrypt(rawurldecode($request->input('e')));
            $data = json_decode($payload, true);
            if (!is_array($data) || empty($data['id'])) {
                return redirect()->route('dashboard')->with('failure', 'Invalid commitment link.');
            }
            $commitment = Commitment::find((int) $data['id']);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('failure', 'Invalid commitment link.');
        }
        if (!$commitment) {
            return redirect()->route('dashboard')->with('failure', 'Commitment not found.');
        }

        return $this->deliverablesWithCommitment($request, $commitment);
    }

    /**
     * Commitment deliverables via path (GET redirects to encrypted URL).
     */
    public function deliverables(Request $request, Commitment $commitment)
    {
        if ($request->isMethod('GET')) {
            return redirect()->to(commitment_deliverables_url($commitment->id));
        }

        return $this->deliverablesWithCommitment($request, $commitment);
    }

    private function deliverablesWithCommitment(Request $request, Commitment $commitment)
    {
        $deliverables = $commitment->deliverables()->get();

        return view('pages.sector.deliverables', compact('commitment', 'deliverables'));
    }

    public function update(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Only PDCU users can update commitments
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can update commitments.');
        }

        $request->validate([
            'commitment_id' => 'required|exists:commitments,id',
        ]);

        $commitment = Commitment::find($request->commitment_id);
        
        // Check if data is locked (confirmed by Coordinator)
        if ($commitment) {
            // Check if any performance tracking for this commitment's deliverables is confirmed
            $hasConfirmedTracking = \App\Models\PerformanceTracking::whereHas('kpi.deliverable', function($q) use ($commitment) {
                $q->where('commitment_id', $commitment->id);
            })
            ->where('confirmation_status', 'Confirmed')
            ->whereNotNull('coordinator_confirmed_at')
            ->exists();
            
            if ($hasConfirmedTracking) {
                return redirect()->back()->with('failure', 'This commitment has confirmed performance tracking and cannot be modified.');
            }
        }
        
        // Check data entry access
        if ($commitment) {
            $accessCheck = $this->checkDataEntryAccess($commitment->sector_id);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

        $request->validate([
            'commitment_id' => 'required|exists:commitments,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|in:Not Started,In Progress,Completed',
            'img_url' => 'nullable|file|mimes:jpg,png,jpeg|max:2048'
        ]);
        
        if (!$commitment) {
            return redirect()->back()->with('failure', 'Commitment not found');
        }

        // Update commitment fields
        $commitment->name = $request->name;
        $commitment->type = $request->type;
        $commitment->description = $request->description;
        $commitment->status = $request->status;

        // Handle image upload if provided
        if ($request->hasFile('img_url')) {
            $file = $request->file('img_url');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $fileName);
            $commitment->img_url = $fileName;
        }

        $commitment->save();

        return redirect()->back()->with('success', 'Commitment updated successfully');
    }

    public function delete(Commitment $commitment)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Only PDCU users can delete commitments
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can delete commitments.');
        }

        // Check if data is locked (confirmed by Coordinator)
        $hasConfirmedTracking = \App\Models\PerformanceTracking::whereHas('kpi.deliverable', function($q) use ($commitment) {
            $q->where('commitment_id', $commitment->id);
        })
        ->where('confirmation_status', 'Confirmed')
        ->whereNotNull('coordinator_confirmed_at')
        ->exists();
        
        if ($hasConfirmedTracking) {
            return redirect()->back()->with('failure', 'This commitment has confirmed performance tracking and cannot be deleted.');
        }

        // Check data entry access
        $accessCheck = $this->checkDataEntryAccess($commitment->sector_id);
        if ($accessCheck) {
            return $accessCheck;
        }

        if (count($commitment->deliverables()->get()) == 0) {
            $commitment->delete();
            return back()->with('success', 'Commitment deleted successfully');
        } else
            return back()->with('failure', 'Oops! This commitment cannot be deleted as it has deliverable(s) attached. Remove the deliverable(s) and try again.');
    }
}
