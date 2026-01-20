<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$student = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
$student_id = $student['id'];
$class = $student['class'];
$fullname = $student['fullname'];
$rollno = $student['username'];

// Get student's individual fee record (must be paid)
$fee = $conn->query("SELECT * FROM fees WHERE student_id=$student_id AND status='Paid' ORDER BY paid_date DESC LIMIT 1")->fetch_assoc();

if (!$fee) {
    // Optionally, allow receipt for class fee if marked paid
    $fee = $conn->query("SELECT * FROM fees WHERE class='$class' AND student_id IS NULL AND status='Paid' ORDER BY paid_date DESC LIMIT 1")->fetch_assoc();
    if (!$fee) {
        echo "<div class='d-flex align-items-center justify-content-center' style='height:90vh;'><div class='alert alert-warning text-center'><strong>No paid fee record found.</strong><br>Please contact the office if you think this is an error.</div></div>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt - DAR UL HUDA PUBLIC SCHOOL SANGHOI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Arial, sans-serif; }
        .receipt-container {
            max-width: 540px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px #b5cff43d;
            padding: 34px 18px 20px 18px;
        }
        .school-header {
            text-align: center;
            font-family: 'Montserrat', Arial, sans-serif;
            color: #2446a6;
            font-size: 1.38em;
            font-weight: 700;
            letter-spacing: 1.2px;
            margin-bottom: 7px;
        }
        .school-address {
            text-align: center;
            color: #3b4563;
            font-size: 1.02em;
            margin-bottom: 17px;
            letter-spacing: .2px;
        }
        .receipt-title {
            text-align: center;
            color: #437ef7;
            font-size: 1.15em;
            font-weight: 600;
            letter-spacing: 1px;
            margin: 18px 0 20px 0;
        }
        .receipt-table th, .receipt-table td {
            padding: 8px 8px;
            border-bottom: 1px solid #e0eaf5;
            font-size: 1em;
        }
        .receipt-table th {
            width: 40%; color: #29467d; background: #f7fbfe; font-weight: 600;
        }
        .receipt-table td { color: #264066; }
        .amount { font-weight: 700; color: #1f9206; font-size: 1.12em;}
        .status-paid { color: #16a148; font-weight: 700;}
        .status-unpaid { color: #e63319; font-weight: 700;}
        .receipt-footer {
            text-align: right;
            margin-top: 19px;
            color: #888;
            font-size: 0.98em;
        }
        .print-btn {
            background: #2446a6;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 10px 36px;
            font-weight: 600;
            font-size: 1.08em;
            cursor: pointer;
            margin-top: 16px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: background 0.17s;
        }
        .print-btn:active,
        .print-btn:focus,
        .print-btn:hover { background: #122356; outline: none;}
        @media (max-width: 650px) {
            .receipt-container { padding: 20px 3vw 20px 3vw; }
            .school-header { font-size: 1.04em;}
        }
        @media print {
            .print-btn { display: none !important; }
            body { background: #fff; }
            .receipt-container { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
<div class="receipt-container shadow-lg">
    <div class="school-header">DAR UL HUDA PUBLIC SCHOOL SANGHOI</div>
    <div class="school-address">
        Village Sanghoi, Jhelum, Punjab, Pakistan<br>
        Phone: 0333-xxxxxxx
    </div>
    <div class="receipt-title">FEE RECEIPT</div>
    <div class="table-responsive">
    <table class="receipt-table table mb-1">
        <tr><th>Receipt Date</th> <td><?= date('d M Y') ?></td></tr>
        <tr><th>Student Name</th> <td><?= htmlspecialchars($fullname) ?></td></tr>
        <tr><th>Student ID</th> <td><?= htmlspecialchars($rollno) ?></td></tr>
        <tr><th>Class</th> <td><?= htmlspecialchars($class) ?></td></tr>
        <tr><th>Fee Amount</th> <td class="amount"><?= number_format($fee['fee_amount'],0) ?></td></tr>
        <tr><th>Discount</th> <td><?= number_format($fee['discount'] ?? 0,0) ?></td></tr>
        <tr><th>Status</th>
            <td class="<?= ($fee['status']=='Paid'?'status-paid':'status-unpaid') ?>"><?= $fee['status'] ?? 'Not Paid' ?></td>
        </tr>
        <tr><th>Due Date</th> <td><?= htmlspecialchars($fee['due_date']) ?></td></tr>
        <tr><th>Paid Date</th> <td><?= htmlspecialchars($fee['paid_date'] ?? '-') ?></td></tr>
        <?php if(!empty($fee['note'])): ?>
        <tr><th>Note</th> <td><?= htmlspecialchars($fee['note']) ?></td></tr>
        <?php endif; ?>
    </table>
    </div>
    <div class="receipt-footer">
        Generated on <?= date('d M Y, h:i a') ?> | Printed by School ERP
    </div>
    <button class="print-btn mt-3" onclick="window.print()" aria-label="Print this receipt">Print Receipt</button>
</div>
</body>
</html>
