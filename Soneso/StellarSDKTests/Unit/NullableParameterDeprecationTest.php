<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\EffectsRequestBuilder;
use Soneso\StellarSDK\Requests\LedgersRequestBuilder;
use Soneso\StellarSDK\Requests\OperationsRequestBuilder;
use Soneso\StellarSDK\Requests\PaymentsRequestBuilder;
use Soneso\StellarSDK\Requests\TransactionsRequestBuilder;

/**
 * Checks compile-time diagnostics in a process that has not loaded the SDK classes.
 */
class NullableParameterDeprecationTest extends TestCase
{
    /**
     * Optional parameter types must allow the classes to load without deprecations.
     */
    public function testClassesLoadWithoutDeprecations(): void
    {
        $classes = [
            HorizonRequestException::class,
            EffectsRequestBuilder::class,
            LedgersRequestBuilder::class,
            OperationsRequestBuilder::class,
            PaymentsRequestBuilder::class,
            TransactionsRequestBuilder::class,
        ];
        $autoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';
        $script = <<<'PHP'
require $argv[1];
foreach (array_slice($argv, 2) as $class) {
    if (class_exists($class, true)) {
        echo $class . PHP_EOL;
    }
}
PHP;
        $process = proc_open([
            PHP_BINARY,
            '-d', 'error_reporting=E_ALL',
            '-d', 'display_errors=stderr',
            '-d', 'log_errors=0',
            '-r', $script,
            $autoloadPath,
            ...$classes,
        ], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        $this->assertIsResource($process, 'The PHP class-loading process must start.');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, $stderr);
        $this->assertSame(implode(PHP_EOL, $classes) . PHP_EOL, $stdout);
        $this->assertSame('', $stderr, 'Loading SDK classes must not emit PHP diagnostics.');
    }
}
