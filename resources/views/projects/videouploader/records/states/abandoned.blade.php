<div class="accordion-item">
    <div class="accordion-header {{$video->isRemote() ? 'alert-light text-muted' : null}}">
      <button class="accordion-button collapsed {{$video->isRemote() ? 'alert-light text-muted' : 'text-muted'}}" type="button" data-bs-toggle="collapse" data-bs-target="#record-{{$video->id}}">
        @fa(['icon' => 'skull-crossbones'])
        @include('projects.videouploader.records.states.header')
    </button>
    </div>
    <div id="record-{{$video->id}}" class="accordion-collapse collapse" data-bs-parent="#records-container">
      <div class="accordion-body bg-light">
        <div class="text-muted small mb-2">
            <label class="fw-bold">START TIME</label>
            <div>{{$video->created_at->diffForHumans()}}</div>
            <label class="fw-bold">USER ID</label>
            <div>{{$video->user_id}}</div>
            <label class="fw-bold">USER EMAIL</label>
            <div>{{$video->user_email}}</div>
            <label class="fw-bold">ORIGINAL SIZE</label>
            <div>{{$video->original_size_mb}}</div>
        </div>

        <div class="d-flex flex-wrap">
          @include('projects.videouploader.records.actions.video')
          @include('projects.videouploader.records.actions.edit')
          @include('projects.videouploader.records.actions.delete')
        </div>
      </div>
    </div>
</div>