@extends('layouts.app', ['title' => $module === 'fees' ? ($record ? 'Edit fee bill' : 'Create fee bill') : ($module === 'payments' ? ($record ? 'Edit payment' : 'Record a payment') : ($record ? 'Edit ' : 'Add ').$definition['singular'])])

@section('content')
<section class="page-heading"><div><div class="eyebrow"><a href="{{ in_array($module, ['fees', 'payments'], true) ? route('fees.desk') : route('modules.index', $module) }}">{{ in_array($module, ['fees', 'payments'], true) ? 'Fee workspace' : $definition['label'] }}</a> / {{ $record ? 'Edit record' : 'New record' }}</div><h1>{{ $module === 'fees' ? ($record ? 'Edit fee bill' : 'Create a fee bill') : ($module === 'payments' ? ($record ? 'Edit payment' : 'Record a payment') : ($record ? 'Edit '.$definition['singular'] : 'Add '.$definition['singular'])) }}</h1><p>{{ $module === 'fees' ? 'Create the amount due for one student and one month.' : ($module === 'payments' ? 'Record money you have actually received from a student.' : 'Complete the details below. Required fields are marked.') }}</p></div></section>
<form method="POST" enctype="multipart/form-data" action="{{ $record ? route('modules.update', [$module, $record->id]) : route('modules.store', $module) }}" class="panel form-panel">
    @csrf @if ($record) @method('PUT') @endif
    @if ($module === 'fees')<div class="fee-form-intro"><div class="fee-form-icon">₨</div><div><span>STEP 2 · CREATE A BILL</span><strong>One student · one month</strong><small>The total adds monthly tuition and any extra charges assigned to this student. This does not record a payment.</small></div><div class="fee-live-total"><small>BILL TOTAL</small><strong id="feeBillTotal">₨ 0</strong></div></div><p class="fee-create-guide"><strong>Before you save:</strong> Choose the student and month. Tuition and recurring extra charges are calculated automatically. Use discount only when an approved reduction applies.</p>@elseif ($module === 'payments')<div class="fee-form-intro payment-intro"><div class="fee-form-icon">↙</div><div><span>STEP 3 · RECORD MONEY RECEIVED</span><strong>Create a payment receipt</strong><small>Choose a student bill. The student and remaining balance are shown for you.</small></div><div class="fee-live-total"><small>STILL DUE</small><strong id="paymentBalance">Choose a bill</strong></div></div><p class="fee-payment-guide"><strong>How to use this form:</strong> Pick the student bill, enter the amount you actually received, then choose the payment method. Enter a smaller amount for a part payment.</p>@endif
    <div class="form-grid">
        @foreach ($definition['fields'] as $name => $field)
            @if ($module === 'fees' && ($name === 'status' && ! $record || $name === 'invoice_no')) @continue @endif
            @php($value = old($name, $record?->{$name} ?? request()->query($name)))
            @if (! $record && $value === null)
                @php($value = match (true) { $module === 'fees' && $name === 'invoice_month' => now()->format('Y-m'), $module === 'fees' && $name === 'due_date' => now()->endOfMonth()->toDateString(), $module === 'fees' && in_array($name, ['discount', 'late_fee'], true) => '0', $module === 'fees' && $name === 'status' => 'unpaid', $module === 'payments' && $name === 'paid_at' => now()->format('Y-m-d\TH:i'), $module === 'payments' && $name === 'method' => 'cash', default => null })
            @endif
            <div class="form-field {{ $field['type'] === 'textarea' ? 'wide' : '' }}">
                @if ($field['type'] !== 'checkbox')<label for="{{ $name }}">{{ $field['label'] }} @if ($field['required'] ?? false)<i>*</i>@endif</label>@endif
                @if ($field['type'] === 'select')
                    @if (in_array($field['source'] ?? '', ['students', 'classes', 'invoices'], true))<input class="select-search" type="search" data-filter-select="{{ $name }}" placeholder="Type to search {{ strtolower($field['label']) }}…" autocomplete="off">@endif
                    <select name="{{ $name }}" id="{{ $name }}" @required($field['required'] ?? false)><option value="">Select an option</option>
                        @foreach (($field['options'] ?? $options[$field['source']] ?? []) as $optionKey => $option)
                            @php($optionValue = is_array($option) ? $option['value'] : $optionKey)
                            @php($optionLabel = is_array($option) ? $option['label'] : $option)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue) @if ($module === 'students' && is_array($option) && isset($option['monthly_fee'])) data-monthly-fee="{{ $option['monthly_fee'] }}" @endif @if ($module === 'fees' && is_array($option) && isset($option['fee_total'])) data-fee-total="{{ $option['fee_total'] }}" data-fee-heads="{{ json_encode($option['fee_heads'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}" @endif @if (is_array($option) && isset($option['student_id'])) data-student-id="{{ $option['student_id'] }}" data-balance="{{ $option['balance'] }}" @endif @if ($module === 'students' && is_array($option) && isset($option['subject_group'])) data-class-name="{{ $option['name'] }}" data-subject-group="{{ $option['subject_group'] }}" data-section="{{ $option['section'] }}" data-monthly-fee="{{ $option['monthly_fee'] }}" @endif>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                @elseif ($field['type'] === 'textarea')
                    <textarea name="{{ $name }}" id="{{ $name }}" rows="4" maxlength="{{ $module === 'messages' ? 160 : 2000 }}" @required($field['required'] ?? false)>{{ $value }}</textarea>
                @elseif ($field['type'] === 'checkbox')
                    <label class="checkbox-field"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" @checked((bool) $value)> {{ $field['label'] }}</label>
                @else
                    @php($inputValue = $value)
                    @if ($value instanceof \DateTimeInterface)
                        @php($inputValue = $field['type'] === 'month' ? $value->format('Y-m') : ($field['type'] === 'datetime-local' ? $value->format('Y-m-d\TH:i') : $value->format('Y-m-d')))
                    @elseif ($field['type'] === 'month' && $value)
                        @php($inputValue = substr((string) $value, 0, 7))
                    @endif
                    <input type="{{ $field['type'] }}" name="{{ $name }}" id="{{ $name }}" value="{{ $inputValue }}" @if ($field['type'] === 'number') min="0" step="0.01" @endif @if ($module === 'fees' && $name === 'amount' && ! $record) readonly @endif @if ($field['required'] ?? false) required @endif>
                @endif
                @if (! empty($field['hint']))<small class="form-hint">{{ $field['hint'] }}</small>@endif
                @error($name)<small class="field-error">{{ $message }}</small>@enderror
            </div>
        @endforeach
    </div>
    @if ($module === 'fees')<div class="fee-head-preview" id="feeHeadPreview" data-edit="{{ $record ? '1' : '0' }}">@if ($record?->fee_head_details)<strong>Saved bill calculation</strong>@foreach ($record->fee_head_details as $item)<span>{{ $item['label'] }} <b>₨ {{ number_format((float) $item['amount'], 2) }}</b></span>@endforeach@else<span class="fee-head-preview-empty">Select a student to see their tuition and any assigned extra charges.</span>@endif</div>@endif
    @if ($module === 'students')
        <div class="admission-class-summary" aria-live="polite">
            <div><small>Class</small><strong id="selected-class">Select a class</strong></div>
            <div><small>Subject group</small><strong id="selected-subject-group">—</strong></div>
            <div><small>Section</small><strong id="selected-section">—</strong></div>
        </div>
        <div class="form-field student-photo-field">
            <label for="photo">Student photo</label>
            <img class="photo-preview" id="photoPreview" src="{{ $record?->photo_path ? asset('storage/'.$record->photo_path) : '' }}" alt="Student photo" @if (! $record?->photo_path) hidden @endif>
            <input type="file" id="photo" name="photo" accept="image/png,image/jpeg,image/webp">
            <small class="form-hint">JPG, PNG or WebP · maximum 2 MB</small>
            @error('photo')<small class="field-error">{{ $message }}</small>@enderror
        </div>
    @endif
    @if ($module === 'attendance')<p class="form-hint">Saving an existing student’s mark for the same date will update that attendance entry.</p>@endif
    @if ($module === 'messages')<p class="form-hint">This creates a message log in draft status. Connect an SMS provider before sending messages to parents.</p>@endif
    <div class="form-actions"><a class="btn-outline" href="{{ in_array($module, ['fees', 'payments'], true) ? route('fees.desk') : route('modules.index', $module) }}">Back</a><button class="btn-primary" type="submit">{{ $record ? 'Save changes' : ($module === 'fees' ? 'Create bill' : ($module === 'payments' ? 'Save payment & create receipt' : 'Save '.$definition['singular'])) }}</button></div>
</form>
@endsection
