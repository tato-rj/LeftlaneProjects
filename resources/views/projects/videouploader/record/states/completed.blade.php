<div class="accordion-item">
    <div class="accordion-header {{$video->isRemote() ? 'alert-success' : null}}">
      <button class="accordion-button collapsed {{$video->isRemote() ? 'alert-success' : 'text-success'}}" type="button" data-bs-toggle="collapse" data-bs-target="#record-{{$video->id}}">
        @fa(['icon' => 'check'])
        @include('projects.videouploader.record.states.header')
      </button>
    </div>
    <div id="record-{{$video->id}}" class="accordion-collapse collapse" data-bs-parent="#records-container">
      <div class="accordion-body bg-light">
        <div class="text-muted small mb-2">
            @if($video->isRemote())
              <label class="fw-bold">NOTIFICATION STATUS</label>
              <div class="{{$video->notification_received_at ? 'text-success' : 'text-danger'}}">{{$video->notification_received_at ? 'Last sent on '.$video->notification_received_at->toFormattedDateString() : 'Not received yet'}}</div>
            @endif

            <label class="fw-bold">PROCESSING TIME</label>
            <div>{{$video->processing_time}} minutes</div>
            <label class="fw-bold">MIME TYPE</label>
            <div>{{$video->mimeType}}</div>
            <label class="fw-bold">ORIGINAL DIMENSIONS</label>
            <div>{{$video->original_dimensions}}</div>
            <label class="fw-bold">COMPRESSED DIMENSIONS</label>
            <div>{{$video->compressed_dimensions}} ({{$video->orientation}})</div>
            <label class="fw-bold">ORIGINAL SIZE</label>
            <div>{{$video->original_size_mb}}</div>
            <label class="fw-bold">COMPRESSED SIZE</label>
            <div>{{$video->compressed_size_mb}} ({{$video->size_decrease_percentage}})</div>
        </div>

        <div class="d-flex flex-wrap">
          @include('projects.videouploader.record.actions.json')
          @include('projects.videouploader.record.actions.video')
          @include('projects.videouploader.record.actions.thumbnail')
          @include('projects.videouploader.record.actions.webhook')
          @include('projects.videouploader.record.actions.edit')
          @include('projects.videouploader.record.actions.delete')
        </div>
      </div>
    </div>
</div>