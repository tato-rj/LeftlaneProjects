<div class="form-group type-container {{$type or 'd-flex'}} mb-2" style="display: {{$display or null}}">

	<a class=" btn btn-sm btn-danger text-white mr-1 remove-field" style="height: 32px;">
		<i class="fas fa-minus"></i>
	</a>

  	<textarea rows="3" class="form-control-sm form-control" name="{{$name or null}}">{{$value or null}}</textarea>

</div>