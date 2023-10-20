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

            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control mb-3" rows="3" maxlength="200">{{$video->notes}}</textarea>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>

        <div class="mt-3 pt-3" style="border-top: 4px dotted lightgrey;">
          @if($video->isCompleted())
            @include('projects.videouploader.records.edit.orientation')
          @elseif($video->isPending())
            <div class="text-warning text-center small fst-italic">@fa(['icon' => 'hourglass-half'])Waiting for {{$video->created_at->longAbsoluteDiffForHumans()}}</div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>