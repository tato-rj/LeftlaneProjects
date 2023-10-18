
<div class="container">
    @include('projects.videouploader.record.filters')
    
    <div class="accordion shadow-lg mb-3" id="records-container">
    @foreach($videos as $video)
        @if($video->completed())
        @include('projects.videouploader.record.states.completed')
        @elseif($video->failed())
        @include('projects.videouploader.record.states.failed')
        @elseif($video->abandoned())
        @include('projects.videouploader.record.states.abandoned')
        @else
        @include('projects.videouploader.record.states.pending')
        @endif
    @endforeach
    </div>

    {{$videos->links()}}
</div>
