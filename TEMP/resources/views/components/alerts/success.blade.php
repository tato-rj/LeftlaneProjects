@component('components/alerts/alert')
	@slot('alert')success
	@endslot
	@slot('message')
		<strong class="mr-2">Success |  </strong>{{$message}}
	@endslot
@endcomponent