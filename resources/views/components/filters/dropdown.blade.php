<div class="d-inline-block">
    <form method="GET" action="{{currentUrlExcept($name)}}">
        @foreach($include as $param)
        @if(request()->has($param))
        <input type="hidden" name="{{$param}}" value="{{request($param)}}">
        @endif
        @endforeach

        <select class="form-select form-select-sm" name="{{$name}}" onchange="this.form.submit()">
            @foreach($options as $label => $value)
            @php($selected = request($name) === $value)

            <option {{$selected ? 'selected' : null}} value="{{$value}}">{{$label}}</option>

            @endforeach
        </select>
    </form>
</div>