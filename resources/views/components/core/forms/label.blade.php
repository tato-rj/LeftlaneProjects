@if($label)
<label class="form-label w-100 mb-1 ml-3 text-nowrap {{$classes ?? null}}">
	<small>
	@isset($icon)
	@fa
	@endisset
	{{$label}}
	@isset($required)
	<span class="text-red">*</span>
	@endisset

	@isset($tippy)
	<i class="ml-1 fas fa-info-circle text-primary cursor-pointer" data-toggle="tippy" data-tippy-content="{{$tippy}}"></i>
	@endisset
	</small>
</label>
@endif