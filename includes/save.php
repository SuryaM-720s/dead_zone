<?php
/**
 * save.php
 * Save / resume with a cookie, so a run survives logging out or closing the browser.
 *
 * What goes in the cookie: progress only (node, hero stats, route). Never the
 * password and never a score: scores are calculated by the server at the end.
 *
 * Why it can't be cheated by editing the cookie:
 *   1. The cookie is signed with HMAC-SHA256 using a secret key that lives
 *      only on the server (data/secret.key). Change one character and the
 *      signature no longer matches, so PHP throws the save away.
 *   2. The save is tied to the username that created it.
 *   3. Even with a valid signature, every value is re-validated against the
 *      game's own data (real node, real class, real items, stats in range).
 */

require_once __DIR__ . '/engine.php';

const SAVE_COOKIE = 'dz_save';
const SAVE_DAYS   = 7;

/**
 * Server-side signing key. Created once, then read from disk.
 */
function save_secret(): string
{
    $file = DATA_DIR . '/secret.key';
    if (!file_exists($file)) {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
        }
        file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
        @chmod($file, 0600);
    }
    return trim((string) file_get_contents($file));
}

/**
 * Cookie path = this app's folder, so it works on CODD (/~user/Folder/).
 */
function save_cookie_path(): string
{
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
}

function set_save_cookie(string $value, int $expires): void
{
    setcookie(SAVE_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => save_cookie_path(),
        'httponly' => true,   // JavaScript can't read it
        'samesite' => 'Lax',
    ]);
}

/**
 * Write the current run to the cookie (call after every move).
 */
function write_save(): void
{
    if (!has_active_run()) {
        return;
    }
    $payload = [
        'u' => strtolower($_SESSION['user']),
        'n' => $_SESSION['node'],
        'h' => $_SESSION['hero'],
        'v' => $_SESSION['visited'],
        // Route stored compactly as [node id, choice index] pairs
        'p' => array_map(function ($step) {
            return [$step['node_id'], $step['index']];
        }, $_SESSION['choices_log']),
        't' => $_SESSION['run_started'],
    ];
    $data = base64_encode((string) json_encode($payload));
    $signature = hash_hmac('sha256', $data, save_secret());
    set_save_cookie($data . '.' . $signature, time() + SAVE_DAYS * 86400);
}

function clear_save(): void
{
    if (isset($_COOKIE[SAVE_COOKIE])) {
        set_save_cookie('', time() - 3600);
        unset($_COOKIE[SAVE_COOKIE]);
    }
}

/**
 * Check every field of a saved hero against the game's rules.
 * Returns the cleaned hero, or null if anything is invalid.
 */
function validate_hero($hero): ?array
{
    if (!is_array($hero)) {
        return null;
    }
    $classes = class_defs();
    $items = item_defs();

    $class = $hero['class'] ?? '';
    if (!is_string($class) || !isset($classes[$class])) {
        return null;
    }
    $name = $hero['name'] ?? '';
    if (!is_string($name) || !preg_match("/^[A-Za-z][A-Za-z '\\-]{1,19}$/", $name)) {
        return null;
    }

    // Integer stats must be whole numbers inside their legal range
    $limits = [
        'health'    => [1, $classes[$class]['health']],
        'stamina'   => [0, 100],
        'luck'      => [$classes[$class]['luck'], $classes[$class]['luck']], // luck never changes
        'noise'     => [0, MAX_NOISE],
        'infection' => [0, 99],
        'survivors' => [0, 10],
        'moves'     => [0, 100],
    ];
    $clean = ['name' => $name, 'class' => $class, 'max_health' => $classes[$class]['health']];
    foreach ($limits as $stat => [$min, $max]) {
        $value = $hero[$stat] ?? null;
        if (!is_int($value) || $value < $min || $value > $max) {
            return null;
        }
        $clean[$stat] = $value;
    }

    if (!is_bool($hero['infected'] ?? null)) {
        return null;
    }
    $clean['infected'] = $hero['infected'];

    $inventory = $hero['inventory'] ?? null;
    if (!is_array($inventory) || count($inventory) > 15) {
        return null;
    }
    foreach ($inventory as $item) {
        if (!is_string($item) || !isset($items[$item])) {
            return null;
        }
    }
    $clean['inventory'] = array_values($inventory);
    return $clean;
}

/**
 * Read and fully validate the save cookie for the logged-in user.
 * Returns ['status' => 'ok', 'save' => [...]], or a status of
 * 'none' (no cookie / someone else's), or 'invalid' (tampered or corrupt).
 */
function read_save(): array
{
    $raw = $_COOKIE[SAVE_COOKIE] ?? '';
    if (!is_string($raw) || $raw === '' || !is_logged_in()) {
        return ['status' => 'none'];
    }

    // 1. Signature check (hash_equals avoids timing attacks)
    $parts = explode('.', $raw);
    if (count($parts) !== 2
        || !hash_equals(hash_hmac('sha256', $parts[0], save_secret()), $parts[1])) {
        return ['status' => 'invalid'];
    }
    $payload = json_decode((string) base64_decode($parts[0], true), true);
    if (!is_array($payload)) {
        return ['status' => 'invalid'];
    }

    // 2. Must belong to this user (a different player on the same browser just ignores it)
    if (($payload['u'] ?? '') !== strtolower($_SESSION['user'])) {
        return ['status' => 'none'];
    }

    // 3. Re-validate everything against the story data
    $story = story_nodes();
    $node = $payload['n'] ?? '';
    if (!is_string($node) || !isset($story[$node]) || isset($story[$node]['ending'])) {
        return ['status' => 'invalid'];
    }
    $hero = validate_hero($payload['h'] ?? null);
    if ($hero === null) {
        return ['status' => 'invalid'];
    }
    $visited = $payload['v'] ?? null;
    if (!is_array($visited) || !in_array($node, $visited, true)) {
        return ['status' => 'invalid'];
    }
    foreach ($visited as $id) {
        if (!is_string($id) || !isset($story[$id])) {
            return ['status' => 'invalid'];
        }
    }
    $log = [];
    foreach (($payload['p'] ?? []) as $step) {
        if (!is_array($step) || count($step) !== 2
            || !is_string($step[0]) || !is_int($step[1])
            || !isset($story[$step[0]]['choices'][$step[1]])) {
            return ['status' => 'invalid'];
        }
        $log[] = log_entry($step[0], $step[1]);
    }
    $started = $payload['t'] ?? null;
    if (!is_int($started) || $started > time()) {
        return ['status' => 'invalid'];
    }

    return ['status' => 'ok', 'save' => [
        'node' => $node, 'hero' => $hero, 'visited' => array_values(array_unique($visited)),
        'log' => $log, 'started' => $started,
    ]];
}

/**
 * Load a validated save into the session.
 */
function restore_save(array $save): void
{
    $_SESSION['hero']        = $save['hero'];
    $_SESSION['node']        = $save['node'];
    $_SESSION['visited']     = $save['visited'];
    $_SESSION['choices_log'] = $save['log'];
    $_SESSION['run_started'] = $save['started'];
    $_SESSION['last_event']  = ['text' => 'Run restored from your last save.', 'events' => []];
    unset($_SESSION['result']);
}
