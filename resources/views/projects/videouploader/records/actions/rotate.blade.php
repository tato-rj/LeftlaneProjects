<div class="col-12 col-sm-auto m-1">
	<form action="{{route('videouploader.videos.rotate', $video)}}" method="POST">
	    @csrf
	    @method('PATCH')
	    <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
	    <input type="hidden" name="user_id" value="{{$video->user_id}}">
	    <input type="hidden" name="piece_id" value="{{$video->piece_id}}">

	    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">@fa(['icon' => 'retweet'])Rotate</button>
	</form>
</div>