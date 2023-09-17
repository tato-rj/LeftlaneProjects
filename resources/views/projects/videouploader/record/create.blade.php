<button type="button" id="choose-video" class="btn btn-primary">
  Test upload
</button>

<div class="modal fade" id="confirm-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="modal-title fs-5" id="exampleModalLabel">Confirm upload</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <button type="submit" id="confirm-button" class="btn btn-primary">Upload video</button>
      </div>
    </div>
  </div>
</div>

{{-- <form method="POST" action="{{route('upload')}}" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
    <input type="hidden" name="email" value="test@email.com">
    <input type="hidden" name="origin" value="local">
    <input type="hidden" name="user_id" value="1">
    <input type="hidden" name="piece_id" value="1">

    <div class="mb-3">
      <input class="form-control" type="file" id="video" name="video" required>
    </div>
    <button type="submit" class="btn btn-primary">Upload</button>
</form> --}}