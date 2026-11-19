<?php
/**
 * engine.php
 * Every game rule lives here, server-side:
 *  - character classes and items
 *  - applying a choice (stats, items, bites, infection, horde redirect)
 *  - win / lose detection and scoring
 *  - the Survival Advisor (our rule-based "AI" feature):
 *      1. consequence preview built from each choice's metadata
 *      2. locked-choice explanations ("needs 40 Stamina, you have 28")
 *      3. the Survivor Report assembled from session data at the end
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/story.php';

const INFECTION_PER_MOVE = 15;   // infection gained each move once bitten
const BITE_INFECTION     = 10;   // infection from a fresh bite
const BITE_DAMAGE        = 10;   // health lost from a bite
const MAX_NOISE          = 10;

/* ─────────────────────────── DATA TABLES ─────────────────────────── */

function class_defs(): array
{
    return [
        'medic' => [
            'label'   => 'Medic',
            'tagline' => 'Keeps people alive, including you.',
            'health'  => 100, 'stamina' => 35, 'luck' => 35,
            'items'   => ['bandages', 'bandages'],
            'perk'    => 'Infection spreads at half speed. Bandages heal 40 instead of 25.',
        ],
        'scavenger' => [
            'label'   => 'Scavenger',
            'tagline' => 'Finds a way into anywhere.',
            'health'  => 80, 'stamina' => 30, 'luck' => 50,
            'items'   => ['lockpick'],
            'perk'    => 'Starts with a lockpick and the highest Luck (fewer bites).',
        ],
        'runner' => [
            'label'   => 'Runner',
            'tagline' => 'Never stops moving.',
            'health'  => 85, 'stamina' => 55, 'luck' => 30,
            'items'   => ['energy_bar'],
            'perk'    => 'Stamina costs are cut in half.',
        ],
    ];
}

function item_defs(): array
{
    return [
        'lockpick'    => ['label' => 'Lockpick',    'usable' => false, 'hint' => 'Maintenance staff keep these.'],
        'flashlight'  => ['label' => 'Flashlight',  'usable' => false, 'hint' => 'Emergency kits usually have one.'],
        'flare'       => ['label' => 'Flare',       'usable' => false, 'hint' => 'Emergency kits usually have one.'],
        'keycard'     => ['label' => 'Medical keycard', 'usable' => false, 'hint' => 'Campus security keeps spare access cards.'],
        'bandages'    => ['label' => 'Bandages',    'usable' => true,  'hint' => 'Stores and pharmacies stock them.'],
        'antibiotics' => ['label' => 'Antibiotics', 'usable' => true,  'hint' => 'Try a med kit or a pharmacy.'],
        'energy_bar'  => ['label' => 'Energy bar',  'usable' => true,  'hint' => ''],
    ];
}

function stat_labels(): array
{
    return ['health' => 'Health', 'stamina' => 'Stamina', 'luck' => 'Luck', 'noise' => 'Noise'];
}

function item_label(string $item): string
{
    return item_defs()[$item]['label'] ?? ucfirst($item);
}

/* ─────────────────────────── RUN STATE ─────────────────────────── */

/**
 * Create a fresh hero and put them at the start node.
 */
function start_run(string $name, string $class): void
{
    $def = class_defs()[$class];
    $_SESSION['hero'] = [
        'name'       => $name,
        'class'      => $class,
        'health'     => $def['health'],
        'max_health' => $def['health'],
        'stamina'    => $def['stamina'],
        'luck'       => $def['luck'],
        'noise'      => 0,
        'infected'   => false,
        'infection'  => 0,
        'inventory'  => $def['items'],
        'survivors'  => 0,
        'moves'      => 0,
    ];
    $_SESSION['node']        = 'start';
    $_SESSION['visited']     = ['start'];
    $_SESSION['choices_log'] = [];
    $_SESSION['last_event']  = null;
    $_SESSION['run_started'] = time();
    unset($_SESSION['result']);
}

function has_active_run(): bool
{
    return isset($_SESSION['hero'], $_SESSION['node']) && !isset($_SESSION['result']);
}

function current_node(): array
{
    $story = story_nodes();
    return $story[$_SESSION['node']] ?? $story['start'];
}

function has_item(array $hero, string $item): bool
{
    return in_array($item, $hero['inventory'], true);
}

