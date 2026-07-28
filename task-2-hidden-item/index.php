<?php

declare(strict_types=1);

require __DIR__ . '/src/Grid.php';
require __DIR__ . '/src/PathFinder.php';
require __DIR__ . '/src/Game.php';

use HiddenItem\Game;

(new Game())->play();
