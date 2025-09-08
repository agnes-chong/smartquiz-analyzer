@extends('layouts.app')
@section('title', 'Zuru Trivia Quiz')

@section('content')

{{-- HERO SECTION --}}
<section class="hero-zuru">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-12 col-lg-6">
        <h1 class="hero-title">PLAYING TOGETHER<br>WITH FRIENDS!</h1>
        <p class="hero-muted">
          Experience the thrill of real-time multiplayer quizzes. Answer questions together and watch the leaderboard update instantly, adding to the excitement.
        </p>
        <a class="btn btn-green" href="#">Invite your friends!</a>
      </div>
      <div class="col-12 col-lg-6 mt-4 mt-lg-0">
        <div class="hero-image-placeholder"></div>
      </div>
    </div>
  </div>
</section>

{{-- PROFILE + RECENTLY --}}
<section class="profile-recent-section v2">
  <div class="container">
    <div class="row g-4 align-items-start">

      <div class="col-12 col-lg-6 position-relative">
        <div class="profile-card-v2">
          <div class="profile-row">
            <div class="avatar-lg"></div>
            <div class="profile-main">
              <div class="name">Ahmad Rahmanu</div>
              <div class="chips">
                <span class="chip chip-level"><i class="fa-solid fa-star"></i> Level 26</span>
                <span class="chip chip-points"><i class="fa-solid fa-coins"></i> Points 2536</span>
              </div>
            </div>
          </div>
          <div class="about-caption">About Me</div>
          <button class="btn-view-green" type="button">View Profile</button>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="recent-header">
          <h2 class="section-title">Recently</h2>
          <button class="btn-circle-ghost" aria-label="Open" title="Open">
            <svg class="arrow-icon" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M7 17 L17 7 M10 7 H17 V14" />
            </svg>
          </button>
        </div>

        <div class="recent-list-v2">
          @foreach ([
            ['cat'=>'Astronomy','q'=>'Which planet is known as the "Red Planet"?','score'=>57,'tone'=>'yellow'],
            ['cat'=>'Astronomy','q'=>'Which scientist discovered the law of gravity?','score'=>35,'tone'=>'red'],
            ['cat'=>'Astronomy','q'=>'Which planet is known as the "Red Planet"?','score'=>60,'tone'=>'green'],
          ] as $r)
          <div class="recent-item-v2">
            <div class="thumb-sm"></div>
            <div class="meta">
              <div class="cat">{{ $r['cat'] }}</div>
              <div class="q">{{ $r['q'] }}</div>
            </div>
            <div class="score-badge score-{{ $r['tone'] }}">{{ $r['score'] }}%</div>
          </div>
          @endforeach
        </div>
      </div>

    </div>
  </div>
</section>

