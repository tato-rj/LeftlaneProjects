@extends('projects.quickreads.layouts.app')
@section('title', 'Statistics')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE READING PICTURE', 'heading' => 'Statistics', 'description' => 'See how your community is growing and which stories bring readers back.'])
    <form method="GET" class="filter-panel stats-filters">
        <div class="filter-bar">
            <div class="date-range">
                <label for="range">
                    Reporting period
                </label>
                <select name="range" id="range">
                    @foreach(['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 365 days', 'custom' => 'Custom dates'] as $value => $label)
                        <option value="{{ $value }}" @selected((string)$range === (string)$value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from">
                    From
                </label>
                <input type="date" id="from" name="from" value="{{ old('from', $start->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}">
            </div>
            <div>
                <label for="to">
                    To
                </label>
                <input type="date" id="to" name="to" value="{{ old('to', $end->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}">
            </div>
            <div>
                <label for="group">
                    Group by
                </label>
                <select name="group" id="group">
                    <option value="day" @selected($group === 'day')>
                        Day
                    </option>
                    <option value="month" @selected($group === 'month')>
                        Month
                    </option>
                </select>
            </div>
            <button class="button primary" type="submit">
                Update report
            </button>
        </div>
        <small>
            Custom reports cover up to 366 days. Dates use the application timezone ({{ config('app.timezone') }}).
        </small>
    </form>
    <div class="report-caption">
        <span>
            {{ $start->format('M j, Y') }} — {{ $end->format('M j, Y') }}
        </span>
        <span>
            Updated {{ $report['generated_at']->format('g:i a') }} · Cached for 5 minutes
        </span>
    </div>
    <div class="metric-grid">
        @foreach([['signups', 'New readers', 'users'], ['saves', 'Story saves', 'bookmark'], ['active_users', 'Readers who saved', 'users'], ['saved_stories', 'Stories saved', 'stories']] as [$key, $label, $icon])
            <div class="metric-card">
                <div class="metric-top">
                    <span>
                        {{ $label }}
                    </span>
                    <span class="metric-icon">
                        @include('projects.quickreads.admin.icon', ['name' => $icon])
                    </span>
                </div>
                <strong>
                    {{ number_format($report[$key]) }}
                </strong>
                <small class="muted">
                    In this reporting period
                </small>
            </div>
        @endforeach
    </div>
    <section class="panel chart-panel">
        <div class="panel-heading">
            <div>
                <h2>
                    Reading activity over time
                </h2>
                <p>
                    New readers and story saves, {{ $group === 'day' ? 'day by day' : 'month by month' }}
                </p>
            </div>
            <div class="chart-legend">
                <span>
                    <i>
                    </i>
                    New readers
                </span>
                <span>
                    <i class="orange">
                    </i>
                    Story saves
                </span>
            </div>
        </div>
        <div class="chart" data-chart data-series='@json($report["series"])' role="img" aria-label="New readers and story saves over the selected reporting period">
        </div>
        <details class="chart-data">
            <summary>
                View chart data as a table
            </summary>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Date
                            </th>
                            <th>
                                New readers
                            </th>
                            <th>
                                Story saves
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['series'] as $point)
                            <tr>
                                <td>
                                    {{ $point['date'] }}
                                </td>
                                <td>
                                    {{ $point['signups'] }}
                                </td>
                                <td>
                                    {{ $point['saves'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>
    <div class="two-column spaced">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        Most saved stories
                    </h2>
                    <p>
                        The top 10 stories in this period
                    </p>
                </div>
                <span class="pill">
                    TOP 10
                </span>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Story
                            </th>
                            <th>
                                Readers
                            </th>
                            <th>
                                Saves
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['top_stories'] as $story)
                            <tr>
                                <td>
                                    <div class="ranking">
                                        <span>
                                            {{ $loop->iteration }}
                                        </span>
                                        <a class="record-title" href="/quickreads/stories/edit/{{ $story->slug }}">
                                            {{ $story->title }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    {{ number_format($story->readers) }}
                                </td>
                                <td>
                                    <strong>
                                        {{ number_format($story->saves) }}
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-state">
                                    No stories saved in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        Most active readers
                    </h2>
                    <p>
                        The top 10 readers by saved-story records
                    </p>
                </div>
                <span class="pill">
                    TOP 10
                </span>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Reader
                            </th>
                            <th>
                                Saves
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['top_users'] as $user)
                            <tr>
                                <td>
                                    <div class="ranking">
                                        <span>
                                            {{ $loop->iteration }}
                                        </span>
                                        <a class="record-title" href="/quickreads/users/{{ $user->id }}">
                                            {{ trim($user->first_name.' '.$user->last_name) ?: $user->email }}
                                            <small class="block muted">{{ $user->email }}</small>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <strong>
                                        {{ number_format($user->saves) }}
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty-state">
                                    No reader activity in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <p class="data-note">
        “Saves” counts saved-story records, including duplicates from older app versions. Reader and story totals count distinct existing accounts and stories. Story rankings omit deleted stories; reader rankings omit deleted accounts. Saves without a recorded date are excluded.
    </p>
@endsection
