@props(['type' => 'success'])

<span {{ $attributes->merge(['class' => "badge bg-$type"]) }}>
    {{ $slot }}
</span>
