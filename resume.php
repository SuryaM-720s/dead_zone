<?php
/**
 * resume.php
 * POST-only handler: restores a run from the validated save cookie.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/save.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('create.php');
}

csrf_check();

$check = read_save();
if ($check['status'] === 'ok') {
    restore_save($check['save']);
    redirect('game.php');
}

if ($check['status'] === 'invalid') {
    clear_save();
    flash('error', 'Your saved run failed its integrity check and was discarded.');
} else {
    flash('info', 'No saved run found for this account.');
}
redirect('create.php');
