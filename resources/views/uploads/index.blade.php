<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Exam Papers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="m-0">Upload Exam Paper</h3>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="file" class="form-label">Exam Paper File</label>
                                <input type="file" class="form-control" id="file" name="file" required>
                                <div class="form-text">Upload .pdf, .docx, .jpg, .png, or .zip files</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="examiner" class="form-label">Examiner</label>
                                    <input type="text" class="form-control" id="examiner" name="examiner" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="class" class="form-label">Class</label>
                                    <input type="text" class="form-control" id="class" name="class" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="term" class="form-label">Term</label>
                                    <select class="form-control" id="term" name="term" required>
                                        <option value="Term 1">Term 1</option>
                                        <option value="Term 2">Term 2</option>
                                        <option value="Term 3">Term 3</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="year" class="form-label">Year</label>
                                    <input type="number" class="form-control" id="year" name="year" min="2000" max="2030" value="{{ date('Y') }}" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="curriculum" class="form-label">Curriculum</label>
                                    <select class="form-control" id="curriculum" name="curriculum" required>
                                        <option value="CBC">CBC</option>
                                        <option value="8-4-4">8-4-4</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="paper_type" class="form-label">Paper Type</label>
                                    <input type="text" class="form-control" id="paper_type" name="paper_type" placeholder="e.g. Paper 1" required>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Upload & Process</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>