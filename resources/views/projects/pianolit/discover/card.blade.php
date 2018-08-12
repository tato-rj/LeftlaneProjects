<button type="submit" class="cursor-pointer border-0 p-0 mx-1" style="background: none">
  <div class="d-flex justify-content-between flex-column rounded text-white py-2 px-3" style="height: 120px; width: 120px; background: {{$model->color}}">
    <div>
      <p class="m-0 clamp-2 text-left" style="max-width: 100%;">{{$model->name}}</p>
    </div>
    <div>
      <p class="m-0 text-left"><span><small>{{$model->count}}</small></span></p>
    </div>
  </div>
</button>