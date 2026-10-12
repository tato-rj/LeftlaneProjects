<label class="sr-only" for="per_page">
    Rows per page
</label>
<select name="per_page" id="per_page" aria-label="Rows per page">
    @foreach([25, 50, 100] as $size)
        <option value="{{ $size }}" @selected(request('per_page', 25) == $size)>
            {{ $size }} per page
        </option>
    @endforeach
</select>
