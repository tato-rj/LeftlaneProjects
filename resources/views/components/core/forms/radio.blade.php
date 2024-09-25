@php($uuid = uuid())
<div class="form-group">
    @isset($label)
    @label
    @endisset

    <div>
        @foreach($options as $radioValue => $label)
        <div class="form-check {{iftrue($inline ?? null, 'form-check-inline')}} {{$classes ?? null}}">
          <input 
            class="form-check-input" 
            name="{{$name}}" 
            type="radio" 
            value="{{$radioValue}}" 
            id="radio-{{$uuid}}-{{$radioValue}}" 
            @isset($value)
            {{ $value == $radioValue ? ' checked' : '' }}
            @else
            {{ old($name) == $radioValue ? 'checked' : '' }}
            @endisset
            {{iftrue($readonly ?? null, 'readonly')}}>
                <label class="form-check-label" for="radio-{{$uuid}}-{{$radioValue}}">{{$label}}</label>
        </div>
        @endforeach
    </div>

    @isset($info)
    <div class="form-text ml-3">ps: {{$info}}</div>
    @endisset

    @feedback(['input' => $name])
</div>