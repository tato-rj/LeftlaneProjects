<div class="container mb-4">
	<div class="row">
		<div class="{{$layout ?? 'col-lg-4 col-md-6 col-10 mx-auto'}}">
			<div class="border-bottom w-100 d-flex align-items-center">
				@fa(['icon' => 'search', 'mr' => 0])
				<input id="{{$id ?? 'table-search'}}" type="text" autocomplete="off" name="{{$name ?? 'search'}}" class="form-control border-0 m-0" placeholder="Search here...">
			</div>
		</div>
	</div>
</div>