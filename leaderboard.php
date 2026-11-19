<?php
/**
 * leaderboard.php
 * Top 10 runs stored in $_SESSION['scores'] for this visit.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_login();

$rows = leaderboard(10);
$endings = ending_defs();
$classes = class_defs();

$pageTitle = 'Leaderboard';
require __DIR__ . '/includes/header.php';
?>
<section>
    <h1>Leaderboard</h1>
    <p class="muted">Top runs this session, ranked by score, then fewest moves.</p>

    <?php if (!$rows): ?>
        <div class="panel empty">
            <p>No finished runs yet.</p>
            <a class="btn btn-primary" href="<?= has_active_run() ? 'game.php' : 'create.php' ?>">Play</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="leaderboard">
                <thead>
                    <tr>
                        <th>#</th><th>Player</th><th>Survivor</th><th>Ending</th>
                        <th class="num">Moves</th><th class="num">Saved</th><th class="num">Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $rank => $row): ?>
                    <tr class="<?= $row['user'] === $_SESSION['user'] ? 'is-me' : '' ?>">
                        <td><?= $rank + 1 ?></td>
                        <td><?= e($row['user']) ?></td>
                        <td><?= e($row['hero']) ?> <span class="muted small"><?= e($classes[$row['class']]['label']) ?></span></td>
                        <td><span class="badge badge-<?= e($endings[$row['ending']]['type']) ?>"><?= e($endings[$row['ending']]['label']) ?></span></td>
                        <td class="num"><?= $row['moves'] ?></td>
                        <td class="num"><?= $row['survivors'] ?></td>
                        <td class="num strong"><?= $row['score'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
