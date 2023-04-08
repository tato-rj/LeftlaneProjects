@extends('projects/quickreads/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('projects/quickreads/components/breadcrumb')
    Stories
    @slot('description')Add a new story 
    @endslot
  @endcomponent
    <form method="POST" action="/quickreads/stories" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-4" enctype="multipart/form-data">
      {{csrf_field()}}
      {{-- Title --}}
      <div class="form-group">
        <input type="text" class="form-control" name="title" placeholder="Title" value="{{ old('title') }}" required>
      </div>
      {{-- Summary --}}
      <div class="form-group">
        <textarea class="form-control" rows="4" name="summary" placeholder="Summary" required>{{ old('summary') }}</textarea>
      </div>
      {{-- Content --}}
      <div class="form-group">
        <textarea class="form-control" rows="12" name="text" placeholder="Content" required>{{ old('text') }}</textarea>
      </div>
      <div class="row">
        <div class="col-6">
          <div class="form-group">
            {{-- Reading Time --}}
            <input type="text" class="form-control" name="time" maxlength="3" placeholder="Reading time" value="{{ old('time') }}" required>
          </div>
          <div class="form-group">
            {{-- Author --}}
            <select name="author_id" class="form-control" required>
              <option selected disabled>Author</option>
              @foreach($authors as $author)
                <option value="{{$author->id}}" {{ (old('author_id') == $author->id) ? 'selected' : '' }}>{{$author->name}}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            {{-- Category --}}
            <select name="category_id" class="form-control" required>
              <option selected disabled>Category</option>
              @foreach($categories as $category)
                <option value="{{$category->id}}" {{ (old('category_id') == $category->id) ? 'selected' : '' }}>{{$category->category}}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            {{-- Cost --}}
            <select name="cost" class="form-control" required>
              <option selected disabled>Cost</option>
              <option value="Free" {{ (old('cost') == 'free') ? 'selected' : '' }}>Free</option>
              <option value="1" {{ (old('cost') == '1') ? 'selected' : '' }}>1 Coin</option>
              <option value="2" {{ (old('cost') == '2') ? 'selected' : '' }}>2 Coins</option>
              <option value="5" {{ (old('cost') == '5') ? 'selected' : '' }}>5 Coins</option>
            </select>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_classic" id="is_classic">
            <label class="form-check-label" for="is_classic">
              Is this a classic?
            </label>
          </div>
        </div>

        <div class="col-6">
          <div id="upload-box" class="card">
            <input type="file" id="image" name="image" style="display:none;" />
            <img class="card-img-top" id="cover-img" src="{{ asset('images/no-image.png') }}" alt="Not an image">
            <div class="card-body text-center">
              <button type="button" id="upload-button" class="btn btn-warning"><i class="fa fa-cloud-upload-alt mr-1" aria-hidden="true"></i>Upload</button>
            </div>
          </div>         
        </div>
      </div>
      <div class="text-center my-5">
        <button type="submit" class="btn btn-block btn-default">Add Story</button>
      </div>
    </form>   
  </div>
</div>

@endsection

@section('scripts')
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