/**
 * Remove ONE copy of an item (the inventory can hold duplicates).
 */
function remove_item(array &$hero, string $item): void
{
    $index = array_search($item, $hero['inventory'], true);
    if ($index !== false) {
        array_splice($hero['inventory'], $index, 1);
    }
}

/**
 * Class perks that change a stat cost (Runner halves stamina costs).
 */
function effective_delta(string $stat, int $delta, array $hero): int
{
    if ($stat === 'stamina' && $delta < 0 && $hero['class'] === 'runner') {
        return (int) ceil($delta / 2);
    }
    return $delta;
}

/**
 * Keep every stat inside its legal range.
 */
function clamp_stats(array &$hero): void
{
    $hero['health']    = max(0, min($hero['max_health'], $hero['health']));
    $hero['stamina']   = max(0, min(100, $hero['stamina']));
    $hero['luck']      = max(0, min(100, $hero['luck']));
    $hero['noise']     = max(0, min(MAX_NOISE, $hero['noise']));
    $hero['infection'] = max(0, min(100, $hero['infection']));
}

/* ─────────────────── SURVIVAL ADVISOR (rule-based AI) ─────────────────── */

/**
 * Advisor rule 1: explain exactly why a choice is locked.
 * Returns an empty array when the choice is available.
 */
function unmet_requirements(array $choice, array $hero): array
{
    $reasons = [];
    $req = $choice['requires'] ?? [];
    $labels = stat_labels();

    foreach (['health', 'stamina', 'luck'] as $stat) {
        if (isset($req[$stat]) && $hero[$stat] < $req[$stat]) {
            $short = $req[$stat] - $hero[$stat];
            $reasons[] = "Needs {$req[$stat]} {$labels[$stat]}. You have {$hero[$stat]} ({$short} short).";
        }
    }
    if (isset($req['noise_max']) && $hero['noise'] > $req['noise_max']) {
        $reasons[] = "Too loud. Noise must be {$req['noise_max']} or less; you're at {$hero['noise']}.";
    }
    if (isset($req['item']) && !has_item($hero, $req['item'])) {
        $hint = item_defs()[$req['item']]['hint'] ?? '';
        $reasons[] = 'Needs a ' . item_label($req['item']) . '. ' . $hint;
    }
    if (isset($req['class']) && $hero['class'] !== $req['class']) {
        $reasons[] = 'Only a ' . class_defs()[$req['class']]['label'] . ' knows how to do this.';
    }
    return $reasons;
}

/**
 * Advisor rule 2: build a consequence preview from the choice metadata.
 * Each part has a 'tone' (good / bad / warn) used for colour in the UI.
 */
function consequence_preview(array $choice, array $hero): array
{
    $parts = [];
    $labels = stat_labels();

    foreach (($choice['effects'] ?? []) as $stat => $delta) {
        $delta = effective_delta($stat, $delta, $hero);
        if ($delta === 0) {
            continue;
        }
        // More noise is bad; more of anything else is good
        $isGood = ($stat === 'noise') ? $delta < 0 : $delta > 0;
        $sign = $delta > 0 ? '+' : '−';
        $parts[] = ['text' => $sign . abs($delta) . ' ' . $labels[$stat], 'tone' => $isGood ? 'good' : 'bad'];
    }
    foreach (($choice['gives'] ?? []) as $item) {
        $parts[] = ['text' => 'Gain ' . item_label($item), 'tone' => 'good'];
    }
    foreach (($choice['uses'] ?? []) as $item) {
        $parts[] = ['text' => 'Uses ' . item_label($item), 'tone' => 'warn'];
    }
    if (!empty($choice['survivor'])) {
        $parts[] = ['text' => '+' . $choice['survivor'] . ' Survivor', 'tone' => 'good'];
    }
    if (isset($choice['bite_risk'])) {
        if ($hero['luck'] < $choice['bite_risk']) {
            $parts[] = ['text' => "Bite risk: your Luck ({$hero['luck']}) is under {$choice['bite_risk']}", 'tone' => 'bad'];
        } else {
            $parts[] = ['text' => 'Bite risk: your Luck should protect you', 'tone' => 'warn'];
        }
    }
    return $parts;
}

/* ─────────────────────────── GAME ACTIONS ─────────────────────────── */

