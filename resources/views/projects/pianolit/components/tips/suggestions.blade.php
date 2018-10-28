<div class="mt-3" data-test="{{count($tips)}}">
@if(count($tips) > 0 && ! empty($tips[0]))
<p class="text-muted mb-2"><strong>Suggested tips:</strong></p>
<ul class="list-style-none p-0 m-0">
	@foreach($tips as $tip)
		@if(! empty($tip))
			<li class="rounded py-1 px-2 mb-2 text-muted cursor-pointer clip" data-clipboard-text="{{$tip}}" data-toggle="tooltip" data-title="Copied!" data-trigger="manual" style="line-height: 1.2; background-color: #2e5ab912"><small>{{$tip}}</small></li>
		@endif
	@endforeach
</ul>
@else
<p class="text-muted mb-1"><i><small>No suggestions to show</small></i></p>
@endif
</div>