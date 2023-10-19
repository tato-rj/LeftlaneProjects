<div class="col-12 col-sm-auto m-1">
@if($video->video_url)
    <a href="{{$video->video_url}}" target="_blank" class="btn btn-outline-primary btn-sm w-100">Video</a>
@else
    <a href="{{storage($video->temp_path)}}" target="_blank" class="btn btn-outline-primary btn-sm w-100">Original Video</a>
@endif
</div>