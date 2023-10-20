@extends('projects.videouploader.layouts.app')

@section('content')
<section>
	@include('projects.videouploader.system.folder', [
		'folder' => $chunks,
		'name' => 'Chunks'])

	@include('projects.videouploader.system.folder', [
		'folder' => $temporary,
		'name' => 'Temporary'])
</section>
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush