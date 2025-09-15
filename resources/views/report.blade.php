<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        h1,
        h2,
        h3 {
            margin: 0.5em 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1em;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .summary-table {
            margin-bottom: 2em;
        }
    </style>
</head>

<body>
    <h1>Scholarship Report</h1>
    <p><strong>Report Type:</strong> {{ ucfirst($reportType) }}</p>
    <p><strong>Filters:</strong>
        @foreach($filters as $key => $value)
        @if($value)
        <span>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}; </span>
        @endif
        @endforeach
    </p>
    @if($reportType === 'summary')
    <h2>Summary</h2>
    <table class="summary-table">
        <tr>
            <th>Total</th>
            <td>{{ $summary['total'] ?? 0 }}</td>
        </tr>
        @if(isset($summary['by_program']))
        <tr>
            <th colspan="2">By Program</th>
        </tr>
        @foreach($summary['by_program'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_school']))
        <tr>
            <th colspan="2">By School</th>
        </tr>
        @foreach($summary['by_school'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_course']))
        <tr>
            <th colspan="2">By Course</th>
        </tr>
        @foreach($summary['by_course'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_year_level']))
        <tr>
            <th colspan="2">By Year Level</th>
        </tr>
        @foreach($summary['by_year_level'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
    </table>
    @else
    <h2>List</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Municipality</th>
                <th>School</th>
                <th>Course</th>
                <th>Year Level</th>
                <th>Date Filed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($profiles as $i => $profile)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $profile->last_name }}, {{ $profile->first_name }}</td>
                <td>{{ $profile->municipality }}</td>
                <td>{{ optional($profile->scholarshipGrant->first()->school ?? $profile->scholarshipGrant->school ?? null)->name ?? '' }}</td>
                <td>{{ optional($profile->scholarshipGrant->first()->course ?? $profile->scholarshipGrant->course ?? null)->name ?? '' }}</td>
                <td>{{ optional($profile->scholarshipGrant->first()->year_level ?? $profile->scholarshipGrant->year_level ?? null) }}</td>
                <td>{{ optional($profile->scholarshipGrant->first()->date_filed ?? $profile->scholarshipGrant->date_filed ?? null) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>

</html>