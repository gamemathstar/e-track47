<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\CommitmentBudget;
use App\Models\Deliverable;
use App\Models\SectorBudget;
use Illuminate\Http\Request;

class CommitmentController extends Controller
{
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

    public function deliverables(Request $request, Commitment $commitment)
    {

        $deliverables = $commitment->deliverables()->get();
        return view('pages.sector.deliverables', compact('commitment', 'deliverables'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'commitment_id' => 'required|exists:commitments,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|in:Not Started,In Progress,Completed',
            'img_url' => 'nullable|file|mimes:jpg,png,jpeg|max:2048'
        ]);

        $commitment = Commitment::find($request->commitment_id);
        
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
        if (count($commitment->deliverables()->get()) == 0) {
            $commitment->delete();
            return back()->with('success', 'Commitment deleted successfully');
        } else
            return back()->with('failure', 'Oops! This commitment cannot be deleted as it has deliverable(s) attached. Remove the deliverable(s) and try again.');
    }
}
