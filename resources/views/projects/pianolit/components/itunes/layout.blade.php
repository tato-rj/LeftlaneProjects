<div class="rounded bg-light px-3 py-2 mb-3">
  <p class="text-brand border-bottom pb-1"><strong>ITUNES</strong></p>

  @include('projects/pianolit/components/itunes/input', [
    'display' => 'none',
    'type' => 'original-type'])

  {{$slot or null}}

  <a class="add-new-field text-warning cursor-pointer d-block text-center" data-type="itunes">
    <small><i class="fas fa-plus mr-2"></i>Add a new one</small>
  </a>
</div>