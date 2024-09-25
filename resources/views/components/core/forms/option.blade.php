<option value="{{$value}}" 
		@if(old($name) == $value)
		selected
		@else
		{{iftrue($selected ?? null, 'selected')}}
		@endif
	>
	{{$label}}
</option>