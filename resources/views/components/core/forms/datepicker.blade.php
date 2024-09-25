<div class="form-group">
	@isset($label)
    <h6 class="text-center">{{$label}}</h6>
    @endisset

	<input type="hidden" id="{{$id}}" name="{{$name}}" value="{{$value ?? null}}">
	<div 
	@isset($options)
	{{implode(' ', $options)}}
	@endisset
	 data-input="#{{$id}}" data-datepicker="{{$value ?? null}}"></div>

	@feedback(['input' => $name])
</div>