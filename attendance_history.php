<?php
session_start();
require '../db.php';

// Only allow admin/teacher
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    http_response_code(403); echo "Not allowed"; exit;
}
$student_id = intval($_GET['student_id'] ?? 0);
if ($student_id <= 0) { echo "<div class='text-danger'>Invalid student selected.</div>"; exit; }

// Get student info
$stmt = $conn->prepare("SELECT fullname, username, class FROM users WHERE id=? AND role='student' LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows != 1) { echo "<div class='text-danger'>Student not found.</div>"; exit; }
$stu = $res->fetch_assoc();

// Fetch full attendance history (sorted by newest first)
$q = $conn->prepare("SELECT date, status FROM attendance WHERE student_id=? ORDER BY date DESC");
$q->bind_param("i", $student_id);
$q->execute();
$result = $q->get_result();
$records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!-- BOOTSTRAP ICONS (already in your project, but include if needed) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- TIPS + EXPORT BUTTONS -->
<div class="mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div style="font-weight:600;color:#2446a6;">
        <?= htmlspecialchars($stu['fullname']) ?>
        <span style="color:#aaa;">(<?= htmlspecialchars($stu['username']) ?>, Class <?= htmlspecialchars($stu['class']) ?>)</span>
    </div>
    <div>
        <button class="btn btn-sm btn-success me-2" onclick="exportCSV()">Export CSV <i class="bi bi-filetype-csv"></i></button>
        <button class="btn btn-sm btn-danger" onclick="exportPDF()">Export PDF <i class="bi bi-file-pdf"></i></button>
    </div>
</div>
<div class="alert alert-info p-2 mb-2 small" style="font-size:1em;">
    <b>Tip:</b> Use <span class="text-success">Export CSV</span> to open in Excel/Sheets, or <span class="text-danger">Export PDF</span> for print-friendly reports.<br>
    Scroll sideways on mobile to see all columns. Colored icons indicate Present <i class="bi bi-check-circle-fill text-success"></i>, Absent <i class="bi bi-x-circle-fill text-danger"></i>, Leave <i class="bi bi-clock-fill text-warning"></i>.
</div>
<div class="table-responsive">
<table class="history-table table table-bordered align-middle" id="attHistoryTable">
    <thead>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="2" class="text-center text-secondary">No attendance records yet.</td></tr>
        <?php else: ?>
            <?php foreach($records as $r): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($r['date']))) ?></td>
                    <td>
                        <?php
                            $s = $r['status'];
                            if ($s == 'Present') {
                                echo "<span class='att-present'><i class='bi bi-check-circle-fill'></i> Present</span>";
                            } elseif ($s == 'Absent') {
                                echo "<span class='att-absent'><i class='bi bi-x-circle-fill'></i> Absent</span>";
                            } elseif ($s == 'Leave') {
                                echo "<span class='att-leave'><i class='bi bi-clock-fill'></i> Leave</span>";
                            } else {
                                echo htmlspecialchars($s);
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>
<!-- Table styling (inline for standalone AJAX modals) -->
<style>
    .history-table { width:100%; border-collapse:collapse; font-size:1em;}
    .history-table th, .history-table td { padding:7px 12px; border:1px solid #e3ecf8;}
    .history-table th { background:#e0eaff; font-weight:700;}
    .att-present { color:#198754; font-weight:600; }
    .att-absent { color:#d7263d; font-weight:600; }
    .att-leave { color:#fbbf24; font-weight:600;}
    @media (max-width:600px) {
        .history-table th, .history-table td { padding:4px 6px; font-size:.96em;}
    }
</style>
<!-- jsPDF for PDF export (loads only if button is clicked) -->
<script>
function exportCSV() {
    // Export visible table to CSV
    let csv = "Date,Status\n";
    document.querySelectorAll('#attHistoryTable tbody tr').forEach(row => {
        let cols = row.querySelectorAll('td');
        if(cols.length) {
            let date = cols[0].innerText.trim();
            let stat = cols[1].innerText.trim();
            csv += `"${date}","${stat}"\n`;
        }
    });
    // Download CSV
    let blob = new Blob([csv], {type: "text/csv"});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = "attendance_history_<?= htmlspecialchars($stu['username']) ?>.csv";
    document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(url);
}

function exportPDF() {
    // Load jsPDF library if not loaded
    if (!window.jspdf) {
        let s = document.createElement('script');
        s.src = "https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js";
        s.onload = doPDF;
        document.body.appendChild(s);
    } else {
        doPDF();
    }
}

function doPDF() {
    const { jsPDF } = window.jspdf || window.jspdf;
    const doc = new jsPDF({orientation:'p', unit:'pt', format:'a4'});
    doc.setFont("helvetica","normal");
    doc.setFontSize(16);
    doc.text("Attendance History - <?= addslashes($stu['fullname']) ?> (<?= addslashes($stu['username']) ?>)", 32, 40);
    doc.setFontSize(11);
    let rows = [];
    document.querySelectorAll('#attHistoryTable tbody tr').forEach(tr=>{
        let cols = tr.querySelectorAll('td');
        if(cols.length)
            rows.push([cols[0].innerText.trim(), cols[1].innerText.trim()]);
    });
    // Table headers
    let startY = 60;
    doc.setFont(undefined,'bold'); doc.text("Date", 32, startY);
    doc.text("Status", 140, startY);
    doc.setFont(undefined,'normal');
    startY += 10;
    rows.forEach((row,i) => {
        doc.text(row[0], 32, startY+18*i);
        doc.text(row[1], 140, startY+18*i);
    });
    doc.save("attendance_history_<?= htmlspecialchars($stu['username']) ?>.pdf");
}
</script>
