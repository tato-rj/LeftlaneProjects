@php($size = bytesToGb(\App\Projects\VideoUploader\Video::sum('original_size')))
<div class="container mb-4">
	<div class="w-100 bg-light border" style="height: 20px">
		<div class="h-100 d-flex align-items-center justify-content-end pe-2 small" style="
			width: {{percentage($size, 20)}}%;
			background: lightgreen;
		">{{$size}}GB</div>
	</div>
</div>