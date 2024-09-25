<div class="text-center mb-3">
<a class="btn btn-primary" type="button" id="choose-video">Upload video</a>
</div>

<div class="modal fade" id="confirm-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="modal-title fs-5" id="exampleModalLabel">Confirm upload</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Name</label>
        <input required placeholder="Video name" name="name" class="form-control mb-3">
        <label class="form-label">Composer</label>
        <input required placeholder="Full name" name="composer" class="form-control mb-3">
        <button type="submit" id="confirm-button" class="btn btn-primary">Upload video</button>
      </div>
    </div>
  </div>
</div>