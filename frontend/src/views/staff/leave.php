<?php
// Staff Leave: submit a leave request, see RSA leave balance, view own history.
$page = 'staff-leave';
$title = 'Leave - CheckMate';
ob_start();
?>

<div class="page-heading">
    <div>
        <h1>Leave</h1>
        <p>Submit your leave request and track its status from one place.</p>
    </div>
</div>

<div class="leave-grid">
    <div class="panel">
        <div class="panel-header">
            <h2>Submit a request</h2>
        </div>

        <div id="leaveBalanceSummary" class="leave-balance-grid"></div>

        <div id="leaveWarning" class="leave-warning hidden"></div>

        <form id="leaveRequestForm" class="leave-form">
            <label>
                <span>Leave type</span>
                <select name="leave_type" required>
                    <option value="">Select leave type</option>
                    <option value="annual">Annual</option>
                    <option value="sick">Sick</option>
                    <option value="family responsibility">Family responsibility</option>
                    <option value="study leave">Study leave</option>
                    <option value="maternity leave">Maternity leave</option>
                    <option value="paternity leave">Paternity leave</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </label>

            <label>
                <span>Start date</span>
                <input type="date" name="start_date" required>
            </label>

            <label>
                <span>End date</span>
                <input type="date" name="end_date" required>
            </label>

            <label>
                <span>Reason</span>
                <textarea name="reason" placeholder="Optional details" rows="4"></textarea>
            </label>

            <p class="modal-note" id="leaveRequestError" style="color:var(--red); display:none;"></p>

            <button type="submit" class="primary-button" id="leaveSubmitBtn">Submit request</button>
        </form>
    </div>

    <div class="panel history-panel">
        <div class="panel-header">
            <h2>Your requests</h2>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="leaveRequestsBody"></tbody>
            </table>
        </div>

        <div id="leaveRequestsEmpty" class="table-state hidden">
            No leave requests found. Submit one using the form.
        </div>
    </div>
</div>

<div class="toast-region"></div>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/leave.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
