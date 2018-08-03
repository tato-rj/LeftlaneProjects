<div id="carouselRecords" class="carousel carousel-fade">
  <div class="select-btn-group btn-group btn-group-sm mb-4">
    <button data-target="#carouselRecords" data-slide-to="0" class="btn btn-blue">Daily</button>
    <button data-target="#carouselRecords" data-slide-to="1"  class="btn btn-light">Monthly</button>
    <button data-target="#carouselRecords" data-slide-to="2"  class="btn btn-light">Yearly</button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <canvas id="day-chart" 
          data-records="{{json_encode($dailySignups)}}" height="100"></canvas>
    </div>

    <div class="carousel-item">
      <canvas id="month-chart" 
          data-records="{{json_encode($monthlySignups)}}" height="100"></canvas>
    </div>

    <div class="carousel-item">
      <canvas id="year-chart" 
          data-records="{{json_encode($yearlySignups)}}" height="100"></canvas>
    </div>
  </div>
</div>