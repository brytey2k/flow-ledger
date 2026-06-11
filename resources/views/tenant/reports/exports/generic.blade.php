@extends('tenant.reports.exports.layout')

@section('meta')
    @isset($dateFrom) Period: {{ $dateFrom }} — {{ $dateTo ?? '' }}@endisset
    @isset($year) Year: {{ $year }}@endisset
    @isset($groupBy) &nbsp;|&nbsp; Grouped by: {{ ucfirst(str_replace('_', ' ', $groupBy)) }}@endisset
    @isset($type) @if($type) &nbsp;|&nbsp; Type: {{ $type }}@endif @endisset
@endsection

@section('table')
<table>
    <thead>
        <tr>
            @foreach($exportHeaders as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($exportRows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($exportHeaders) }}" style="text-align:center;padding:12px;color:#888;">No data found for this period.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
