@extends('layouts.app', ['title' => ($record ? 'Edit ' : 'Add ').$definition['singular']])

@section('content')
<section class="page-heading"><div><div class="eyebrow"><a href="{{ route('modules.index', $module) }}">{{ $definition['label'] }}</a> / {{ $record ? 'Edit record' : 'New record' }}</div><h1>{{ $record ? 'Edit '.$definition['singular'] : 'Add '.$definition['singular'] }}</h1><p>Complete the details below. Required fields are marked.</p></div></section>
<form method="POST" action="{{ $record ? route('modules.update', [$module, $record->id]) : route('modules.store', $module) }}" class="panel form-panel">
    @csrf @if ($record) @method('PUT') @endif
    <div class="form-grid">
        @foreach ($definition['fields'] as $name => $field)
            @php($value = old($name, $record?->{$name}))
            <div class="form-field {{ $field['type'] === 'textarea' ? 'wide' : '' }}">
                @if ($field['type'] !== 'checkbox')<label for="{{ $name }}">{{ $field['label'] }} @if ($field['required'] ?? false)<i>*</i>@endif</label>@endif
                @if ($field['type'] === 'select')
                    <select name="{{ $name }}" id="{{ $name }}" @required($field['required'] ?? false)><option value="">Select an option</option>
                        @foreach (($field['options'] ?? $options[$field['source']] ?? []) as $optionKey => $option)
                            @php($optionValue = is_array($option) ? $option['value'] : $optionKey)
                            @php($optionLabel = is_array($option) ? $option['label'] : $option)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
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
                    <input type="{{ $field['type'] }}" name="{{ $name }}" id="{{ $name }}" value="{{ $inputValue }}" @if ($field['type'] === 'number') min="0" step="0.01" @endif @if ($field['required'] ?? false) required @endif>
                @endif
                @error($name)<small class="field-error">{{ $message }}</small>@enderror
            </div>
        @endforeach
    </div>
    @if ($module === 'payments')<p class="form-hint">Receipt numbers are generated automatically. Payments cannot exceed the remaining challan balance.</p>@endif
    @if ($module === 'attendance')<p class="form-hint">Saving an existing student’s mark for the same date will update that attendance entry.</p>@endif
    @if ($module === 'messages')<p class="form-hint">This creates a message log in draft status. Connect an SMS provider before sending messages to parents.</p>@endif
    <div class="form-actions"><a class="btn-outline" href="{{ route('modules.index', $module) }}">Cancel</a><button class="btn-primary" type="submit">{{ $record ? 'Save changes' : 'Save '.$definition['singular'] }}</button></div>
</form>
@endsection
