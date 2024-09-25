@php($video = $row)
@switch((new \App\Table\Table)->getFieldname($field))
  @case('created_at')
    {{$videos->created_at->toFormattedDateString()}}
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

      @endcomponent
      @break
@endswitch