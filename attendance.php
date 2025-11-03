<?php
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Attendance Management';
$current_page = 'attendance';

// Initialize variables
$success = '';
$error = '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Set month range variables
$month_start = date('Y-m-01', strtotime($selected_month));
$month_end = date('Y-m-t', strtotime($selected_month));

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'mark_attendance') {
        $date = $_POST['date'];
        $attendances = $_POST['attendance'];
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            foreach ($attendances as $attendant_id => $data) {
                $status = $data['status'];
                $time_in = !empty($data['time_in']) ? $data['time_in'] : NULL;
                $time_out = !empty($data['time_out']) ? $data['time_out'] : NULL;
                
                // Check if attendance already exists
                $check_sql = "SELECT attendance_id FROM attendance WHERE attendant_id = ? AND date = ?";
                $check_stmt = prepare_query($check_sql, [$attendant_id, $date]);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    // Update existing attendance
                    $update_sql = "UPDATE attendance 
                                 SET status = ?, time_in = ?, time_out = ?, approved_by = ?
                                 WHERE attendant_id = ? AND date = ?";
                    $stmt = prepare_query($update_sql, [$status, $time_in, $time_out, $_SESSION['user_id'], $attendant_id, $date]);
                } else {
                    // Insert new attendance
                    $insert_sql = "INSERT INTO attendance (attendant_id, date, status, time_in, time_out, approved_by)
                                 VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = prepare_query($insert_sql, [$attendant_id, $date, $status, $time_in, $time_out, $_SESSION['user_id']]);
                }
                
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            $success = 'Attendance marked successfully';
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = 'Error marking attendance: ' . $e->getMessage();
        }
    }
}

// Get all active employees
$employees_query = "SELECT attendant_id, name FROM attendants WHERE status = 'active' ORDER BY name";
$employees_result = $conn->query($employees_query);
$employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $employees[] = $row;
}

// Get existing attendance for selected date
$existing_attendance = [];
$existing_query = "SELECT * FROM attendance WHERE date = ?";
$existing_stmt = prepare_query($existing_query, [$selected_date]);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();
while ($row = $existing_result->fetch_assoc()) {
    $existing_attendance[$row['attendant_id']] = $row;
}

// Get attendance data for selected month
$attendance_query = "SELECT a.*, att.name as employee_name 
                    FROM attendance a
                    JOIN attendants att ON a.attendant_id = att.attendant_id
                    WHERE a.date BETWEEN ? AND ?
                    ORDER BY a.date DESC, att.name";
$attendance_stmt = prepare_query($attendance_query, [$month_start, $month_end]);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Mark Attendance Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mark Daily Attendance</h5>
            <a href="employees.php" class="btn btn-light btn-sm">
                <i class="fas fa-users me-1"></i>Employee List
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="attendanceForm">
                <input type="hidden" name="action" value="mark_attendance">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="date" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo $selected_date; ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-8">
                        <div class="mt-4">
                            <button type="button" class="btn btn-secondary btn-sm me-2" onclick="setAllStatus('present')">
                                All Present
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="setAllStatus('absent')">
                                All Absent
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%">Employee</th>
                                <th style="width: 20%">Status</th>
                                <th style="width: 25%">Time In</th>
                                <th style="width: 25%">Time Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <?php 
                                $existing = $existing_attendance[$employee['attendant_id']] ?? null;
                                $status = $existing ? $existing['status'] : 'present';
                                $time_in = $existing ? $existing['time_in'] : '';
                                $time_out = $existing ? $existing['time_out'] : '';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td>
                                        <select class="form-select status-select" 
                                                name="attendance[<?php echo $employee['attendant_id']; ?>][status]" required>
                                            <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="leave" <?php echo $status === 'leave' ? 'selected' : ''; ?>>Leave</option>
                                            <option value="half-day" <?php echo $status === 'half-day' ? 'selected' : ''; ?>>Half Day</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-input" 
                                               name="attendance[<?php echo $employee['attendant_id']; ?>][time_in]"
                                               value="<?php echo $time_in; ?>">
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-input" 
                                               name="attendance[<?php echo $employee['attendant_id']; ?>][time_out]"
                                               value="<?php echo $time_out; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Monthly Attendance View -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Monthly Attendance Record</h5>
            <button class="btn btn-light btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-1"></i>Export to Excel
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="month" class="form-label">Select Month</label>
                    <input type="month" class="form-control" id="month" 
                           value="<?php echo $selected_month; ?>"
                           onchange="window.location.href='?month='+this.value">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="attendanceTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Working Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $attendance_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $record['status'] === 'present' ? 'success' : 
                                            ($record['status'] === 'absent' ? 'danger' : 
                                            ($record['status'] === 'leave' ? 'warning' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                <td>
                                    <?php
                                    if ($record['time_in'] && $record['time_out']) {
                                        $time_in = strtotime($record['time_in']);
                                        $time_out = strtotime($record['time_out']);
                                        $diff = round(($time_out - $time_in) / 3600, 1);
                                        echo $diff . ' hrs';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($attendance_result->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    const table = document.getElementById('attendanceTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Attendance"});
    const fileName = 'attendance_report_<?php echo $selected_month; ?>.xlsx';
    XLSX.writeFile(wb, fileName);
}

function setAllStatus(status) {
    document.querySelectorAll('.status-select').forEach(select => {
        select.value = status;
    });
}

// Initialize tooltips
$(document).ready(function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Handle date change
    $('#date').on('change', function() {
        window.location.href = '?date=' + this.value;
    });

    // Handle status change
    $('.status-select').on('change', function() {
        const row = $(this).closest('tr');
        const timeInputs = row.find('.time-input');
        
        if (this.value === 'absent' || this.value === 'leave') {
            timeInputs.val('').prop('disabled', true);
        } else {
            timeInputs.prop('disabled', false);
        }
    });

    // Initialize status-based time input states
    $('.status-select').trigger('change');
});
</script>

<style>
.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.form-control[type="time"] {
    width: 140px;
}

.card-header {
    background-color: #0d6efd !important;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.9);
}

.btn-light:hover {
    background-color: #fff;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.status-select {
    min-width: 120px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 