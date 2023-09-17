@extends('projects.videouploader.layouts.app')

@push('header')
<style type="text/css">
.screen-lock-overlay {
  position: fixed;
  top: 0;
  left: 0;
  background: rgba(0,0,0,0.7);
  z-index: 10000;
  width: 100%;
  height: 100vh;
}
</style>
@endpush

@section('content')
@auth
<div class="container mb-4">
    <div class="mb-3">
        <a href="{{route('videouploader.tokens.index')}}">Api tokens</a>
        |
        <a href="{{config('filesystems.disks.gcs.bucketUrl')}}" target="_blank">See GCS bucket</a>
        |
        <a href="/horizon" target="_blank">Horizon dashboard</a>
    </div>

    @include('projects.videouploader.record.create')
</div>

<div class="container">
    <div class="accordion shadow-lg mb-4" id="records-container">
    @foreach($videos as $video)
        @if($video->completed())
        @include('projects.videouploader.record.states.completed')
        @else
        @include('projects.videouploader.record.states.pending')
        @endif
    @endforeach
    </div>

    {{$videos->links()}}
</div>

@include('projects.videouploader.record.overlay')
@endauth
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.0.3/resumable.min.js"></script>
@auth
<script type="text/javascript">
let $progressBar = $('.progress-bar');
let $uploadOverlay = $('#upload-overlay');
let $uploadButton = $('#choose-video');
let $confirmModal = $('#confirm-modal');
let $confirmButton = $('#confirm-button');
let resumable = new Resumable({
    target: '{{ route('videouploader.upload') }}',
    query:{
        _token:'{{ csrf_token() }}',
        secret:'{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}',
        origin: 'local',
        user_id: 1,
        piece_id: 1,
        email: 'test@email.com'
    },
    fileType: ['mp4', 'MOV'],
    maxFileSize: 500000000,
    headers: {
        'Accept' : 'application/json'
    },
    testChunks: false,
    throttleProgressCallbacks: 1,
});

resumable.assignBrowse($uploadButton[0]);

$confirmButton.on('click', function() {
    if (resumable.files.length) {
        $(this).prop('disabled', true);
        resumable.upload();
        $uploadOverlay.show();
    }
});

resumable.on('fileAdded', function (file) {
    showProgress();
    $confirmModal.modal('show');
});

resumable.on('fileProgress', function (file) {
    console.log(file);
    updateProgress(Math.floor(file.progress() * 100));
});

resumable.on('fileSuccess', function (file, response) {
    console.log(file);
    console.log(response);
    setTimeout(function() {
        $progressBar.removeClass('progress-bar-striped progress-bar-animated').addClass('bg-success').text('DONE!');

        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 1000);
});

resumable.on('fileError', function (file, response) {
    console.log(response);
    alert('File uploading error.');
});

$confirmModal.on('hidden.bs.modal', function() {
    resumable.cancel();
});
</script>
<script type="text/javascript">
let progress = $('.progress');
function showProgress() {
    progress.find('.progress-bar').css('width', '0%');
    progress.find('.progress-bar').html('0%');
    progress.find('.progress-bar').removeClass('bg-success');
    progress.show();
}
function updateProgress(value) {
    progress.find('.progress-bar').css('width', `${value}%`);
    progress.find('.progress-bar').html(`${value}%`);
}

function hideProgress() {
    progress.hide();
}
</script>
@endauth
@endpush