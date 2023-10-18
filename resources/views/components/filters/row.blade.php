<div class="mb-2 d-flex justify-content-end">
    <div class="btn-group">
        @foreach($options as $label => $value)
        @php($selected = request($name) == $value)
        <form method="GET" action="{{$value ? url()->full() : currentUrlExcept($name)}}">
            @if($value)
            <input type="hidden" name="{{$name}}" value="{{$value}}">
            @endif

            @foreach($include as $param)
            @if(request()->has($param))
            <input type="hidden" name="{{$param}}" value="{{request($param)}}">
            @endif
            @endforeach

            <button type="submit" 
                class="btn 
                {{$selected ? 'btn-primary' : 'btn-outline-primary'}}
                {{! $loop->first ? 'border-s-0' : null}}
                btn-sm">
                {{$label}}
            </button>
        </form>
        @endforeach
    </div>
</div>