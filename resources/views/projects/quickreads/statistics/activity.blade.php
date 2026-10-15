@extends('projects.quickreads.layouts.app')
@section('title', 'Story activity')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE READING PICTURE', 'heading' => 'Story activity', 'description' => 'The latest Reading, Saved, and Finished states reported by readers.'])
    <form method="GET" class="filter-panel">
        <div class="filter-bar">
            <div>
                <label for="reader-kind">Readers</label>
                <select name="reader_kind" id="reader-kind">
                    <option value="">Guests and accounts</option>
                    <option value="guest" @selected($kind === 'guest')>Guests</option>
                    <option value="account" @selected($kind === 'account')>Accounts</option>
                </select>
            </div>
            <button type="submit" class="button primary">Update report</button>
        </div>
    </form>
    <p class="muted">Counts reflect the latest reports retained for up to 90 days. Saved is independent of Reading and Finished. Guest counts represent installations and can change after reinstall.</p>
    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Story</th><th scope="col">Readers</th><th scope="col">Reading</th><th scope="col">Saved</th><th scope="col">Finished</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->title ?? 'Unavailable story' }} <small class="muted">#{{ $row->story_id }}</small></td>
                        <td>{{ $row->reader_kind === 'guest' ? 'Guests' : 'Accounts' }}</td>
                        <td>{{ number_format($row->reading) }}</td>
                        <td>{{ number_format($row->saved) }}</td>
                        <td>{{ number_format($row->finished) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No story activity has been reported yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </section>
@endsection
