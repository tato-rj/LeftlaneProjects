@if(! $rows->count())  
  @include('components.empty')
@else  
<section class="container {{$margin ?? 'mb-5'}}">
  @include('components.table.table')
</section>
@endif