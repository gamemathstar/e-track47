<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Report - {{ $report['reference'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #00693e;
            --text: #171d19;
            --muted: #3e4a41;
            --border: #d6e0d8;
            --bg: #f5f8f7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Public Sans', sans-serif;
            color: var(--text);
            background: #fff;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 24px 48px;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn {
            border: 1px solid rgba(0, 105, 62, 0.25);
            background: #fff;
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--border);
        }

        .header h1 {
            margin: 0 0 8px;
            color: var(--primary);
            font-size: 28px;
        }

        .header p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            background: var(--bg);
        }

        .card h2 {
            margin: 0 0 12px;
            font-size: 16px;
            color: var(--primary);
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .meta-row:last-child { border-bottom: 0; }

        .meta-label {
            color: var(--muted);
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .quarter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .quarter-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            background: #fff;
        }

        .quarter-card strong {
            display: block;
            font-size: 22px;
            color: var(--primary);
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid var(--border);
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: var(--bg);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .audit-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .audit-item:last-child { border-bottom: 0; }

        @media print {
            .toolbar { display: none; }
            .page { padding: 0; max-width: none; }
            body { font-size: 11px; }
        }
    </style>
</head>
<body>
@php
    $meta = $report['meta'];
    $uploadMode = $report['upload_mode'] ?? 'structure';
    $isActualsUpload = $uploadMode === 'actuals';
    $stats = $report['import_stats'] ?? [];
    $records = $report['rows'] ?? $report['kpis'] ?? [];
@endphp

<div class="page">
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.close()">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">Save as PDF / Print</button>
    </div>

    <header class="header">
        <h1>Submission Successful</h1>
        <p>Reference: {{ $report['reference'] }}</p>
    </header>

    <div class="grid-2">
        <section class="card">
            <h2>Metadata</h2>
            <div class="meta-row"><span class="meta-label">Sector</span><span>{{ $meta['sector_name'] }}</span></div>
            <div class="meta-row"><span class="meta-label">Reporting Year</span><span>FY {{ $meta['framework_year'] }}</span></div>
            <div class="meta-row"><span class="meta-label">Submitted By</span><span>{{ $report['submitted_by'] }}</span></div>
            <div class="meta-row"><span class="meta-label">Timestamp</span><span>{{ $submittedAt->format('M j, Y - H:i') }}</span></div>
            <div class="meta-row"><span class="meta-label">Source File</span><span>{{ $meta['file_name'] ?? '—' }}</span></div>
            @if($isActualsUpload && !empty($meta['reporting_quarter']))
                <div class="meta-row"><span class="meta-label">Reporting Quarter</span><span>Q{{ $meta['reporting_quarter'] }}</span></div>
            @endif
        </section>

        <section class="card">
            <h2>Import Summary</h2>
            @if($isActualsUpload)
                <div class="meta-row"><span class="meta-label">Rows Processed</span><span>{{ $stats['rows_processed'] ?? 0 }}</span></div>
                <div class="meta-row"><span class="meta-label">Actuals Updated</span><span>{{ $stats['actuals_updated'] ?? 0 }}</span></div>
                <div class="meta-row"><span class="meta-label">New Submissions</span><span>{{ $stats['actuals_submitted'] ?? 0 }}</span></div>
                <div class="meta-row"><span class="meta-label">Skipped (Locked)</span><span>{{ $stats['skipped_locked'] ?? 0 }}</span></div>
            @else
                <div class="meta-row"><span class="meta-label">Commitments</span><span>{{ $stats['commitments_created'] ?? 0 }} new / {{ $stats['commitments_matched'] ?? 0 }} matched</span></div>
                <div class="meta-row"><span class="meta-label">Deliverables</span><span>{{ $stats['deliverables_created'] ?? 0 }} new / {{ $stats['deliverables_matched'] ?? 0 }} matched</span></div>
                <div class="meta-row"><span class="meta-label">KPIs</span><span>{{ $stats['kpis_created'] ?? 0 }} new / {{ $stats['kpis_matched'] ?? 0 }} matched</span></div>
                <div class="meta-row"><span class="meta-label">Milestones</span><span>{{ $stats['milestones_created'] ?? 0 }} new / {{ $stats['milestones_updated'] ?? 0 }} updated</span></div>
            @endif
        </section>
    </div>

    <section class="card" style="margin-bottom: 24px;">
        <h2>{{ $isActualsUpload ? 'Quarterly Performance' : 'Quarterly Target Distribution' }}</h2>
        <div class="quarter-grid">
            @foreach($report['quarterly_averages'] as $quarter)
                <div class="quarter-card">
                    <span class="meta-label">{{ $quarter['label'] }}</span>
                    <strong>{{ $quarter['percent'] }}%</strong>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card" style="margin-bottom: 24px;">
        <h2>Submitted Records</h2>
        <table>
            <thead>
            <tr>
                <th>S/N</th>
                <th>Deliverable</th>
                <th>KPI</th>
                <th>Annual Target</th>
                <th>Q1</th>
                <th>Q2</th>
                <th>Q3</th>
                <th>Q4</th>
                <th>Full Year Actual</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $row)
                <tr>
                    <td>{{ $row['sn'] ?? '—' }}</td>
                    <td>{{ $row['deliverable'] ?? '—' }}</td>
                    <td>{{ $row['kpi'] ?? '—' }}</td>
                    <td>{{ $row['full_year_target'] ?? $row['target'] ?? '—' }}</td>
                    <td>{{ ($row['q1_actual'] ?? '') !== '' ? $row['q1_actual'] : ($row['q1_target'] ?? '—') }}</td>
                    <td>{{ ($row['q2_actual'] ?? '') !== '' ? $row['q2_actual'] : ($row['q2_target'] ?? '—') }}</td>
                    <td>{{ ($row['q3_actual'] ?? '') !== '' ? $row['q3_actual'] : ($row['q3_target'] ?? '—') }}</td>
                    <td>{{ ($row['q4_actual'] ?? '') !== '' ? $row['q4_actual'] : ($row['q4_target'] ?? '—') }}</td>
                    <td>{{ ($row['full_year_actual'] ?? '') !== '' ? $row['full_year_actual'] : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No records in this submission.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Audit Trail</h2>
        @foreach($report['audit_trail'] as $event)
            <div class="audit-item">
                <strong>{{ $event['title'] }}</strong>
                <div>{{ $event['timestamp']->format('M j, Y - H:i:s') }} UTC</div>
                <div style="color: var(--muted);">{{ $event['description'] }}</div>
            </div>
        @endforeach
    </section>
</div>

@if(request()->boolean('print'))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
