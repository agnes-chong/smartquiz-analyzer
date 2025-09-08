@props(['label' => '', 'name', 'type' => 'text'])

<div class="mb-3">
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
           {{ $attributes->merge(['class' => 'form-control']) }}>
    @error($name)
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>
