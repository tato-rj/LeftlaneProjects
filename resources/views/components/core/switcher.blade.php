<label class="switch {{$shape ?? 'round'}}">
  <input type="checkbox"{{$on ? ' checked' : null}}@isset($url)data-url="{{$url}}"@endisset>
  <span class="slider"></span>
</label>