<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\CommitmentBudget;
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
            'sector_id'=>"required",
            'commitment_title'=>"required",
            'description'=>"required",
        ]);

        Commitment::create($request->all());

        return redirect()->back()->with('success', 'Commitment created successfully');
    }

    public function deliverables(Request $request,$id)
    {
        $commitment = Commitment::find($id);
        return view('pages.sector.load_deliverables',compact('commitment'));
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

        return redirect()->route('sectors.view',[$commitment->sector_id,$commitment->id]);
    }
}