{{-- QUIZ CATEGORY & GRID --}}
<section class="quiz-section">
  <div class="container">

    <div class="quizcat-toolbar">
      <h2 class="section-title mb-0">Quiz Category</h2>

      <div class="search-input has-filter">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="quiz-search" type="text" placeholder="Search quiz...">
        <button class="filter-btn" type="button" aria-label="Filter">
          <i class="fa-solid fa-sliders"></i>
        </button>
      </div>
    </div>

    @php
      $cats = [
        ['name' => 'Art'],
        ['name' => 'History', 'center' => true, 'trending' => true, 'count' => 215],
        ['name' => 'Science'],
        ['name' => 'World'],
        ['name' => 'Technology'],
      ];
    @endphp

    <div class="category-carousel" id="categoryCarousel">
      @foreach($cats as $idx => $c)
        <div class="cat-card {{ ($c['center'] ?? false) ? 'is-center' : '' }}" data-index="{{ $idx }}">
          <div class="thumb"></div>

          @if(!empty($c['center']))
            <div class="center-meta">
              @if(!empty($c['trending'])) <span class="chip-trending">Trending</span> @endif
              <div class="title" id="activeCategoryTitle">{{ $c['name'] }}</div>
              <div class="nav-row">
                <button class="nav-btn" type="button" aria-label="Prev" data-action="prev"><i class="fa-solid fa-angle-left"></i></button>
                <div class="muted"><span id="activeCategoryCount">{{ $c['count'] ?? 0 }}</span> Quiz Available</div>
                <button class="nav-btn" type="button" aria-label="Next" data-action="next"><i class="fa-solid fa-angle-right"></i></button>
              </div>
              <button class="btn-selected" type="button">Selected</button>
            </div>
          @else
            <div class="name">{{ $c['name'] }}</div>
          @endif
        </div>
      @endforeach
    </div>

    <div class="quiz-grid-header v2">
      <h2 class="section-title"><span id="quizCount">215</span> Quiz</h2>
      <div class="pill-filters" id="pillFilters">
        <button class="pill is-active" data-filter="trending">Trending</button>
        <button class="pill" data-filter="newest">Newest</button>
        <button class="pill" data-filter="oldest">Oldest</button>
      </div>
    </div>

    <div class="row g-4 quiz-grid-v2" id="quizGrid">
      @foreach ([
        ['title' => 'Ancient Civilizations Quiz', 'desc' => 'Description', 'tag'=>'trending'],
        ['title' => 'World War II Quiz', 'desc' => 'Description', 'tag'=>'newest'],
        ['title' => 'The Renaissance Quiz', 'desc' => 'Description', 'tag'=>'trending'],
        ['title' => 'Medieval Europe Quiz', 'desc' => 'Description', 'tag'=>'oldest'],
        ['title' => 'Ancient Rome Quiz', 'desc' => 'Description', 'tag'=>'newest'],
        ['title' => 'Ancient Civilizations Quiz', 'desc' => 'Description', 'tag'=>'oldest'],
      ] as $quiz)
      <div class="col-12 col-lg-6 quiz-item" data-title="{{ Str::lower($quiz['title']) }}" data-tag="{{ $quiz['tag'] }}">
        <div class="quiz-card-v2">
          <div class="thumb-wrap">
            <div class="thumb"></div>
            <button class="btn-play-fab" type="button">Play Now</button>
          </div>
          <div class="info">
            <span class="chip-muted">{{ ucfirst($quiz['tag']) }}</span>
            <div class="title">{{ $quiz['title'] }}</div>
            <div class="desc">{{ $quiz['desc'] }}</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="pager-v2">
      <button class="pager-arrow" aria-label="Prev"><i class="fa-solid fa-angle-left"></i></button>
      <div class="dots"><span class="dot"></span><span class="dot is-active"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
      <button class="pager-arrow" aria-label="Next"><i class="fa-solid fa-angle-right"></i></button>
      <div class="see-all"><a href="#" class="see-all-text">See All</a></div>
    </div>

  </div>
</section>

{{-- RECOMMENDATIONS --}}
<section class="recommendation-section">
  <div class="container">
    <div class="recommendation-header">
      <p class="subtitle">Recommendation for you</p>
      <h2 class="title">Explore a wide range of trivia categories that challenge your knowledge and keep you entertained!</h2>
    </div>
    <div class="row g-4">
      @foreach ([
        ['title' => 'Nature & Animal', 'desc' => 'Explore the breathtaking diversity of life on Earth...', 'img' => ''],
        ['title' => 'Science & Technology', 'desc' => 'Dive into the dynamic world of Science & Technology...', 'img' => ''],
        ['title' => 'World History', 'desc' => 'Uncover the events and figures that have shaped our world...', 'img' => '']
      ] as $item)
      <div class="col-12 col-lg-4">
        <div class="recommendation-card" style="background-image: url('{{ $item['img'] }}')">
          <div class="overlay"></div>
          <div class="content">
            <h3 class="title">{{ $item['title'] }}</h3>
            <p class="desc">{{ $item['desc'] }}</p>
            <button class="btn-enter">Enter</button>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="footer-links">
      <a href="#">About Us</a><a href="#">Quiz</a><a href="#">Assessment</a><a href="#">Leaderboard</a><a href="#">Profile</a>
    </div>
  </div>
</footer>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
