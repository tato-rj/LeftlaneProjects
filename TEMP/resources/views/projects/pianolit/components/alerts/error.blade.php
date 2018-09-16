@component('projects/pianolit/components/alerts/alert')
	@slot('alert')danger
	@endslot
	@slot('message')
	<ul class="p-0 m-0 list-style-none">
		@foreach ($errors->all() as $error)
		<li>{{$error}}</li>
		@endforeach
	</ul>
	@endslot
@endcomponent