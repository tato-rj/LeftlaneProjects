@if($video->isRemote())
#{{$video->id}} | {{$video->created_at->toFormattedDateString()}} @fa(['icon' => $video->origin_icon, 'mr' => 2, 'ml' => 2]) {{$video->user_email}} - Piece ID: {{$video->piece_id}}
@else
#{{$video->id}} | Test
@endif

@if($video->notes)
({{$video->notes}})
@endif