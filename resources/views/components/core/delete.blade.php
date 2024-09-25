@btn(['icon' => ['name' => 'trash-alt', 'position' => 'center', 'color' => $color ?? 'red'], 'theme' => 'raw', 'url' => null, 'modal' => $target, 'fa_size' => $size ?? null])
@modal(['title' => 'Delete ' . $item, 'id' => $target, 'size' => 'sm'])
	@form(['method' => 'DELETE', 'url' => $url, 'borderless' => true, 'data' => ['trigger' => 'loader']])
	<div>Are you sure?</div>
	<p class="text-red"><small>This action cannot be undone.</small></p>
	@submit(['label' => 'Yes, delete this ' . $item, 'theme' => 'red', 'classes' => 'btn-block'])
	@endform
@endmodal