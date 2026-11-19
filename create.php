<?php
/**
 * create.php
 * Character creation form: survivor name + class.
 * Server-side validation, error messages, and sticky values on failure.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_once __DIR__ . '/includes/save.php';
require_login();

// Offer to resume a saved run if there's no run in this session
$saved = has_active_run() ? ['status' => 'none'] : read_save();
if ($saved['status'] === 'invalid') {
    clear_save();
    flash('error', 'Your saved run failed its integrity check and was discarded.');
}

$classes = class_defs();
$errors = [];
$name = '';
$class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string) filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $class = (string) filter_input(INPUT_POST, 'class', FILTER_UNSAFE_RAW);

    // Name: letters, spaces, apostrophes or hyphens, 2–20 characters, starts with a letter
    $validName = filter_var($name, FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => "/^[A-Za-z][A-Za-z '\\-]{1,19}$/"],
    ]);
    if ($name === '') {
        $errors['name'] = 'Give your survivor a name.';
    } elseif ($validName === false) {
        $errors['name'] = 'Use 2–20 letters (spaces, hyphens and apostrophes are OK).';
    }

    // Class must be one of the keys we define; anything else is rejected
    if (!array_key_exists($class, $classes)) {
        $errors['class'] = 'Choose a class.';
        $class = '';
    }

    if (!$errors) {
        start_run($name, $class);
        write_save();
        redirect('game.php');
    }
}

$pageTitle = 'New Survivor';
require __DIR__ . '/includes/header.php';
?>
<section class="create">
    <h1>Who are you?</h1>
    <p class="muted">Your class decides your starting stats, your gear, and which routes you can take.</p>

    <?php if ($saved['status'] === 'ok'):
        $savedHero = $saved['save']['hero'];
        $savedPlace = story_nodes()[$saved['save']['node']]['title'];
    ?>
        <div class="panel resume">
            <div>
                <h2>Saved run found</h2>
                <p class="muted small">
                    <?= e($savedHero['name']) ?> the <?= e(class_defs()[$savedHero['class']]['label']) ?>
                    · <?= e($savedPlace) ?> · move <?= $savedHero['moves'] + 1 ?>
                </p>
            </div>
            <form method="post" action="resume.php">
                <button class="btn btn-primary" type="submit">Resume</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (has_active_run()): ?>
        <div class="alert alert-info">
            Starting a new survivor abandons <?= e($_SESSION['hero']['name']) ?>'s run.
            <a href="game.php">Go back to it</a>.
        </div>
    <?php endif; ?>

    <form method="post" action="create.php" novalidate>
        <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <label for="name">Survivor name</label>
            <input id="name" name="name" type="text" maxlength="20" value="<?= e($name) ?>" required>
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <fieldset class="class-picker <?= isset($errors['class']) ? 'has-error' : '' ?>">
            <legend>Class</legend>
            <?php foreach ($classes as $key => $def): ?>
                <label class="class-card">
                    <input type="radio" name="class" value="<?= e($key) ?>" <?= $class === $key ? 'checked' : '' ?>>
                    <span class="class-body">
                        <strong><?= e($def['label']) ?></strong>
                        <em><?= e($def['tagline']) ?></em>
                        <span class="class-stats">
                            HP <?= $def['health'] ?> · STA <?= $def['stamina'] ?> · LUCK <?= $def['luck'] ?>
                        </span>
                        <span class="class-perk"><?= e($def['perk']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
            <?php if (isset($errors['class'])): ?><p class="field-error"><?= e($errors['class']) ?></p><?php endif; ?>
        </fieldset>

        <button class="btn btn-primary" type="submit">Enter the Dead Zone</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
