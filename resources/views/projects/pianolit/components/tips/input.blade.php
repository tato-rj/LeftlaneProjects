<div class="form-group type-container {{$type or 'd-flex'}} mb-2" style="display: {{$display or null}}">

	<a class="align-self-stretch btn btn-sm btn-danger text-white mr-1 remove-field">
		<i class="fas fa-minus"></i>
	</a>

  	<input rows="1" class="form-control-sm form-control" name="{{$name or null}}" value="{{$value or null}}">

</div>