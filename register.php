<?php
/**
 * register.php
 * New account form. Validates on the server, keeps the username filled in
 * when something is wrong (sticky form), and stores a hashed password.
 */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('game.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw input, then validate it ourselves
    $username = trim((string) filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $password = (string) filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    $confirm  = (string) filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW);

    $validUsername = filter_var($username, FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => '/^[A-Za-z0-9_]{3,20}$/'],
    ]);

    if ($username === '') {
        $errors['username'] = 'Pick a username.';
    } elseif ($validUsername === false) {
        $errors['username'] = 'Use 3–20 letters, numbers or underscores.';
    } elseif (find_user($username) !== null) {
        $errors['username'] = 'That username is taken.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    if (!$errors) {
        if (register_user($username, $password)) {
            flash('success', 'Account created. Log in to start your run.');
            redirect('login.php');
        }
        $errors['form'] = 'Could not save your account. Try again.';
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <h1>Create an account</h1>
    <p class="lead">Your username goes on the leaderboard next to every run you finish.</p>

    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="register.php" novalidate>
        <div class="field <?= isset($errors['username']) ? 'has-error' : '' ?>">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" maxlength="20" autocomplete="username" autocapitalize="none" spellcheck="false"
                   value="<?= e($username) ?>" required>
            <?php if (isset($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['confirm']) ? 'has-error' : '' ?>">
            <label for="confirm">Confirm password</label>
            <input id="confirm" name="confirm" type="password" autocomplete="new-password" required>
            <?php if (isset($errors['confirm'])): ?><p class="field-error"><?= e($errors['confirm']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit">Create account</button>
    </form>
    <p class="form-alt">Already have an account? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
