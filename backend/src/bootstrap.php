<?php
// Every API response must be pure JSON. If ANY PHP notice/warning/deprecation
// gets printed to the body (a missing array key, a bad date format, whatever),
// it corrupts the JSON and the frontend fails with "Unexpected token '<'".
// Log errors instead of echoing them as HTML into the response body.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Buffer everything so jsonResponse() can discard any stray output (a
// warning, a BOM character, whitespace before an opening <?php tag in a
// required file, etc.) before it ever reaches the client.
ob_start();

// Qaasim fvcked up
// ------------------- Load .env file (no dependencies) -------------------
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove surrounding quotes if present
            if (preg_match('/^(["\']).*\1$/', $value)) {
                $value = substr($value, 1, -1);
            }
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Load .env from project root
loadEnv(__DIR__ . '/../.env');

// After loading .env, make JWT_SECRET available as a constant
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-me');
}
/**
 * Bootstrap – database connection, CORS, autoloader, helpers
 */

// Defensive session start: on a fresh XAMPP/PHP install the default session
// save path is sometimes missing or not writable, which makes session_start()
// throw a PHP warning. Since display_errors is commonly On in dev, that
// warning gets printed as raw HTML *before* our JSON body, which breaks
// every single API response with "Unexpected token '<'" on the frontend.
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();
    if (!$sessionPath || !is_dir($sessionPath) || !is_writable($sessionPath)) {
        session_save_path(sys_get_temp_dir());
    }
    @session_start();
}

// ------------------- CORS -----------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ------------------- Database Connection -------------------
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Without this, a slow/unreachable/sleeping remote database (e.g.
            // a Railway free-tier MySQL instance waking from idle) makes
            // every single request — including login — hang indefinitely
            // with zero feedback to the user. Fail fast instead so the
            // frontend's fetch() actually gets a response (or a clear
            // connection error) within a few seconds.
            PDO::ATTR_TIMEOUT             => 8,
        ]);

        // Server's default session timezone is UTC, but the app runs in
        // SAST (UTC+2) — without this, every NOW() call (emergency
        // start/end, marked_safe_at, attendance timestamps, etc.) gets
        // written 2 hours behind local time.
        $pdo->exec("SET time_zone = '+02:00'");
    }
    return $pdo;
}

// ------------------- Simple Autoloader -------------------
spl_autoload_register(function ($class) {
    // PHPMailer is distributed with this application in
    // backend/vendor/phpmailer/phpmailer, so production deployments do not
    // need Composer or an additional package-install step.
    $mailerPrefix = 'PHPMailer\\PHPMailer\\';
    $mailerPrefixLength = strlen($mailerPrefix);
    if (strncmp($mailerPrefix, $class, $mailerPrefixLength) === 0) {
        $mailerClass = substr($class, $mailerPrefixLength);
        $mailerFile = __DIR__ . '/../vendor/phpmailer/phpmailer/src/' . $mailerClass . '.php';

        if (is_file($mailerFile)) {
            require $mailerFile;
        }

        return;
    }

    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

// ------------------- JSON Helper -------------------
function jsonResponse($data, int $code = 200): void {
    // Belt-and-braces: if something upstream already leaked output (a stray
    // echo, a warning that slipped through, a BOM in a required file), throw
    // it away right before we send the real JSON body rather than prefixing
    // it and breaking every client-side JSON.parse()/response.json() call.
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($code);
    echo json_encode($data);
    exit;
}
