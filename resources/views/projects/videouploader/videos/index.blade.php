@extends('projects.videouploader.layouts.app')

@push('header')
@endpush

@section('content')

@include('projects.videouploader.videos.create')

@searchbar

<div id="videos-container">
	<div class="container mb-4">Total of <strong>{{bytesToGb(\App\Projects\VideoUploader\Video::sum('original_size'))}}</strong></div>
	@include('projects.videouploader.videos.results')
</div>

@endsection

@push('scripts')
<script type="text/javascript">
let debounceTimer;

$('input#table-search').on('keyup', function() {
    clearTimeout(debounceTimer);

    let input = $(this).val();
    debounceTimer = setTimeout(function() {
        let url = updateUrl('input', input);

        // axios.get(url)
        //     .then(function(response) {
        //         $('#results-container').html(response.data);

        //         if (input.length > 1)
        //             $('.highlight-match').mark(input);
        //     })
        //     .catch(function(error) {
        //         log(error);
        //     });
    }, 300);
});

function updateUrl(key, value) {
    var url = new URL(window.location.href);
    url.searchParams.set(key, value);
    return url;
}
</script>
@endpush