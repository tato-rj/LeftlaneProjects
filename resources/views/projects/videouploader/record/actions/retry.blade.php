<form method="POST" action="{{route('videouploader.videos.retry', $video)}}">
  @csrf
  <button type="submit" class="btn btn-warning btn-sm w-100">@fa(['icon' => 'redo'])Retry</button>
</form>