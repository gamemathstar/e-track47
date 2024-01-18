<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\CommitmentBudget;
use App\Models\DeliveryKpi;
use App\Models\Sector;
use App\Models\SectorBudget;
use App\Models\SectorFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectorController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index(Request $request)
    {
        $sectors = Sector::get();
        return view('pages.sector.index',compact('sectors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:sectors|max:255',
            'description' => 'required|max:255',
            // Add other validation rules as needed
        ]);

        Sector::create($request->all());

        return redirect()->route('sectors.index')->with('success', 'Sector created successfully');
    }

    public function storeBudget(Request $request)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'amount' => 'required|max:255',
            'year' => 'required|integer',
            // Add other validation rules as needed
        ]);

        $bdg = new SectorBudget();
        $bdg->year = $request->year;
        $bdg->sector_id = $request->sector_id;
        $bdg->amount = $request->amount;
        $bdg->save();
        return back();
    }

    public function storeDoc(Request $request)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'title' => 'required|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            // Add other validation rules as needed
        ]);
// Check if the request has an uploaded file
        if ($request->hasFile('image')) {
            // Get the file from the request
            $image = $request->file('image');

            // Generate a unique name for the file
            $imageName = time() . '_' . $image->getClientOriginalName();

            // Specify the disk to store the file (you can change it to 'public', 's3', etc.)
            $disk = 'public';

            // Store the file in the specified disk
            $path = $image->storeAs('uploads', $imageName, $disk);

            // You can also store the file details in the database if needed
            // Example: Image::create(['path' => $path, 'name' => $imageName]);
            $doc = new SectorFile();
            $doc->url = $path;
            $doc->sector_id = $request->sector_id;
            $doc->title = $request->title;
            $doc->type = 'image';
            $doc->save();

            // Return a response, redirect, or any other logic based on your requirements
            return back()->withErrors(['message' => 'Image uploaded successfully', 'path' => $path]);
        }

        return back();
    }

    public function view(Request $request,$id,$comm_id=null)
    {
        $sector = Sector::find($id);
        $commitments = $sector->__commitments()->get();
        return view('pages.sector.view', compact('sector','commitments','comm_id'));
    }

    public function show(Request $request,$id)
    {
        $sector = Sector::find($id);
        $commitments = $sector->__commitments()->get();
        $commitmentsX = Commitment::with('deliverables.kpis')->get();
        $years = DeliveryKpi::distinct('year')->orderBy('year','ASC')->pluck('year')->toArray();
        $lyear = $years[0];
        $head = $sector->head();
        return view('pages.sector.show', compact('lyear','sector','commitments','head','commitmentsX','years'));
    }

    public function edit(Sector $sector)
    {
        return view('sectors.edit', compact('sector'));
    }

    public function update(Request $request)
    {
        $sector = Sector::find($request->id);
        $request->validate([
            'name' => 'required|unique:sectors,name,' . $sector->id . '|max:255',
            'description' => 'required|max:255',
            // Add other validation rules as needed
        ]);

        $sector->update($request->all());

        return redirect()->route('sectors.index')->with('success', 'Sector updated successfully');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();

        return redirect()->route('sectors.index')->with('success', 'Sector deleted successfully');
    }

    public function budget(Request $request)
    {
        $sector_id = $request->sector_id;
        $year = $request->year;

        $budgets = CommitmentBudget::leftJoin('commitments',function ($join) use($year){
            $join->on('commitments.id','=','commitment_budgets.commitment_id')
                ->on('commitment_budgets.year','=',DB::raw($year));
        })
            ->where(['year'=>$year,'sector_id'=>$sector_id])
            ->get();

        return view("pages.sector.commitent_budget",compact('budgets','year'));
    }
}
