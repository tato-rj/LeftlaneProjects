<div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Delete <strong><span id="name"></span></strong>?
        <p class="text-danger"><small>This action cannot be undone</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="/quickreads/{{$slot}}/">
          {{csrf_field()}}
          {{method_field('DELETE')}}
          <input type="hidden" name="slug" value="">
          <button type="submit" class="btn btn-block btn-danger">Yes, I am sure</button>
        </form>
      </div>
    </div>
  </div>
</div>