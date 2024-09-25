<div class="text-white d-flex">
  @foreach($headers as $field => $header)
    @if(is_string($field))
      @include('components.table.headers.sortable')
    @else
      @include('components.table.headers.simple')
    @endif
  @endforeach
</div>