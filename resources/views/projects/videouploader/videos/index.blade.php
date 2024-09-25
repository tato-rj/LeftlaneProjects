@extends('projects.videouploader.layouts.app')

@push('header')
@endpush

@section('content')

@include('projects.videouploader.videos.create')

@searchbar

<div id="videos-container">
	@include('projects.videouploader.videos.results')
</div>

@endsection

@push('scripts')
@endpush