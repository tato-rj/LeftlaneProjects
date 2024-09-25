@extends('projects.videouploader.layouts.app')

@push('header')
@endpush

@section('content')

@include('projects.videouploader.videos.create')

<div id="videos-container" class="text-left">
	@include('projects.videouploader.videos.results')
</div>

@endsection

@push('scripts')
@endpush