/**
 * Apply the player's choice at the current node.
 * Returns false if the choice does not exist or is locked
 * (never trust the browser: a disabled button can still be forged).
 */
function apply_choice(int $index): bool
{
    $hero  = $_SESSION['hero'];
    $node  = current_node();
    $story = story_nodes();

    if (!isset($node['choices'][$index])) {
        return false;
    }
    $choice = $node['choices'][$index];
    if (unmet_requirements($choice, $hero)) {
        return false;
    }

    $events = [];
    $wasInfected = $hero['infected'];

    // 1. Stat effects
    foreach (($choice['effects'] ?? []) as $stat => $delta) {
        $hero[$stat] += effective_delta($stat, $delta, $hero);
    }

    // 2. Items used up and items found
    foreach (($choice['uses'] ?? []) as $item) {
        remove_item($hero, $item);
    }
    foreach (($choice['gives'] ?? []) as $item) {
        $hero['inventory'][] = $item;
        $events[] = 'Picked up: ' . item_label($item) . '.';
    }

    // 3. Survivors rescued
    if (!empty($choice['survivor'])) {
        $hero['survivors'] += (int) $choice['survivor'];
        $events[] = 'A survivor joins you.';
    }

    // 4. Bite check: bitten if Luck is below the choice's bite_risk
    if (isset($choice['bite_risk'])) {
        if ($hero['luck'] < $choice['bite_risk']) {
            $hero['health'] -= BITE_DAMAGE;
            $hero['infection'] += BITE_INFECTION;
            $hero['infected'] = true;
            $events[] = 'You were bitten. Find antibiotics before the infection takes over.';
        } else {
            $events[] = 'Teeth snap an inch from your arm. Your luck held.';
        }
    }

    // 5. An existing infection spreads with every move (Medics resist it)
    if ($wasInfected) {
        $spread = $hero['class'] === 'medic' ? (int) ceil(INFECTION_PER_MOVE / 2) : INFECTION_PER_MOVE;
        $hero['infection'] += $spread;
        $events[] = "The infection spreads (+{$spread}%).";
    }

    $hero['moves']++;
    clamp_stats($hero);

    // 6. Log the choice (used by the Survivor Report)
    $_SESSION['choices_log'][] = [
        'node'   => $node['title'],
        'choice' => $choice['text'],
        'major'  => !empty($choice['gives']) || !empty($choice['survivor'])
                    || isset($choice['bite_risk']) || !empty($choice['requires']),
    ];

    // 7. Move, unless the noise you've made pulls a horde onto you
    $next = $choice['next'];
    if (isset($story[$next]['horde']) && $hero['noise'] >= $story[$next]['horde']['noise']) {
        $events[] = "You've made too much noise (Noise {$hero['noise']}). The horde found you.";
        $next = $story[$next]['horde']['to'];
    }
    $_SESSION['node'] = $next;
    if (!in_array($next, $_SESSION['visited'], true)) {
        $_SESSION['visited'][] = $next;
    }

    $_SESSION['hero'] = $hero;
    $_SESSION['last_event'] = ['text' => $choice['result'] ?? '', 'events' => $events];

    // 8. Win / lose checks: death beats everything, then endings
    if ($hero['health'] <= 0) {
        finish_run('overrun');
    } elseif ($hero['infection'] >= 100) {
        finish_run('turned');
    } elseif (isset($story[$next]['ending'])) {
        finish_run($story[$next]['ending']);
    }
    return true;
}

/**
 * Use a consumable item. Does not cost a move.
 */
function use_item(string $item): bool
{
    $hero = $_SESSION['hero'];
    $defs = item_defs();
    if (!isset($defs[$item]) || !$defs[$item]['usable'] || !has_item($hero, $item)) {
        return false;
    }

    switch ($item) {
        case 'bandages':
            $heal = $hero['class'] === 'medic' ? 40 : 25;
            $hero['health'] += $heal;
            $message = "You patch yourself up (+{$heal} Health).";
            break;
        case 'antibiotics':
            if (!$hero['infected']) {
                $_SESSION['last_event'] = ['text' => "You're not infected. Save them.", 'events' => []];
                return true;
            }
            $hero['infected'] = false;
            $hero['infection'] = 0;
            $message = 'The fever breaks. Infection cleared.';
            break;
        case 'energy_bar':
            $hero['stamina'] += 20;
            $message = 'You wolf down the energy bar (+20 Stamina).';
            break;
        default:
            return false;
    }

    remove_item($hero, $item);
    clamp_stats($hero);
    $_SESSION['hero'] = $hero;
    $_SESSION['last_event'] = ['text' => $message, 'events' => []];
    return true;
}

