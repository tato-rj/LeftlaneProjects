<form action="{{route('videouploader.delete')}}" method="POST">
    @csrf
    @method('DELETE')
    <input type="hidden" name="secret" value="{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}">
    <input type="hidden" name="user_id" value="{{$video->user_id}}">
    <input type="hidden" name="piece_id" value="{{$video->piece_id}}">
    <button type="submit" class="btn btn-danger btn-sm">@fa(['icon' => 'trash-alt'])Delete</button>
</form>