<div class="px-3 pb-2 col fw-bold text-truncate {{in_array($loop->iteration, $optional ?? []) ? 'd-none d-md-block' : null}}" 
        style="min-width: {{$header == '' ? '180px' : null}}">{{$header}}</div>