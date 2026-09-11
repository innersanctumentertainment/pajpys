@props(['name' => 'currency', 'selected' => null, 'allowed' => ['TTD'], 'default' => 'TTD', 'id' => null])

@php
    $selected = strtoupper(old($name, $selected ?? $default));
    $inputId = $id ?? $name;
@endphp

<label for="{{ $inputId }}" class="form-label">{{ $attributes->get('label', 'Currency') }}</label>
<select id="{{ $inputId }}" name="{{ $name }}" class="form-input w-full" {{ $attributes->except(['label']) }}>
    @foreach ($allowed as $code)
        <option value="{{ $code }}" @selected($selected === $code)>
            {{ $code }}@if ($code === 'TTD') (default)@endif
        </option>
    @endforeach
</select>
