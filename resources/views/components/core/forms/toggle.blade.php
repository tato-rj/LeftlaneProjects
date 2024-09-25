<div class="d-apart">
  <h6 class="mr-2 mb-0">
    {{$label ?? null}}
  </h6>
  <label class="switch">
    <input name="{{$name}}" data-url="{{$url ?? null}}" type="checkbox" {{$on ? 'checked' : null}}>
    <span class="slider round"></span>
  </label>
</div>