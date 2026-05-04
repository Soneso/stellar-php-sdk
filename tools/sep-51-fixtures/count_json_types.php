<?php declare(strict_types=1);

// Reflection-based enumeration of every Soneso\StellarSDK\Xdr class whose
// reflection reports `hasMethod('toJson')`. Wrapper / *Base.php pairs are
// deduplicated: when both XdrXxx and XdrXxxBase declare toJson, only XdrXxx is
// emitted (the wrapper is the externally-visible class).
//
// stdout: one type name per line, sorted alphabetically.
// stderr: a single summary line "TOTAL: <count>".
//
// Exit 0 always (the script is informational; the G-Roundtrip floor check is
// applied by the test harness comparing this output against the floor).
//
// Usage: php tools/sep-51-fixtures/count_json_types.php

$repoRoot = realpath(__DIR__ . '/../..');
if ($repoRoot === false) {
    fwrite(STDERR, "could not resolve repo root\n");
    exit(0);
}

$autoload = $repoRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php missing; run composer install first.\n");
    fwrite(STDERR, "TOTAL: 0\n");
    exit(0);
}
require_once $autoload;

$xdrDir = $repoRoot . '/Soneso/StellarSDK/Xdr';
if (!is_dir($xdrDir)) {
    fwrite(STDERR, "Soneso/StellarSDK/Xdr directory missing\n");
    fwrite(STDERR, "TOTAL: 0\n");
    exit(0);
}

$declared = [];
foreach (glob($xdrDir . '/Xdr*.php') as $path) {
    $stem = pathinfo($path, PATHINFO_FILENAME);
    $fqcn = 'Soneso\\StellarSDK\\Xdr\\' . $stem;
    if (!class_exists($fqcn)) {
        continue;
    }
    try {
        $rc = new \ReflectionClass($fqcn);
    } catch (\ReflectionException $e) {
        continue;
    }
    if (!$rc->hasMethod('toJson')) {
        continue;
    }
    $declared[$stem] = $fqcn;
}

// Deduplicate wrapper / Base pairs: prefer the wrapper.
$dedup = [];
foreach ($declared as $stem => $_fqcn) {
    if (str_ends_with($stem, 'Base')) {
        $wrapper = substr($stem, 0, -4);
        if (isset($declared[$wrapper])) {
            continue; // skip Base; the wrapper carries it
        }
    }
    $dedup[] = $stem;
}

sort($dedup, SORT_STRING);
foreach ($dedup as $name) {
    echo $name, "\n";
}

fwrite(STDERR, sprintf("TOTAL: %d\n", count($dedup)));
exit(0);
