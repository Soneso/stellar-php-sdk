<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\StrKey;

/**
 * Keeps a secret seed given by mistake out of error messages and Horizon request URLs.
 *
 * A key position is an input whose every valid value is a strkey or an id that cannot start
 * with "S" or "s", the first letter of a secret seed: an address, an account, contract or
 * muxed id, a pool or claimable balance id, a transaction hash. A message shows such an input
 * through keyForMessage(), which names a value starting with that letter by its kind and never
 * shows it; this covers a seed with a changed, a missing or an extra character and a lowercase
 * seed. Any other position, such as an amount, a federation address or a name, and a text that
 * wraps a caller's input go through redactSecretSeeds(). A request method refuses a key
 * position value that starts with that letter through requireNotSecretSeed() before it builds
 * the request.
 *
 * @internal used by the address, id, amount and TxRep error messages and the request builders
 */
final class SecretSeedRedaction
{
    /**
     * The text naming a valid secret seed.
     */
    private const SECRET_SEED = 'a secret seed (S...)';

    /**
     * The text naming any other value that starts with "S" or "s" in a key position.
     */
    private const VALUE_STARTING_WITH_S = 'a value starting with S (not shown, it may be a secret seed)';

    /**
     * The shortest run of letters and digits redactSecretSeeds() replaces. A secret seed has
     * 56 characters, so a seed with a few characters missing is still replaced.
     */
    private const SEED_LIKE_RUN_MIN_LENGTH = 40;

    /**
     * The length of a secret seed strkey.
     */
    private const SEED_LENGTH = 56;

    private const LETTERS_AND_DIGITS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    private const BASE32_EITHER_CASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz234567';

    /**
     * The white space trimmed from a value: ASCII white space, the Unicode separators, the next
     * line character and the byte order mark.
     */
    private const WHITE_SPACE = '[\s\p{Z}\x{0085}\x{FEFF}]';

    /**
     * Returns the kind of $value given in a key position: "a secret seed (S...)" when $value,
     * trimmed of white space, is a valid secret seed in either letter case, "a value starting
     * with S (not shown, it may be a secret seed)" for any other value whose trimmed form starts
     * with "S" or "s", and null for every other value.
     *
     * No address, account, contract or muxed id, pool or claimable balance id or hex id of a
     * valid kind starts with this letter, so a valid input never has a kind.
     *
     * @param string $value the input as given
     * @return string|null the kind, or null when the value does not start with "S" or "s"
     */
    public static function seedKind(string $value): ?string
    {
        $start = self::withoutLeadingWhiteSpace($value);
        if (!str_starts_with($start, 'S') && !str_starts_with($start, 's')) {
            return null;
        }
        // A seed holds no white space, so the trimmed value is a seed exactly when its first
        // 56 characters are one and only white space follows them.
        $isSeed = StrKey::isValidSeed(strtoupper(substr($start, 0, self::SEED_LENGTH)))
            && self::isWhiteSpace(substr($start, self::SEED_LENGTH));
        return $isSeed ? self::SECRET_SEED : self::VALUE_STARTING_WITH_S;
    }

    /**
     * Returns $value, an input meant as an address, an account id, another strkey or an id, the
     * way a message shows it: the kind seedKind() names, otherwise $value unchanged.
     *
     * @param string $value the input as given
     * @return string the text a message shows for the input
     */
    public static function keyForMessage(string $value): string
    {
        return self::seedKind($value) ?? $value;
    }

    /**
     * Returns $text with every maximal run of ASCII letters and digits that starts with "S" or
     * "s" and has at least 40 characters replaced by "a secret seed (S...)", and then every
     * valid secret seed in any letter case that is left inside the text replaced as well.
     *
     * A secret seed is such a run of 56 characters, and a seed with a changed, a missing or an
     * extra character still is one. Shorter runs, such as words, asset codes and numbers, stay.
     * A run of that length that is no seed, for example a fragment of base64 text that starts
     * with "S", is replaced as well. A valid seed glued to a preceding letter or digit opens no
     * run of its own and is found by its checksum.
     *
     * @param string $text the diagnostic text, which may hold a caller's input
     * @return string the text without anything that may be a secret seed
     */
    public static function redactSecretSeeds(string $text): string
    {
        return self::replaceValidSecretSeeds(self::replaceSeedLikeRuns($text));
    }

