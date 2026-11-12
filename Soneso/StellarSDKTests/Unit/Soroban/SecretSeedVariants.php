<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\Assert;
use Soneso\StellarSDK\Crypto\KeyPair;
use Throwable;

/**
 * A secret seed generated at run time and the variants of it a caller may pass by mistake, for
 * the tests that check that no error message and no stored exception shows such a value.
 *
 * Each variant names its input, the kind a key position reports it as, and the text rule F of
 * SecretSeedRedaction::redactSecretSeeds() leaves in place of the input.
 */
final class SecretSeedVariants
{
    public const SECRET_SEED = 'a secret seed (S...)';
    public const VALUE_STARTING_WITH_S = 'a value starting with S (not shown, it may be a secret seed)';

    /**
     * The length of the windows of the seed that no message may contain.
     */
    private const WINDOW_LENGTH = 16;

    /**
     * @param string $seed the generated seed every variant derives from
     * @param array<string, array{input: string, kind: string, redacted: string}> $variants
     */
    private function __construct(
        public readonly string $seed,
        public readonly array $variants,
    ) {
    }

    /**
     * Generates a seed and derives the variants: the seed itself, one character changed, all in
     * lowercase, the last character missing, one character added and two leading blanks.
     */
    public static function create(): self
    {
        $seed = KeyPair::random()->getSecretSeed();
        Assert::assertNotNull($seed);
        $changed = $seed;
        $changed[28] = $seed[28] === 'A' ? 'B' : 'A';
        return new self($seed, [
            'valid seed' => [
                'input' => $seed, 'kind' => self::SECRET_SEED, 'redacted' => self::SECRET_SEED,
            ],
            'one changed character' => [
                'input' => $changed, 'kind' => self::VALUE_STARTING_WITH_S, 'redacted' => self::SECRET_SEED,
            ],
            'lowercase seed' => [
                'input' => strtolower($seed), 'kind' => self::SECRET_SEED, 'redacted' => self::SECRET_SEED,
            ],
            'one character missing' => [
                'input' => substr($seed, 0, -1), 'kind' => self::VALUE_STARTING_WITH_S,
                'redacted' => self::SECRET_SEED,
            ],
            'one character added' => [
                'input' => $seed . 'A', 'kind' => self::VALUE_STARTING_WITH_S, 'redacted' => self::SECRET_SEED,
            ],
            'leading blanks' => [
                'input' => '  ' . $seed, 'kind' => self::SECRET_SEED, 'redacted' => '  ' . self::SECRET_SEED,
            ],
        ]);
    }

    /**
     * Asserts that neither the message of $error nor the message of any exception it wraps
     * holds a part of the seed of 16 characters or more, in any letter case.
     */
    public function assertNotShown(Throwable $error, string $context): void
    {
        for ($wrapped = $error; $wrapped !== null; $wrapped = $wrapped->getPrevious()) {
            $message = $wrapped->getMessage();
            for ($start = 0; $start + self::WINDOW_LENGTH <= strlen($this->seed); $start++) {
                Assert::assertFalse(
                    stripos($message, substr($this->seed, $start, self::WINDOW_LENGTH)) !== false,
                    $context . ': the message of ' . get_class($wrapped) . ' holds a part of the seed'
                );
            }
        }
    }
}
