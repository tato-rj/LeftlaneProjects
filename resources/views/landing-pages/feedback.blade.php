@if(session('status'))
<div class="alert alert-success" role="alert">
  <p class="text-success">Thank you for stopping by!</p>
  {{session('status')}}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger" role="alert">
	<p class="text-danger"><strong>Sorry!</strong></p>
    @foreach ($errors->all() as $error)
        <p class="m-0 text-danger">{{ $error }}</p>
    @endforeach
</div>
@endif
