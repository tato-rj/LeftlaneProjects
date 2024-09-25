<div class="alert-container alert-{{$style ?? 'regular'}} rounded-0 {{$classes ?? null}} @isset($pos)alert-{{$pos}}@endisset"@isset($countdown)data-countdown="{{$countdown}}"@endisset @isset($attr){{$attr}}@endisset>
	<div class="alert py-2 px-3 border-0 @isset($animation)animate__animated animate__{{$animation['in']}}@endisset rounded-pill m-0 alert-{{$color}} alert-dismissible {{$classes ?? null}}" role="alert">
		@isset($headline)<div class="badge rounded-pill bg-{{$color}} mr-2 text-uppercase">new</div>@endisset<strong>{!! $message !!}</strong>
	@isset($link)
	<a href="{{$link}}" class="alert-feature-link d-center text-{{$color}}">@fa(['icon' => 'chevron-right', 'mr' => 0])</a>
	@else
	<button type="button" class="btn-close p-0 no-stroke text-{{$color}}" data-dismiss="alert"@isset($animation){{' data-animation=animate__'.$animation['out']}}@endisset aria-label="Close"></button>
	@endisset
</div>  
</div>