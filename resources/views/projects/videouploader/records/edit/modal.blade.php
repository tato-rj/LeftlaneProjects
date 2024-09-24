<div class="modal fade" id="edit-video-{{$video->id}}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="modal-title fs-5" id="exampleModalLabel">Edit</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{route('videouploader.videos.update', $video)}}">
            @method('PATCH')
            @csrf

            <label class="form-label">File path</label>
            <input value="{{$video->video_path}}" name="video_path" class="form-control mb-3">
            
            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
      </div>
    </div>
  </div>
</div>