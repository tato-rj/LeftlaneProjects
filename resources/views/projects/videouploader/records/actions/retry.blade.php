<div class="col-12 col-sm-auto m-1">
	<form method="POST" action="{{route('videouploader.videos.retry', $video)}}">
	  @csrf
	  <button type="submit" class="btn btn-outline-success btn-sm w-100">@fa(['icon' => 'redo'])Retry</button>
	</form>
</div>