    /**
     * Refuses a value that starts with "S" or "s" for a request parameter whose every valid
     * value is a strkey or a hex id, before the value is put into a request URL. The message
     * names the parameter and the kind of value, never the value.
     *
     * @param string $value the parameter value as given
     * @param string $name the parameter, as the message names it
     * @throws InvalidArgumentException when the trimmed value starts with "S" or "s"
     */
    public static function requireNotSecretSeed(string $value, string $name): void
    {
        $kind = self::seedKind($value);
        if ($kind !== null) {
            throw new InvalidArgumentException($name . ' is ' . $kind . '; the request is not sent');
        }
    }

    /**
     * Refuses the issuer of a credit asset through requireNotSecretSeed(), reported as the
     * issuer of $name; the native asset and a pool share asset have no issuer to check.
     *
     * @param Asset $asset the asset a request filters by
     * @param string $name the asset parameter, as the message names it
     * @throws InvalidArgumentException when the issuer, trimmed, starts with "S" or "s"
     */
    public static function requireAssetIssuerNotSecretSeed(Asset $asset, string $name): void
    {
        if ($asset instanceof AssetTypeCreditAlphanum) {
            self::requireNotSecretSeed($asset->getIssuer(), $name . ' issuer');
        }
    }

    /**
     * Returns $value without its leading white space. A value that is not valid UTF-8 loses
     * its leading ASCII white space only.
     */
    private static function withoutLeadingWhiteSpace(string $value): string
    {
        $start = ltrim($value);
        return preg_replace('/^' . self::WHITE_SPACE . '+/u', '', $start) ?? $start;
    }

    /**
     * Returns whether $text is empty or consists of white space only.
     */
    private static function isWhiteSpace(string $text): bool
    {
        return trim($text) === '' || preg_match('/^' . self::WHITE_SPACE . '+$/Du', $text) === 1;
    }

    /**
     * Returns $text with every maximal run of ASCII letters and digits that starts with "S" or
     * "s" and has at least SEED_LIKE_RUN_MIN_LENGTH characters replaced by SECRET_SEED.
     */
    private static function replaceSeedLikeRuns(string $text): string
    {
        $result = '';
        $length = strlen($text);
        $index = 0;
        while ($index < $length) {
            $gapLength = strcspn($text, self::LETTERS_AND_DIGITS, $index);
            $result .= substr($text, $index, $gapLength);
            $index += $gapLength;
            $runLength = strspn($text, self::LETTERS_AND_DIGITS, $index);
            $run = substr($text, $index, $runLength);
            $seedLike = $runLength >= self::SEED_LIKE_RUN_MIN_LENGTH && ($run[0] === 'S' || $run[0] === 's');
            $result .= $seedLike ? self::SECRET_SEED : $run;
            $index += $runLength;
        }
        return $result;
    }

    /**
     * Returns $text with every 56 character substring that is a valid secret seed once
     * uppercased replaced by SECRET_SEED, scanning from the start.
     */
    private static function replaceValidSecretSeeds(string $text): string
    {
        $length = strlen($text);
        $result = '';
        $copied = 0;
        $index = strcspn($text, 'Ss');
        while ($index + self::SEED_LENGTH <= $length) {
            if (strspn($text, self::BASE32_EITHER_CASE, $index, self::SEED_LENGTH) === self::SEED_LENGTH
                && StrKey::isValidSeed(strtoupper(substr($text, $index, self::SEED_LENGTH)))) {
                $result .= substr($text, $copied, $index - $copied) . self::SECRET_SEED;
                $index += self::SEED_LENGTH;
                $copied = $index;
            } else {
                $index++;
            }
            $index += strcspn($text, 'Ss', $index);
        }
        return $result . substr($text, $copied);
    }
}
