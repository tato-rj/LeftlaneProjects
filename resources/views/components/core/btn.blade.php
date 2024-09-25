@if(isset($url))
<a href="{{$url}}" class="btn btn-{{$theme}} {{isset($type) ? ' btn-'.$type : null}}{{isset($icon) ? ' d-center' : null}} {{$classes ?? null}}"@isset($trigget) data-trigger="{{$trigger ?? null}}"@endisset @isset($id)id="{{$id}}"@endisset @isset($attr){{$attr}}@endisset>
@isset($icon)
	@if($icon['position'] == 'left')
	@fa(['icon' => $icon['name'], 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
	{{$label}}
@isset($icon)
	@if($icon['position'] == 'right')
	@fa(['icon' => $icon['name'], 'mr' => 0, 'ml' => 2, 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
</a>

@elseif(isset($modal))

<button data-toggle="modal" data-target="#{{$modal}}" class="btn btn-{{$theme}} {{isset($type) ? ' btn-'.$type : null}}{{isset($icon) ? ' d-center' : null}} {{$classes ?? null}}"@isset($trigget) data-trigger="{{$trigger ?? null}}"@endisset @isset($id)id="{{$id}}"@endisset @isset($attr){{$attr}}@endisset>
@isset($icon)
	@if($icon['position'] == 'left')
	@fa(['icon' => $icon['name'], 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
	{{$label}}
@isset($icon)
	@if($icon['position'] == 'right')
	@fa(['icon' => $icon['name'], 'mr' => 0, 'ml' => 2, 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
</button>

@else

<button class="btn btn-{{$theme}} {{isset($type) ? ' btn-'.$type : null}}{{isset($icon) ? ' d-center' : null}} {{$classes ?? null}}" type="{{iftrue($submit ?? null, 'submit')}}" @isset($trigger) data-trigger="{{$trigger ?? null}}"@endisset @isset($id)id="{{$id}}"@endisset @isset($attr){{$attr}}@endisset>
@isset($icon)
	@if($icon['position'] == 'left')
	@fa(['icon' => $icon['name'], 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
	{{$label}}
@isset($icon)
	@if($icon['position'] == 'right')
	@fa(['icon' => $icon['name'], 'mr' => 0, 'ml' => 2, 'classes' => $icon['animation'] ?? null])
	@endif
@endisset
</button>
@endif