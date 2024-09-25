<button type="button" data-bs-toggle="modal" data-bs-target="#delete-video-{{$video->id}}" class="me-2 btn btn-danger btn-sm">
  @fa(['icon' => 'trash-alt', 'mr' => 0])
</button>

<div class="modal fade" id="delete-video-{{$video->id}}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="modal-title fs-5" id="exampleModalLabel">Delete</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="m-0">Are you sure?</p>
        <p class="text-danger">This action cannot be undone</p>
        <form action="{{route('videouploader.videos.destroy', $video)}}" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
            <button type="submit" class="w-100 btn btn-danger">Yes, delete this upload</button>
        </form>
      </div>
    </div>
  </div>
</div>