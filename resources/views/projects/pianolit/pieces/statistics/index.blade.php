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
function shadeColor(color, percent) {  // deprecated. See below.
    var num = parseInt(color.slice(1),16), amt = Math.round(2.55 * percent), R = (num >> 16) + amt, G = (num >> 8 & 0x00FF) + amt, B = (num & 0x0000FF) + amt;
    return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 + (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255)).toString(16).slice(1);
}
</script>
<script type="text/javascript">
let tagsRecords = JSON.parse($('#tagsChart').attr('data-records'));
let tags = [];
let tags_pieces_count = [];
let tags_backgrounds = [];
let tags_count = tagsRecords.length;

for (var i=0; i < tags_count; i++) {
  tags.push(tagsRecords[i].name);
  tags_pieces_count.push(tagsRecords[i].pieces_count);
  tags_backgrounds.push(shadeColor('#2e5ab9', (80/tags_count)*i));
}
var tagsChartElement = document.getElementById("tagsChart").getContext('2d');
var tagsChart = new Chart(tagsChartElement, {
    type: 'bar',
    data: {
        labels: tags,
        datasets: [{
            data: tags_pieces_count,
            backgroundColor: tags_backgrounds
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
let composers_backgrounds = [];
let composers_count = composersRecords.length;

for (var i=0; i < composers_count; i++) {
  composers.push(composersRecords[i].name);
  composers_pieces_count.push(composersRecords[i].pieces_count);
  composers_backgrounds.push(shadeColor('#2e5ab9', (80/composers_count)*i));
}

var composersChartElement = document.getElementById("composersChart").getContext('2d');
var composersChart = new Chart(composersChartElement, {
    type: 'bar',
    data: {
        labels: composers,
        datasets: [{
            data: composers_pieces_count,
            backgroundColor: composers_backgrounds
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
let rec = [];
let rec_pieces_count = [];

for (var i=0; i < Object.keys(recRecords).length; i++) {
  rec.push(i + ' audio');
  rec_pieces_count.push(recRecords[i].count);
}
console.log();
var recChartElement = document.getElementById("recChart").getContext('2d');
var recChart = new Chart(recChartElement,{
    type: 'pie',
    data: {
        labels: rec,
        datasets: [{
            data: rec_pieces_count,
            backgroundColor: ['#34b887', '#fec45a', '#ff5f6c']
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
