@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h4 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-plus-circle me-2"></i>Create New Quiz
                </h4>
            </div>
            <div class="card-body">
                <form action="{{ route('quizzes.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    
                    <div class="mb-4">
                        <label for="quizTitle" class="form-label fw-bold">Quiz Title</label>
                        <input type="text" class="form-control form-control-lg" id="quizTitle" 
                               name="title" placeholder="Enter quiz title" required>
                        <div class="invalid-feedback">
                            Please provide a quiz title.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="duration" class="form-label fw-bold">Duration (minutes)</label>
                            <input type="number" class="form-control form-control-lg" 
                                   id="duration" name="duration_minutes" min="1" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="difficulty" class="form-label fw-bold">Difficulty</label>
                            <select class="form-select form-select-lg" id="difficulty" name="difficulty">
                                <option value="easy">Easy</option>
                                <option value="medium" selected>Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Quiz Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Brief description about the quiz"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="fas fa-save me-2"></i>Create Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Form validation example
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
@endsection