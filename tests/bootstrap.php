<?php
// Loads just the pieces unit tests need: composer autoload + the pure
// helper functions in functions.php. Deliberately does NOT include
// includes/db.php, so these tests never touch the real database or need
// MySQL running — see tests/README.md for what these tests do and don't cover.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/functions.php';
