
<div class="container">
    <div class="d-flex justify-content-between align-items-end mb-3">
        
        <div class="small mb-0 text-muted">
            @if(! $videos->isEmpty())
            Showing {{$videos->firstItem()}} - {{$videos->lastItem()}} of 
            @endif
            {{$videos->total()}} uploads
        </div>

        <div>
            @filters([
                'format' => 'dropdown',
                'name' => 'origin',
                'include' => ['state'],
                'options' => [
                    'Any origin' => '',
                    'Remote' => 'remote',
                    'Test' => 'local'
                ]
            ])

            @filters([
                'format' => 'dropdown',
                'name' => 'state',
                'include' => ['origin'],
                'options' => [
                    'Any state' => '',
                    'Pending' => 'pending',
                    'Completed' => 'completed',
                    'Failed' => 'failed',
                    'Abandoned' => 'abandoned'
                ]
            ])
        </div>
    </div>

    @if($videos->isEmpty())
        <div class="d-flex justify-content-center pt-5" style="font-size: 8rem; opacity: .1;">@fa(['icon' => 'box-open'])</div>
    @else
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
    @endif
</div>
