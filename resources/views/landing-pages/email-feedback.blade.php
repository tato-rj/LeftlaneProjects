@extends('layouts.landing-page')

@section('content')
<div class="container-fluid bg-gradient d-flex align-items-center justify-content-center flex-column text-white" style="height: 100vh">
	<div class="row">
		<div class="col-lg-8 col-md-8 col-sm-10 col-10 mx-auto">
			<h1 style="font-size: 5em">Hello!</h1>
			<p class="text-white lead mb-1">{{$message}}</p>
			<p class="text-white lead text-right" style="opacity: 0.6"><i>- the PianoLIT team</i></p>
		</div>
	</div>
</div>
@endsection