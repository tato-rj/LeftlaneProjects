<div class="d-flex justify-content-between align-items-center p-2 mt-1 bg-light">
	<div class="d-flex align-items-center">
		<p class="m-0 mr-2" style="color: #2093a5"><strong>{{$story->title}}</strong></p>
		<div class="d-flex align-items-center">
			@for($i=1; $i<=5; $i++)
				<i class="fa fa-star
					@if($i <= $user->ratingsFor($story->id))
						text-warning
					@else
						text-secondary
					@endif
				 mx-1"></i>
			@endfor
		</div>
	</div>
	<p class="m-0 text-muted">{{$story->created_at->toFormattedDateString()}}</p>
</div>