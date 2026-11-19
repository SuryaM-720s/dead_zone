<?php
/**
 * team.php
 * Public team introduction for Dead Zone: Downtown.
 * Lists the project leader and every member with their role, from one array
 * so adding a member is a one-line change. No login required.
 */
require_once __DIR__ . '/includes/auth.php';

$members = [
    [
        'name' => 'Suryaprakash Murugavvel',
        'role' => 'Project leader · PHP game development',
        'does' => 'Story engine, game rules and scoring, login and sessions, save/resume, UI design.',
    ],
    [
        'name' => 'Victor Doga',
        'role' => 'Documentation · form security',
        'does' => 'Team page, README and setup guide, CSRF protection on every form.',
    ],
];

$pageTitle = 'Team';
require __DIR__ . '/includes/header.php';
?>
<section class="team" aria-labelledby="team-title">
    <p class="label">Project leader: <?= e($members[0]['name']) ?></p>
    <h1 id="team-title">Dead Zone: Downtown</h1>
    <p class="lead">A PHP survival game where players cross a downtown outbreak to the last evacuation flight, and every choice changes whether they make it.</p>

    <h2>The team</h2>
    <ul class="team-grid">
        <?php foreach ($members as $member): ?>
            <li class="panel">
                <h3><?= e($member['name']) ?></h3>
                <p class="label"><?= e($member['role']) ?></p>
                <p class="muted"><?= e($member['does']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="btn-row">
        <a class="btn btn-primary" href="<?= is_logged_in() ? 'game.php' : 'register.php' ?>"><?= is_logged_in() ? 'Play the game' : 'Create an account' ?></a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
