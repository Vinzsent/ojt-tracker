<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = $_POST['report_date'];
    
    if (isset($_POST['report_content'])) {
        // Handle written report
        $report_content = mysqli_real_escape_string($connection, $_POST['report_content']);
        $file_path = null;
        
        // Handle image uploads
        $image_paths = [];
        if (!empty($_FILES['evidence_images']['name'][0])) {
            // Check if at least 2 images are uploaded
            if (count($_FILES['evidence_images']['name']) < 2) {
                $_SESSION['error'] = "Please upload at least 2 images as evidence.";
                header("Location: narrative_report.php");
                exit();
            }
            
            $upload_dir = 'uploads/evidence/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['evidence_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['evidence_images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = array('jpg', 'jpeg', 'png');
                
                if (in_array($file_ext, $allowed_ext)) {
                    $new_file_name = uniqid() . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $image_paths[] = $destination;
                    }
                }
            }
        }
        
        // Store image paths as JSON in report_content
        if (!empty($image_paths)) {
            $report_content = json_encode([
                'text' => $report_content,
                'images' => $image_paths
            ]);
        }
        
    } else if (!empty($_FILES['report_file']['name'])) {
        // Handle file upload
        $upload_dir = 'uploads/reports/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['report_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('doc', 'docx', 'pdf');
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid() . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $destination)) {
                $report_content = null;
                $file_path = $destination;
                $original_filename = $file_name; // Store the original filename
            }
        }
    }
    
    // Insert into database
    $query = "INSERT INTO narrative_reports (user_id, report_date, report_content, file_path, original_filename) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $report_date, $report_content, $file_path, $original_filename);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Narrative report submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting report: " . mysqli_error($connection);
    }
    
    header("Location: narrative_report.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Narrative Report</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dcc-green: #0B4619;
            --dcc-gold: #FFB800;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: var(--dcc-green);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--dcc-green);
            border-color: var(--dcc-green);
        }
        
        .btn-primary:hover {
            background-color: #083714;
            border-color: #083714;
        }

        .instruction-card {
            background-color: #f8f9fa;
            border-left: 5px solid var(--dcc-gold);
            padding: 15px;
            margin-bottom: 20px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">OJT Tracker</a>
            <div class="d-flex">
                <a href="student-dashboard.php" class="btn btn-outline-light btn-sm me-2">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daily Narrative Report</h4>
                        
                        <div class="instruction-card">
                            <h5>Instructions:</h5>
                            <ol>
                                <li>Choose the date for your narrative report</li>
                                <li>You can either:
                                    <ul>
                                        <li>Write your narrative report directly in the text editor below, or</li>
                                        <li>Upload a Word/PDF document containing your report</li>
                                    </ul>
                                </li>
                                <li>If you choose to upload a document file make sure the evidence image/photos is on the file.</li>
                                <li>Add supporting images/photos as evidence of your OJT activities.</li>
                                <li>Upload 2 images as evidence of your OJT activities if you choose to write on the text editor</li>
                                <li>Review your report before submitting</li>
                                <li>Submit your report by clicking the "Submit Report" button</li>
                                <li>If you have experienced technical issues, contact the IT department for assistance.</li>
                            </ol>
                        </div>

                        <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="write-tab" data-bs-toggle="tab" data-bs-target="#write" type="button" role="tab">Write Report</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">Upload File</button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="reportTabContent">
                            <!-- Write Report Tab -->
                            <div class="tab-pane fade show active" id="write" role="tabpanel">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="report_date" class="form-label">Report Date</label>
                                        <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $current_date; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="report_content" class="form-label">Narrative Report</label>
                                        <textarea class="form-control" id="report_content" name="report_content" rows="10" placeholder="Write your narrative report here..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="evidence_images" class="form-label">Upload Evidence (Images) - Minimum 2 images required</label>
                                        <input type="file" class="form-control" id="evidence_images" name="evidence_images[]" accept="image/*" multiple required>
                                        <div class="form-text text-muted">Please select at least 2 images as evidence of your OJT activities.</div>
                                        <div id="image-preview" class="mt-2 d-flex flex-wrap"></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Report</button>
                                </form>
                            </div>

                            <!-- Upload File Tab -->
                            <div class="tab-pane fade" id="upload" role="tabpanel">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="report_date" class="form-label">Report Date</label>
                                        <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $current_date; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="report_file" class="form-label">Upload Report File (DOC, DOCX, PDF)</label>
                                        <input type="file" class="form-control" id="report_file" name="report_file" accept=".doc,.docx,.pdf" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('evidence_images').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            // Check if at least 2 files are selected
            if (this.files.length < 2) {
                alert('Please select at least 2 images as evidence.');
                this.value = ''; // Clear the file input
                return;
            }
            
            for (const file of this.files) {
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>
