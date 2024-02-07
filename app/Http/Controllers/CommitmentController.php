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
            'start_date' => 'required',
            'end_date' => 'required',
            'status' => 'required',
            'budget' => 'required',
            'img_url'=>'required|file|mimes:jpg,png|max:2048'
        ]);

//        return [];
//        if ($request->file('img_url')->isValid()) {
            $file = $request->file('img_url');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $fileName); // Move the file to a directory (here, 'uploads')

//        }

        $dt_start = new \DateTime($request->start_date);
        $dt_end = new \DateTime($request->end_date);
        $diff = $dt_start->diff($dt_end);
        $duration = $diff->format('%a');

        $commitment = new Commitment();
        $commitment->sector_id = $request->sector_id;
        $commitment->name = $request->name;
        $commitment->type = $request->type;
        $commitment->description = $request->description;
        $commitment->duration_in_days = $duration;
        $commitment->start_date = $request->start_date;
        $commitment->end_date = $request->end_date;
        $commitment->status = $request->status;
        $commitment->budget = $request->budget;
        $commitment->img_url = $fileName;
        $commitment->save();

        return redirect()->back()->with('success', 'Commitment created successfully');
    }

    public function deliverables(Request $request, Commitment $commitment)
    {

        $deliverables = $commitment->deliverables()->get();
        return view('pages.sector.deliverables', compact('commitment', 'deliverables'));
    }

    public function update(Request $request)
    {
        $commitment = Commitment::find($request->commitment_id);
        $request->validate([
            'commitment_title' => 'required|unique:commitments,commitment_title,' . $commitment->id . '|max:255',
            'description' => 'required|max:255',
            // Add other validation rules as needed
        ]);

        $commitment->update($request->all());

        return redirect()->route('sectors.view', [$commitment->sector_id, $commitment->id]);
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
