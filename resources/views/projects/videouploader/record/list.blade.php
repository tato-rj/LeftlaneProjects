
<div class="container">
    @filters([
        'name' => 'filter',
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
    <div class="text-muted">@fa(['icon' => 'box-open'])</div>
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
