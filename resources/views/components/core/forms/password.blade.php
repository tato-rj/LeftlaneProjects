<div class="form-group text-left">
	@isset($label)
    @label
    @endisset

    <div class="form-control form-control-{{$size ?? null}} {{$classes ?? null}} d-flex align-items-center">
    	<div>
    		@fa(['icon' => 'lock', 'fa_color' => 'grey-light'])	
    	</div>

		<input 
			class="border-0 w-100 h-100"
			type="password" 
			placeholder="••••••"
			autocomplete="off"
			maxlength="255" 
			name="{{$name}}"

			@isset($id)id="{{$id}}"@endisset

			{{iftrue($required ?? null, 'required data-required')}}
			{{iftrue($readonly ?? null, 'readonly')}}>
	</div>
	
	@isset($info)
	<div class="form-text">{{$info}}</div>
	@endisset

	@feedback(['input' => $name])
</div>