<?php
// Admin Leave Requests: review, filter by status, approve/reject.
$page = 'admin-leave-requests';
$title = 'Leave Requests - CheckMate';
ob_start();
?>

<div class="page-heading">
    <div>
        <h1>Leave Requests</h1>
        <p>Review and manage leave requests from your team.</p>
    </div>
    <div class="topbar-actions">
        <button id="refreshLeaveRequests" class="filter-button" type="button">↻ Refresh</button>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;">
    <div class="filters">
        <button type="button" class="filter-button active leave-status-button" data-status="">All</button>
        <button type="button" class="filter-button leave-status-button" data-status="pending">Pending</button>
        <button type="button" class="filter-button leave-status-button" data-status="approved">Approved</button>
        <button type="button" class="filter-button leave-status-button" data-status="rejected">Rejected</button>
    </div>
</div>

<div class="panel requests-panel">
    <div class="panel-header">
        <h2>Requests</h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Days left</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="leaveRequestsBody"></tbody>
        </table>
    </div>

    <div id="leaveRequestsEmpty" class="table-state hidden">
        No leave requests available yet. New requests will appear here.
    </div>
</div>

<div class="toast-region"></div>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/leave.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
