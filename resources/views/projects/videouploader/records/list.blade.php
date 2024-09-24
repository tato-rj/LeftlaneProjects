
<div class="container">
    <div class="d-flex justify-content-between align-items-end mb-2 flex-wrap">
        
        <div class="small mb-0 text-muted mb-1">
            @if(! $videos->isEmpty())
            Showing {{$videos->firstItem()}} - {{$videos->lastItem()}} of {{$videos->total()}} uploads
            @endif
        </div>

{{--         <div class="mb-1">
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
        </div> --}}
    </div>

    @if($videos->isEmpty())
        <div class="d-flex justify-content-center" style="font-size: 5rem; opacity: .08; padding-top: 8rem;">@fa(['icon' => 'box-open'])</div>
    @else
        <div class="accordion shadow-lg mb-3" id="records-container">
        @foreach($videos as $video)
            @if($video->isCompleted())
            @include('projects.videouploader.records.states.completed')
            @elseif($video->isFailed())
            @include('projects.videouploader.records.states.failed')
            @elseif($video->isAbandoned())
            @include('projects.videouploader.records.states.abandoned')
            @else
            @include('projects.videouploader.records.states.pending')
            @endif
        @endforeach
        </div>

        {{$videos->links()}}
    @endif
</div>
