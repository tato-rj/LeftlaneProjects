<div class="container mb-4">
	<div class="bg-light border p-4">
		<h5 class="mb-4">@fa(['icon' => 'folder-open']){{$name}} folder</h5>
		@if($folder->files()->isEmpty())
		<div class="text-success">@fa(['icon' => 'check'])This folder is empty</div>
		@else
		<div class="text-danger mb-2 d-flex justify-content-between align-items-end">
			@php($count = $folder->files()->count())
			<div>We found {{$count}} {{str_plural('file', $count)}} in this folder</div>
			<form method="POST" action="{{route('videouploader.system.delete')}}">
				@csrf
				@method('DELETE')
				<input type="hidden" name="command" value="{{$folder->command()}}">
				<button type="submit" class="btn btn-danger btn-sm">@fa(['icon' => "trash-alt"])Delete all</button>
			</form>
		</div>
		<div style="max-height: 300px; overflow-y: scroll;">
			<table class="table table-striped table-sm">
			  <thead>
			    <tr>
			      <th scope="col">File name</th>
			      <th scope="col">Size</th>
			      <th scope="col">Last modified</th>
			    </tr>
			  </thead>
			  <tbody>
			  	@foreach($folder->files() as $file)
			    <tr>
			      <td class="text-nowrap">@fa(['icon' => 'file text-danger']){{$file['name']}}</td>
			      <td class="text-nowrap">{{bytesToMb($file['size'])}}</td>
			      <td class="text-nowrap">{{$file['last_modified']->toDayDateTimeString()}}</td>
			    </tr>
			    @endforeach
			  </tbody>
			</table>
		</div>
		@endif
	</div>
</div>