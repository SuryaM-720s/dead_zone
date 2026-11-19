<?php
/**
 * team.php
 * Public team introduction for Dead Zone: Downtown.
 * Uses the shared site header, footer, and HTML escaping helper.
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Team';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h1><?= e('Team') ?></h1></div>
    <p class="lead"><?= e('Dead Zone: Downtown') ?></p>
    <p><?= e('A PHP survival game where players navigate a downtown outbreak through choices that affect their survival.') ?></p>
    <p class="label"><?= e('Project leader') ?></p>
    <p><?= e('Suryaprakash Murugavvel (confirm leadership)') ?></p>
</section>
<section class="panel">
    <div class="panel-head"><h2><?= e('Team members') ?></h2></div>
    <p><?= e('Suryaprakash Murugavvel') ?></p>
    <p class="muted small"><?= e('Role: Project lead and PHP game development (confirm responsibilities)') ?></p>
    <p><?= e('Victor Doga') ?></p>
    <p class="muted small"><?= e('Role: Team page, project documentation, and form security') ?></p>
    <div class="btn-row"><a class="btn btn-primary" href="index.php"><?= e('Back to home') ?></a></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
