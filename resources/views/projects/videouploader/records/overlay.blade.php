@php($sentences = ['Tuning the piano', 'Arranging rows of comfy seats', 'Adjusting the bench', 'Warming up fingers', 'Greeting the eager audience', 'Dimming the lights', 'Wrapping up'])

<div style="display: none" id="upload-overlay" class="screen-lock-overlay">
    <div class="h-100 text-white d-flex text-center justify-content-center align-items-center">
        <div class="px-4">
            <div class="progress mb-3 animate__animated shadow rounded-pill" style="height: 25px">
                <div class="progress-bar progress-bar-stripedx progress-bar-animated fw-bold rounded-pill" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%"></div>
            </div>
            <div class="text-center">
                <div class="fw-bold" data-sentences="{{json_encode($sentences)}}" id="loading-text">{{$sentences[0]}}</div>
                <div style="opacity: .6">Please keep this browser tab open until uploading completes.</div>
            </div>
        </div>
    </div>
</div>