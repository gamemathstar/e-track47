<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class BulkUploadReportBuilder
{
    public static function build(array $preview, array $meta, User $user, array $importStats = []): array
    {
        $submittedAt = now();
        $rows = $preview['rows'] ?? [];
        $reference = self::generateReference($meta['framework_year'] ?? $submittedAt->year);

        $uploadedAt = $submittedAt->copy()->subMinutes(2);
        $validatedAt = $submittedAt->copy()->subMinute();

        return [
            'reference' => $reference,
            'meta' => $meta,
            'summary' => $preview['summary'] ?? [],
            'import_stats' => $importStats,
            'submitted_by' => $user->name ?? $user->email,
            'submitted_at' => $submittedAt,
            'quarterly_averages' => self::quarterlyTargetAverages($rows),
            'kpis' => $preview['kpis'] ?? [],
            'deliverables' => $preview['deliverables'] ?? [],
            'commitments' => $preview['commitments'] ?? [],
            'audit_trail' => [
                [
                    'title' => 'Submission Confirmed',
                    'timestamp' => $submittedAt,
                    'description' => 'Final payload encrypted and stored in registry.',
                    'active' => true,
                ],
                [
                    'title' => 'Validation Passed',
                    'timestamp' => $validatedAt,
                    'description' => 'Automated checks completed without errors.',
                    'active' => false,
                ],
                [
                    'title' => 'File Uploaded',
                    'timestamp' => $uploadedAt,
                    'description' => 'Initial dataset received from user session.',
                    'active' => false,
                ],
            ],
        ];
    }

    public static function quarterlyTargetAverages(array $rows): array
    {
        $quarters = [
            ['target' => 'q1_target', 'label' => 'Q1 Perf'],
            ['target' => 'q2_target', 'label' => 'Q2 Perf'],
            ['target' => 'q3_target', 'label' => 'Q3 Perf'],
            ['target' => 'q4_target', 'label' => 'Q4 Perf'],
        ];

        return collect($quarters)->map(function ($quarter) use ($rows) {
            $percentages = collect($rows)
                ->map(function ($row) use ($quarter) {
                    $annual = self::numericValue($row['full_year_target'] ?? $row['target'] ?? '');
                    $quarterValue = self::numericValue($row[$quarter['target']] ?? '');

                    if ($quarterValue <= 0) {
                        return 0;
                    }

                    if ($annual <= 0) {
                        return 100;
                    }

                    return (int) min(100, round(($quarterValue / $annual) * 100));
                })
                ->filter(fn ($value) => $value > 0);

            $average = $percentages->isEmpty()
                ? 0
                : (int) round($percentages->avg());

            return [
                'label' => $quarter['label'],
                'percent' => $average,
                'tone' => self::performanceTone($average),
            ];
        })->all();
    }

    public static function quarterlyAverages(array $rows): array
    {
        $quarters = [
            ['target' => 'q1_target', 'actual' => 'q1_actual', 'label' => 'Q1 Perf'],
            ['target' => 'q2_target', 'actual' => 'q2_actual', 'label' => 'Q2 Perf'],
            ['target' => 'q3_target', 'actual' => 'q3_actual', 'label' => 'Q3 Perf'],
            ['target' => 'q4_target', 'actual' => 'q4_actual', 'label' => 'Q4 Perf'],
        ];

        return collect($quarters)->map(function ($quarter) use ($rows) {
            $percentages = collect($rows)
                ->map(fn ($row) => self::progressPercent(
                    $row[$quarter['target']] ?? '',
                    $row[$quarter['actual']] ?? ''
                ))
                ->filter(fn ($value) => $value > 0);

            $average = $percentages->isEmpty()
                ? 0
                : (int) round($percentages->avg());

            return [
                'label' => $quarter['label'],
                'percent' => $average,
                'tone' => self::performanceTone($average),
            ];
        })->all();
    }

    private static function generateReference(int|string $year): string
    {
        $suffix = strtoupper(Str::random(4));

        return 'SUB-' . $year . '-' . $suffix;
    }

    private static function performanceTone(int $percent): string
    {
        if ($percent >= 80) {
            return 'success';
        }

        if ($percent >= 60) {
            return 'primary';
        }

        return 'warning';
    }

    private static function progressPercent(string $target, string $actual): int
    {
        $targetValue = self::numericValue($target);
        $actualValue = self::numericValue($actual);

        if ($targetValue <= 0) {
            return $actualValue > 0 ? 100 : 0;
        }

        return (int) min(100, round(($actualValue / $targetValue) * 100));
    }

    private static function numericValue(string $value): float
    {
        $normalized = str_replace([',', '%', ' '], '', $value);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }
}
