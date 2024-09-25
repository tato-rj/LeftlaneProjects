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
@endpush