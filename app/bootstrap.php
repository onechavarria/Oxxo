<?php
declare(strict_types=1);

$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Configura app/config.local.php siguiendo el README.');
}
$config = require $configFile;
date_default_timezone_set('America/Santiago');
ini_set('display_errors', '0');
if (!session_start([
    'cookie_secure' => str_starts_with($config['url'], 'https://'),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
])) {
    http_response_code(503);
    exit('No se pudo iniciar la sesión. Revisa la configuración de PHP.');
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Cache-Control: no-store');

function db(): PDO
{
    global $config;
    static $pdo;
    return $pdo ??= new PDO($config['dsn'], $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function h(string|int|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clp(int $amount): string
{
    return '$' . number_format($amount, 0, ',', '.');
}

function url(string $page = 'home', array $params = []): string
{
    return './?' . http_build_query(['p' => $page] + $params);
}

function go(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params), true, 303);
    exit;
}

function token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}

function fields(string $action): string
{
    return '<input type="hidden" name="csrf" value="' . h(token()) . '">'
        . '<input type="hidden" name="action" value="' . h($action) . '">';
}

function post(string $key): string
{
    return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
}

function input(string $key): string
{
    return is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
}

function user(): ?array
{
    return isset($_SESSION['uid']) ? (query('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null) : null;
}

function signIn(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    unset($_SESSION['csrf']);
}

function flash(string $message, bool $error = false): void
{
    $_SESSION['flash'] = [$message, $error];
}

function limitAuth(string $purpose): void
{
    $bucket = hash('sha256', $purpose . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $now = time();
    query('INSERT INTO login_limits VALUES (?,1,?) ON DUPLICATE KEY UPDATE
           attempts=IF(window_start<?,1,attempts+1), window_start=IF(window_start<?,?,window_start)',
        [$bucket, $now, $now-900, $now-900, $now]);
    if ((int)query('SELECT attempts FROM login_limits WHERE bucket=?', [$bucket])->fetchColumn() > 20) {
        throw new DomainException('Demasiados intentos. Vuelve a probar en unos minutos.');
    }
}
