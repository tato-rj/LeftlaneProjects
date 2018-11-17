@if($piece->audio_path)
<a href="{{$piece->file_path('audio_path')}}" target="_blank" class="text-brand mr-1"><i class="fas fa-microphone"></i></a>
@elseif($piece->youtube_array)
<a href="https://www.youtube.com/watch?v={{$piece->youtube_array[0]}}" target="_blank" class="text-brand mr-1"><i class="fab fa-youtube"></i></a>
@endif