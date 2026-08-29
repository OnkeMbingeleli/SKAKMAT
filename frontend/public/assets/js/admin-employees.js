/**
 * Employees page (admin-employees.php): searchable/filterable staff
 * directory with add / edit / delete. Matches the markup + CSS shipped
 * in that view (employee-card, employee-card-main, employee-meta-list,
 * employee-status-pill, employee-actions, modal-backdrop/panel/form).
 * Relies on api.js for apiCall()/getToken()/API_URL.
 */
(function () {
    'use strict';

    const PAGE_SIZE = 12;
    const el = (id) => document.getElementById(id);

    let state = {
        page: 1,
        totalPages: 1,
        search: '',
        department: '',
        position: '',
        employees: [],
        departments: [],
        positions: [],
    };

    function toast(message, isError) {
        const region = document.querySelector('.toast-region');
        if (!region) { alert(message); return; }
        const node = document.createElement('div');
        node.className = 'toast ' + (isError ? 'error' : 'success');
        node.textContent = message;
        region.appendChild(node);
        setTimeout(() => node.remove(), 3500);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function initials(first, last) {
        return ((first || '').charAt(0) + (last || '').charAt(0)).toUpperCase() || '?';
    }

    function debounce(fn, wait) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    }

    // ---------------------------------------------------------------
    // Load
    // ---------------------------------------------------------------

    async function loadEmployees() {
        const grid = el('employeesBody');
        grid.innerHTML = '';
        el('employeesEmpty').classList.add('hidden');

        const params = new URLSearchParams({
            role: 'staff',
            attendance: 'true',
            limit: String(PAGE_SIZE),
            page: String(state.page),
        });
        if (state.search) params.set('search', state.search);
        if (state.department) params.set('department', state.department);
        if (state.position) params.set('position', state.position);

        const res = await apiCall(`/api/users?${params.toString()}`);
        if (!res || res.success === false) {
            el('employeesEmpty').classList.remove('hidden');
            el('employeesEmpty').textContent = (res && res.error) || 'Could not load employees.';
            return;
        }

        state.employees = res.data || [];
        const meta = res.meta || {};
        state.departments = meta.departments || [];
        state.positions = meta.positions || [];
        const total = meta.total ?? state.employees.length;
        state.totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

        populateDepartmentControls();
        renderEmployees();
        renderPagination();
    }

    function fillSelect(selectEl, options, placeholder, selected) {
        const current = selected !== undefined ? selected : selectEl.value;
        selectEl.innerHTML = (placeholder ? `<option value="">${placeholder}</option>` : '') +
            options.map(o => `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`).join('');
        selectEl.value = options.includes(current) ? current : (placeholder ? '' : (options[0] || ''));
    }

    function populateDepartmentControls() {
        fillSelect(el('employeeDepartment'), state.departments, 'All departments');
        fillSelect(el('employeePosition'), state.positions, 'All positions');
    }

    // ---------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------

    function renderEmployees() {
        const grid = el('employeesBody');
        const empty = el('employeesEmpty');

        if (!state.employees.length) {
            grid.innerHTML = '';
            empty.classList.remove('hidden');
            empty.textContent = 'No employee records found. Adjust filters to try again.';
            return;
        }
        empty.classList.add('hidden');

        grid.innerHTML = state.employees.map(emp => {
            const onsite = emp.status === 'onsite';
            return `
            <div class="employee-card" data-id="${emp.id}">
                <div class="employee-card-main">
                    <div class="employee-avatar">${initials(emp.first_name, emp.last_name)}</div>
                    <div class="employee-copy">
                        <h3>${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</h3>
                        <p><span class="employee-card-icon employee-mail-icon" aria-hidden="true"></span>${escapeHtml(emp.email)}</p>
                    </div>
                </div>
                <div class="employee-meta-list">
                    <p><span class="employee-card-icon employee-department-icon" aria-hidden="true"></span>${escapeHtml(emp.department || '—')} · ${escapeHtml(emp.position || '—')}</p>
                </div>
                <span class="employee-status-pill ${onsite ? 'is-onsite' : 'is-offsite'}">
                    <span></span>${onsite ? 'Onsite' : 'Offsite'}
                </span>
                <div class="employee-actions" style="margin-top:14px;">
                    <button type="button" class="detail-button" data-action="edit" data-id="${emp.id}">
                        <span class="edit-icon" aria-hidden="true"></span>Edit
                    </button>
                    <button type="button" class="employee-delete-button" data-action="delete" data-id="${emp.id}" aria-label="Remove employee">
                        <span class="trash-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        `;
        }).join('');
    }

    function renderPagination() {
        el('employeesPaginationLabel').textContent = `Page ${state.page} of ${state.totalPages}`;
        el('employeesPrevPage').disabled = state.page <= 1;
        el('employeesNextPage').disabled = state.page >= state.totalPages;
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    function openCreateModal() {
        const form = el('employeeCreateForm');
        form.reset();
        fillSelect(form.department, state.departments, '', state.departments[0]);
        el('employeeCreateModal').classList.remove('hidden');
        document.body.classList.add('modal-open');
    }
    function closeCreateModal() {
        el('employeeCreateModal').classList.add('hidden');
        document.body.classList.remove('modal-open');
    }

    async function submitCreate(event) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('.employee-create-submit');
        const original = btn.textContent;

        const payload = {
            first_name: form.first_name.value.trim(),
            last_name: form.last_name.value.trim(),
            email: form.email.value.trim(),
            department: form.department.value,
            position: form.position.value.trim(),
        };

        btn.disabled = true;
        btn.textContent = 'Creating…';
        try {
            const res = await apiCall('/api/users/staff', { method: 'POST', body: JSON.stringify(payload) });
            if (!res || res.success === false) {
                throw new Error((res && res.error) || 'Could not create employee.');
            }
            closeCreateModal();
            toast(`${payload.first_name} was added. Login details were emailed to them.`);
            state.page = 1;
            await loadEmployees();
        } catch (error) {
            toast(error.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = original;
        }
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    async function openEditModal(id) {
        const emp = state.employees.find(e => String(e.id) === String(id));
        if (!emp) return;
        const form = el('employeeEditForm');
        form.dataset.employeeId = id;
        form.first_name.value = emp.first_name || '';
        form.last_name.value = emp.last_name || '';
        form.email.value = emp.email || '';
        fillSelect(form.department, state.departments, '', emp.department);
        form.position.value = emp.position || '';

        el('detailAttendanceCount').textContent = '…';
        el('detailCheckins').textContent = '…';
        el('detailCheckouts').textContent = '…';
        el('detailLateArrivals').textContent = '…';
        el('detailLastSeen').textContent = '…';

        el('employeeEditModal').classList.remove('hidden');
        document.body.classList.add('modal-open');

        const res = await apiCall(`/api/users/${id}`);
        if (res && res.success !== false && res.data) {
            const a = res.data.attendance || {};
            el('detailAttendanceCount').textContent = a.attendance_count ?? 0;
            el('detailCheckins').textContent = a.total_checkins ?? 0;
            el('detailCheckouts').textContent = a.total_checkouts ?? 0;
            el('detailLateArrivals').textContent = a.late_arrivals ?? 0;
            el('detailLastSeen').textContent = a.last_seen || '—';
        }
    }
    function closeEditModal() {
        el('employeeEditModal').classList.add('hidden');
        document.body.classList.remove('modal-open');
    }

    async function submitEdit(event) {
        event.preventDefault();
        const form = event.target;
        const id = form.dataset.employeeId;
        const btn = form.querySelector('.employee-create-submit');
        const original = btn.textContent;

        const payload = {
            first_name: form.first_name.value.trim(),
            last_name: form.last_name.value.trim(),
            email: form.email.value.trim(),
            department: form.department.value,
            position: form.position.value.trim(),
        };

        btn.disabled = true;
        btn.textContent = 'Saving…';
        try {
            const res = await apiCall(`/api/users/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
            if (!res || res.success === false) {
                throw new Error((res && res.error) || 'Could not update employee.');
            }
            closeEditModal();
            toast('Employee details updated.');
            await loadEmployees();
        } catch (error) {
            toast(error.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = original;
        }
    }

    // ---------------------------------------------------------------
    // Delete
    // ---------------------------------------------------------------

    let pendingDeleteId = null;

    function openDeleteModal(id) {
        const emp = state.employees.find(e => String(e.id) === String(id));
        pendingDeleteId = id;
        el('employeeDeleteName').textContent = emp ? `${emp.first_name} ${emp.last_name}` : 'this employee';
        el('employeeDeleteModal').classList.remove('hidden');
        document.body.classList.add('modal-open');
    }
    function closeDeleteModal() {
        pendingDeleteId = null;
        el('employeeDeleteModal').classList.add('hidden');
        document.body.classList.remove('modal-open');
    }

    async function confirmDelete() {
        if (!pendingDeleteId) return;
        const btn = el('confirmEmployeeDelete');
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Removing…';
        try {
            const res = await apiCall(`/api/users/${pendingDeleteId}`, { method: 'DELETE' });
            if (!res || res.success === false) {
                throw new Error((res && res.error) || 'Could not remove employee.');
            }
            toast('Employee removed.');
            closeDeleteModal();
            if (state.employees.length === 1 && state.page > 1) state.page -= 1;
            await loadEmployees();
        } catch (error) {
            toast(error.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = original;
        }
    }

    // ---------------------------------------------------------------
    // Bulk import
    // ---------------------------------------------------------------

    function importHeader(value) {
        return String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    }

    function parseImportCsv(text) {
        const lines = text.split(/\r?\n/).filter(line => line.trim());
        if (lines.length < 2) return [];
        const split = line => line.split(/\t|,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/).map(value => value.replace(/^\"|\"$/g, '').trim());
        const headers = split(lines.shift()).map(importHeader);
        return lines.map(line => Object.fromEntries(split(line).map((value, i) => [headers[i], value])));
    }

    function importRows(rows) {
        return rows.map(row => {
            const values = Object.fromEntries(Object.entries(row).map(([key, value]) => [importHeader(key), value]));
            return { first_name: values.first_name || values.firstname || values.name || '', last_name: values.last_name || values.lastname || values.surname || '', email: values.email || values.email_address || '', department: values.department || 'General', position: values.position || values.job_title || 'Staff member' };
        }).filter(row => row.first_name && row.last_name && row.email);
    }

    async function readImportFile(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        if (extension === 'json') {
            const data = JSON.parse(await file.text());
            return importRows(Array.isArray(data) ? data : (data.employees || data.rows || []));
        }
        if (['xls', 'xlsx', 'ods'].includes(extension)) {
            if (!window.XLSX) throw new Error('Spreadsheet support is unavailable. Refresh and try again.');
            const workbook = XLSX.read(await file.arrayBuffer(), { type: 'array' });
            return importRows(XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], { defval: '' }));
        }
        return importRows(parseImportCsv(await file.text()));
    }

    async function submitImport(event) {
        event.preventDefault();
        const file = el('employeeImportFile').files[0];
        const status = el('employeeImportStatus');
        const button = el('submitEmployeeImport');
        if (!file) return;
        button.disabled = true;
        status.textContent = 'Reading employee file...';
        try {
            const rows = await readImportFile(file);
            if (!rows.length) throw new Error('No valid employee rows were found.');
            const res = await apiCall('/api/employee-imports', { method: 'POST', body: JSON.stringify({ source_name: file.name, rows }) });
            if (!res || res.success === false) throw new Error((res && res.error) || 'Import failed.');
            toast(`${res.data.rows_imported} imported, ${res.data.rows_skipped} skipped.`);
            el('employeeImportModal').classList.add('hidden');
            document.body.classList.remove('modal-open');
            await loadEmployees();
        } catch (error) {
            status.textContent = error.message;
            toast(error.message, true);
        } finally {
            button.disabled = false;
        }
    }

    // ---------------------------------------------------------------
    // Wiring
    // ---------------------------------------------------------------

    function init() {
        el('employeeSearch').addEventListener('input', debounce((e) => {
            state.search = e.target.value.trim();
            state.page = 1;
            loadEmployees();
        }, 350));
        el('employeeDepartment').addEventListener('change', (e) => {
            state.department = e.target.value;
            state.page = 1;
            loadEmployees();
        });
        el('employeePosition').addEventListener('change', (e) => {
            state.position = e.target.value;
            state.page = 1;
            loadEmployees();
        });
        el('employeesPrevPage').addEventListener('click', () => {
            if (state.page > 1) { state.page -= 1; loadEmployees(); }
        });
        el('employeesNextPage').addEventListener('click', () => {
            if (state.page < state.totalPages) { state.page += 1; loadEmployees(); }
        });

        el('openEmployeeCreate').addEventListener('click', openCreateModal);
        el('closeEmployeeCreate').addEventListener('click', closeCreateModal);
        el('cancelEmployeeCreate').addEventListener('click', closeCreateModal);
        el('employeeCreateForm').addEventListener('submit', submitCreate);

        el('closeEmployeeEdit').addEventListener('click', closeEditModal);
        el('cancelEmployeeEdit').addEventListener('click', closeEditModal);
        el('employeeEditForm').addEventListener('submit', submitEdit);

        el('cancelEmployeeDelete').addEventListener('click', closeDeleteModal);
        el('confirmEmployeeDelete').addEventListener('click', confirmDelete);

        el('openEmployeeImport').addEventListener('click', () => {
            el('employeeImportForm').reset();
            el('employeeImportStatus').textContent = '';
            el('employeeImportModal').classList.remove('hidden');
            document.body.classList.add('modal-open');
        });
        el('closeEmployeeImport').addEventListener('click', () => { el('employeeImportModal').classList.add('hidden'); document.body.classList.remove('modal-open'); });
        el('cancelEmployeeImport').addEventListener('click', () => { el('employeeImportModal').classList.add('hidden'); document.body.classList.remove('modal-open'); });
        el('employeeImportForm').addEventListener('submit', submitImport);

        el('employeesBody').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const id = btn.dataset.id;
            if (btn.dataset.action === 'edit') openEditModal(id);
            if (btn.dataset.action === 'delete') openDeleteModal(id);
        });

        [el('employeeCreateModal'), el('employeeEditModal'), el('employeeDeleteModal'), el('employeeImportModal')].forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) { modal.classList.add('hidden'); document.body.classList.remove('modal-open'); }
            });
        });

        loadEmployees();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
