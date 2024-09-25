<div class="alert-container alert-{{$style ?? 'regular'}} rounded-0  {{$classes ?? null}} @isset($pos)alert-{{$pos}}@endisset"@isset($countdown)data-countdown="{{$countdown}}"@endisset @isset($attr){{$attr}}@endisset @isset($status){{$status}}@endisset 
	style="z-index: 10000; display: {{iftrue($hide ?? null, 'none')}}">
	<div class="alert @isset($animation)animate__animated animate__{{$animation['in']}}@endisset rounded m-0 alert-{{$color}} alert-dismissible {{$classes ?? null}}" role="alert">
		@isset($headline)<strong class="mr-2"> {!! $headline !!} |</strong>@endisset @isset($icon)@fa(['icon' => $icon])@endisset<span class="popup-message">{!! $message !!}</span>
	<button type="button" class="btn-close no-stroke text-{{$color}}" data-dismiss="alert"@isset($animation){{' data-animation=animate__'.$animation['out']}}@endisset aria-label="Close"></button>
</div>  
</div>