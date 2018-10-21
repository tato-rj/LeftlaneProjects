@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Statistics',
    'description' => 'See how are the pieces and their tags being used across the database'])
    
    <div class="row my-5 mx-2">
      <canvas id="tagsChart" class="w-100" height="300" data-records="{{$tagStats}}"></canvas>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
function getRandomColor() {
  var letters = '0123456789ABCDEF';
  var color = '#';
  for (var i = 0; i < 6; i++) {
    color += letters[Math.floor(Math.random() * 16)];
  }
  return color;
}

let records = JSON.parse($('#tagsChart').attr('data-records'));
let colors = [];
let tags = [];
let pieces_count = [];

for (var i=0; i < records.length; i++) {
  colors.push(getRandomColor());
  tags.push(records[i].name);
  pieces_count.push(records[i].pieces_count);
}

var ctx = document.getElementById("tagsChart").getContext('2d');
var tagsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: tags,
        datasets: [{
            data: pieces_count,
            backgroundColor: '#2e5ab9'
        }]
    },
    options: {
        legend: {
          display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        }
    }
});
</script>
@endsection
