<div class="form-group text-left">
	@isset($label)
    @label
    @endisset

    <div class="form-control form-control-{{$size ?? null}} {{$classes ?? null}}">
    	@isset($chatgpt)
		<button id="ask-chatgpt" data-chatgpt="{{$chatgpt['query']}}" data-url="{{$chatgpt['route']}}" type="button" class="w-100 mb-1 btn btn-sm btn-outline-secondary">@fa(['icon' => 'wand-magic-sparkles'])Ask ChatGPT</button>
		@endisset

		<textarea class="border-0 w-100 h-100" 
			name="{{$name}}" 
			rows="{{$rows ?? 4}}" 
			placeholder="{{$placeholder ?? null}}"
			@isset($id)
			id="{{$id}}"
			@endisset 
			{{iftrue($required ?? null, 'required data-required')}}
			{{iftrue($readonly ?? null, 'readonly')}}>{{$value ?? null}}</textarea>
	</div>
	
	@isset($info)
	<div class="form-text">{{$info}}</div>
	@endisset

	@isset($chatgpt)
		@include('components.chatgpt.disclaimer', ['hidden' => true])
	@endisset
	
	@feedback(['input' => $name])
</div>