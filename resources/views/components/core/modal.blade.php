<div class="modal fade {{$class ?? null}}-modal {{iftrue($modalcenter ?? null, 'd-center')}} {{iftrue($autoshow ?? null, 'modal-autoshow')}}"
  @isset($data)
  @foreach($data as $type => $action)
  data-{{$type}}="{{$action}}"
  @endforeach
  @endisset
 id="{{$id}}" style="white-space: initial; cursor: default;">
  <div class="modal-dialog modal-{{$size ?? null}}">
    <div class="modal-content rounded border-0">

      @isset($header)
      <div class="modal-header border-0 p-4 align-items-start">
        <div class="text-truncate">{!! $header !!}</div>
        <button type="button" class="btn-close btn-raw text-dark" style="width: inherit; height: inherit;" data-bs-dismiss="modal" aria-label="Close">@fa(['icon' => 'times', 'fa_size' => '2x', 'mr' => 0])</button>
      </div>
      @else
      <div class="modal-header border-0 {{isset($title) ? 'pb-0' : null}}">
        <h4 class="modal-title text-secondary no-stroke">{!!$title ?? null!!}</h4>
        <button type="button" class="btn-close btn-raw" style="width: inherit; height: inherit;" data-bs-dismiss="modal" aria-label="Close">@fa(['icon' => 'times', 'fa_size' => '2x', 'mr' => 0])</button>
      </div>
      @endisset

      <div class="modal-body {{! isset($title) ? 'pt-0' : null}}">
        {{$slot}}
      </div>

      @isset($footer)
      <div class="modal-footer">
        {{$footer}}
      </div>
      @endisset

    </div>
  </div>
</div>