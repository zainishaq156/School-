<?php
session_start();
require '../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = $error = "";

// Check if status column exists in staff_salary table
$check_status_column = $conn->query("SHOW COLUMNS FROM staff_salary LIKE 'status'");
$has_status_column = $check_status_column->num_rows > 0;

// If status column doesn't exist, add it
if (!$has_status_column) {
    $conn->query("ALTER TABLE staff_salary ADD COLUMN status VARCHAR(10) DEFAULT 'paid'");
    $has_status_column = true;
}

// Add new staff (from modal)
if (isset($_POST['add_staff'])) {
    $fullname = trim($_POST['staff_fullname']);
    $designation = trim($_POST['staff_designation']);
    $username = trim($_POST['staff_username']);
    
    if (!$fullname || !$designation || !$username) {
        $error = "Please enter name, designation and username for staff.";
    } else {
        $check = $conn->prepare("SELECT id FROM staff WHERE username=? LIMIT 1");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Staff username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO staff (fullname, designation, username) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullname, $designation, $username);
            if ($stmt->execute()) {
                $message = "Staff added successfully!";
            } else {
                $error = "Could not add staff.";
            }
        }
        $check->close();
    }
}

// Add Salary
if (isset($_POST['add_salary'])) {
    $staff_id = intval($_POST['staff_id']);
    $salary_amount = floatval($_POST['salary_amount']);
    $month = $_POST['month'];
    $year = intval($_POST['year']);
    $date_paid = $_POST['date_paid'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks']);
    $status = isset($_POST['status']) && $_POST['status'] === 'unpaid' ? 'unpaid' : 'paid';

    if ($staff_id && $salary_amount > 0 && $month && $year) {
        // Check if salary record already exists for this staff, month, and year
        $check_stmt = $conn->prepare("SELECT id FROM staff_salary WHERE staff_id=? AND month=? AND year=? LIMIT 1");
        $check_stmt->bind_param("isi", $staff_id, $month, $year);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error = "Salary record already exists for this staff in {$month} {$year}.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            if ($has_status_column) {
                $stmt = $conn->prepare("INSERT INTO staff_salary (staff_id, salary_amount, month, year, date_paid, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idsssss", $staff_id, $salary_amount, $month, $year, $date_paid, $remarks, $status);
            } else {
                $stmt = $conn->prepare("INSERT INTO staff_salary (staff_id, salary_amount, month, year, date_paid, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idssss", $staff_id, $salary_amount, $month, $year, $date_paid, $remarks);
            }
            
            if ($stmt->execute()) {
                $message = "Salary added successfully!";
            } else {
                $error = "Error adding salary.";
            }
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Update Salary
if (isset($_POST['update_salary'])) {
    $salary_id = intval($_POST['salary_id']);
    $salary_amount = floatval($_POST['salary_amount']);
    $month = $_POST['month'];
    $year = intval($_POST['year']);
    $date_paid = $_POST['date_paid'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks']);
    $status = isset($_POST['status']) && $_POST['status'] === 'unpaid' ? 'unpaid' : 'paid';

    if ($salary_id && $salary_amount > 0 && $month && $year) {
        if ($has_status_column) {
            $stmt = $conn->prepare("UPDATE staff_salary SET salary_amount=?, month=?, year=?, date_paid=?, remarks=?, status=? WHERE id=?");
            $stmt->bind_param("dsssssi", $salary_amount, $month, $year, $date_paid, $remarks, $status, $salary_id);
        } else {
            $stmt = $conn->prepare("UPDATE staff_salary SET salary_amount=?, month=?, year=?, date_paid=?, remarks=? WHERE id=?");
            $stmt->bind_param("dssssi", $salary_amount, $month, $year, $date_paid, $remarks, $salary_id);
        }
        
        if ($stmt->execute()) {
            $message = "Salary updated successfully!";
        } else {
            $error = "Error updating salary.";
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Delete Salary
if (isset($_POST['delete_salary'])) {
    $sid = intval($_POST['delete_salary']);
    
    $conn->query("DELETE FROM staff_salary WHERE id=$sid");
    $message = "Salary record deleted successfully!";
}

// Toggle salary status (paid/unpaid) - only if status column exists
if (isset($_POST['toggle_status']) && $has_status_column) {
    $sid = intval($_POST['toggle_status']);
    
    $result = $conn->query("SELECT status FROM staff_salary WHERE id=$sid");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_status = ($row['status'] === 'paid') ? 'unpaid' : 'paid';
        $conn->query("UPDATE staff_salary SET status='$new_status' WHERE id=$sid");
        $message = "Salary status updated successfully!";
    }
}

// Fetch staff for dropdown
$staff = $conn->query("SELECT id, fullname, designation, username FROM staff ORDER BY fullname")->fetch_all(MYSQLI_ASSOC);

// Get filter parameters from GET request
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Check if any filter is active
$is_filtered = !empty($search) || !empty($filter_month) || !empty($filter_year) || !empty($filter_status);

// Build the SQL query with filters - handle status filter only if column exists
$sql = "SELECT ss.*, s.fullname, s.designation FROM staff_salary ss INNER JOIN staff s ON ss.staff_id = s.id";
$conditions = [];
$params = [];
$types = '';

// Add search condition
if (!empty($search)) {
    $conditions[] = "(s.fullname LIKE ? OR s.username LIKE ? OR s.designation LIKE ?)";
    $params[] = "%" . $conn->real_escape_string($search) . "%";
    $params[] = "%" . $conn->real_escape_string($search) . "%";
    $params[] = "%" . $conn->real_escape_string($search) . "%";
    $types .= 'sss';
}

// Add month filter
if (!empty($filter_month)) {
    $conditions[] = "ss.month = ?";
    $params[] = $filter_month;
    $types .= 's';
}

// Add year filter
if (!empty($filter_year)) {
    $conditions[] = "ss.year = ?";
    $params[] = $filter_year;
    $types .= 's';
}

// Add status filter only if column exists
if (!empty($filter_status) && $has_status_column) {
    $conditions[] = "ss.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

// Combine conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add ordering
$sql .= " ORDER BY ss.year DESC, ss.month DESC, ss.date_paid DESC";

// Prepare and execute the query
$salary_list = [];
try {
    $stmt = $conn->prepare($sql);
    if ($stmt !== false) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $res = $stmt->get_result();
        $salary_list = $res->fetch_all(MYSQLI_ASSOC);
    } else {
        // Fallback to simple query if prepared statement fails
        $result = $conn->query($sql);
        if ($result) {
            $salary_list = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    // Fallback to simple query
    $result = $conn->query("SELECT ss.*, s.fullname, s.designation FROM staff_salary ss INNER JOIN staff s ON ss.staff_id = s.id ORDER BY ss.year DESC, ss.month DESC, ss.date_paid DESC");
    $salary_list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Calculate summary statistics
$total_paid = 0;
$total_unpaid = 0;
$total_records = 0;
$paid_records = 0;
$unpaid_records = 0;

foreach ($salary_list as $salary) {
    // Check if status exists in the array, default to 'paid' if not
    $status = isset($salary['status']) ? $salary['status'] : 'paid';
    
    if ($status === 'paid') {
        $total_paid += $salary['salary_amount'];
        $paid_records++;
    } else {
        $total_unpaid += $salary['salary_amount'];
        $unpaid_records++;
    }
    $total_records++;
}

// For edit - preserve filter parameters in edit URL
$edit_salary = null;
if (isset($_GET['edit_salary'])) {
    $edit_id = intval($_GET['edit_salary']);
    $edit_salary = $conn->query("SELECT * FROM staff_salary WHERE id=$edit_id")->fetch_assoc();
}

$months = [
    "January", "February", "March", "April", "May", "June", 
    "July", "August", "September", "October", "November", "December"
];
$current_year = date('Y');

// Designation color mapping
$designation_colors = [
    'Principal' => '#1e40af',
    'Vice Principal' => '#3b82f6',
    'Senior Teacher' => '#10b981',
    'Teacher' => '#0ea5e9',
    'Assistant Teacher' => '#8b5cf6',
    'Admin Staff' => '#f59e0b',
    'Librarian' => '#ef4444',
    'Lab Assistant' => '#ec4899',
    'Peon' => '#6b7280',
    'Driver' => '#14b8a6',
];
$message = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Salary Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Professional Styles -->
    <style>
        :root {
            --primary-blue: #1e40af;
            --primary-light: #3b82f6;
            --secondary-blue: #1e3a8a;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border-light: #e5e7eb;
            --shadow-light: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-medium: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-large: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --status-paid: #10b981;
            --status-unpaid: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Layout Structure */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Professional Sidebar */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: var(--shadow-large);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .school-logo {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: white;
            margin-bottom: 0.5rem;
        }

        .school-subtitle {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        /* Navigation Menu */
        .nav-menu {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-green);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: var(--accent-green);
            font-weight: 600;
        }

        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Sidebar Actions */
        .sidebar-actions {
            padding: 1.5rem;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .action-btn.danger {
            background: var(--accent-red);
            color: white;
        }

        .action-btn.danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: var(--bg-white);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-light);
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Content Area */
        .content-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Statistics Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total::before { background: linear-gradient(90deg, var(--primary-light), var(--accent-green)); }
        .stat-card.paid::before { background: var(--status-paid); }
        .stat-card.unpaid::before { background: var(--status-unpaid); }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .stat-card .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .stat-card .stat-details {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        /* Form Card */
        .form-card {
            background: var(--bg-white);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--accent-green));
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-light);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--bg-white);
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Status Radio Buttons */
        .status-radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .status-label.paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--status-paid);
        }

        .status-label.unpaid {
            background: rgba(239, 68, 68, 0.1);
            color: var(--status-unpaid);
        }

        .status-radio:checked + .status-label {
            border-color: currentColor;
            transform: scale(1.05);
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-light);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-blue);
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-light);
            color: var(--primary-light);
        }

        .btn-outline-primary:hover {
            background: var(--primary-light);
            color: white;
        }

        .btn-success {
            background: var(--accent-green);
            color: white;
        }

        .btn-success:hover {
            background: #0a8f6a;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-info {
            background: #0ea5e9;
            color: white;
        }

        .btn-info:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--status-paid);
        }

        .status-badge.unpaid {
            background: rgba(239, 68, 68, 0.1);
            color: var(--status-unpaid);
        }

        /* Designation Badge */
        .designation-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }

        /* Data Table */
        .data-card {
            background: var(--bg-white);
            border-radius: 1rem;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            margin: 0;
            width: 100%;
        }

        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid var(--border-light);
            font-weight: 600;
            color: var(--text-dark);
            padding: 1rem;
            white-space: nowrap;
            text-align: center;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            text-align: center;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .table tbody tr.unpaid-row {
            background: rgba(239, 68, 68, 0.03);
        }

        .table tbody tr.paid-row {
            background: rgba(16, 185, 129, 0.03);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 3000;
            min-width: 300px;
            box-shadow: var(--shadow-large);
            animation: alertSlideIn 0.3s ease;
        }

        @keyframes alertSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-info {
            background: #e0f2fe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Sidebar Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            background: var(--primary-light);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem;
            cursor: pointer;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 250px;
            }

            .sidebar-open .admin-sidebar {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-container {
                padding: 1rem;
            }

            .top-header {
                padding: 1rem;
            }

            .form-card, .data-card, .stat-card {
                margin: 0 -0.5rem 1rem;
                border-radius: 0.75rem;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .header-title {
                font-size: 1.25rem;
            }

            .table-container {
                font-size: 0.875rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
                margin-bottom: 0.5rem;
            }

            .form-control, .form-select {
                margin-bottom: 0.5rem;
            }

            .sidebar-toggle {
                display: block;
            }
            
            /* Action buttons on mobile */
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-action {
                width: 100%;
                text-align: center;
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }

        /* Tablet Responsiveness */
        @media (min-width: 769px) and (max-width: 1024px) {
            .admin-sidebar {
                width: 220px;
            }
            
            .main-content {
                margin-left: 220px;
            }
            
            .content-container {
                padding: 1.5rem;
            }
            
            .table thead th,
            .table tbody td {
                padding: 0.75rem;
            }
        }

        /* Filter Indicators */
        .filter-indicator {
            font-size: 0.875rem;
            color: var(--accent-orange);
            font-weight: 500;
        }

        .filter-active::after {
            content: '●';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6rem;
            color: var(--accent-orange);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar Toggle Button for Mobile -->
    <button class="sidebar-toggle" onclick="document.body.classList.toggle('sidebar-open')">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="school-logo">DHPS</div>
            <div class="school-subtitle">Staff Salary Management</div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></div>
            <div class="nav-item"><a href="teachers.php" class="nav-link"><i class="bi bi-person-workspace"></i> Teachers</a></div>
            <div class="nav-item"><a href="students.php" class="nav-link"><i class="bi bi-people"></i> Students</a></div>
            <div class="nav-item"><a href="subjects.php" class="nav-link"><i class="bi bi-book"></i> Subjects</a></div>
            <div class="nav-item"><a href="attendance.php" class="nav-link"><i class="bi bi-calendar-check"></i> Attendance</a></div>
            <div class="nav-item"><a href="notices.php" class="nav-link"><i class="bi bi-megaphone"></i> Notice Board</a></div>
            <div class="nav-item"><a href="marks.php" class="nav-link"><i class="bi bi-clipboard2-data"></i> Marks</a></div>
            <div class="nav-item"><a href="timetable.php" class="nav-link"><i class="bi bi-table"></i> Time Table</a></div>
            <div class="nav-item"><a href="fee.php" class="nav-link"><i class="bi bi-cash-coin"></i> Fee Management</a></div>
            <div class="nav-item"><a href="staff_salary.php" class="nav-link active"><i class="bi bi-currency-rupee"></i> Staff Salary</a></div>
            <div class="nav-item"><a href="import_teacher_attendance.php" class="nav-link"><i class="bi bi-person-lines-fill"></i> Teachers Attendance</a></div>
        </nav>

        <div class="sidebar-actions">
            <a href="../logout.php" class="action-btn danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <div class="header-content">
                <h2 class="header-title"><i class="bi bi-currency-rupee"></i> Staff Salary Management</h2>
                <?php if ($is_filtered): ?>
                <div class="filter-indicator">
                    <i class="bi bi-funnel-fill"></i> Filters Active
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-container">
            <?php if ($message): ?>
                <div class="alert alert-success" id="msgbox"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" id="msgbox"><?= $error ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <div class="stat-value">Rs <?= number_format($total_paid + $total_unpaid, 2) ?></div>
                    <div class="stat-label">Total Salary Amount</div>
                    <div class="stat-details"><?= $total_records ?> records</div>
                </div>
                <div class="stat-card paid">
                    <div class="stat-value">Rs <?= number_format($total_paid, 2) ?></div>
                    <div class="stat-label">Paid Salary</div>
                    <div class="stat-details">
                        <span class="status-badge paid"><?= $paid_records ?> paid</span>
                    </div>
                </div>
                <div class="stat-card unpaid">
                    <div class="stat-value">Rs <?= number_format($total_unpaid, 2) ?></div>
                    <div class="stat-label">Unpaid Salary</div>
                    <div class="stat-details">
                        <span class="status-badge unpaid"><?= $unpaid_records ?> unpaid</span>
                    </div>
                </div>
            </div>

            <!-- Add / Edit Form -->
            <div class="form-card">
                <h3 class="mb-4"><i class="bi <?= $edit_salary ? 'bi-pencil' : 'bi-plus-lg' ?>"></i> <?= $edit_salary ? 'Edit Salary Record' : 'Add Staff Salary' ?></h3>
                <form method="post" class="row g-3" id="salaryForm">
                    <?php if ($edit_salary): ?>
                        <input type="hidden" name="salary_id" value="<?= $edit_salary['id'] ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            Staff Member
                            <button type="button" class="btn btn-sm btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                <i class="bi bi-plus-circle"></i> Add Staff
                            </button>
                        </label>
                        <select name="staff_id" class="form-select" required <?= $edit_salary ? "disabled" : "" ?>>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $st): 
                                $designation_color = $designation_colors[$st['designation']] ?? '#6b7280';
                            ?>
                                <option value="<?= $st['id'] ?>"
                                    <?= ($edit_salary && $edit_salary['staff_id'] == $st['id']) ? 'selected' : '' ?>
                                    style="color: <?= $designation_color ?>;">
                                    <?= htmlspecialchars($st['fullname']) ?> (<?= htmlspecialchars($st['designation']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($edit_salary): ?>
                            <input type="hidden" name="staff_id" value="<?= $edit_salary['staff_id'] ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select" required>
                            <option value="">Month</option>
                            <?php foreach ($months as $m): ?>
                                <option value="<?= $m ?>" <?= ($edit_salary && $edit_salary['month'] == $m) ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" required>
                            <?php for ($y = $current_year; $y >= $current_year-5; $y--): ?>
                                <option value="<?= $y ?>" <?= ($edit_salary && $edit_salary['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Salary Amount (Rs)</label>
                        <input type="number" name="salary_amount" step="0.01" class="form-control" required 
                               placeholder="Enter amount" value="<?= $edit_salary['salary_amount'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date Paid</label>
                        <input type="date" name="date_paid" class="form-control" value="<?= $edit_salary['date_paid'] ?? date('Y-m-d') ?>">
                    </div>
                    
                    <?php if ($has_status_column): ?>
                    <div class="col-md-12">
                        <label class="form-label">Status</label>
                        <div class="status-radio-group">
                            <input type="radio" id="status_paid" name="status" value="paid" 
                                   class="status-radio" <?= (isset($edit_salary['status']) && $edit_salary['status'] === 'paid') ? 'checked' : 'checked' ?>>
                            <label for="status_paid" class="status-label paid">Paid</label>
                            
                            <input type="radio" id="status_unpaid" name="status" value="unpaid"
                                   class="status-radio" <?= (isset($edit_salary['status']) && $edit_salary['status'] === 'unpaid') ? 'checked' : '' ?>>
                            <label for="status_unpaid" class="status-label unpaid">Unpaid</label>
                        </div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="status" value="paid">
                    <?php endif; ?>
                    
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" maxlength="100"
                               value="<?= $edit_salary['remarks'] ?? '' ?>" placeholder="(optional)">
                    </div>
                    <div class="col-md-12 mt-2">
                        <button type="submit" class="btn btn-success" name="<?= $edit_salary ? 'update_salary' : 'add_salary' ?>">
                            <i class="bi <?= $edit_salary ? 'bi-pencil' : 'bi-plus-lg' ?>"></i>
                            <?= $edit_salary ? 'Update Salary' : 'Add Salary' ?>
                        </button>
                        <?php if ($edit_salary): ?>
                            <?php 
                            // Build cancel URL with filters preserved
                            $cancel_params = [];
                            if (!empty($search)) $cancel_params[] = "search=" . urlencode($search);
                            if (!empty($filter_month)) $cancel_params[] = "filter_month=" . urlencode($filter_month);
                            if (!empty($filter_year)) $cancel_params[] = "filter_year=" . urlencode($filter_year);
                            if (!empty($filter_status)) $cancel_params[] = "filter_status=" . urlencode($filter_status);
                            $cancel_url = "staff_salary.php" . (!empty($cancel_params) ? "?" . implode("&", $cancel_params) : "");
                            ?>
                            <a href="<?= $cancel_url ?>" class="btn btn-secondary ms-2">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Filter/Search -->
            <div class="data-card">
                <div class="d-flex justify-content-between align-items-center mb-3 p-3">
                    <h3 class="mb-0">Salary Records <?= $is_filtered ? '<small class="text-warning">(Filtered)</small>' : '' ?></h3>
                    <form method="get" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control <?= !empty($search) ? 'border-warning border-2' : '' ?>" 
                               placeholder="Search staff..." value="<?= htmlspecialchars($search) ?>" style="max-width: 200px;">
                        <select name="filter_month" class="form-select <?= !empty($filter_month) ? 'border-warning border-2' : '' ?>" style="max-width: 120px;">
                            <option value="">Month</option>
                            <?php foreach ($months as $m): ?>
                                <option value="<?= $m ?>" <?= $filter_month == $m ? 'selected' : '' ?>><?= substr($m, 0, 3) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_year" class="form-select <?= !empty($filter_year) ? 'border-warning border-2' : '' ?>" style="max-width: 120px;">
                            <option value="">Year</option>
                            <?php for ($y = $current_year; $y >= $current_year-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <?php if ($has_status_column): ?>
                        <select name="filter_status" class="form-select <?= !empty($filter_status) ? 'border-warning border-2' : '' ?>" style="max-width: 120px;">
                            <option value="">Status</option>
                            <option value="paid" <?= $filter_status == 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="unpaid" <?= $filter_status == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        </select>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                        <?php if ($is_filtered): ?>
                            <a href="staff_salary.php" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Clear</a>
                        <?php else: ?>
                            <a href="staff_salary.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Salary Table -->
                <div class="table-container">
                    <?php if (empty($salary_list)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No salary records found. <?= $is_filtered ? 'Try changing your filters.' : '' ?></p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Staff</th>
                                    <th>Designation</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Amount (Rs)</th>
                                    <?php if ($has_status_column): ?>
                                    <th>Status</th>
                                    <?php endif; ?>
                                    <th>Date Paid</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($salary_list as $sal): 
                                    $designation_color = $designation_colors[$sal['designation']] ?? '#6b7280';
                                    $status = isset($sal['status']) ? $sal['status'] : 'paid';
                                    $row_class = $has_status_column && $status === 'paid' ? 'paid-row' : ($has_status_column && $status === 'unpaid' ? 'unpaid-row' : '');
                                    
                                    // Build URLs with all current filter parameters
                                    $base_params = [];
                                    if (!empty($search)) $base_params[] = "search=" . urlencode($search);
                                    if (!empty($filter_month)) $base_params[] = "filter_month=" . urlencode($filter_month);
                                    if (!empty($filter_year)) $base_params[] = "filter_year=" . urlencode($filter_year);
                                    if (!empty($filter_status)) $base_params[] = "filter_status=" . urlencode($filter_status);
                                    $query_string = !empty($base_params) ? "?" . implode("&", $base_params) : "";
                                    
                                    // Build URLs
                                    $edit_url = "staff_salary.php" . ($query_string ? $query_string . "&" : "?") . "edit_salary=" . $sal['id'];
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($sal['fullname']) ?></td>
                                        <td>
                                            <span class="designation-badge" style="background-color: <?= $designation_color ?>;">
                                                <?= htmlspecialchars($sal['designation']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($sal['month']) ?></td>
                                        <td><?= htmlspecialchars($sal['year']) ?></td>
                                        <td><strong>Rs <?= number_format($sal['salary_amount'], 2) ?></strong></td>
                                        <?php if ($has_status_column): ?>
                                        <td>
                                            <span class="status-badge <?= $status ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars($sal['date_paid']) ?></td>
                                        <td><?= htmlspecialchars($sal['remarks']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?= $edit_url ?>" class="btn btn-info btn-action">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                                <?php if ($has_status_column): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="toggle_status" value="<?= $sal['id'] ?>">
                                                    <button type="submit" class="btn btn-warning btn-action"
                                                            onclick="return confirm('Toggle status to <?= $status === 'paid' ? 'unpaid' : 'paid' ?>?')">
                                                        <i class="bi bi-toggle-<?= $status === 'paid' ? 'off' : 'on' ?>"></i> Status
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="delete_salary" value="<?= $sal['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-action"
                                                            onclick="return confirm('Delete this salary record?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Footer -->
            <div class="data-card">
                <div class="p-3">
                    <h5><i class="bi bi-calculator"></i> Summary</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="calculation-row">
                                <span>Total Records:</span>
                                <span><?= $total_records ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="calculation-row">
                                <span>Paid Records:</span>
                                <span class="text-success"><?= $paid_records ?></span>
                            </div>
                        </div>
                        <?php if ($has_status_column): ?>
                        <div class="col-md-3">
                            <div class="calculation-row">
                                <span>Unpaid Records:</span>
                                <span class="text-danger"><?= $unpaid_records ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <div class="calculation-row">
                                <span><strong>Total Amount:</strong></span>
                                <span><strong>Rs <?= number_format($total_paid + $total_unpaid, 2) ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn btn-outline-success ms-2" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addStaffModalLabel"><i class="bi bi-person-plus"></i> Add New Staff</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-12">
          <label class="form-label">Full Name</label>
          <input type="text" name="staff_fullname" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Designation</label>
          <select name="staff_designation" class="form-select" required>
              <option value="">Select Designation</option>
              <?php foreach (array_keys($designation_colors) as $designation): ?>
                  <option value="<?= $designation ?>" style="color: <?= $designation_colors[$designation] ?>;">
                      <?= $designation ?>
                  </option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Username</label>
          <input type="text" name="staff_username" class="form-control" required>
          <small class="text-muted">Unique (used for records)</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_staff" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Staff</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alerts after 3 seconds
setTimeout(function() { 
    var msgbox = document.getElementById('msgbox');
    if (msgbox) msgbox.style.display = 'none'; 
}, 3000);

// Toggle sidebar on mobile
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768 && !e.target.closest('.admin-sidebar') && !e.target.closest('.sidebar-toggle')) {
        document.body.classList.remove('sidebar-open');
    }
});

// Highlight filtered fields
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.querySelector('input[name="search"]');
    var monthSelect = document.querySelector('select[name="filter_month"]');
    var yearSelect = document.querySelector('select[name="filter_year"]');
    var statusSelect = document.querySelector('select[name="filter_status"]');
    
    if (searchInput && searchInput.value) {
        searchInput.classList.add('border-warning', 'border-2');
    }
    if (monthSelect && monthSelect.value) {
        monthSelect.classList.add('border-warning', 'border-2');
    }
    if (yearSelect && yearSelect.value) {
        yearSelect.classList.add('border-warning', 'border-2');
    }
    if (statusSelect && statusSelect.value) {
        statusSelect.classList.add('border-warning', 'border-2');
    }
});
</script>
</body>
</html>