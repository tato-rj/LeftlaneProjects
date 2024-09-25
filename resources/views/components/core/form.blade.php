@php($csrf = $csrf ?? true)

<form method="{{formMethod($method)}}"
	@isset($data)
	@foreach($data as $type => $action)
	data-{{$type}}="{{$action}}"
	@endforeach
	@endisset
 	class="{{iftrue($borderless ?? null, 'form-borderless')}} {{iftrue($light ?? null, 'form-light')}} text-center {{$classes ?? null}}" action="{{$url}}">
 	
 	@if($csrf)
	@csrf
	@endif

	@if(in_array(strtolower($method), ['delete', 'patch']))
	@method($method)
	@endif

	{{$slot}}	

</form>