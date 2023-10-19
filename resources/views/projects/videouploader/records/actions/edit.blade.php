<div class="col-12 col-sm-auto m-1">
  <button type="button" data-bs-toggle="modal" data-bs-target="#edit-video-{{$video->id}}" class="w-100 btn btn-warning btn-sm">
    @fa(['icon' => 'edit'])Edit
  </button>
  @include('projects.videouploader.records.edit.modal')
</div>