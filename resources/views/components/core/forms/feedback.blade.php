<div class="valid-feedback">
  Looks good!
</div>

@isset($errors)
<div class="invalid-feedback {{$errors->has($input) ? 'd-block' : null}}">
  @error($input)
  @fa(['icon' => 'exclamation-circle', 'mr' => 1]){{$message}}
  @enderror
</div>
@endisset