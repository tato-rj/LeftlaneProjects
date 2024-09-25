<nav>
  <ol class="breadcrumb">
    @foreach($trail as $page)
    <li class="breadcrumb-item"><a href="{{$page['url']}}">{{$page['name']}}</a></li>
    @endforeach
    <li class="breadcrumb-item active" aria-current="page">{{$current}}</li>
  </ol>
</nav>