/* ─────────────────────────── ENDINGS & SCORE ─────────────────────────── */

/**
 * Score formula (documented in the README):
 *   ending base + 150 per survivor
 *   + remaining health + 20 per item carried   (only if you survived)
 *   + 20 per move under 14 (speed bonus)
 *   − 2 per point of infection
 */
function calculate_score(array $hero, string $endingKey): array
{
    $ending = ending_defs()[$endingKey];
    $survived = $ending['type'] !== 'tragic';

    $breakdown = [
        'Ending: ' . $ending['label'] => $ending['base'],
        'Survivors rescued'           => $hero['survivors'] * 150,
        'Health remaining'            => $survived ? $hero['health'] : 0,
        'Supplies carried'            => $survived ? count($hero['inventory']) * 20 : 0,
        'Speed bonus'                 => max(0, (14 - $hero['moves']) * 20),
        'Infection penalty'           => -2 * $hero['infection'],
    ];
    return ['total' => max(0, array_sum($breakdown)), 'breakdown' => $breakdown];
}

/**
 * Advisor rule 3: the Survivor Report, assembled from session data.
 */
function survivor_report(array $hero, string $endingKey): string
{
    $class = class_defs()[$hero['class']]['label'];
    $ending = ending_defs()[$endingKey];

    $outcome = [
        'extraction' => 'made it onto the last helicopter',
        'signal'     => 'broadcast the safe route and saved half the city',
        'turned'     => 'lost the fight against the infection',
        'overrun'    => 'was overrun before reaching safety',
    ][$endingKey];

    $report = "{$hero['name']} the {$class} {$outcome} after {$hero['moves']} moves. ";

    // Up to three key decisions from the log
    $major = array_values(array_filter($_SESSION['choices_log'], function ($entry) {
        return $entry['major'];
    }));
    if ($major) {
        $picks = array_map(function ($entry) {
            return '"' . $entry['choice'] . '" at ' . $entry['node'];
        }, array_slice($major, 0, 3));
        $report .= 'Key decisions: ' . implode('; ', $picks) . '. ';
    }

    if ($hero['survivors'] > 0) {
        $report .= $hero['survivors'] === 1
            ? 'One other survivor owes their life to this run. '
            : "{$hero['survivors']} other survivors owe their lives to this run. ";
    } else {
        $report .= 'Nobody else was saved along the way. ';
    }

    if ($hero['infected']) {
        $report .= "Infection reached {$hero['infection']}% and was never treated.";
    } elseif ($hero['noise'] >= 5) {
        $report .= 'The noise you made drew attention all night.';
    } else {
        $report .= 'You moved quietly and kept your head down.';
    }
    return $report;
}

/**
 * End the run: score it, push it onto the session leaderboard,
 * and store the result for ending.php.
 */
function finish_run(string $endingKey): void
{
    $hero = $_SESSION['hero'];
    $score = calculate_score($hero, $endingKey);

    $entry = [
        'user'      => $_SESSION['user'],
        'hero'      => $hero['name'],
        'class'     => $hero['class'],
        'ending'    => $endingKey,
        'score'     => $score['total'],
        'moves'     => $hero['moves'],
        'survivors' => $hero['survivors'],
        'seconds'   => time() - ($_SESSION['run_started'] ?? time()),
    ];
    $_SESSION['scores'][] = $entry;

    $_SESSION['result'] = $entry + [
        'breakdown' => $score['breakdown'],
        'report'    => survivor_report($hero, $endingKey),
    ];
}

/**
 * Top N runs this session: highest score first, then fewer moves, then faster time.
 */
function leaderboard(int $limit = 10): array
{
    $scores = $_SESSION['scores'] ?? [];
    usort($scores, function ($a, $b) {
        if ($a['score'] !== $b['score']) {
            return $b['score'] <=> $a['score'];
        }
        if ($a['moves'] !== $b['moves']) {
            return $a['moves'] <=> $b['moves'];
        }
        return $a['seconds'] <=> $b['seconds'];
    });
    return array_slice($scores, 0, $limit);
}
