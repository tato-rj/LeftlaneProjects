<div class="accordion-item">
    <div class="accordion-header {{$video->isRemote() ? 'alert-success' : null}}">
      <button class="accordion-button collapsed {{$video->isRemote() ? 'alert-success' : 'text-success'}}" type="button" data-bs-toggle="collapse" data-bs-target="#record-{{$video->id}}">
        @fa(['icon' => 'check'])
        @include('projects.videouploader.records.states.header')
      </button>
    </div>
    <div id="record-{{$video->id}}" class="accordion-collapse collapse" data-bs-parent="#records-container">
      <div class="accordion-body bg-light">
{{--         <div class="text-muted small mb-2">
            @if($video->isRemote())
              <div class="d-flex align-items-end">
                <label class="fw-bold me-1">NOTIFICATION STATUS</label>
                <form method="POST" action="{{route('videouploader.webhook.resend', $video)}}">
                  @csrf
                  <button type="submit" style="font-size: 88%" class="btn-link btn btn-sm p-0">resend</button>
                </form>
              </div>
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
        </div> --}}

        <div class="d-flex flex-wrap">
          @include('projects.videouploader.records.actions.json')
          @include('projects.videouploader.records.actions.video')
          {{-- @include('projects.videouploader.records.actions.thumbnail') --}}
          {{-- @include('projects.videouploader.records.actions.rotate') --}}
          @include('projects.videouploader.records.actions.edit')
          @include('projects.videouploader.records.actions.delete')
        </div>
      </div>
    </div>
</div>