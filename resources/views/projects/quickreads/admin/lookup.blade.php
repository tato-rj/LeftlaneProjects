<div class="lookup" data-lookup="{{ $type }}">
    <label for="{{ $field }}_search">
        {{ $label }}
    </label>
    <input type="hidden" name="{{ $field }}" value="{{ $selected->id ?? '' }}" data-lookup-id>
    <div class="lookup-input">
        <input type="search" id="{{ $field }}_search" value="{{ $selected->name ?? $selected->category ?? '' }}" placeholder="Search {{ $type }}…" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $field }}_options" data-lookup-search>
        <button type="button" aria-label="Clear {{ strtolower($label) }}" data-lookup-clear>
            ×
        </button>
    </div>
    <div class="lookup-options" id="{{ $field }}_options" role="listbox" hidden>
    </div>
    <small class="lookup-status" aria-live="polite">
        {{ isset($required) && $required ? 'Select a result to link this story.' : 'Optional filter' }}
    </small>
    <noscript>
        <p>
            JavaScript is needed to search {{ $type }}.
        </p>
    </noscript>
</div>
