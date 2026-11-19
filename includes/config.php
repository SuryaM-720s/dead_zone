<?php
/**
 * config.php
 * Shared bootstrap loaded at the top of every page.
 * - Starts the PHP session (must happen before any HTML output)
 * - Defines file paths and small helper functions used everywhere
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Dead Zone: Downtown');
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_FILE', DATA_DIR . '/users.json');

/**
 * Escape any value before printing it into HTML (prevents XSS).
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape story text and set it with proper typography:
 * curly quotes, real apostrophes and a single-character ellipsis.
 */
function prose(string $text): string
{
    $text = str_replace('...', '…', $text);
    $text = preg_replace('/"([^"]*)"/', '“$1”', $text);  // paired double quotes
    $text = str_replace("'", '’', $text);                 // apostrophes
    return e($text);
}

/**
 * Send the browser to another page and stop running this script.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Queue a one-time message (success / error / info) for the next page load.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Read and clear all queued flash messages.
 */
function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Create or retrieve the session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Render an escaped hidden CSRF field for POST forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Reject POST requests without the current session CSRF token. */
function csrf_check(): void
{
    $given = $_POST['csrf'] ?? null;
    if (!is_string($given) || !hash_equals(csrf_token(), $given)) {
        flash('error', 'Your form expired. Please try again.');
        $page = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? 'index.php', PHP_URL_PATH));
        redirect($page ?: 'index.php');
    }
}
