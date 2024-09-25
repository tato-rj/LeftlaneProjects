<div class="results-container">
  <div class="table-responsive te">
    <table class="table table-sm text-nowrap">
      <thead>
        <tr>
          @foreach($columns as $field => $column)
          <th scope="col" style="min-width: 160px; width: {{$column['width']}}" 
            @if((new \Table)->isSortable($field))
            data-sort="{{(new \Table)->getFieldname($field)}}" 
            @endif
            class="px-2">
              @if((new \Table)->isSortable($field))
                @include('components.table.headers.sortable')
              @else
              <button class="btn-raw opacity-4" style="font-weight: normal; cursor: default;">{{$column['label']}}</button>
              @endif
          </th>
          @endforeach
        </tr>
      </thead>

      <tbody class="table-group-divider">
        @foreach($rows as $row)
        <tr>
          @foreach($columns as $field => $label)
            <td class="{{$field == 'actions' ? 'text-right' : null}} text-truncate align-middle {{$padding ?? 'px-2 py-2'}} {{$label['classes'] ?? null}}" style="max-width: 200px;">
              @if($field == 'actions')
              @include($view)
              @else
              @include($view)
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if(hasPagination($rows))
  {{ $rows->appends(array_filter(request()->all()))->links() }}
  @endif
</div>