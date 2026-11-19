<?php
/**
 * login.php
 * Checks the username + password against data/users.json and starts a session.
 */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('game.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $password = (string) filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

    if ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $user = find_user($username);
        // Same message for "no such user" and "wrong password" so attackers
        // cannot tell which usernames exist.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $error = 'Incorrect username or password.';
        } else {
            session_regenerate_id(true); // new session id after login
            $_SESSION['user'] = $user['username'];
            flash('success', 'Welcome back, ' . $user['username'] . '.');
            redirect('game.php');
        }
    }
}

// Message shown after logout.php sends the user here
if (filter_input(INPUT_GET, 'bye', FILTER_VALIDATE_INT) === 1) {
    flash('info', 'You logged out. Your session has been cleared.');
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <h1>Log in</h1>
    <p class="lead">The last evacuation flight leaves at dawn.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" maxlength="20" autocomplete="username" autocapitalize="none" spellcheck="false"
                   value="<?= e($username) ?>" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Log in</button>
    </form>
    <p class="form-alt">New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
