@extends('layouts.app')

@section('content')
<style>
/* Hide Laravel navbar just for this page */
.navbar, header.navbar, nav.navbar, .navbar-expand-md { display:none!important; }
body { padding-top:0!important; }
</style>

<div class="ed">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
  /* =============== Scope & Tokens =============== */
  .ed{
    --ink:#0b1220; --muted:#60697b; --border:#e6e8ee; --surface:#ffffff;
    --bg:#fbfbfd; --chip:#f3f4f7;
    --brandA:#8b5cf6; --brandB:#6366f1; --accent:#111827;
    --yellow:#ffe799; --mint:#cff7dc; --rose:#ffdfe7;
    --shadow:0 10px 24px rgba(12,17,29,.08);
    font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,Helvetica,Arial;
    color:var(--ink); background:var(--bg);
    -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
  }
  .ed *{ box-sizing:border-box }
  .wrap{ max-width:1220px; margin:0 auto; padding:0 20px }

  /* neutralize bootstrap within scope */
  .ed a, .ed button, .ed input { font: inherit; color: inherit; text-decoration:none }
  .ed input { appearance:none; -webkit-appearance:none; border-radius:10px; }

  /* =============== Header =============== */
  .top{ display:flex; align-items:center; gap:18px; justify-content:space-between; padding:16px 0 }
  .brand{ display:flex; align-items:center; gap:.6rem; font-weight:900 }
  .brand-dot{ width:10px;height:10px;border-radius:50%;background:var(--brandA) }
  .nav{ display:flex; gap:18px; color:#6b7280; font-weight:600 }
  .nav a{ padding:8px 12px; border-radius:999px; background:transparent }
  .nav a:hover{ background:var(--chip); color:var(--ink) }
  .auth{ display:flex; gap:10px }
  .chip{ padding:8px 14px; border-radius:999px; border:1px solid var(--border); background:#fff; font-weight:700 }
  .signup{ background:linear-gradient(90deg,var(--brandA),var(--brandB)); color:#fff!important; border:none; box-shadow:0 6px 16px rgba(99,102,241,.3); }

  /* search */
  .search{ flex:1; display:flex; justify-content:center }
  .search .box{ display:flex; align-items:center; gap:10px; background:#fff; border:1px solid var(--border); border-radius:999px; padding:10px 14px; width:520px; }
  .search input{ border:0; outline:0; flex:1; background:transparent; }

  /* =============== Hero =============== */
  .hero{ padding:12px 0 32px; }
  .hero-grid{ display:grid; grid-template-columns:1.1fr .9fr; gap:28px; align-items:center }
  .h-title{ font-size:44px; line-height:1.1; letter-spacing:-.4px; font-weight:900; }
  .h-sub{ color:var(--muted); margin-top:10px }
  .trial{ margin-top:18px; display:flex; gap:10px }
  .trial input{ background:#fff; border:1px solid var(--border); padding:12px 14px; min-width:260px }
  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border-radius:999px; font-weight:800; background:#111827; color:#fff; }
  .btn.grad{ background:linear-gradient(90deg,var(--brandA),var(--brandB)); box-shadow:0 6px 16px rgba(99,102,241,.3) }

  .stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-top:22px; }
  .stat{ text-align:center; padding:12px; border-radius:14px; background:#fff; border:1px solid var(--border) }
  .stat b{ font-size:20px }
  .ill{ position:relative; border:1px solid var(--border); background:#fff; border-radius:28px; padding:16px; box-shadow:var(--shadow) }
  .ill .badge{ position:absolute; left:-10px; bottom:12px; background:#fff; border:1px solid var(--border); padding:8px 10px; border-radius:12px; font-weight:800; box-shadow:var(--shadow) }
  .face{ width:100%; aspect-ratio:1/1; border-radius:20px; background:linear-gradient(180deg,#ffcfe7,#ffd9a8); border:6px solid #111827; position:relative; overflow:hidden }
  .spark{ position:absolute; right:-10px; top:14px; width:62px; height:62px; border:2px dashed #111827; border-radius:50% }

  /* separator pill */
  .bar{ background:#fff; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:18px 0; margin:28px 0 }

  /* =============== Cards (Courses) =============== */
  .section-title{ font-size:26px; font-weight:900; margin:6px 0 14px }
  .cards{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px }
  .card{ background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 8px 18px rgba(12,17,29,.04) }
  .card .ph{ height:150px; background:#efeefc; display:grid; place-items:center; font-weight:800; color:#7c82a6 }
  .card .ct{ padding:12px }
  .meta{ display:flex; align-items:center; justify-content:space-between; font-size:12px; color:#9097a6 }
  .price{ display:flex; gap:8px; align-items:center; margin-top:8px }
  .price b{ font-size:16px }
  .view-all{ display:flex; justify-content:center; margin:16px 0 6px }
  .view-all .btn{ padding:10px 16px; background:#0f172a; }

  /* badges row */
  .badges{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
  .badge-row{ background:#fff; border:1px solid var(--border); border-radius:14px; padding:12px 14px; display:flex; gap:12px; align-items:flex-start; }
  .dot{ width:34px; height:34px; border-radius:50%; display:grid; place-items:center; font-weight:900 }
  .dot.b1{ background:var(--mint) } .dot.b2{ background:#e9ebff } .dot.b3{ background:#ffe9d0 }

  /* =============== Top Categories =============== */
  .cats{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px }
  .cat{ background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 18px; display:flex; justify-content:space-between; align-items:center; font-weight:800 }
  .cat:nth-child(1){ background:#eef1ff } .cat:nth-child(2){ background:#e9fff1 }
  .cat:nth-child(3){ background:#fff0ef } .cat:nth-child(4){ background:#fff8df }

  /* =============== Mentors (carousel) =============== */
  .carousel{ position:relative }
  .mentor-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; overflow:hidden }
  .mentor{ background:#fff; border:1px solid var(--border); border-radius:16px; padding:14px; text-align:center }
  .avatar{ width:72px; height:72px; border-radius:50%; margin:6px auto 10px; background:linear-gradient(180deg,#dfe7ff,#f6dfff); }
  .arrows{ display:flex; gap:10px; justify-content:center; margin:10px 0 0 }
  .circle{ width:34px; height:34px; border-radius:50%; border:1px solid var(--border); background:#fff; display:grid; place-items:center; font-weight:900 }

  /* =============== Testimonials =============== */
  .testi{ display:grid; grid-template-columns:repeat(3,1fr); gap:14px }
  .quote{ background:#fff; border:1px solid var(--border); border-radius:16px; padding:14px }
  .quote .row{ display:flex; align-items:center; gap:12px; margin-bottom:8px }
  .quote .mini{ width:42px;height:42px;border-radius:50%;background:#f2f4ff }

  /* =============== Blog =============== */
  .blog-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px }
  .blog{ background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden }
  .blog .ph{ height:120px; background:#eaf7ff }
  .blog .ct{ padding:12px; color:var(--muted) }
  .blog h5{ margin:0 0 6px; color:var(--ink); font-size:14px }

  /* =============== Footer =============== */
  .foot{ background:#fff; border-top:1px solid var(--border); margin-top:34px }
  .foot-grid{ display:grid; grid-template-columns:1.1fr 1fr 1fr 1fr 1.2fr; gap:18px; padding:24px 0 }
  .foot h6{ margin:0 0 8px; font-weight:800 }
  .foot a{ color:#6b7280 } .foot a:hover{ color:#0f172a }
  .newsletter{ display:flex; gap:8px; margin-top:8px }
  .newsletter input{ flex:1; background:#fff; border:1px solid var(--border); padding:10px 12px }
  .copyright{ border-top:1px solid var(--border); padding:12px 0; color:#9aa1af; font-size:13px; }

  /* =============== Responsive =============== */
  @media (max-width: 1080px){
    .hero-grid{ grid-template-columns:1fr }
    .cards{ grid-template-columns:repeat(2,1fr) }
    .badges, .cats, .mentor-grid, .testi, .blog-grid{ grid-template-columns:repeat(2,1fr) }
    .foot-grid{ grid-template-columns:1fr 1fr; }
    .search .box{ width:100% }
  }
  @media (max-width: 640px){
    .cards, .badges, .cats, .mentor-grid, .testi, .blog-grid{ grid-template-columns:1fr }
    .stats{ grid-template-columns:repeat(2,1fr) }
  }
  </style>

  <!-- ================= Header ================= -->
  <header class="wrap top">
    <a href="{{ url('/') }}" class="brand">
      <span class="brand-dot"></span><span>Edtech</span>
    </a>

    <nav class="nav">
      <a href="#cats">Categories</a>
      <a href="#courses">Courses</a>
      <a href="#mentors">Mentors</a>
      <a href="#blog">Blog</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="search">
      <div class="box">
        🔎 <input type="text" placeholder="Search any course">
      </div>
    </div>

    <div class="auth">
      <a class="chip" href="{{ route('login') }}">Login</a>
      <a class="chip signup" href="{{ route('register') }}">Sign up</a>
    </div>
  </header>

  <!-- ================= Hero ================= -->
  <section class="wrap hero">
    <div class="hero-grid">
      <div>
        <h1 class="h-title">Learning skills<br>for a better career</h1>
        <p class="h-sub">We believe the world is more beautiful as each person gets more skills and knows how to implement.</p>

        <div class="trial">
          <input type="email" placeholder="Enter your email">
          <a class="btn grad" href="{{ route('register') }}">14 Days Trial</a>
        </div>

        <div class="stats">
          <div class="stat"><b>12k+</b><div>Fresh Graduates</div></div>
          <div class="stat"><b>9+</b><div>Years of Experience</div></div>
          <div class="stat"><b>358+</b><div>Excellence Awards</div></div>
          <div class="stat"><b>47+</b><div>Brand Partners</div></div>
        </div>
      </div>

      <div class="ill">
        <div class="face"></div>
        <div class="spark"></div>
        <div class="badge">99.24% ★★★★★<br><small>5.2k+ Reviews</small></div>
      </div>
    </div>
  </section>

  <!-- badges row -->
  <div class="bar">
    <div class="wrap badges">
      <div class="badge-row">
        <div class="dot b1">🏅</div>
        <div><b>World Best Instructors</b><br><small>18,500 courses</small></div>
      </div>
      <div class="badge-row">
        <div class="dot b2">🎥</div>
        <div><b>Live Class & Video Courses</b><br><small>492,000 Courses</small></div>
      </div>
      <div class="badge-row">
        <div class="dot b3">👥</div>
        <div><b>Over Active Students</b><br><small>367,000 students</small></div>
      </div>
    </div>
  </div>

  <!-- ================= Popular Courses ================= -->
  <section id="courses" class="wrap">
    <h3 class="section-title">Popular Courses</h3>
    <div class="cards">
      @foreach([1,2,3,4] as $i)
      <article class="card">
        <div class="ph">Course Cover</div>
        <div class="ct">
          <div class="meta"><b>Design-Led Strategy</b><span>176,8k ⭐</span></div>
          <div class="price"><b>$15.33</b><small style="text-decoration:line-through;color:#94a3b8">$20.77</small></div>
        </div>
      </article>
      @endforeach
    </div>
    <div class="view-all"><a href="#" class="btn">View All</a></div>
  </section>

  <!-- ================= Top Categories ================= -->
  <section id="cats" class="wrap" style="margin-top:6px">
    <h3 class="section-title">Top Categories</h3>
    <div class="cats">
      <div class="cat">Academic <span>📘</span></div>
      <div class="cat">Technical <span>🧪</span></div>
      <div class="cat">Vocational <span>🔧</span></div>
      <div class="cat">Others <span>✨</span></div>
    </div>
  </section>

  <!-- ================= Mentors ================= -->
  <section id="mentors" class="wrap" style="margin-top:6px">
    <h3 class="section-title">Meet Our Mentors</h3>
    <div class="carousel">
      <div class="mentor-grid" data-track>
        @foreach([['Ronald','English'],['Theresa','Web Development'],['Leslie','Programming'],['Darrell','Physics']] as [$n,$t])
        <article class="mentor">
          <div class="avatar"></div>
          <b>{{ $n }}</b>
          <div style="color:#97a0b3">{{ $t }}</div>
          <div style="margin-top:6px;color:#f59e0b">★ 4.9</div>
        </article>
        @endforeach
      </div>
      <div class="arrows">
        <button class="circle" data-left>‹</button>
        <button class="circle" data-right>›</button>
      </div>
    </div>
  </section>

  <!-- ================= Testimonials ================= -->
  <section class="wrap" style="margin-top:6px">
    <h3 class="section-title">Testimonials</h3>
    <div class="testi">
      @foreach([1,2,3] as $t)
      <article class="quote">
        <div class="row"><div class="mini"></div><div><b>Student {{ $t }}</b><br><small style="color:#97a0b3">Top Enroller</small></div></div>
        <p style="color:#60697b">E-learning here is engaging and thoughtful. New concepts become clear with more interactions.</p>
        <div style="margin-top:6px;color:#f59e0b">★★★★★</div>
      </article>
      @endforeach
    </div>
  </section>

  <!-- ================= Blog ================= -->
  <section id="blog" class="wrap" style="margin-top:6px">
    <h3 class="section-title">Read Our Daily Blogs</h3>
    <div class="blog-grid">
      @foreach([1,2,3,4] as $b)
      <article class="blog">
        <div class="ph"></div>
        <div class="ct">
          <h5>10 realities you really want to reexamine before start profession</h5>
          <small>06/10/2025 · 7min read</small>
        </div>
      </article>
      @endforeach
    </div>
    <div class="view-all"><a href="#" class="btn">Read More</a></div>
  </section>

  <!-- ================= Footer ================= -->
  <footer id="contact" class="foot">
    <div class="wrap foot-grid">
      <div>
        <div class="brand" style="margin-bottom:6px"><span class="brand-dot"></span><span>Edtech</span></div>
        <div style="color:#97a0b3">+998 7384 5867<br>info@edtechclasses.com</div>
      </div>
      <div>
        <h6>Menu</h6>
        <a href="#cats">Categories</a><br><a href="#courses">Courses</a><br><a href="#mentors">Mentors</a><br><a href="#blog">Blog</a>
      </div>
      <div>
        <h6>Company</h6>
        <a href="#">About us</a><br><a href="#">News</a><br><a href="#">Careers</a>
      </div>
      <div>
        <h6>Support</h6>
        <a href="#">Security</a><br><a href="#">Terms & Conditions</a><br><a href="#">Help</a>
      </div>
      <div>
        <h6>Subscribe to our Newsletter</h6>
        <div class="newsletter">
          <input type="email" placeholder="Enter your email">
          <a class="btn grad" href="{{ route('register') }}">Subscribe</a>
        </div>
      </div>
    </div>
    <div class="wrap copyright">© {{ date('Y') }} Edtech — All rights reserved.</div>
  </footer>

</div>

<script>
/* super-light carousel */
(function(){
  const track = document.querySelector('.ed [data-track]');
  if(!track) return;
  let offset = 0;
  const step = () => track.firstElementChild.offsetWidth + 14; // card + gap

  document.querySelector('.ed [data-left]').addEventListener('click', ()=>{
    offset = Math.min(offset + step(), 0);
    track.style.transform = `translateX(${offset}px)`;
    track.style.transition = 'transform .25s ease';
  });
  document.querySelector('.ed [data-right]').addEventListener('click', ()=>{
    const max = -(track.scrollWidth - track.clientWidth);
    offset = Math.max(offset - step(), max);
    track.style.transform = `translateX(${offset}px)`;
    track.style.transition = 'transform .25s ease';
  });
})();
</script>
@endsection
