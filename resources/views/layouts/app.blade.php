<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Zuru Trivia')</title>
  @vite(['resources/sass/app.scss','resources/js/app.js'])
  @stack('styles')
</head>
<body>

  {{-- NAVBAR --}}
  <nav class="navbar navbar-expand-lg navbar-light bg-transparent navbar-zuru fixed-top border-bottom-0">
    <div class="container">
      <a class="navbar-brand" href="{{ url('/') }}">Zuru Trivia Quiz</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="mainNav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item"><a class="nav-link" href="/about">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="/quiz">Quiz</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ url('/assessment') }}">Assessment</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ url('/leaderboard') }}">Leaderboard</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ url('/profile') }}">Profile</a></li>
          <li class="nav-item ms-lg-2">
            <a href="{{ url('/login') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Login</a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/register') }}" class="btn btn-sm btn-primary ms-lg-2 rounded-pill">Register Now</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  {{-- MAIN CONTENT --}}
  <main>
    @yield('content')
  </main>

  {{-- FOOTER --}}
  <footer class="py-4 footer-zuru">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2 small">
      <div>© {{ date('Y') }} Zuru. All rights reserved.</div>
      <div class="d-flex gap-3">
        <a href="#" class="text-decoration-none">Privacy</a>
        <a href="#" class="text-decoration-none">Terms</a>
        <a href="#" class="text-decoration-none">Support</a>
      </div>
    </div>
  </footer>

  @stack('scripts')
</body>
</html>
