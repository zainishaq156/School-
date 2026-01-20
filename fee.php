<?php
session_start();
require '../db.php';

// Ensure the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

// Fetch student details
$username = $_SESSION['username'];
$student = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
$student_id = $student['id'];
$class = $student['class'];

// Fetch fee records for the student
$fee_records = $conn->query("
    SELECT * FROM fees 
    WHERE student_id=$student_id 
    ORDER BY year DESC, FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
")->fetch_all(MYSQLI_ASSOC);

// Group fees by year
$fees_by_year = [];
foreach ($fee_records as $fee) {
    $year = $fee['year'];
    if (!isset($fees_by_year[$year])) {
        $fees_by_year[$year] = [];
    }
    $fees_by_year[$year][] = $fee;
}

// Calculate overall totals
$total_fees = 0;
$total_paid = 0;
$remaining_balance = 0;

foreach ($fee_records as $fee) {
    $fee_amount = $fee['fee_amount'];
    $status = $fee['status'] ?? 'Not Paid';
    
    $total_fees += $fee_amount;
    
    if ($status == 'Paid') {
        $total_paid += $fee_amount;
    } else {
        $remaining_balance += $fee_amount;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Fee Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .fee-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .fee-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid #e8ecf4;
        }
        
        .balance-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .balance-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .balance-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e8ecf4;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .balance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .balance-card.total {
            border-top: 4px solid #3498db;
        }
        
        .balance-card.paid {
            border-top: 4px solid #27ae60;
        }
        
        .balance-card.remaining {
            border-top: 4px solid #e74c3c;
        }
        
        .balance-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .balance-card.total .icon { color: #3498db; }
        .balance-card.paid .icon { color: #27ae60; }
        .balance-card.remaining .icon { color: #e74c3c; }
        
        .balance-card .amount {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .balance-card .label {
            font-size: 0.9rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .fee-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e8ecf4;
        }
        
        .fee-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
            text-align: center;
        }
        
        .fee-table td {
            padding: 15px;
            border: 1px solid #e8ecf4;
            text-align: center;
            vertical-align: middle;
        }
        
        .fee-table tbody tr:hover {
            background: #f8f9fa;
            transition: background 0.3s ease;
        }
        
        .badge-paid { 
            background: #d4edda !important; 
            color: #155724 !important; 
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .badge-unpaid { 
            background: #f8d7da !important; 
            color: #721c24 !important; 
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin: 20px 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .print-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        .back-link {
            color: #667eea !important;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #764ba2 !important;
        }
        
        .page-title {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .student-info {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
        }
        
        .currency {
            font-size: 0.8em;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .fee-box { 
                padding: 20px; 
                margin: 10px;
            }
            
            .balance-cards {
                grid-template-columns: 1fr;
            }
            
            .balance-card .amount {
                font-size: 1.5rem;
            }
            
            .fee-table th,
            .fee-table td {
                padding: 10px 8px;
                font-size: 0.9rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
        
        @media print {
            .print-btn, .back-link { display: none !important; }
            .fee-box { box-shadow: none; margin: 0; }
            body { background: #fff !important; }
            .balance-summary { background: #f8f9fa !important; color: #333 !important; }
        }
    </style>
</head>
<body>
<div class="fee-container">
    <h1 class="page-title">Fee Management System</h1>
    
    <div class="student-info">
        <h3><i class="fas fa-user"></i> Welcome, <?= htmlspecialchars($student['fullname'] ?? $username) ?></h3>
        <p>Class: <?= htmlspecialchars($class) ?> | Student ID: <?= htmlspecialchars($student_id) ?></p>
    </div>

    <div class="fee-box">
        <!-- Balance Summary -->
        <div class="balance-summary">
            <h2><i class="fas fa-chart-line"></i> Financial Overview</h2>
            <p>Complete breakdown of your fee status and payment history</p>
        </div>

        <!-- Balance Cards -->
        <div class="balance-cards">
            <div class="balance-card total">
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="amount">
                    <span class="currency">PKR</span> <?= number_format($total_fees, 0) ?>
                </div>
                <div class="label">Total Fees</div>
            </div>
            
            <div class="balance-card paid">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="amount">
                    <span class="currency">PKR</span> <?= number_format($total_paid, 0) ?>
                </div>
                <div class="label">Amount Paid</div>
            </div>
            
            <div class="balance-card remaining">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="amount">
                    <span class="currency">PKR</span> <?= number_format($remaining_balance, 0) ?>
                </div>
                <div class="label">Remaining Balance</div>
            </div>
        </div>

        <!-- Fee Records Table -->
        <h3 style="color: #2c3e50; margin-bottom: 20px;"><i class="fas fa-table"></i> Detailed Fee Records</h3>
        
        <?php if ($fee_records): ?>
        <div class="table-responsive">
            <table class="fee-table table mb-3">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Month</th>
                        <th><i class="fas fa-money-bill"></i> Amount</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-clock"></i> Due Date</th>
                        <th><i class="fas fa-check"></i> Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_keys($fees_by_year) as $year): ?>
                        <tr style="background: #eee; font-weight: bold;">
                            <td colspan="5">Year <?= $year ?></td>
                        </tr>
                        <?php foreach ($fees_by_year[$year] as $fee): ?>
                            <?php
                            $fee_amount = $fee['fee_amount'];
                            $status = $fee['status'] ?? 'Not Paid';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($fee['month']) ?></strong></td>
                                <td>PKR <?= number_format($fee_amount, 0) ?></td>
                                <td>
                                    <?php
                                    if ($status == 'Paid') echo '<span class="badge badge-paid"><i class="fas fa-check"></i> Paid</span>';
                                    else echo '<span class="badge badge-unpaid"><i class="fas fa-times"></i> Not Paid</span>';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($fee['due_date'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($fee['paid_date'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php 
                        // Calculate year subtotals
                        $year_total = 0;
                        $year_paid = 0;
                        $year_remaining = 0;
                        foreach ($fees_by_year[$year] as $fee) {
                            $fee_amount = $fee['fee_amount'];
                            $year_total += $fee_amount;
                            if ($fee['status'] == 'Paid') {
                                $year_paid += $fee_amount;
                            } else {
                                $year_remaining += $fee_amount;
                            }
                        }
                        ?>
                        <tr style="font-weight: bold; background: #f8f9fa;">
                            <td colspan="1">Subtotal for <?= $year ?></td>
                            <td>PKR <?= number_format($year_total, 0) ?></td>
                            <td colspan="3">Paid: PKR <?= number_format($year_paid, 0) ?> | Remaining: PKR <?= number_format($year_remaining, 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-warning text-center mb-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>No fee records found.</strong><br>
            Please contact the office if you think this is an error.
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <a href="dashboard.php" class="back-link d-inline-block mx-3">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add some interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Highlight overdue payments
    const today = new Date();
    const tableRows = document.querySelectorAll('.fee-table tbody tr:not([style*="background"])');
    
    tableRows.forEach(row => {
        const dueDateCell = row.cells[3]; // Due date column
        const statusCell = row.cells[2]; // Status column
        
        if (dueDateCell && dueDateCell.textContent !== '-') {
            const dueDate = new Date(dueDateCell.textContent);
            const status = statusCell.textContent.toLowerCase();
            
            if (dueDate < today && status.includes('not paid')) {
                row.style.backgroundColor = '#ffebee';
                row.style.border = '2px solid #ffcdd2';
            }
        }
    });
    
    // Add animation to balance cards
    const balanceCards = document.querySelectorAll('.balance-card');
    balanceCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
</script>
</body>
</html>