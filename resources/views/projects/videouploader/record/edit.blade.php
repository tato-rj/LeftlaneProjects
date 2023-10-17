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

        @if($video->completed())
        <div class="mt-3 pt-3" style="border-top: 4px dotted lightgrey;">
          <form action="{{route('videouploader.orientation')}}" method="POST">
              @csrf
              <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
              <input type="hidden" name="user_id" value="{{$video->user_id}}">
              <input type="hidden" name="piece_id" value="{{$video->piece_id}}">

              <button type="submit" class="btn btn-warning btn-sm">Fix orientation</button>
          </form>
        </div>
        @endif

      </div>
    </div>
  </div>
</div>