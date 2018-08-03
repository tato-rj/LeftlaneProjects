@extends('projects/quickreads/layouts/app')

@section('content')

<div class="content-wrapper pb-4">
  <div class="container-fluid">
  @component('projects/quickreads/components/breadcrumb', ['description' => 'Data from users behavior'])
    Statistics
  @endcomponent
    <div class="row mt-5">
      <div class="col-12 mx-auto mb-4">
        <h4 class="mb-4 text-center"><strong>Flow of users over time</strong></h4>
        @include('projects/quickreads/statistics/users')
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
        <h4 class="mb-4 text-center"><strong>Top stories</strong></h4>
        @component('projects/quickreads/statistics/list', ['left' => 'Title', 'right' => '# of downloads'])
          @foreach($topStories as $id => $count)
          <li class="d-flex justify-content-between">
            @if(! \App\Story::find($id))
            {{dd($id)}}
            @endif
            <span>{{\App\Story::find($id)->title}}</span>
            <span>{{$count}}</span>
          </li>
          @endforeach
        @endcomponent
      </div>

      <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
        <h4 class="mb-4 text-center"><strong>Most active users</strong></h4>
        @component('projects/quickreads/statistics/list', ['left' => 'Name', 'right' => '# of stories'])
          @foreach($activeUsers as $id => $count)
          <li class="d-flex justify-content-between">
            @if(! \App\User::find($id))
            {{dd($id)}}
            @endif
            <span>{{\App\User::find($id)->fullName}}</span>
            <span>{{$count}}</span>
          </li>
          @endforeach
        @endcomponent
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
 $('canvas').attr('height', 225);
}
</script>

<script type="text/javascript">
$('.select-btn-group .btn').on('click', function() {
  $(this).siblings().removeClass('btn-blue').addClass('btn-light');
  $(this).removeClass('btn-light').addClass('btn-blue');
});

createChart = function(type) {
  var chart = document.getElementById(type+"-chart");
  var ctx = chart.getContext('2d');
  var activeRecords = JSON.parse(chart.getAttribute('data-records'));

  var activeData = [];
  var fields = [];

  for (var i = 0;i < activeRecords.length; i++) {
    if (type == 'day') {
        fields.push(activeRecords[i].month+" "+activeRecords[i].day);
    } else if (type == 'month') {
      fields.push(activeRecords[i].month);
    } else {
      fields.push(activeRecords[i].year);
    }

    activeData.push(activeRecords[i].count);
  }

  console.log(activeRecords.length);

  var myChart = new Chart(ctx, {
      type: 'line',
      data: {
          labels: fields,
          datasets: [
          {
              label: 'New users',
              data: activeData,
              pointBackgroundColor: 'rgba(255, 99, 132, 0.2)',
              pointBorderColor: 'rgba(255, 99, 132, 1)',
              backgroundColor: 'rgba(255, 99, 132, 0.2)',
              borderColor: 'rgba(255,99,132,1)',
              borderWidth: 1
          }
      ]},
      options: {
          scales: {
              yAxes: [{
                  ticks: {
                      min: 0,
                      beginAtZero: true,
                      callback: function(value, index, values) {
                          if (Math.floor(value) === value) {
                              return value;
                          }
                      }
                  }
              }]
          }
      }
  }); 
}

createChart('day');
createChart('month');
createChart('year');
</script>
@endsection
