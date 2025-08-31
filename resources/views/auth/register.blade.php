@extends('layouts.app')

@section('content')
<style>
/* --- Kill the default header/navbar just for this page --- */
.navbar, header.navbar, nav.navbar, .navbar-expand-md { display:none !important; }
body { padding-top:0 !important; }

/* --- Fullscreen split layout --- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
body { font-family:'Poppins',sans-serif; }

.quiz-auth-full { display:flex; height:100vh; }
.quiz-left{ flex:1; background:#f7f8ff; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px; position:relative; }
.quiz-right{ flex:1; background:#fff; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; padding:60px 80px; position:relative; }

.brand{ position:absolute; top:30px; left:40px; display:flex; align-items:center; gap:.5rem; font-weight:700; color:#0f172a; }
.brand-dot{ width:10px; height:10px; border-radius:50%; background:#6d7cff; }

.illu{ width:80%; max-width:380px; margin-bottom:20px; }
.tag{ color:#374151; font-weight:500; text-align:center; }

.top-cta{ position:absolute; top:30px; right:40px; font-size:14px; color:#9ca3af; }
.top-cta a{ margin-left:8px; padding:6px 16px; border-radius:999px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; }

h1{ font-size:28px; font-weight:700; color:#0f172a; margin:0 0 6px 0; }
.sub{ color:#9ca3af; margin-bottom:24px; }

/* --- Tidy, same-length fields --- */
.form-area{ width:100%; max-width:460px; }         /* controls the uniform width */
.field{ display:block; width:100%; margin-bottom:16px; }  /* equal gaps */
.form-label{ font-weight:600; color:#111827; font-size:14px; margin-bottom:6px; }

.form-control.quiz-input{
width:100%;
border-radius:12px;
border:1px solid #e5e7eb;
padding:12px 14px;
height:auto;
box-shadow:none !important;
}

/* Password with eye keeps same width */
.input-group.quiz-group{ width:100%; }
.input-group.quiz-group .form-control.quiz-input{ border-right:0; border-radius:12px 0 0 12px; }
.input-group.quiz-group .toggle-eye{
border:1px solid #e5e7eb; border-left:0; background:#fff;
padding:0 14px; border-radius:0 12px 12px 0;
}

/* Uniform inputs */
.form-control.quiz-input{ box-sizing:border-box; width:100%; border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; }

/* Password field that matches width & corners */
.pw-wrap{
position:relative;
box-sizing:border-box;
width:100%;
border:1px solid #e5e7eb;
border-radius:12px;
background:#fff;
}
.pw-wrap input{
border:0; outline:0; width:100%;
padding:12px 46px 12px 14px; /* space for eye */
border-radius:12px;  /* keeps same feel on focus */
background:transparent;
}
.pw-wrap input:focus{ box-shadow:none; }

/* Eye button inside, clipped by wrapper */
.pw-toggle{
position:absolute; top:50%; right:8px; transform:translateY(-50%);
width:34px; height:34px; border:0; background:transparent; border-radius:999px;
display:grid; place-items:center; transition:background .2s ease, transform .12s ease;
}
.pw-toggle:hover{ background:#f3f4f6; }
.pw-toggle:active{ transform:translateY(-50%) scale(.96); }

/* Open/close animation */
.pw-icon{ width:22px; height:22px; position:absolute; transition:transform .2s ease, opacity .2s ease; }
.eye-open{ opacity:0; transform:scale(.8); }
.eye-closed{ opacity:1; transform:scale(1); }
.pw-toggle.open .eye-open{ opacity:1; transform:scale(1); }
.pw-toggle.open .eye-closed{ opacity:0; transform:scale(.8); }

/* CTA */
.btn-cta{
background:#6d7cff; border-color:#6d7cff; color:#fff;
width:100%; padding:12px 20px; border-radius:999px; font-weight:600;
}
.btn-cta:hover{ filter:brightness(.95); }

.text-muted{ color:#9ca3af !important; }
.social-row{ display:flex; gap:12px; margin-top:10px; }
.social{ width:40px; height:40px; border-radius:50%; border:1px solid #e5e7eb; background:#fff; display:grid; place-items:center; }

.err{ color:#ef4444; font-size:13px; margin-top:6px; }

/* Responsive */
@media (max-width: 992px){
.quiz-auth-full{ flex-direction:column; height:auto; }
.quiz-left, .quiz-right{ width:100%; padding:32px 20px; }
.top-cta{ position:static; text-align:right; margin-bottom:8px; width:100%; }
}
</style>

<div class="quiz-auth-full">
<!-- LEFT -->
<div class="quiz-left">
<div class="brand"><span class="brand-dot"></span><span>Quizkarooo</span></div>

<svg class="illu" viewBox="0 0 320 240" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <rect width="320" height="240" rx="22" fill="#eef1ff"/>
    <circle cx="160" cy="110" r="70" fill="#ffffff"/>
    <path d="M145 112c0-18 35-18 35-40 0-14-11-24-29-24-14 0-24 6-30 15"
        stroke="#6d7cff" stroke-width="8" fill="none" stroke-linecap="round"/>
    <circle cx="160" cy="148" r="8" fill="#6d7cff"/>
</svg>

<p class="tag">Take a Quiz be more creative<br>in your work</p>
</div>

<!-- RIGHT -->
<div class="quiz-right">
<div class="top-cta">Already have an account?
    <a href="{{ route('login') }}">Login</a>
</div>

<h1>Welcome to Quizkarooo</h1>
<div class="sub">Register your account</div>

<form method="POST" action="{{ route('register') }}" class="form-area">
    @csrf

    <div class="field">
    <label for="name" class="form-label">Enter Name</label>
    <input id="name" type="text" class="form-control quiz-input @error('name') is-invalid @enderror"
            name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
    @error('name') <div class="err">{{ $message }}</div> @enderror
    </div>

    <div class="field">
    <label for="email" class="form-label">Enter Email</label>
    <input id="email" type="email" class="form-control quiz-input @error('email') is-invalid @enderror"
            name="email" value="{{ old('email') }}" required autocomplete="email">
    @error('email') <div class="err">{{ $message }}</div> @enderror
    </div>

<div class="field">
<label for="password" class="form-label">Enter Password</label>
<div class="pw-wrap">
<input id="password" type="password"
        class="@error('password') is-invalid @enderror"
        name="password" required autocomplete="new-password">
<button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false"
        onclick="togglePwVisibility('password', this)">
    <!-- closed eye -->
    <svg class="pw-icon eye-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M3 3l18 18" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M10.6 10.6A3 3 0 0012 15a3 3 0 002.4-4.4" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6c-2.2 0-4.1-.7-5.7-1.7" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    <!-- open eye -->
    <svg class="pw-icon eye-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle cx="12" cy="12" r="3" stroke="#0f172a" stroke-width="1.6"/>
    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
</button>
</div>
@error('password') <div class="err">{{ $message }}</div> @enderror
</div>



    <div class="field">
    <label for="password-confirm" class="form-label">Confirm Password</label>
    <input id="password-confirm" type="password" class="form-control quiz-input"
            name="password_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-cta mb-3">Sign Up</button>

    <div class="text-muted mb-2">Create account with</div>
    <div class="social-row">
    <div class="social" title="Facebook">f</div>
    <div class="social" title="LinkedIn">in</div>
    <div class="social" title="Google">G</div>
    </div>
</form>
</div>
</div>

<script>
// keep state in sync with UI
function setPwBtnState(input, btn){
  const showing = input.type === 'text';
  btn.classList.toggle('open', showing);        // swaps the two SVGs via CSS
  btn.setAttribute('aria-pressed', showing ? 'true' : 'false');
  btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
}

function togglePwVisibility(inputId, btn){
  const input = document.getElementById(inputId);
  if (!input) return;
  input.type = (input.type === 'password') ? 'text' : 'password';
  setPwBtnState(input, btn);
}

// optional: initialize state on load (in case browser restores form values)
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('password');
  const btn = document.querySelector('.pw-toggle');
  if (input && btn) setPwBtnState(input, btn);
});
</script>

@endsection
