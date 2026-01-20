<?php
// promotion_logs.php
session_start();
require '../db.php';

// Only allow admin/teacher
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch all promotion logs
$query = "SELECT 
            pl.*, 
            u.fullname as student_name,
            u.class as current_class
          FROM promotion_logs pl
          JOIN users u ON pl.student_id = u.id
          ORDER BY pl.promoted_at DESC";
$result = $conn->query($query);
$promotions = $result->fetch_all(MYSQLI_ASSOC);

// Statistics
$stats_query = "SELECT 
                  COUNT(*) as total_promotions,
                  COUNT(DISTINCT student_id) as unique_students,
                  AVG(overall_percentage) as avg_percentage,
                  MIN(promoted_at) as first_promotion,
                  MAX(promoted_at) as last_promotion
                FROM promotion_logs";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotion History - DHPS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stats-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #0d6efd; }
        .stat-label { color: #6c757d; font-size: 0.9rem; }
        .promotion-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #0d6efd; }
        .from-to { font-size: 1.2rem; font-weight: bold; }
        .percentage-badge { font-size: 0.9rem; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="bi bi-graph-up-arrow"></i> Promotion History</h1>
                <a href="marks.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Back to Marks</a>
            </div>
            <p class="text-muted">Track all student promotions with details</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stat-number"><?= $stats['total_promotions'] ?? 0 ?></div>
                <div class="stat-label">Total Promotions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stat-number"><?= $stats['unique_students'] ?? 0 ?></div>
                <div class="stat-label">Unique Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stat-number"><?= number_format($stats['avg_percentage'] ?? 0, 1) ?>%</div>
                <div class="stat-label">Average Percentage</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <div class="stat-label">Last Promotion</div>
                <div class="stat-number" style="font-size: 1rem;"><?= $stats['last_promotion'] ? date('d M Y', strtotime($stats['last_promotion'])) : 'N/A' ?></div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search Student</label>
                            <input type="text" name="search" class="form-control" placeholder="Student name..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Class</label>
                            <select name="from_class" class="form-select">
                                <option value="">All Classes</option>
                                <option value="PG" <?= ($_GET['from_class'] ?? '') == 'PG' ? 'selected' : '' ?>>PG</option>
                                <option value="Nursery" <?= ($_GET['from_class'] ?? '') == 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                                <option value="Prep" <?= ($_GET['from_class'] ?? '') == 'Prep' ? 'selected' : '' ?>>Prep</option>
                                <?php for($i=1; $i<=8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_GET['from_class'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Class</label>
                            <select name="to_class" class="form-select">
                                <option value="">All Classes</option>
                                <option value="PG" <?= ($_GET['to_class'] ?? '') == 'PG' ? 'selected' : '' ?>>PG</option>
                                <option value="Nursery" <?= ($_GET['to_class'] ?? '') == 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                                <option value="Prep" <?= ($_GET['to_class'] ?? '') == 'Prep' ? 'selected' : '' ?>>Prep</option>
                                <?php for($i=1; $i<=8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_GET['to_class'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotion Logs Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($promotions)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-info-circle display-1 text-muted"></i>
                            <h3 class="mt-3">No Promotion Records Found</h3>
                            <p class="text-muted">Student promotions will appear here when they are promoted.</p>
                            <a href="marks.php" class="btn btn-primary mt-3">Go to Marks Management</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Promotion</th>
                                        <th>Percentage</th>
                                        <th>Promoted By</th>
                                        <th>Date & Time</th>
                                        <th>Current Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($promotions as $index => $promo): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($promo['student_name']) ?></strong><br>
                                            <small class="text-muted">ID: <?= $promo['student_id'] ?></small>
                                        </td>
                                        <td>
                                            <div class="from-to">
                                                <span class="badge bg-secondary"><?= $promo['from_class'] ?></span>
                                                <i class="bi bi-arrow-right mx-2"></i>
                                                <span class="badge bg-success"><?= $promo['to_class'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="percentage-badge 
                                                <?php 
                                                $percentage = $promo['overall_percentage'];
                                                if ($percentage >= 80) echo 'bg-success';
                                                elseif ($percentage >= 60) echo 'bg-info';
                                                elseif ($percentage >= 40) echo 'bg-warning';
                                                else echo 'bg-danger';
                                                ?> text-white">
                                                <?= number_format($percentage, 1) ?>%
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($promo['promoted_by']) ?></td>
                                        <td>
                                            <?= date('d M Y', strtotime($promo['promoted_at'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($promo['promoted_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= $promo['current_class'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Export Option -->
                        <div class="mt-3">
                            <a href="export_promotions.php" class="btn btn-outline-success">
                                <i class="bi bi-download"></i> Export to Excel
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>