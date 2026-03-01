<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Psr\Log\AbstractLogger;

/**
 * Simple PSR-3 logger that prints to stdout.
 * Used in integration tests to replicate the old enableLogging behavior.
 */
class PrintLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $body = $context['body'] ?? '';
        print($message . ': ' . $body . PHP_EOL);
    }
}
