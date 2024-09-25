@php($uuid = uuid())
<div class="form-group">
    @isset($label)
    @label
    @endisset

    <div>
        @isset($options)
        @foreach($options as $checkboxValue => $label)
        <div class="form-check {{iftrue($inline ?? null, 'form-check-inline')}} {{$classes ?? null}}">
          <input 
            class="form-check-input" 
            name="{{$name}}[]" 
            type="checkbox" 
            value="{{$checkboxValue}}" 
            id="checkbox-{{$uuid}}-{{$checkboxValue}}" 
            {{ is_array(old($name)) && in_array($checkboxValue, old($name)) ? ' checked' : '' }}
            {{iftrue($readonly ?? null, 'readonly')}}>
                <label class="form-check-label" for="checkbox-{{$uuid}}-{{$checkboxValue}}">{{$label}}</label>
        </div>
        @endforeach
        @else
        {{$slot}}
        @endisset
    </div>

    @isset($info)
    <div class="form-text">{{$info}}</div>
    @endisset

    @feedback(['input' => $name])
</div>