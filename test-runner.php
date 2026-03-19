#!/usr/bin/env php
<?php

echo "Testing Log Platform Package...\n\n";

// Check if vendor autoload exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ vendor/autoload.php not found. Run: composer install\n";
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';

echo "✅ Autoloader found\n";

// Check if LogParser class exists
if (!class_exists('Willypelz\LogPlatform\Services\LogParser')) {
    echo "❌ LogParser class not found\n";
    exit(1);
}

echo "✅ LogParser class found\n";

// Create parser instance
$parser = new \Willypelz\LogPlatform\Services\LogParser();
echo "✅ LogParser instantiated\n\n";

// Test 1: Parse a standard log line
echo "Test 1: Parsing standard log line...\n";
$line = '[2026-03-18 10:30:45] local.ERROR: Database connection failed';
$result = $parser->parse($line);

if ($result['success']) {
    echo "  ✅ Parse successful\n";
} else {
    echo "  ❌ Parse failed\n";
    exit(1);
}

$entry = $parser->flushBuffer();
if ($entry && $entry['level'] === 'error' && $entry['message'] === 'Database connection failed') {
    echo "  ✅ Correct data extracted\n";
} else {
    echo "  ❌ Incorrect data\n";
    var_dump($entry);
    exit(1);
}

// Test 2: Detect log start
echo "\nTest 2: Detecting log start...\n";
if ($parser->isLogStart('[2026-03-18 10:30:45] local.ERROR: Test')) {
    echo "  ✅ Correctly identified log start\n";
} else {
    echo "  ❌ Failed to identify log start\n";
    exit(1);
}

if (!$parser->isLogStart('    at SomeClass->method()')) {
    echo "  ✅ Correctly rejected non-log line\n";
} else {
    echo "  ❌ Incorrectly identified non-log as log start\n";
    exit(1);
}

// Test 3: JSON context extraction
echo "\nTest 3: Extracting JSON context...\n";
$parser = new \Willypelz\LogPlatform\Services\LogParser(); // Fresh instance
$line = '[2026-03-18 10:30:45] local.ERROR: Test {"user_id":123,"action":"login"}';
$parser->parse($line);
$entry = $parser->flushBuffer();

if (isset($entry['context']) && $entry['context']['user_id'] === 123) {
    echo "  ✅ JSON context extracted correctly\n";
} else {
    echo "  ❌ JSON context extraction failed\n";
    var_dump($entry);
    exit(1);
}

if ($entry['message'] === 'Test') {
    echo "  ✅ Message cleaned correctly\n";
} else {
    echo "  ❌ Message not cleaned\n";
    exit(1);
}

echo "\n🎉 All tests passed!\n\n";
echo "To run PHPUnit tests, use:\n";
echo "  php vendor/phpunit/phpunit/phpunit --testdox\n";
echo "  or\n";
echo "  composer test (if configured)\n";

exit(0);

