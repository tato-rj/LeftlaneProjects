@php($video = $row)
@switch((new \App\Table\Table)->getFieldname($field))
  @case('name')
    {{$video->name}}
    @break

  @case('composer')
    {{$video->composer}}
    @break

  @case('actions')
      @component('components.table.actions', [
        'edit' => ['modal' => 'projects.videouploader.videos.edit']
      ])
      @include('projects.videouploader.videos.edit')

      @endcomponent
      @break
@endswitch