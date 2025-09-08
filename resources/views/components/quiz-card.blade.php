@props([
  'title' => 'Ancient Civilizations',
  'category' => 'History',
  'description' => 'Explore milestones and empires that shaped the world.',
  'image' => null,   // e.g. Vite::asset('resources/images/history.jpg')
  'questions' => 15,
  'plays' => '10k',
  'tag' => 'Trending', // Trending | Newest | Oldest | Selected
  'href' => '#'
])

<div class="card h-100 border-0 shadow-sm card-hover">
  @if($image)
    <img src="{{ $image }}" class="card-img-top" alt="{{ $title }}">
  @endif
  <div class="card-body d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <x-badge type="secondary">{{ $category }}</x-badge>
      @if($tag)
        <x-badge type="primary">{{ $tag }}</x-badge>
      @endif
    </div>

    <h5 class="fw-bold mb-1">{{ $title }}</h5>
    <p class="text-muted small mb-3">{{ $description }}</p>

    <div class="d-flex gap-3 text-muted small mb-3">
      <span><i class="fa-solid fa-circle-question me-1"></i>{{ $questions }} Questions</span>
      <span><i class="fa-solid fa-fire me-1"></i>{{ $plays }} Plays</span>
    </div>

    <div class="mt-auto d-grid">
      <a href="{{ $href }}" class="btn btn-primary">
        Play Now <i class="fa-solid fa-chevron-right ms-1"></i>
      </a>
    </div>
  </div>
</div>
