
<div class="container">
    @filters([
        'name' => 'origin',
        'include' => ['state'],
        'options' => [
            'All' => '',
            'Remote' => 'remote',
            'Test' => 'local'
        ]
    ])

    @filters([
        'name' => 'state',
        'include' => ['filter'],
        'options' => [
            'All' => '',
            'Pending' => 'pending',
            'Completed' => 'completed',
            'Failed' => 'failed',
            'Abandoned' => 'abandoned'
        ]
    ])

    @if($videos->isEmpty())
    <div class="d-flex justify-content-center pt-5" style="
    font-size: 8rem;
    opacity: .1;">@fa(['icon' => 'box-open'])</div>
    @else
    <div class="small mb-2 text-muted">Showing {{$videos->firstItem()}} - {{$videos->lastItem()}} of {{$videos->total()}}</div>
    @endif

    <div class="accordion shadow-lg mb-3" id="records-container">
    @foreach($videos as $video)
        @if($video->isCompleted())
        @include('projects.videouploader.record.states.completed')
        @elseif($video->isFailed())
        @include('projects.videouploader.record.states.failed')
        @elseif($video->isAbandoned())
        @include('projects.videouploader.record.states.abandoned')
        @else
        @include('projects.videouploader.record.states.pending')
        @endif
    @endforeach
    </div>

    {{$videos->links()}}
</div>
