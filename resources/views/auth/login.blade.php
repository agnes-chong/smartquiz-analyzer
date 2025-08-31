@extends('layouts.app')

@section('content')
  <style>
    /* Remove default navbar */
    .navbar,
    header.navbar,
    nav.navbar,
    .navbar-expand-md {
    display: none !important;
    }

    body {
    padding-top: 0 !important;
    font-family: 'Poppins', sans-serif;
    }

    /* Layout */
    .quiz-auth-full {
    display: flex;
    height: 100vh;
    }

    .quiz-left {
    flex: 1;
    background: #f7f8ff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    position: relative;
    }

    /* make the brand a clean link and position it */
    .brand {
    position: absolute;
    top: 30px;
    left: 40px;
    display: flex;
    align-items: center;
    gap: .5rem;
    font-weight: 700;
    color: #0f172a;
    text-decoration: none;
    /* no underline */
    }

    .brand:focus {
    outline: none;
    }

    .brand:hover {
    opacity: .9;
    }

    /* the colored dot */
    .brand-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6d7cff;
    display: inline-block;
    }

    /* safety reset in case any parent is a list */
    .brand,
    .brand * {
    list-style: none;
    }


    .illu {
    width: 80%;
    max-width: 380px;
    margin-bottom: 20px;
    }

    .tag {
    color: #1f2937;
    font-weight: 600;
    text-align: center;
    margin-top: 12px;
    }

    .dots {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 18px;
    }

    .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cfd3ff;
    }

    .dot.active {
    background: #6d7cff;
    }

    .quiz-right {
    flex: 1;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 80px;
    position: relative;
    }

    .top-cta {
    position: absolute;
    top: 30px;
    right: 40px;
    font-size: 14px;
    color: #9ca3af;
    }

    .top-cta a {
    margin-left: 8px;
    padding: 6px 16px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    color: #6b7280;
    text-decoration: none;
    }

    h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
    }

    .sub {
    color: #9ca3af;
    margin-bottom: 24px;
    }

    /* Form fields */
    .form-area {
    width: 100%;
    max-width: 460px;
    }

    .field {
    margin-bottom: 16px;
    width: 100%;
    }

    .form-label {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    margin-bottom: 6px;
    }

    .form-control.quiz-input {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 14px;
    height: auto;
    box-shadow: none !important;
    }

    /* Password eye inside field */
    .pw-wrap {
    position: relative;
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    }

    .pw-wrap input {
    border: 0;
    outline: 0;
    width: 100%;
    padding: 12px 46px 12px 14px;
    border-radius: 12px;
    background: transparent;
    }

    .pw-toggle {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border: 0;
    background: transparent;
    border-radius: 999px;
    display: grid;
    place-items: center;
    }

    .pw-icon {
    width: 22px;
    height: 22px;
    position: absolute;
    transition: transform .2s ease, opacity .2s ease;
    }

    .eye-open {
    opacity: 0;
    transform: scale(.8);
    }

    .eye-closed {
    opacity: 1;
    transform: scale(1);
    }

    .pw-toggle.open .eye-open {
    opacity: 1;
    transform: scale(1);
    }

    .pw-toggle.open .eye-closed {
    opacity: 0;
    transform: scale(.8);
    }

    /* Buttons */
    .btn-cta {
    background: #6d7cff;
    border-color: #6d7cff;
    color: #fff;
    width: 100%;
    padding: 12px;
    border-radius: 999px;
    font-weight: 600;
    }

    .btn-cta:hover {
    filter: brightness(.95);
    }

    /* Links */
    .right-link {
    color: #9ca3af;
    text-decoration: none;
    font-size: 14px;
    }

    .right-link:hover {
    text-decoration: underline;
    }

    .err {
    color: #ef4444;
    font-size: 13px;
    margin-top: 6px;
    }

    /* Responsive */
    @media (max-width:992px) {
    .quiz-auth-full {
      flex-direction: column;
      height: auto;
    }

    .quiz-left,
    .quiz-right {
      width: 100%;
      padding: 32px 20px;
    }

    .top-cta {
      position: static;
      text-align: right;
      margin-bottom: 8px;
      width: 100%;
    }
    }

    /* uniform wrapper for ALL inputs */
    .input-wrap,
    .pw-wrap {
    position: relative;
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    }

    .input-wrap input,
    .pw-wrap input {
    width: 100%;
    border: 0;
    outline: 0;
    padding: 12px 14px;
    /* pw gets more below for eye */
    border-radius: 12px;
    background: transparent;
    }

    .pw-wrap input {
    padding-right: 46px;
    }

    /* space for the eye button */
  </style>

  <div class="quiz-auth-full">
    <div class="quiz-left">
    <a href="{{ url('/') }}" class="brand" aria-label="Go to Home">
      <span class="brand-dot"></span>
      <span>Quizkarooo</span>
    </a>
    <svg class="illu" viewBox="0 0 320 240" xmlns="http://www.w3.org/2000/svg">
      <rect width="320" height="240" rx="22" fill="#eef1ff" />
      <circle cx="160" cy="110" r="70" fill="#ffffff" />
      <path d="M145 112c0-18 35-18 35-40 0-14-11-24-29-24-14 0-24 6-30 15" stroke="#6d7cff" stroke-width="8" fill="none"
      stroke-linecap="round" />
      <circle cx="160" cy="148" r="8" fill="#6d7cff" />
    </svg>
    <p class="tag">Take a Quiz be more creative<br>in your work</p>
    <div class="dots"><span class="dot"></span><span class="dot active"></span></div>
    </div>

    <div class="quiz-right">
    <div class="top-cta">Create an account
      <a href="{{ route('register') }}">Sign Up</a>
    </div>
    <h1>Welcome to Quizkarooo</h1>
    <div class="sub">Login & start playing</div>

    <form method="POST" action="{{ route('login') }}" class="form-area">
      @csrf
      <div class="field">
      <label for="email" class="form-label">Enter Name</label>
      <div class="input-wrap">
        <input id="email" type="email" class="@error('email') is-invalid @enderror" name="email"
        value="{{ old('email') }}" required autocomplete="email" autofocus>
      </div>
      @error('email') <div class="err">{{ $message }}</div> @enderror
      </div>


      <div class="field">
      <label for="password" class="form-label">Enter Password</label>
      <div class="pw-wrap">
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="togglePwVisibility('password', this)">
        <svg class="pw-icon eye-closed" viewBox="0 0 24 24" fill="none">
          <path d="M3 3l18 18" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round" />
          <path d="M10.6 10.6A3 3 0 0012 15a3 3 0 002.4-4.4" stroke="#0f172a" stroke-width="1.6"
          stroke-linecap="round" />
          <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6c-2.2 0-4.1-.7-5.7-1.7" stroke="#0f172a"
          stroke-width="1.6" stroke-linecap="round" />
        </svg>
        <svg class="pw-icon eye-open" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="3" stroke="#0f172a" stroke-width="1.6" />
          <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" stroke="#0f172a" stroke-width="1.6"
          stroke-linecap="round" />
        </svg>
        </button>
      </div>
      @error('password') <div class="err">{{ $message }}</div> @enderror
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
        <label class="form-check-label" for="remember">Remember me</label>
      </div>
      @if (Route::has('password.request'))
      <a class="right-link" href="{{ route('password.request') }}">Forgot password?</a>
    @endif
      </div>

      <button type="submit" class="btn btn-cta">Login</button>
    </form>
    </div>
  </div>

  <script>
    function setPwBtnState(input, btn) {
    const showing = input.type === 'text';
    btn.classList.toggle('open', showing);
    }
    function togglePwVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.type = (input.type === 'password') ? 'text' : 'password';
    setPwBtnState(input, btn);
    }
  </script>
@endsection