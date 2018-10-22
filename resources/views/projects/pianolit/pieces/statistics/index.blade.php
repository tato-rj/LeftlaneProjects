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
    <div class="row my-5 mx-2">
      <canvas id="composersChart" class="w-100" height="300" data-records="{{$composersStats}}"></canvas>
    </div>
    <div class="row my-5 mx-2">
        <div class="col-3">
          <canvas id="levelsChart" height="300" data-records="{{$levelsStats}}"></canvas>
      </div>
    </div>
  </div>
</div>

@component('projects/pianolit/components/modals/results', ['title' => 'We found the following pieces'])
@endcomponent

@endsection

@section('scripts')
<script type="text/javascript">
let tagsRecords = JSON.parse($('#tagsChart').attr('data-records'));
let tags = [];
let tags_pieces_count = [];

for (var i=0; i < tagsRecords.length; i++) {
  tags.push(tagsRecords[i].name);
  tags_pieces_count.push(tagsRecords[i].pieces_count);
}
var tagsChartElement = document.getElementById("tagsChart").getContext('2d');
var tagsChart = new Chart(tagsChartElement, {
    type: 'bar',
    data: {
        labels: tags,
        datasets: [{
            data: tags_pieces_count,
            backgroundColor: '#2e5ab9'
        }]
    },
    options: {
        legend: {
          display: false
        },
        tooltips: {
          enabled: false
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
        },
        events: ['click', 'hover'],
        onClick: function(element, item) {
            let tag = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/tags/"+tag+"/pieces", function(data, status){
            
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>

<script type="text/javascript">
let composersRecords = JSON.parse($('#composersChart').attr('data-records'));
let composers = [];
let composers_pieces_count = [];

for (var i=0; i < composersRecords.length; i++) {
  composers.push(composersRecords[i].name);
  composers_pieces_count.push(composersRecords[i].pieces_count);
}
var composersChartElement = document.getElementById("composersChart").getContext('2d');
var composersChart = new Chart(composersChartElement, {
    type: 'bar',
    data: {
        labels: composers,
        datasets: [{
            data: composers_pieces_count,
            backgroundColor: '#2e5ab9'
        }]
    },
    options: {
        legend: {
          display: false
        },
        tooltips: {
          enabled: false
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
        },
        layout: {
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        events: ['click', 'hover'],
        onClick: function(element, item) {
            let composer = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/composers/"+composer+"/pieces", function(data, status){
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>
<script type="text/javascript">
let levelsRecords = JSON.parse($('#levelsChart').attr('data-records'));
let levels = [];
let levels_pieces_count = [];

for (var i=0; i < levelsRecords.length; i++) {
  levels.push(levelsRecords[i].name);
  levels_pieces_count.push(levelsRecords[i].pieces_count);
}

var levelsChartElement = document.getElementById("levelsChart").getContext('2d');
var levelsChart = new Chart(levelsChartElement,{
    type: 'pie',
    data: {
        labels: levels,
        datasets: [{
            data: levels_pieces_count,
            backgroundColor: ['#2d995b', '#f2da00', '#d9700a', '#c51f1a']
        }]
    },
    options: {
        legend: {
          // display: false
        },
        tooltips: {
          // enabled: false
        },
        events: ["mousemove", "mouseout", "click"],
        onClick: function(element, item) {
            let level = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/tags/"+level+"/pieces", function(data, status){
            
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>
@endsection
