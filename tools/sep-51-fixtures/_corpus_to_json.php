<?php declare(strict_types=1);

// Read the seed JSON list (produced by _corpus_seed.php) from stdin and
// re-emit it on stdout with each entry's spec_reference_json populated by
// the SDK's toJson() output for the entry's base64 XDR input.
//
// The PHP SDK's emission is the canonical SEP-0051 wire form (verified by
// SpecAnchorTest); snapshotting it freezes it as the regression baseline
// for CorpusSnapshotTest.
//
// Run via:
//   php tools/sep-51-fixtures/_corpus_seed.php \
//     | php tools/sep-51-fixtures/_corpus_to_json.php

namespace Soneso\StellarSDKTests\Internal\Sep51Corpus;

require_once __DIR__ . '/../../vendor/autoload.php';

$input = stream_get_contents(STDIN);
if ($input === false || $input === '') {
    fwrite(STDERR, "_corpus_to_json: empty stdin\n");
    exit(2);
}

$entries = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($entries)) {
    fwrite(STDERR, "_corpus_to_json: stdin did not decode to a JSON array\n");
    exit(2);
}

$out = [];
foreach ($entries as $entry) {
    $type = $entry['type'];
    $b64 = $entry['base64'];
    $phpClass = "Soneso\\StellarSDK\\Xdr\\Xdr$type";
    if (!class_exists($phpClass)) {
        fwrite(STDERR, "_corpus_to_json: class $phpClass not found for fixture {$entry['id']}\n");
        exit(3);
    }
    if (!method_exists($phpClass, 'fromBase64Xdr')) {
        fwrite(STDERR, "_corpus_to_json: $phpClass missing fromBase64Xdr for fixture {$entry['id']}\n");
        exit(3);
    }
    if (!method_exists($phpClass, 'toJson')) {
        fwrite(STDERR, "_corpus_to_json: $phpClass missing toJson for fixture {$entry['id']}\n");
        exit(3);
    }
    try {
        $decoded = $phpClass::fromBase64Xdr($b64);
        $json = $decoded->toJson();
    } catch (\Throwable $e) {
        fwrite(STDERR, "_corpus_to_json: fixture {$entry['id']} ($type) toJson threw: " . $e->getMessage() . "\n");
        exit(4);
    }
    $entry['spec_reference_json'] = $json;
    $out[] = $entry;
}

echo json_encode($out, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
