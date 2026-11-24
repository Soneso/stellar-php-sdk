<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypePoolShare;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\SecretSeedRedaction;

class SecretSeedRedactionTest extends TestCase
{
    /**
     * @return array<string, string> one valid value of every kind a key position takes
     */
    public static function validKeyValues(): array
    {
        return [
            'account (G...)' => StrKey::encodeAccountId(str_repeat("\x01", 32)),
            'muxed account (M...)' => StrKey::encodeMuxedAccountId(str_repeat("\x02", 40)),
            'contract (C...)' => StrKey::encodeContractId(str_repeat("\x03", 32)),
            'muxed contract (W...)' => StrKey::encodeMuxedContractId(str_repeat("\x04", 40)),
            'liquidity pool (L...)' => StrKey::encodeLiquidityPoolId(str_repeat("\x05", 32)),
            'claimable balance (B...)' => StrKey::encodeClaimableBalanceId(str_repeat("\x06", 32)),
            'hex id' => str_repeat('5e', 32),
        ];
    }

    public function testSeedKindNamesEveryVariant(): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $this->assertSame($variant['kind'], SecretSeedRedaction::seedKind($variant['input']), $name);
            $this->assertSame($variant['kind'], SecretSeedRedaction::keyForMessage($variant['input']), $name);
        }
    }

    public function testSeedKindTrimsUnicodeWhiteSpace(): void
    {
        $seed = SecretSeedVariants::create()->seed;
        $padded = [
            'no-break space' => "\u{00A0}" . $seed,
            'em space' => "\u{2003}" . $seed,
            'next line' => "\u{0085}" . $seed,
            'byte order mark' => "\u{FEFF}" . $seed,
            'tab and newline around' => "\t" . $seed . "\n",
            'trailing no-break space' => $seed . "\u{00A0}",
        ];
        foreach ($padded as $name => $value) {
            $this->assertSame(SecretSeedVariants::SECRET_SEED, SecretSeedRedaction::seedKind($value), $name);
        }
        $this->assertSame(SecretSeedVariants::VALUE_STARTING_WITH_S,
            SecretSeedRedaction::seedKind("\u{00A0}" . substr($seed, 0, 40)));
    }

    public function testSeedKindOfAValueThatIsNotValidUtf8(): void
    {
        $seed = SecretSeedVariants::create()->seed;
        // Only ASCII white space is trimmed, and a byte that is no white space follows the seed.
        $this->assertSame(SecretSeedVariants::VALUE_STARTING_WITH_S,
            SecretSeedRedaction::seedKind(" \t" . $seed . "\xFF"));
        $this->assertNull(SecretSeedRedaction::seedKind("\xFF" . $seed));
    }

    public function testSeedKindOfShortValuesStartingWithS(): void
    {
        foreach (['S', 's', 'SEED', 'sam'] as $value) {
            $this->assertSame(SecretSeedVariants::VALUE_STARTING_WITH_S, SecretSeedRedaction::seedKind($value), $value);
        }
        foreach (['', ' ', 'GABC', 'xS'] as $value) {
            $this->assertNull(SecretSeedRedaction::seedKind($value), $value);
            $this->assertSame($value, SecretSeedRedaction::keyForMessage($value));
        }
    }

    public function testValidKeyValuesHaveNoKindAndStayUnchanged(): void
    {
        foreach (self::validKeyValues() as $name => $value) {
            $this->assertNull(SecretSeedRedaction::seedKind($value), $name);
            $this->assertSame($value, SecretSeedRedaction::keyForMessage($value), $name);
            $this->assertSame($value, SecretSeedRedaction::redactSecretSeeds($value), $name);
            $this->assertSame('x ' . $value . ' y', SecretSeedRedaction::redactSecretSeeds('x ' . $value . ' y'), $name);
            SecretSeedRedaction::requireNotSecretSeed($value, 'id');
        }
    }

    public function testRedactSecretSeedsReplacesEveryVariant(): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $this->assertSame($variant['redacted'], SecretSeedRedaction::redactSecretSeeds($variant['input']), $name);
            $this->assertSame('amount ' . $variant['redacted'] . ', 5',
                SecretSeedRedaction::redactSecretSeeds('amount ' . $variant['input'] . ', 5'), $name);
        }
    }

    public function testRedactSecretSeedsReplacesRunsFromFortyCharacters(): void
    {
        $this->assertSame('S' . str_repeat('A', 38), SecretSeedRedaction::redactSecretSeeds('S' . str_repeat('A', 38)));
        $this->assertSame(SecretSeedVariants::SECRET_SEED, SecretSeedRedaction::redactSecretSeeds('S' . str_repeat('A', 39)));
        $this->assertSame(SecretSeedVariants::SECRET_SEED, SecretSeedRedaction::redactSecretSeeds('s' . str_repeat('9', 39)));
        // A run that starts with another letter stays when it holds no valid seed.
        $this->assertSame('A' . str_repeat('S', 60), SecretSeedRedaction::redactSecretSeeds('A' . str_repeat('S', 60)));
    }

    public function testRedactSecretSeedsFindsAGluedSeed(): void
    {
        $seed = SecretSeedVariants::create()->seed;
        $this->assertSame('x' . SecretSeedVariants::SECRET_SEED, SecretSeedRedaction::redactSecretSeeds('x' . $seed));
        $this->assertSame('9' . SecretSeedVariants::SECRET_SEED . 'z',
            SecretSeedRedaction::redactSecretSeeds('9' . strtolower($seed) . 'z'));
        $this->assertSame('ab' . SecretSeedVariants::SECRET_SEED . SecretSeedVariants::SECRET_SEED,
            SecretSeedRedaction::redactSecretSeeds('ab' . $seed . $seed));
    }

    public function testRedactSecretSeedsKeepsOrdinaryText(): void
    {
        foreach (['12.5', 'SEED', 'STELLARTOKEN', 'sam*soneso.com', 'Sorry, 7 decimal places at most', ''] as $text) {
            $this->assertSame($text, SecretSeedRedaction::redactSecretSeeds($text));
        }
    }

    public function testRequireNotSecretSeedNamesTheParameterAndTheKind(): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            try {
                SecretSeedRedaction::requireNotSecretSeed($variant['input'], 'accountId');
                $this->fail('Accepted the ' . $name);
            } catch (InvalidArgumentException $e) {
                $this->assertSame('accountId is ' . $variant['kind'] . '; the request is not sent', $e->getMessage());
                $variants->assertNotShown($e, $name);
            }
        }
    }

    public function testRequireAssetIssuerNotSecretSeed(): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            foreach ([new AssetTypeCreditAlphanum4('USD', $variant['input']),
                         new AssetTypeCreditAlphanum12('LONGCODE', $variant['input'])] as $asset) {
                try {
                    SecretSeedRedaction::requireAssetIssuerNotSecretSeed($asset, 'asset');
                    $this->fail('Accepted the ' . $name);
                } catch (InvalidArgumentException $e) {
                    $this->assertSame('asset issuer is ' . $variant['kind'] . '; the request is not sent',
                        $e->getMessage());
                    $variants->assertNotShown($e, $name);
                }
            }
        }
        $issuer = StrKey::encodeAccountId(str_repeat("\x01", 32));
        SecretSeedRedaction::requireAssetIssuerNotSecretSeed(new AssetTypeCreditAlphanum4('SEED', $issuer), 'asset');
        SecretSeedRedaction::requireAssetIssuerNotSecretSeed(Asset::native(), 'asset');
        SecretSeedRedaction::requireAssetIssuerNotSecretSeed(
            new AssetTypePoolShare(Asset::native(), new AssetTypeCreditAlphanum4('SEED', $issuer)), 'asset');
        $this->addToAssertionCount(3);
    }

    public function testKeyForMessageRedactsEmbeddedSeedsAndKeepsPlainValues(): void
    {
        $variants = SecretSeedVariants::create();
        $seed = $variants->seed;
        $lowerSeed = strtolower($seed);
        $truncated = substr($seed, 0, 45);
        $lowerTruncated = strtolower(substr($seed, 0, 40));

        $cases = [
            'seed at start uppercase' => [
                $seed . ' tail',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'seed at start lowercase' => [
                $lowerSeed . ' tail',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'seed at start glued' => [
                $seed . 'xyz',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'seed at start after leading blank' => [
                ' ' . $seed . ' tail',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'seed in middle' => [
                'prefix ' . $seed . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'lowercase seed in middle' => [
                'prefix ' . $lowerSeed . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'glued seed in middle' => [
                'ab' . $seed . 'cd',
                'aba secret seed (S...)cd',
            ],
            'lowercase glued seed in middle' => [
                'ab' . $lowerSeed . 'cd',
                'aba secret seed (S...)cd',
            ],
            'seed at end' => [
                'prefix ' . $seed,
                'prefix a secret seed (S...)',
            ],
            'lowercase seed at end' => [
                'prefix ' . $lowerSeed,
                'prefix a secret seed (S...)',
            ],
            'glued seed at end' => [
                'G' . $seed,
                'Ga secret seed (S...)',
            ],
            'lowercase glued seed at end' => [
                'x' . $lowerSeed,
                'xa secret seed (S...)',
            ],
            'truncated seed-like run in middle' => [
                'prefix ' . $truncated . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'lowercase truncated seed-like run at end' => [
                'prefix ' . $lowerTruncated,
                'prefix a secret seed (S...)',
            ],
        ];
        foreach ($variants->insideLongerValues() as $form => $value) {
            $cases['insideLongerValues ' . $form] = [$value['input'], $value['redacted']];
        }

        foreach ($cases as $name => [$input, $expected]) {
            $actual = SecretSeedRedaction::keyForMessage($input);
            $this->assertTrue(
                $actual === $expected,
                $name . ': keyForMessage() did not return the expected redacted text'
            );
            for ($start = 0; $start + 16 <= strlen($seed); $start++) {
                $this->assertFalse(
                    stripos($actual, substr($seed, $start, 16)) !== false,
                    $name . ': keyForMessage() output contains a part of the seed'
                );
            }
        }

        $plainValues = [
            '',
            ' ',
            'XYZ',
            'GABC',
            'prefix GABC suffix',
            'prefix S' . str_repeat('A', 38) . ' suffix',
            'x' . str_repeat('S', 39),
            "prefix\twith\nbytes\x00",
        ];
        foreach ($plainValues as $plain) {
            $this->assertSame($plain, SecretSeedRedaction::keyForMessage($plain));
        }
    }

    public function testPlaceholdersAreUnchangedUnderRepeatedRedaction(): void
    {
        $placeholders = [
            'secret seed' => 'a secret seed (S...)',
            'value starting with S' => 'a value starting with S (not shown, it may be a secret seed)',
        ];
        foreach ($placeholders as $name => $placeholder) {
            $this->assertTrue(
                SecretSeedRedaction::redactSecretSeeds($placeholder) === $placeholder,
                $name . ': redactSecretSeeds() altered the placeholder'
            );
            $this->assertTrue(
                SecretSeedRedaction::redactSecretSeeds(
                    SecretSeedRedaction::redactSecretSeeds($placeholder)
                ) === $placeholder,
                $name . ': repeated redactSecretSeeds() altered the placeholder'
            );
            $this->assertTrue(
                SecretSeedRedaction::keyForMessage($placeholder) === $placeholder,
                $name . ': keyForMessage() altered the placeholder'
            );
            $wrapped = 'invalid accountId: ' . $placeholder;
            $this->assertTrue(
                SecretSeedRedaction::redactSecretSeeds($wrapped) === $wrapped,
                $name . ': redactSecretSeeds() altered a message containing the placeholder'
            );
        }

        $variants = SecretSeedVariants::create();
        $seed = $variants->seed;
        $chained = [
            'valid seed' => [
                $seed,
                'a secret seed (S...)',
            ],
            'longer starting with S' => [
                $seed . ' tail',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'longer starting with s' => [
                strtolower($seed) . ' tail',
                'a value starting with S (not shown, it may be a secret seed)',
            ],
            'embedded seed' => [
                'prefix ' . $seed . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'lowercase glued seed' => [
                'x' . strtolower($seed),
                'xa secret seed (S...)',
            ],
            'truncated run' => [
                'prefix ' . substr($seed, 0, 40) . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
        ];
        foreach ($chained as $name => [$input, $expected]) {
            $once = SecretSeedRedaction::keyForMessage($input);
            $twice = SecretSeedRedaction::redactSecretSeeds($once);
            $this->assertTrue(
                $once === $expected,
                $name . ': keyForMessage() did not return the expected output'
            );
            $this->assertTrue(
                $twice === $expected,
                $name . ': redactSecretSeeds(keyForMessage()) did not keep output identical'
            );
        }
    }
}
