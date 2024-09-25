@php($selected = request()->sort_by == (new \Table)->getFieldname($field))

@form(['method' => 'GET', 'url' => url()->full(), 'csrf' => false])
<input type="hidden" name="sort_by" value="{{(new \Table)->getFieldname($field)}}">
<input type="hidden" name="order_by" value="{{$selected && request()->order_by == 'asc' ? 'desc' : 'asc'}}">
<button type="submit" class="d-apart btn-raw w-100
  @if(request()->has('sort_by'))
  @unless($selected && request()->sort_by == (new \Table)->getFieldname($field))
  opacity-4
  @endunless
  @endif
"> 
  {{$column['label']}}
  @fa(['icon' => $selected && request()->order_by == 'asc' ? 'chevron-up' : 'chevron-down', 'mr' => 0])
</button>
@endform
