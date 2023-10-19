@if($video->isRemote())
<div class="col-12 col-sm-auto m-1">
<form method="POST" action="{{route('videouploader.webhook.resend', $video)}}">
  @csrf
  <button type="submit" class="btn btn-warning btn-sm w-100">RESEND WEBHOOK</button>
</form>
</div>
@endif