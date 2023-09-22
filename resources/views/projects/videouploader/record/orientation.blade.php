<form action="{{route('videouploader.orientation')}}" method="POST" class="me-2">
    @csrf
    <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
    <input type="hidden" name="user_id" value="{{$video->user_id}}">
    <input type="hidden" name="piece_id" value="{{$video->piece_id}}">

    <button type="submit" class="btn btn-warning btn-sm">FIX ORIENTATION</button>
</form>