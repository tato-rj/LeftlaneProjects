<div class="accordion-item">
    <div class="accordion-header {{$video->isRemote() ? 'alert-grey' : null}}">
      <button class="accordion-button collapsed {{$video->isRemote() ? 'alert-grey' : 'text-grey'}}" type="button" data-bs-toggle="collapse" data-bs-target="#record-{{$video->id}}">
        @fa(['icon' => 'skull-crossbones'])
        @include('projects.videouploader.record.states.header')
    </button>
    </div>
    <div id="record-{{$video->id}}" class="accordion-collapse collapse" data-bs-parent="#records-container">
      <div class="accordion-body">
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

        <div class="d-flex">
          <a href="{{storage($video->temp_path)}}" target="_blank" class="btn btn-outline-primary btn-sm me-2">Original Video</a>

          <a href="{{route('videouploader.videos.status', $video)}}" class="btn btn-outline-primary btn-sm me-2">Status</a>
          
          @include('projects.videouploader.record.edit')
          @include('projects.videouploader.record.delete')
        </div>
      </div>
    </div>
</div>