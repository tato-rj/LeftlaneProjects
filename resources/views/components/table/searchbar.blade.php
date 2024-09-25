<div class="container mb-4">
	<div class="row">
		<div class="
		@if(subdomain('admin'))
		{{$layout ?? 'col-lg-3 col-md-4 col-8 ml-auto'}}
		@else
		{{$layout ?? 'col-lg-4 col-md-6 col-10 mx-auto'}}
		@endif
		">
			<div class="border-bottom w-100 d-flex align-items-center">
				@fa(['icon' => 'search', 'mr' => 0])
				<input id="{{$id ?? 'table-search'}}" type="text" autocomplete="off" name="{{$name ?? 'search'}}" class="form-control border-0 m-0" placeholder="Search here...">
			</div>
		</div>
	</div>
</div>