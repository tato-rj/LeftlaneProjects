@php($size = bytesToGb(\App\Projects\VideoUploader\Video::sum('original_size')))
@php($percentage = percentage($size, 20))

@switch($percentage)
    @case(< 50)
        @php($color = 'lightgreen')
        @break
    @case(< 90)
        @php($color = 'lightyellow')
        @break
    @default
        @php($color = 'lightred')
@endswitch


<div class="container mb-4">
	<div class="w-100 bg-light border" style="height: 20px">
		<div class="h-100 d-flex align-items-center justify-content-end pe-2 small" style="
			width: {{$percentage}}%;
			background: {{$color}};
		">{{$size}}GB</div>
	</div>
</div>