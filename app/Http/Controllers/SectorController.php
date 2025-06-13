<?php

namespace App\Http\Controllers;

use App\Models\CommitmentBudget;
use App\Models\Sector;
use App\Models\SectorBudget;
use App\Models\SectorFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        return view('pages.sector.index', compact('sectors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sector_name' => 'required|unique:sectors|max:255',
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

    public function view(Request $request, $id, $comm_id = null)
    {
        $sector = Sector::find($id);
        $commitments = $sector->__commitments()->orderBy('created_at', 'desc')->get();
        return view('pages.sector.view', compact('sector', 'commitments', 'comm_id'));
    }

    public function show(Request $request, $id)
    {

        $sector = Sector::find($id);
        $commitments = $sector->__commitments()->get();
        $baseYear = 2023;
        $targetYear = 2024;
        $alphas = range("A", "Z");
        $spreadsheet = new Spreadsheet();
        // Add data to the first sheet
        $sheet = $spreadsheet->getActiveSheet();


// Set styles for merged cell
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];
        $styleVertArray = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
//        $sheet->setCellValue('A1', 'Merged Cells');
        $sheet->setCellValue('A2', 'SN');
        $sheet->setCellValue('B2', 'Performance Tracking and Measuring Framework');
        $sheet->setCellValue('B3', 'Expected Output Deliverables and Performance Measurement');
        $sheet->setCellValue('B4', 'Basic Information Entry');
        $sheet->setCellValue('B5', 'Commitment');
        $sheet->setCellValue('C5', 'Output Deliverable');
        $sheet->setCellValue('D5', 'Output KPIs');
        $sheet->setCellValue('E5', 'Unit of Measurement');
        $sheet->setCellValue('F5', "Baseline Value (Actual-$baseYear)");
        $sheet->setCellValue('G5', "$targetYear Target");
        $quarter = 1;
        for ($numb = 7; $numb < 23; $numb += 4) {
            $sheet->setCellValue($alphas[$numb] . "4", "$targetYear-Quarter-$quarter");
            $sheet->mergeCells("{$alphas[$numb]}4:{$alphas[$numb+3]}4");
            $sheet->getStyle("{$alphas[$numb]}4:{$alphas[$numb+3]}4")->applyFromArray($styleArray);
            $sheet->setCellValue("{$alphas[$numb]}5", "Delivery Milestone");
            $sheet->setCellValue("{$alphas[$numb+1]}5", "Actual Delivery");
            $sheet->setCellValue("{$alphas[$numb+2]}5", "Performance Measure");
            $sheet->setCellValue("{$alphas[$numb+3]}5", "Performance Score (% of milestone)");
            $sheet->getStyle("{$alphas[$numb]}5:{$alphas[$numb+3]}5")->applyFromArray($styleArray);

            $quarter++;
        }

        $cellPos = 6;
        $sn = 1;
        foreach ($commitments as $commitment) {
            $kpiCount = $commitment->countKPI();
            $commCount = $commitment->deliverables->count();
            $count = max($kpiCount, $commCount, 1);
            $sheet->setCellValue("A{$cellPos}", $sn);
            $sheet->setCellValue("B{$cellPos}", $commitment->name);
            if ($count > 1) {
                $cellAdr = "A{$cellPos}:A" . ($cellPos - 1 + $count);
                $sheet->mergeCells($cellAdr);
                $sheet->getStyle($cellAdr)->applyFromArray($styleArray);

                $cellAdr = str_replace("A", "B", $cellAdr);
                $sheet->mergeCells($cellAdr);
                $sheet->getStyle($cellAdr)->applyFromArray($styleVertArray);
            }

            $delCellPosBegin = $cellPos;
            foreach ($commitment->deliverables as $deliverable) {
                $kpiCountDel = $deliverable->countKPI();
                $countDel = max(1, $kpiCountDel);
                $sheet->setCellValue("C{$delCellPosBegin}", $deliverable->deliverable);
                if ($countDel > 1) {
                    $cellAdr = "C{$delCellPosBegin}:C" . ($delCellPosBegin - 1 + $countDel);
                    $sheet->mergeCells($cellAdr);
                    $sheet->getStyle($cellAdr)->applyFromArray($styleVertArray);
                }

                $dIndex = $delCellPosBegin;
                foreach ($deliverable->kpis as $kpi) {
                    $target = $kpi->kpiTargets($targetYear)->first();
                    $quart1 = $kpi->quarter();
                    $quart2 = $kpi->quarter(2);
                    $quart3 = $kpi->quarter(3);
                    $quart4 = $kpi->quarter(4);
                    $pscor1 = number_format($quart1 && $quart1->milestone && $quart1->delivery_department_value ? ($quart1->delivery_department_value / $quart1->milestone) * 100 : 0, 2);
                    $pscor2 = number_format($quart2 && $quart2->milestone && $quart2->delivery_department_value ? ($quart2->delivery_department_value / $quart2->milestone) * 100 : 0, 2);
                    $pscor3 = number_format($quart3 && $quart3->milestone && $quart3->delivery_department_value ? ($quart3->delivery_department_value / $quart3->milestone) * 100 : 0, 2);
                    $pscor4 = number_format($quart4 && $quart4->milestone && $quart4->delivery_department_value ? ($quart4->delivery_department_value / $quart4->milestone) * 100 : 0, 2);
                    $remark = "";
                    $lastValue = 0;
                    foreach (range(1, 4) as $indx) {
                        $obj = "quart" . $indx;
                        if ($$obj && $$obj->delivery_department_remark) {
                            $remark .= $$obj->delivery_department_remark . ".\n";
                        }
                        if ($$obj && $$obj->delivery_department_value) {
                            $lastValue = $$obj->delivery_department_value;
                        }
                    }

                    $sheet->setCellValue("D{$dIndex}", $kpi->kpi);
                    $sheet->setCellValue("E{$dIndex}", $kpi->unit_of_measurement);
                    $sheet->setCellValue("F{$dIndex}", $kpi->target_value);
                    $sheet->setCellValue("G{$dIndex}", $target ? $target->target : "");

                    $sheet->setCellValue("H{$dIndex}", $quart1 ? $quart1->milestone : "");
                    $sheet->setCellValue("I{$dIndex}", $quart1 ? $quart1->actual_value : "");
                    $sheet->setCellValue("J{$dIndex}", $quart1 ? $quart1->delivery_department_value : "");
                    $sheet->setCellValue("K{$dIndex}", $pscor1);

                    $sheet->setCellValue("L{$dIndex}", $quart2 ? $quart2->milestone : "");
                    $sheet->setCellValue("M{$dIndex}", $quart2 ? $quart2->actual_value : "");
                    $sheet->setCellValue("N{$dIndex}", $quart2 ? $quart2->delivery_department_value : "");
                    $sheet->setCellValue("O{$dIndex}", $pscor2);

                    $sheet->setCellValue("P{$dIndex}", $quart3 ? $quart3->milestone : "");
                    $sheet->setCellValue("Q{$dIndex}", $quart3 ? $quart3->actual_value : "");
                    $sheet->setCellValue("R{$dIndex}", $quart3 ? $quart3->delivery_department_value : "");
                    $sheet->setCellValue("S{$dIndex}", $pscor3);

                    $sheet->setCellValue("T{$dIndex}", $quart4 ? $quart4->milestone : "");
                    $sheet->setCellValue("U{$dIndex}", $quart4 ? $quart4->actual_value : "");
                    $sheet->setCellValue("V{$dIndex}", $quart4 ? $quart4->delivery_department_value : "");
                    $sheet->setCellValue("W{$dIndex}", $pscor4);
                    $sheet->setCellValue("X{$dIndex}", $remark);
                    $sheet->setCellValue("Y{$dIndex}", $target ? (doubleval($target->target) - doubleval($lastValue) < 1 ? 0 : $target->target - $lastValue) : 0);

                    $dIndex++;
                }

                $delCellPosBegin = $delCellPosBegin + $deliverable->countKPI();
            }
            $sn++;
            $cellPos += $count;
        }
// Merge cells
//        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:A5');
        $sheet->mergeCells('B2:Y2');
        $sheet->mergeCells('B3:O3');
        $sheet->mergeCells('B4:G4');

//        $sheet->getStyle('A1:G1')->applyFromArray($styleArray);
        $sheet->getStyle('A2:A5')->applyFromArray($styleArray);
        $sheet->getStyle('B2:Y2')->applyFromArray($styleArray);
        $sheet->getStyle('B3:O3')->applyFromArray($styleArray);
        $sheet->getStyle('B4:G4')->applyFromArray($styleArray);

        foreach (range('A', 'Y') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

// Create a writer and save the spreadsheet to a file
        $writer = new Xlsx($spreadsheet);
        $filePath = 'report_' . time() . '.xlsx';
        $writer->save($filePath);
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function edit(Sector $sector)
    {
        return view('sectors.edit', compact('sector'));
    }

    public function update(Request $request)
    {
        $sector = Sector::find($request->id);
        $request->validate([
            'sector_name' => 'required|unique:sectors,sector_name,' . $sector->id . '|max:255',
            'description' => 'required|max:255',
            // Add other validation rules as needed
        ]);

        $sector->update($request->all());

        return redirect()->route('sectors.index')->with('success', 'Sector updated successfully');
    }

    public function destroy(Sector $sector)
    {
        if (count($sector->commitments()->get()) == 0) {
            $sector->delete();
            return redirect()->route('sectors.index')->with('success', 'Sector deleted successfully');
        } else
            return back()->with('failure', 'This sector cannot be deleted as it has commitment(s) assigned to it. Remove the commitment(s) and try again');

    }

    public function budget(Request $request)
    {
        $sector_id = $request->sector_id;
        $year = $request->year;

        $budgets = CommitmentBudget::leftJoin('commitments', function ($join) use ($year) {
            $join->on('commitments.id', '=', 'commitment_budgets.commitment_id')
                ->on('commitment_budgets.year', '=', DB::raw($year));
        })
            ->where(['year' => $year, 'sector_id' => $sector_id])
            ->get();

        return view("pages.sector.commitent_budget", compact('budgets', 'year'));
    }

    public function targets(Request $request)
    {



    }
}
