@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Statistics',
    'description' => 'See how are the pieces and their tags being used across the database'])

    <div class="row"> 
      @include('projects.pianolit.pieces.statistics.row', [
        'title' => 'Tags',
        'id' => 'tagsChart',
        'col' => '12',
        'data' => $tagStats])
    </div>

    <div class="row"> 
      @include('projects.pianolit.pieces.statistics.row', [
        'title' => 'Composers',
        'id' => 'composersChart',
        'col' => '12',
        'data' => $composersStats])
    </div>

    <div class="row"> 
      @include('projects.pianolit.pieces.statistics.row', [
        'title' => 'Levels',
        'id' => 'levelsChart',
        'col' => '4',
        'data' => $levelsStats])

      @include('projects.pianolit.pieces.statistics.row', [
        'title' => 'Recordings count',
        'id' => 'recChart',
        'col' => '4',
        'data' => $recStats])
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
let tags_count = tagsRecords.length;

for (var i=0; i < tags_count; i++) {
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
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 2
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        },
        events: ["mousemove", "mouseout", "click"],
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
let composers_count = composersRecords.length;

for (var i=0; i < composers_count; i++) {
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
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 2
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
        events: ["mousemove", "mouseout", "click"],
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
            backgroundColor: ['#34b887', '#fec45a', '#ff5f6c', '#aa35e0']
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
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
<script type="text/javascript">
let recRecords = JSON.parse($('#recChart').attr('data-records'));
let rec_pieces_count = [0,0,0,0];

for (var i=0; i < Object.keys(recRecords).length; i++) {
    let index = Object.keys(recRecords)[i];
    rec_pieces_count[index] = recRecords[index].count;
}

var recChartElement = document.getElementById("recChart").getContext('2d');
var recChart = new Chart(recChartElement,{
    type: 'pie',
    data: {
        labels: ['0 recorgings', '1 recording','2 recordings','3 recordings'],
        datasets: [{
            data: rec_pieces_count,
            backgroundColor: ['#34b887', '#fec45a', '#ff5f6c', '#aa35e0']
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        },
        events: ["mousemove", "mouseout", "click"],
        // onClick: function(element, item) {
        //     let level = item[0]._view.label;
        //     let $modal = $('#results-modal');
        //     $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
        //     $modal.modal('show');
        //   $.get("/piano-lit/tags/"+level+"/pieces", function(data, status){
            
        //     $modal.find('.modal-body').html(data);
        //   });
        // }
    }
});
</script>
@endsection
