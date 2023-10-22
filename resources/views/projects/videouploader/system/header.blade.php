@php($filesCount = $folder->files()->count())
@php($junkCount = $folder->junk()->count())

<div class="mb-2 d-flex justify-content-between align-items-start">
	<div>
		<div class="text-warning">{{$filesCount}} {{str_plural('file', $filesCount)}} in use</div>
		<div class="text-danger">{{$junkCount}} junk {{str_plural('file', $junkCount)}} left behind</div>
	</div>

	@if($junkCount)
	<form method="POST" action="{{route('videouploader.system.delete')}}">
		@csrf
		@method('DELETE')
		<input type="hidden" name="command" value="{{$folder->command()}}">
		<button type="submit" class="btn btn-danger btn-sm">@fa(['icon' => "trash-alt"])Remove junk</button>
	</form>
	@endif
</div>