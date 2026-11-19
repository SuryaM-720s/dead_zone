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
    <div class="board-head">
        <div>
            <h1>Leaderboard</h1>
            <p class="lead">Top runs this session, ranked by score, then fewest moves.</p>
        </div>
        <a class="btn btn-ghost" href="<?= has_active_run() ? 'game.php' : 'create.php' ?>"><?= has_active_run() ? 'Continue your run' : 'Start a run' ?></a>
    </div>

    <?php if (!$rows): ?>
        <div class="panel empty">
            <h2>No finished runs yet</h2>
            <p>Finish a run, any ending counts, and it lands here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="leaderboard">
                <thead>
                    <tr>
                        <th scope="col">Rank</th><th scope="col">Player</th><th scope="col">Survivor</th><th scope="col">Ending</th>
                        <th scope="col" class="num">Moves</th><th scope="col" class="num">Saved</th><th scope="col" class="num">Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $rank => $row): ?>
                    <tr class="<?= $row['user'] === $_SESSION['user'] ? 'is-me' : '' ?>">
                        <td class="rank"><?= str_pad((string) ($rank + 1), 2, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($row['user']) ?></td>
                        <td><?= e($row['hero']) ?> <span class="class-note"><?= e($classes[$row['class']]['label']) ?></span></td>
                        <td><span class="badge badge-<?= e($endings[$row['ending']]['type']) ?>"><?= e($endings[$row['ending']]['label']) ?></span></td>
                        <td class="num"><?= $row['moves'] ?></td>
                        <td class="num"><?= $row['survivors'] ?></td>
                        <td class="num score"><?= $row['score'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
