<div class="container mb-4">
	<div class="bg-light border p-4">
		<h5 class="mb-4">@fa(['icon' => 'folder-open']){{$name}} folder</h5>
		@if($folder->isEmpty())
		<div class="text-success">@fa(['icon' => 'check'])This folder is empty</div>
		@else

		@include('projects.videouploader.system.header')

			@unless($folder->junk()->isEmpty() || $folder->files()->isEmpty())
			<div class="mt-2" style="max-height: 300px; overflow-y: scroll;">
				<table class="table table-responsive table-striped table-sm">
				  <thead>
				    <tr>
				      <th scope="col">File name</th>
				      <th scope="col">Size</th>
				      <th scope="col">Last modified</th>
				    </tr>
				  </thead>
				  <tbody>
				  	@foreach($folder->junk() as $file)
				    <tr>
				      <td class="text-nowrap">@fa(['icon' => 'file text-danger']){{$file['name']}}</td>
				      <td class="text-nowrap">{{bytesToMb($file['size'])}}</td>
				      <td class="text-nowrap">{{$file['last_modified']->toDayDateTimeString()}}</td>
				    </tr>
				    @endforeach
				  </tbody>
				</table>
			</div>
			@endunless
		@endif
	</div>
</div>