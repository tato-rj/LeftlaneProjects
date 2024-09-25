@php($piece = $row)
@switch((new \App\Table\Table)->getFieldname($field))
  @case('name')
    {{$piece->name}}
    @break

  @case('composer')
    {{$piece->composer}}
    @break

  @case('actions')
      @component('components.table.actions', [
        'edit' => ['modal' => 'projects.videouploader.videos.edit']
      ])
      @include('projects.videouploader.videos.edit')

      @endcomponent
      @break
@endswitch