
<div class="container">
    <div class="mb-3 d-flex justify-content-end">
        <div class="btn-group">
            <form method="GET" action="{{url()->current()}}">
                <button type="submit" class="btn {{request('filter') == '' ? 'btn-primary' : 'btn-outline-primary'}} btn-sm">All</button>
            </form>

            <form method="GET" action="{{url()->current()}}">
                <input type="hidden" name="filter" value="remote">
                <button type="submit" class="btn {{request('filter') == 'remote' ? 'btn-primary' : 'btn-outline-primary'}} border-x-0 btn-sm">Remote</button>
            </form>

            <form method="GET" action="{{url()->current()}}">
                <input type="hidden" name="filter" value="local">
                <button type="submit" class="btn {{request('filter') == 'local' ? 'btn-primary' : 'btn-outline-primary'}} btn-sm">Test</button>
            </form>
        </div>
    </div>
    <div class="accordion shadow-lg" id="records-container">
    @foreach($videos as $video)
        @if($video->completed())
        @include('projects.videouploader.record.states.completed')
        @else
        @include('projects.videouploader.record.states.pending')
        @endif
    @endforeach
    </div>

    {{$videos->links()}}
</div>
