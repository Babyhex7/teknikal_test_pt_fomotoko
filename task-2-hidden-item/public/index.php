<?php

declare(strict_types=1);

require __DIR__ . '/../src/Grid.php';
require __DIR__ . '/../src/PathFinder.php';

use HiddenItem\Grid;
use HiddenItem\PathFinder;

session_start();

// Start a fresh game (new secret) on first visit or when "Play again" is clicked.
if (isset($_GET['reset']) || !isset($_SESSION['secret'])) {
    $grid = Grid::fromDefaultLayout();
    $candidates = (new PathFinder($grid))->findProbableLocations();

    $_SESSION['secret'] = $candidates[array_rand($candidates)];
    $_SESSION['attempts'] = 0;
    $_SESSION['found'] = false;
    $_SESSION['message'] = 'Guess a coordinate to find the hidden item.';
}

$grid = Grid::fromDefaultLayout();
$candidates = (new PathFinder($grid))->findProbableLocations();

$message = $_SESSION['message'];
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_SESSION['found']) {
    $row = filter_input(INPUT_POST, 'row', FILTER_VALIDATE_INT);
    $col = filter_input(INPUT_POST, 'col', FILTER_VALIDATE_INT);

    if ($row === null || $row === false || $col === null || $col === false) {
        $message = 'Please enter valid row and column numbers.';
        $messageType = 'error';
    } else {
        $_SESSION['attempts']++;

        if ([$row, $col] === $_SESSION['secret']) {
            $_SESSION['found'] = true;
            $message = "Found it in {$_SESSION['attempts']} guess(es)! The item was at ({$row}, {$col}).";
            $messageType = 'success';
        } else {
            $message = "Not there. ({$row}, {$col}) is empty. Try again.";
            $messageType = 'error';
        }
    }

    $_SESSION['message'] = $message;
}

$found = $_SESSION['found'];
$attempts = $_SESSION['attempts'];

// Before the item is found: show every probable candidate marked with $.
// After: collapse that down to just the one cell that was actually correct.
$displayGrid = $found
    ? $grid->render([$_SESSION['secret']])
    : $grid->render($candidates);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hidden Item — Demo UI</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            max-width: 720px;
            margin: 2rem auto;
            padding: 0 1.25rem;
            line-height: 1.5;
        }
        h1 { margin-bottom: .25rem; }
        p.sub { color: #888; margin-top: 0; }
        pre.grid {
            font-size: 1.4rem;
            line-height: 1.3;
            background: #8881;
            padding: 1rem;
            border-radius: 10px;
            display: inline-block;
            letter-spacing: .1em;
        }
        .legend { font-size: .85rem; color: #888; margin: .5rem 0 1.5rem; }
        form.guess {
            display: flex;
            gap: .5rem;
            align-items: end;
            margin-bottom: 1rem;
        }
        label { font-size: .8rem; font-weight: 600; display: block; margin-bottom: .25rem; }
        input, button {
            font: inherit;
            padding: .5rem;
            border-radius: 6px;
            border: 1px solid #8886;
            width: 5rem;
        }
        button {
            cursor: pointer;
            background: #2563eb;
            color: white;
            border: none;
            font-weight: 600;
            width: auto;
            padding: .5rem 1rem;
        }
        button:disabled { opacity: .5; cursor: default; }
        #message {
            padding: .75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        #message.info { background: #8881; }
        #message.error { background: #dc262622; border: 1px solid #dc262655; }
        #message.success { background: #16a34a22; border: 1px solid #16a34a55; }
        a.reset { color: #2563eb; font-weight: 600; text-decoration: none; }
        .candidates { font-size: .85rem; color: #888; }
    </style>
</head>
<body>
    <h1>Hidden Item</h1>
    <p class="sub">
        Item hidden North &rarr; East &rarr; South from <code>X</code>. Rows and columns are 0-indexed, row 0 at the top.
    </p>

    <div id="message" class="<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>

    <pre class="grid"><?= htmlspecialchars($displayGrid) ?></pre>
    <p class="legend">
        <code>#</code> obstacle &middot; <code>X</code> start &middot; <code>$</code> probable location
        (<?= count($candidates) ?> candidates) &middot; attempts: <?= (int) $attempts ?>
    </p>

    <?php if (!$found): ?>
        <form class="guess" method="post">
            <div>
                <label for="row">Row</label>
                <input type="number" id="row" name="row" min="0" max="<?= $grid->height() - 1 ?>" required>
            </div>
            <div>
                <label for="col">Col</label>
                <input type="number" id="col" name="col" min="0" max="<?= $grid->width() - 1 ?>" required>
            </div>
            <button type="submit">Guess</button>
        </form>
    <?php endif; ?>

    <p><a class="reset" href="?reset=1">&#8635; Play again (new hidden item)</a></p>
</body>
</html>
