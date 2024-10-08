@php($video = $row)
@switch((new \App\Table\Table)->getFieldname($field))
  @case('created_at')
    {{$video->created_at->toFormattedDateString()}}
    @break

  @case('name')
    {{$video->filename()}}
    @break

  @case('composer')
    {{$video->composer()}}
    @break

  @case('actions')
      @component('components.table.actions', [
        'edit' => ['modal' => '#edit-video-' . $video->id]
      ])
      @include('projects.videouploader.videos.edit')
      @include('projects.videouploader.videos.json')
      @include('projects.videouploader.videos.preview')
      @include('projects.videouploader.videos.delete')

      @endcomponent
      @break
@endswitch