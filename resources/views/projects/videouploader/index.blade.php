@extends('projects.videouploader.layouts.app')

@push('header')
@endpush

@section('content')

@include('projects.videouploader.records.list')

@endsection

@push('scripts')
@endpush