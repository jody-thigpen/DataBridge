@props([
    'href',
    'title',
    'description',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'action-tile']) }}>
    <div class="action-tile-title">{{ $title }}</div>
    <p class="action-tile-desc">{{ $description }}</p>
</a>
