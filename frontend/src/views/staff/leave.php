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

        <form id="leaveRequestForm" class="leave-form">
            <label>
                <span>Leave type</span>
                <select name="leave_type" required>
                    <option value="">Select leave type</option>
                </select>
            </label>

            <label>
                <span>Start date</span>
                <input type="date" name="start_date" required />
            </label>

            <label>
                <span>End date</span>
                <input type="date" name="end_date" required />
            </label>

            <label>
                <span>Reason</span>
                <textarea name="reason" placeholder="Optional details" rows="5"></textarea>
            </label>

            <button type="submit" class="primary-button">Submit request</button>
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
                        <th>ID</th>
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

        <div id="leaveRequestsEmpty" class="table-state">
            No leave requests found. Submit one using the form.
        </div>
    </div>
</div>

<div class="toast-region"></div>
