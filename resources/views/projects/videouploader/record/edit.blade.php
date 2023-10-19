<button type="button" data-bs-toggle="modal" data-bs-target="#edit-video-{{$video->id}}" class="btn btn-warning btn-sm me-2">
  @fa(['icon' => 'edit'])Edit
</button>

<div class="modal fade" id="edit-video-{{$video->id}}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="modal-title fs-5" id="exampleModalLabel">Edit</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{route('videouploader.videos.update', $video)}}" enctype="multipart/form-data">
            @method('PATCH')
            @csrf

            <input class="form-control mb-3" type="text" name="notes" placeholder="Notes" value="{{$video->notes}}">
            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>

        <div class="mt-3 pt-3" style="border-top: 4px dotted lightgrey;">
          @if($video->isCompleted())
            @include('projects.videouploader.record.actions.orientation')
          @elseif($video->isPending())
            <div class="text-muted text-center small fst-italic">Waiting for {{$video->created_at->longAbsoluteDiffForHumans()}}</div>
          @elseif($video->isAbandoned() || $video->isFailed())
            @include('projects.videouploader.record.actions.retry')
          @endif
        </div>
      </div>
    </div>
  </div>
</div>