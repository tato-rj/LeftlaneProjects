<div class="navbar navbar-light">
  <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header">
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
        <li class="nav-item">
          <a class="nav-link" href="{{route('videouploader.home')}}">Dashboard</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{route('videouploader.tokens.index')}}">Api tokens</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{config('filesystems.disks.gcs.bucketUrl')}}" target="_blank">See GCS bucket</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/horizon" target="_blank">Horizon dashboard</a>
        </li>

        <li class="nav-item">
          @include('projects.videouploader.records.create')
        </li>

        <li class="nav-item">
          @auth
          <a class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" href="">
              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  @csrf
              </form>Logout
          </a>
          @else
          <a class="nav-link" href="{{route('login')}}">Login</a>
          @endauth
        </li>
      </ul>
    </div>
  </div>
</div>