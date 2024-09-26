@php($size = bytesToGb(\App\Projects\VideoUploader\Video::sum('original_size')))
@php($percentage = percentage($size, 20))

@if($percentage < 50)
@php($color = 'lightgreen')
@elseif($percentage < 90)
@php($color = 'lightyellow')
@else
@php($color = 'lightyellow')
@endif

<div class="container mb-4 d-flex align-items-center">
	<div class="me-2 text-nowrap"><strong>Max storage 20GB</strong></div>
	<div class="w-100 bg-light border" style="height: 20px">
		<div class="h-100 d-flex align-items-center justify-content-end pe-2 small" style="
			width: {{$percentage}}%;
			background: {{$color}};
		">{{$size}}GB</div>
	</div>
</div>