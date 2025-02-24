<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all reports for the user
$query = "SELECT * FROM narrative_reports WHERE user_id = ? ORDER BY report_date DESC";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Handle file download
if (isset($_GET['download']) && isset($_GET['type']) && isset($_GET['id'])) {
    $report_id = $_GET['id'];
    $type = $_GET['type'];
    
    // Verify the report belongs to the user
    $verify_query = "SELECT * FROM narrative_reports WHERE report_id = ? AND user_id = ?";
    $verify_stmt = mysqli_prepare($connection, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, "ii", $report_id, $user_id);
    mysqli_stmt_execute($verify_stmt);
    $report = mysqli_fetch_assoc(mysqli_stmt_get_result($verify_stmt));
    
    if ($report) {
        if ($type === 'report' && $report['file_path']) {
            // Download report file
            $file = $report['file_path'];
            if (file_exists($file)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $report['original_filename'] . '"');
                readfile($file);
                exit();
            }
        } elseif ($type === 'evidence' && $report['report_content']) {
            // Download evidence images as zip
            $content = json_decode($report['report_content'], true);
            if (isset($content['images']) && !empty($content['images'])) {
                $zip = new ZipArchive();
                $zipName = 'evidence_images_' . $report_id . '.zip';
                $zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                
                foreach ($content['images'] as $image) {
                    if (file_exists($image)) {
                        $zip->addFile($image, basename($image));
                    }
                }
                
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipName . '"');
                readfile($zipName);
                unlink($zipName); // Delete the temporary zip file
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Narrative Reports</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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

        .evidence-image {
            max-width: 150px;
            max-height: 150px;
            margin: 5px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .report-card {
            transition: transform 0.2s;
        }

        .report-card:hover {
            transform: translateY(-5px);
        }

        .download-btn {
            font-size: 0.9rem;
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">My Narrative Reports</h4>
                            <a href="narrative_report.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create New Report
                            </a>
                        </div>

                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="row">
                                <?php while ($report = mysqli_fetch_assoc($result)): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card report-card">
                                            <div class="card-body">
                                                <h5 class="card-title">Report for <?php echo date('F d, Y', strtotime($report['report_date'])); ?></h5>
                                                <p class="text-muted mb-3">
                                                    <small>Submitted on <?php echo date('F d, Y g:i A', strtotime($report['created_at'])); ?></small>
                                                </p>

                                                <?php if ($report['file_path']): ?>
                                                    <!-- Uploaded file report -->
                                                    <p class="mb-2">
                                                        <i class="fas fa-file-alt me-2"></i>
                                                        Uploaded Document: <?php echo htmlspecialchars($report['original_filename']); ?>
                                                    </p>
                                                    <a href="?download=true&type=report&id=<?php echo $report['report_id']; ?>" 
                                                       class="btn btn-sm btn-primary download-btn">
                                                        <i class="fas fa-download"></i> Download Report
                                                    </a>
                                                <?php else: ?>
                                                    <!-- Written report -->
                                                    <?php $content = json_decode($report['report_content'], true); ?>
                                                    <div class="mb-3">
                                                        <h6>Report Content:</h6>
                                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($content['text'] ?? $report['report_content'])); ?></p>
                                                    </div>
                                                    <?php if (isset($content['images']) && !empty($content['images'])): ?>
                                                        <div class="mb-3">
                                                            <h6>Evidence Images:</h6>
                                                            <div class="d-flex flex-wrap">
                                                                <?php foreach ($content['images'] as $image): ?>
                                                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                                                         class="evidence-image" 
                                                                         alt="Evidence Image">
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <a href="?download=true&type=evidence&id=<?php echo $report['report_id']; ?>" 
                                                               class="btn btn-sm btn-primary download-btn mt-2">
                                                                <i class="fas fa-download"></i> Download All Images
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You haven't submitted any narrative reports yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
