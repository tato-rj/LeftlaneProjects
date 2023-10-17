<section class="bg-white py-5">
    <div class="container">
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
</section>