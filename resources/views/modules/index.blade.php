@extends('layouts.app', ['title' => $definition['label']])

@section('content')
<section class="page-heading"><div><div class="eyebrow">Academy operations</div><h1>{{ $definition['label'] }}</h1><p>Manage {{ strtolower($definition['label']) }} and keep your academy records up to date.</p></div><a class="btn-primary" href="{{ route('modules.create', $module) }}">＋ Add {{ strtolower($definition['singular']) }}</a></section>
<section class="panel data-panel"><div class="panel-heading data-heading"><div><h2>{{ $definition['label'] }} register</h2><p>{{ number_format($records->total()) }} records</p></div><form method="GET" class="search-form"><input type="search" name="q" value="{{ $search }}" placeholder="Search {{ strtolower($definition['label']) }}"><button type="submit">Search</button>@if ($search !== '')<a href="{{ route('modules.index', $module) }}">Clear</a>@endif</form></div>
    <div class="table-scroll"><table><thead><tr>@foreach ($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach<th class="actions-column">Actions</th></tr></thead><tbody>
    @forelse ($records as $record)<tr>
        @foreach ($definition['columns'] as $key => $label)
            @php($value = data_get($record, $key))
            <td>
                @if (str_ends_with($key, 'status'))<span class="status-pill {{ in_array($value, ['paid', 'active', 'present', 'sent', 'admitted'], true) ? 'paid' : (in_array($value, ['unpaid', 'new', 'late', 'partial', 'draft', 'visit'], true) ? 'pending' : 'neutral') }}">{{ Str::headline((string) $value) }}</span>
                @elseif (in_array($key, ['monthly_fee', 'amount', 'monthly_salary', 'marks_obtained', 'total_marks', 'passing_marks'], true))<span class="table-strong">₨ {{ number_format((float) $value, 2) }}</span>
                @elseif (in_array($key, ['active'], true))<span class="status-pill {{ $value ? 'paid' : 'neutral' }}">{{ $value ? 'Yes' : 'No' }}</span>
                @elseif ($value instanceof \DateTimeInterface){{ $value->format('d M, Y') }}
                @else{{ $value ?: '—' }}@endif
            </td>
        @endforeach
        <td class="row-actions">
            @if ($module === 'inquiries' && $record->status !== 'admitted' && $record->class_id)<form method="POST" action="{{ route('inquiries.admit', $record->id) }}">@csrf<button type="submit">Admit</button></form>@endif
            @if ($module === 'fees')<a href="{{ route('challans.print', $record->id) }}" target="_blank">Print</a>@endif
            @if ($module === 'payments')<a href="{{ route('receipts.print', $record->id) }}" target="_blank">Receipt</a>@endif
            <a href="{{ route('modules.edit', [$module, $record->id]) }}">Edit</a>
            <form method="POST" action="{{ route('modules.destroy', [$module, $record->id]) }}" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')<button type="submit">Delete</button></form>
        </td>
    </tr>@empty<tr><td class="empty-state" colspan="{{ count($definition['columns']) + 1 }}">No records yet. Use “Add {{ strtolower($definition['singular']) }}” to get started.</td></tr>@endforelse
    </tbody></table></div><div class="pagination-wrap">{{ $records->links() }}</div>
</section>
@endsection
