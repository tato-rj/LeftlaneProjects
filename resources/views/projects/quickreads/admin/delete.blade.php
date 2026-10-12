<details class="danger-zone">
    <summary>
        Delete {{ $kind }}
    </summary>
    <div>
        <h3>
            Delete this {{ $kind }} permanently?
        </h3>
        <p>
            {{ $warning }}
        </p>
        <form method="POST" action="{{ $deleteUrl }}">
            @csrf
            @method('DELETE')
            <label class="checkbox">
                <input type="checkbox" name="confirm" value="1" required>
                I understand this cannot be undone.
            </label>
            <button type="submit" class="button danger">
                Delete {{ $kind }}
            </button>
        </form>
    </div>
</details>
