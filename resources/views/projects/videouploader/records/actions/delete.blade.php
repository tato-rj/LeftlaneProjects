<div class="col-12 col-sm-auto m-1">
  <button type="button" data-bs-toggle="modal" data-bs-target="#delete-video-{{$video->id}}" class="w-100 btn btn-danger btn-sm">
    @fa(['icon' => 'trash-alt'])Delete
  </button>
  @include('projects.videouploader.records.delete')
</div>