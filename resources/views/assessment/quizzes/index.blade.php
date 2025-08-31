@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold text-primary">
            <i class="fas fa-tachometer-alt me-2"></i>Quiz Dashboard
        </h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('quizzes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> New Quiz
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm card-hover bg-gradient-primary text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-clipboard-list me-2"></i>Total Quizzes</h5>
                <h2 class="fw-bold">{{ $quizzes->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm card-hover bg-gradient-success text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>Active Students</h5>
                <h2 class="fw-bold">142</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm card-hover bg-gradient-info text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-star me-2"></i>Avg. Score</h5>
                <h2 class="fw-bold">84%</h2>
            </div>
        </div>
    </div>
</div>

<!-- Quiz List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 py-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-list-ol me-2 text-primary"></i>Recent Quizzes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Quiz Title</th>
                        <th>Questions</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quizzes as $quiz)
                    <tr>
                        <td>
                            <a href="{{ route('quizzes.show', $quiz->id) }}" class="text-decoration-none">
                                <strong>{{ $quiz->title }}</strong>
                            </a>
                        </td>
                        <td>{{ $quiz->questions_count }}</td>
                        <td>{{ $quiz->duration_minutes }} mins</td>
                        <td>
                            <span class="badge bg-success">Active</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('quizzes.show', $quiz->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection