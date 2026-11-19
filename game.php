<?php
/**
 * game.php
 * The main game screen. Handles two POST actions, then redirects
 * (Post/Redirect/Get) so refreshing the page never repeats a move:
 *   action=choose&choice=N  → take choice N at the current node
 *   action=use&item=KEY     → use a consumable item
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_once __DIR__ . '/includes/save.php';
require_login();

if (isset($_SESSION['result'])) {
    redirect('ending.php');
}
if (!has_active_run()) {
    redirect('create.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);

    if ($action === 'choose') {
        $choice = filter_input(INPUT_POST, 'choice', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($choice === false || $choice === null || !apply_choice($choice)) {
            flash('error', "You can't take that path right now.");
        }
    } elseif ($action === 'use') {
        $item = (string) filter_input(INPUT_POST, 'item', FILTER_UNSAFE_RAW);
        if (!use_item($item)) {
            flash('error', "You can't use that.");
        }
    }

    // Keep the save cookie in sync: update it mid-run, delete it once the run ends
    if (isset($_SESSION['result'])) {
        clear_save();
        redirect('ending.php');
    }
    write_save();
    redirect('game.php');
}

$hero  = $_SESSION['hero'];
$node  = current_node();
$event = $_SESSION['last_event'];
$_SESSION['last_event'] = null; // narrator message shows once
$classLabel = class_defs()[$hero['class']]['label'];
$items = item_defs();
$map = explored_map();

$areaLabel = area_defs()[$node['area']]['label'];

/** Percentage of $max, for meter widths. */
function bar(int $value, int $max): int
{
    return $max > 0 ? (int) round($value / $max * 100) : 0;
}

/**
 * One stat row: label, mono value, thin meter. 'is-low' turns it orange-red
 * when the stat drops into danger.
 */
function readout(string $label, string $value, int $percent, bool $low = false, string $meterClass = ''): string
{
    return '<div class="readout' . ($low ? ' is-low' : '') . '">'
        . '<span>' . e($label) . '</span><b>' . e($value) . '</b>'
        . '<div class="meter"><i class="' . e($meterClass) . '" style="width: ' . $percent . '%"></i></div>'
        . '</div>';
}

$arrowIcon = '<svg class="choice-arrow" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M11 5.5 15.5 10 11 14.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$lockIcon  = '<svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><rect x="2" y="5" width="8" height="6" rx="1" fill="currentColor"/><path d="M4 5V3.5a2 2 0 0 1 4 0V5" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>';

$pageTitle = $node['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="game-layout">
    <section class="story" aria-labelledby="node-title">
        <p class="location"><span>Move <?= $hero['moves'] + 1 ?></span><span><?= e($areaLabel) ?></span></p>
        <h1 id="node-title"><?= e($node['title']) ?></h1>

        <ul class="mobile-status" aria-label="Status">
            <li class="<?= $hero['health'] <= 30 ? 'is-low' : '' ?>">Health <b><?= $hero['health'] ?></b></li>
            <li class="<?= $hero['stamina'] < 20 ? 'is-low' : '' ?>">Stamina <b><?= $hero['stamina'] ?></b></li>
            <li>Noise <b><?= $hero['noise'] ?>/<?= MAX_NOISE ?></b></li>
            <?php if ($hero['infected']): ?>
                <li class="is-low">Infection <b><?= $hero['infection'] ?>%</b></li>
            <?php endif; ?>
        </ul>

        <?php if ($event): ?>
            <div class="log" role="status">
                <p class="label">Last move</p>
                <?php if ($event['text'] !== ''): ?><p><?= prose($event['text']) ?></p><?php endif; ?>
                <?php if ($event['events']): ?>
                    <ul class="log-events">
                        <?php foreach ($event['events'] as $line): ?>
                            <li><?= prose($line) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="story-text">
            <?php foreach ($node['text'] as $i => $paragraph): ?>
                <p style="--delay: <?= $i * 0.12 ?>s"><?= prose($paragraph) ?></p>
            <?php endforeach; ?>
        </div>

        <form method="post" action="game.php">
            <input type="hidden" name="action" value="choose">
            <p class="label">What do you do?</p>
            <ol class="choices">
                <?php foreach ($node['choices'] as $i => $choice):
                    $locked  = unmet_requirements($choice, $hero);
                    $preview = consequence_preview($choice, $hero);
                ?>
                    <li class="choice<?= $locked ? ' is-locked' : '' ?>">
                        <button type="submit" name="choice" value="<?= $i ?>"<?= $locked ? ' disabled' : '' ?>>
                            <span class="choice-key" aria-hidden="true"><?= $i + 1 ?></span>
                            <span class="choice-text">
                                <span><?= e($choice['text']) ?></span>
                                <?php if ($locked): ?>
                                    <span class="lock-reason"><?= $lockIcon ?><span><?= e(implode(' ', $locked)) ?></span></span>
                                <?php elseif ($preview): ?>
                                    <span class="preview">
                                        <?php foreach ($preview as $part): ?>
                                            <span class="tone-<?= e($part['tone']) ?>"><?= e($part['text']) ?></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <?= $locked ? '<span></span>' : $arrowIcon ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
        </form>
    </section>

    <aside class="sidebar" aria-label="Survivor status">
        <div class="panel">
            <div class="panel-head">
                <h2><?= e($hero['name']) ?></h2>
                <span><?= e($classLabel) ?></span>
            </div>
            <div class="stats">
                <?= readout('Health', $hero['health'] . '/' . $hero['max_health'],
                        bar($hero['health'], $hero['max_health']), $hero['health'] <= 30) ?>
                <?= readout('Stamina', (string) $hero['stamina'], min(100, $hero['stamina']), $hero['stamina'] < 20) ?>
                <?= readout('Luck', (string) $hero['luck'], min(100, $hero['luck'])) ?>
                <div class="readout">
                    <span>Noise</span><b><?= $hero['noise'] ?>/<?= MAX_NOISE ?></b>
                    <div class="segments" aria-hidden="true">
                        <?php for ($s = 1; $s <= MAX_NOISE; $s++): ?>
                            <i class="<?= $s <= $hero['noise'] ? 'on' : '' ?><?= $s >= 5 ? ' loud' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if ($hero['infected']): ?>
                    <?= readout('Infection', $hero['infection'] . '%', $hero['infection'], true, 'm-infection') ?>
                <?php endif; ?>
            </div>
            <p class="survivors">Survivors with you <b><?= $hero['survivors'] ?></b></p>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h2>Map</h2>
                <span><?= $map['seen'] ?>/<?= $map['total'] ?> explored</span>
            </div>
            <div class="map-grid" role="img" aria-label="Areas: <?= e(implode(', ', array_map(function ($a) {
                return $a['label'] . ' ' . ($a['state'] === 'unknown' ? 'unexplored' : $a['state']);
            }, $map['areas']))) ?>">
                <?php foreach ($map['areas'] as $area): ?>
                    <div class="map-cell map-<?= e($area['state']) ?>"
                        style="grid-row: <?= $area['row'] ?>; grid-column: <?= $area['col'] ?>">
                        <?= $area['state'] === 'unknown' ? '' : e($area['label']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h2>Inventory</h2>
                <span><?= count($hero['inventory']) ?> items</span>
            </div>
            <?php if (!$hero['inventory']): ?>
                <p class="muted small">Nothing yet.</p>
            <?php else: ?>
                <ul class="inventory">
                    <?php foreach (array_count_values($hero['inventory']) as $key => $count): ?>
                        <li>
                            <span><?= e(item_label($key)) ?><?php if ($count > 1): ?><span class="count">×<?= $count ?></span><?php endif; ?></span>
                            <?php if ($items[$key]['usable']): ?>
                                <form method="post" action="game.php">
                                    <input type="hidden" name="action" value="use">
                                    <input type="hidden" name="item" value="<?= e($key) ?>">
                                    <button type="submit" class="btn-use" aria-label="Use <?= e(item_label($key)) ?>">Use</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h2>Path tracker</h2>
                <span><?= count($_SESSION['choices_log']) ?> moves</span>
            </div>
            <?php if (!$_SESSION['choices_log']): ?>
                <p class="muted small">Your route shows up here as you move.</p>
            <?php else: ?>
                <ol class="path-log">
                    <?php foreach ($_SESSION['choices_log'] as $step): ?>
                        <li class="<?= $step['major'] ? 'is-major' : '' ?>">
                            <span class="path-node"><?= e($step['node']) ?></span>
                            <span class="path-choice"><?= e($step['choice']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
