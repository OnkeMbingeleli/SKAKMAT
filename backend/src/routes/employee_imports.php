<?php
// This route is intentionally a no-op: POST /api/employee-imports is already
// handled inside routes/users.php (which already instantiates
// EmployeeImportController). Keeping both would run the import twice on
// every request. This file is kept (rather than deleted, since this
// environment has no file-delete tool) so a future glob doesn't need to
// change, but it does nothing.
