<div class="form-group">
	@isset($label)
    @label
    @endisset

    <input type="hidden" id="{{$id}}" name="{{$name}}" value="{{$value ?? null}}">
    
    <div class="timepicker-container d-flex">
    	@include('components.core.forms.timepicker.select', [
            'options' => hourslots(),
            'type' => 'hour',
            'format' => $hour ?? null])

    	@include('components.core.forms.timepicker.dots')

    	@include('components.core.forms.timepicker.select', [
            'options' => minuteslots(),
            'type' => 'minute',
            'format' => $minute ?? null])
    </div>

    @feedback(['input' => $name])
</div>