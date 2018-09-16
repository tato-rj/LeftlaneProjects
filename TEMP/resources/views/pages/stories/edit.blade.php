@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Stories
    @slot('description')Edit a story 
    @endslot
  @endcomponent

    <form id="select-form" action="/quickreads/stories/edit/" class="col-lg-8 col-sm-10 col-xs-12 mx-auto my-4" enctype="multipart/form-data">
      <div class="form-group">
        {{-- Stories --}}
        <select class="form-control" onchange="select()">
          <option selected disabled>Choose a story</option>
          @foreach($stories as $select_story)
            <option data-slug="{{$select_story->slug}}" value="{{$select_story->id}}">{{$select_story->title}}</option>
          @endforeach
        </select>        
      </div>
    </form>

    @if(isset($story))

    <form method="POST" action="/quickreads/stories/{{$story->slug}}" class="col-lg-8 col-sm-10 col-xs-12 mx-auto my-5" enctype="multipart/form-data">
      {{csrf_field()}}
      {{method_field('PATCH')}}
      {{-- Title --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Title</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="title" placeholder="Title" value="{{ $story->title }}" required>
        </div>
      </div>
      {{-- Summary --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Summary</label>
        <div class="col-sm-10">
          <textarea class="form-control" rows="4" name="summary" placeholder="Summary" required>{{ $story->summary }}</textarea>
        </div>
      </div>
      {{-- Content --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Content</label>
        <div class="col-sm-10">
          <textarea class="form-control" rows="12" name="text" placeholder="Content" required>{{ $story->text }}</textarea>
        </div>
      </div>
      <div class="row">
        <div class="col-6">
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Reading Time</label>
            {{-- Reading Time --}}
            <div class="col-sm-8">
              <input type="text" class="form-control" name="time" maxlength="3" placeholder="Reading time" value="{{ $story->time }}" required>
            </div>            
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Author</label>
            {{-- Authors --}}
            <div class="col-sm-8">
              <select name="author_id" class="form-control" required>
                <option selected disabled>Author</option>
                @foreach($authors as $author)
                  <option value="{{$author->id}}" {{ ($story->author_id == $author->id) ? 'selected' : '' }}>{{$author->name}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Category</label>
            {{-- Categories --}}
            <div class="col-sm-8">
              <select name="category_id" class="form-control" required>
                <option selected disabled>Category</option>
                @foreach($categories as $category)
                  <option value="{{$category->id}}" {{ ($story->category_id == $category->id) ? 'selected' : '' }}>{{$category->category}}</option>
                @endforeach
              </select>              
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Cost</label>
            {{-- Cost --}}
            <div class="col-sm-8">
              <select name="cost" class="form-control" required>
                <option selected disabled>Cost</option>
                <option value="Free" {{ ($story->cost == 'Free') ? 'selected' : '' }}>Free</option>
                <option value="1" {{ ($story->cost == '1') ? 'selected' : '' }}>1 Coin</option>
                <option value="2" {{ ($story->cost == '2') ? 'selected' : '' }}>2 Coins</option>
                <option value="5" {{ ($story->cost == '5') ? 'selected' : '' }}>5 Coins</option>
              </select>              
            </div>
          </div>
        </div>
        <div class="col-6">
          <div id="upload-box" class="card">
            <input type="file" id="image" name="image" style="display:none;" />
            <img class="card-img-top" id="cover-img" src="{{ asset($story->image()) }}" alt="Not an image">
            <div class="card-body text-center">
              <button type="button" id="upload-button" class="btn btn-warning"><i class="fa fa-cloud-upload mr-1" aria-hidden="true"></i>Upload</button>
            </div>
          </div>         
        </div>
      </div>
      <div class="text-center my-5">
        <button type="submit" class="btn btn-block btn-default">Edit Story</button>
      </div>
    </form>
    @endif
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
function select()
{
  $form = $('#select-form');
  $slug = $('#select-form select option:selected').attr('data-slug');
  window.location = $form.attr('action')+$slug;
}
</script>

<script type="text/javascript">
$('#upload-button').on('click', function() {
  $('input#image').click();
});
function readURL(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#cover-img').attr('src', e.target.result);
    }
    reader.readAsDataURL(input.files[0]);
  }
}

$("input#image").change(function() {
  readURL(this);
});
</script>
@endsection
