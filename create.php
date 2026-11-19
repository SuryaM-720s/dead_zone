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
    csrf_check();
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
    <h1>Choose your survivor</h1>
    <p class="lead">Your class sets your starting stats and gear, and opens routes the other two can't take.</p>

    <?php if ($saved['status'] === 'ok'):
        $savedHero = $saved['save']['hero'];
        $savedPlace = story_nodes()[$saved['save']['node']]['title'];
    ?>
        <div class="resume">
            <div>
                <h2>Saved run: <?= e($savedHero['name']) ?></h2>
                <p class="muted small mono">
                    <?= e(class_defs()[$savedHero['class']]['label']) ?>
                    · <?= e($savedPlace) ?> · move <?= $savedHero['moves'] + 1 ?>
                </p>
            </div>
            <form method="post" action="resume.php">
            <?= csrf_field() ?>
                <button class="btn btn-primary" type="submit">Resume this run</button>
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
            <?= csrf_field() ?>
        <div class="field name-field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <label for="name">Survivor name</label>
            <input id="name" name="name" type="text" maxlength="20" autocomplete="off" value="<?= e($name) ?>" required>
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <fieldset class="class-picker <?= isset($errors['class']) ? 'has-error' : '' ?>">
            <legend class="field-legend">Class</legend>
            <div class="class-grid">
                <?php foreach ($classes as $key => $def): ?>
                    <label class="class-card">
                        <input type="radio" name="class" value="<?= e($key) ?>" <?= $class === $key ? 'checked' : '' ?>>
                        <span class="class-body">
                            <span class="class-name"><?= e($def['label']) ?></span>
                            <span class="class-tagline"><?= e($def['tagline']) ?></span>
                            <span class="class-stats">
                                <?php foreach (['health' => 'Health', 'stamina' => 'Stamina', 'luck' => 'Luck'] as $stat => $label): ?>
                                    <span class="readout">
                                        <span><?= $label ?></span><b><?= $def[$stat] ?></b>
                                        <span class="meter"><i style="width: <?= $def[$stat] ?>%"></i></span>
                                    </span>
                                <?php endforeach; ?>
                            </span>
                            <span class="class-perk"><?= e($def['perk']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($errors['class'])): ?><p class="field-error"><?= e($errors['class']) ?></p><?php endif; ?>
        </fieldset>

        <button class="btn btn-primary" type="submit">Start the run</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
