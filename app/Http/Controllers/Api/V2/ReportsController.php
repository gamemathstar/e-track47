<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Reports\GenerateComprehensiveReportRequest;
use App\Http\Requests\V2\Reports\GenerateComprehensiveRequest;
use App\Http\Requests\V2\Reports\GenerateWordDocumentRequest;
use App\Http\Requests\V2\Reports\GenerateWordRequest;
use App\Http\Requests\V2\Reports\ReportSetupRequest;
use App\Services\V2\ReportsService;
use Illuminate\Http\Request;

/**
 * Reports (API_REFERENCE.md §11.8).
 */
class ReportsController extends BaseController
{
    public function __construct(private readonly ReportsService $reports)
    {
    }

    public function hub(Request $request): array
    {
        $validated = $request->validate([
            'sectorId' => ['nullable', 'string'],
            'quarter' => ['nullable', 'in:q1,q2,q3,q4'],
            'year' => ['nullable', 'integer'],
        ]);

        return $this->reports->hub(
            $validated['sectorId'] ?? null,
            $validated['quarter'] ?? null,
            isset($validated['year']) ? (int) $validated['year'] : null,
        );
    }

    public function setupPreview(ReportSetupRequest $request): array
    {
        return $this->reports->setupPreview($request->validated());
    }

    public function viewer(ReportSetupRequest $request): array
    {
        return $this->reports->viewerContent($request->validated());
    }

    public function comprehensive(GenerateComprehensiveRequest $request): array
    {
        return $this->reports->generateComprehensive($request->user(), $request->validated());
    }

    public function comprehensiveReport(GenerateComprehensiveReportRequest $request): array
    {
        return $this->reports->generateComprehensiveReport($request->user(), $request->validated());
    }

    public function wordDocument(GenerateWordDocumentRequest $request): array
    {
        return $this->reports->generateWordDocument($request->user(), $request->validated());
    }

    public function word(GenerateWordRequest $request): array
    {
        return $this->reports->generateWord($request->user(), $request->validated());
    }

    public function printPreview(Request $request): array
    {
        return $this->reports->printPreview();
    }
}
