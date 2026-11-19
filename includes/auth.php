<?php
/**
 * auth.php
 * Flat-file user storage (data/users.json) and login helpers.
 * Passwords are never stored in plain text: we save password_hash() output
 * and check logins with password_verify().
 */

require_once __DIR__ . '/config.php';

/**
 * Load every registered user as an array keyed by lowercase username.
 */
function load_users(): array
{
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $users = json_decode((string) file_get_contents(USERS_FILE), true);
    return is_array($users) ? $users : [];
}

/**
 * Write the users array back to disk. LOCK_EX stops two registrations
 * from overwriting each other at the same moment.
 */
function save_users(array $users): bool
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    $json = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents(USERS_FILE, $json, LOCK_EX) !== false;
}

/**
 * Look up one user (case-insensitive). Returns null when not found.
 */
function find_user(string $username): ?array
{
    $users = load_users();
    return $users[strtolower($username)] ?? null;
}

/**
 * Create a new account. Returns false if the username is already taken
 * or the file could not be written.
 */
function register_user(string $username, string $password): bool
{
    $users = load_users();
    $key = strtolower($username);
    if (isset($users[$key])) {
        return false;
    }
    $users[$key] = [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('c'),
    ];
    return save_users($users);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Guard for protected pages: anyone without a session goes to login.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Log in to enter the Dead Zone.');
        redirect('login.php');
    }
}
