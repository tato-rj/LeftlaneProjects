<div class="modal fade" id="tag-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit tag</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" id="edit-tag" class="form-row">
          {{csrf_field()}}
          {{method_field('PATCH')}}
          <div class="col" style="flex-grow: 3">
            <input type="text" name="name" class="form-control" id="name">
          </div>
          <div class="col">
            <button type="submit" class="btn btn-default">Save changes</button>
          </div>
        </form>  
      </div>
      <div class="modal-footer text-center border-0 pt-0">
        <form method="POST" id="delete-tag" class="w-100">
          {{csrf_field()}}
          {{method_field('DELETE')}}
          <button type="submit" class="btn btn-link btn-sm text-danger no-underline p-0"><i class="fas fa-trash-alt mr-2"></i>Delete tag</button>
          <p class="text-muted text-right mb-0 mt-2"><i><small>Tag created by <strong><span id="creator"></span></strong></small></i></p>
        </form>
      </div>
    </div>
  </div>
</div>