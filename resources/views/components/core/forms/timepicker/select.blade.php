<div data-timepicker="{{$value ?? null}}" class="timepicker-select col d-flex flex-column">

	<button class="btn-raw" data-target="next" type="button">@fa(['icon' => 'angle-up', 'mr' => 0])</button>
	
	<div class="timepicker-times text-noselect text-center">
		@foreach($options as $time => $date)
		<div data-input="#{{$id}}" data-type="{{$type}}" data-name="{{$name}}"
			@isset($format)
			style="display: {{$format == $time ? 'block' : 'none'}}"
			@else
			style="display: {{$loop->first ? 'block' : 'none'}}"
			@endisset
		 >{{$time}}</div>
		@endforeach
	</div>

	<button class="btn-raw" data-target="prev" type="button">@fa(['icon' => 'angle-down', 'mr' => 0])</button>

</div>