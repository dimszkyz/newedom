@php
    use Illuminate\Support\Str;

    $text = trim((string) ($getState() ?? ''));
    $limit = 120;
    $isLong = Str::length($text) > $limit;
    $shortText = Str::limit($text, $limit);
@endphp

<div
    x-data="{ expanded: false }"
    class="max-w-3xl whitespace-normal break-words leading-relaxed text-sm"
>
    @if ($isLong)
        <span x-show="! expanded">
            {{ $shortText }}
        </span>

        <span x-cloak x-show="expanded">
            {{ $text }}
        </span>

        <button
            type="button"
            class="ms-1 inline-flex items-center text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            x-on:click.stop="expanded = ! expanded"
        >
            <span x-show="! expanded">Show more</span>
            <span x-cloak x-show="expanded">Show less</span>
        </button>
    @else
        {{ $text }}
    @endif
</div>
