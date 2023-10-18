<form method="POST" action="{{route('videouploader.videos.retry', $video)}}">
  @csrf
  <button type="submit" class="btn btn-primary btn-sm me-2">@fa(['icon' => 'redo'])Retry</button>
</form>