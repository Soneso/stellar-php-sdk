<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Derivation;

class Bip39
{
    protected array $words;

    /**
     * Returns the hex-encoded checksum for the raw bytes in the entropy
     *
     * @param string $entropyBytes
     * @return string
     */
    public static function getEntropyChecksumHex(string $entropyBytes): string
    {
        $checksumLengthBits = (strlen($entropyBytes)*8) / 32;
        $hashBytes = hash('sha256', $entropyBytes, true);

        // base_convert can only handle up to 64 bits, so we have to reduce the
        // length of data that gets sent to it
        $checksumLengthBytes = ceil($checksumLengthBits / 8);
        $reducedBytesToChecksum = substr($hashBytes, 0, $checksumLengthBytes);

        $reducedChecksumHex = bin2hex($reducedBytesToChecksum);
        $reducedChecksumBits = str_pad(base_convert($reducedChecksumHex, 16, 2), $checksumLengthBytes * 8, '0', STR_PAD_LEFT);

        $checksumBitstring = substr($reducedChecksumBits, 0, $checksumLengthBits);
        $checksumHex = static::bitstringToHex($checksumBitstring);

        return $checksumHex;
    }

    /**
     * Utility method to convert a bitstring to hex.
     *
     * Primarily a workaround to avoid requiring a real math library
     *
     * @param string $bitstring
     * @return string
     */
    public static function bitstringToHex(string $bitstring): string
    {
        $chunkSizeBits = 8;

        // If the string is shorter than the chunk size it can be 0-padded
        if (strlen($bitstring) < $chunkSizeBits) {
            $bitstring = str_pad($bitstring, $chunkSizeBits, '0', STR_PAD_LEFT);
        }

        if (strlen($bitstring) % $chunkSizeBits !== 0) throw new \InvalidArgumentException(sprintf('Got bitstring of length %s, but it must be divisible by %s', strlen($bitstring), $chunkSizeBits));

        $finalHex = '';
        for ($i=0; $i < strlen($bitstring); $i += $chunkSizeBits) {
            $bitstringPart = substr($bitstring, $i, $chunkSizeBits);
            $hex = base_convert($bitstringPart, 2, 16);
            // Ensure hex is always two characters
            $hex = str_pad($hex, 2, '0', STR_PAD_LEFT);

            $finalHex .= $hex;
        }

        return $finalHex;
    }

    /**
     * Bip39 constructor.
     *
     * @param string|null $lang possible values: english, chinese_simplified, chinese_traditional, french, italian, spanish, korean, japanese
     */
    public function __construct(?string $lang = null)
    {
        $wordlistPath = __DIR__ . "/wordlists/english.txt";
        if ($lang != null) {
            $wordlistPath = __DIR__ . "/wordlists/" . $lang . ".txt";
        }

        $this->words = $this->loadWordlist($wordlistPath);
    }

    /**
     * Converts a mnemonic to raw bytes
     *
     * NOTE: this is NOT the raw bytes used for a Stellar key! See mnemonicToSeedBytes
     *
     * @param string $mnenomic
     * @return false|string
     */
    public function mnemonicToEntropy(string $mnenomic): false|string
    {
        $bitstring = $this->parseMnemonic($mnenomic);

        // Calculate expected lengths
        $numChecksumBits = strlen($bitstring) / 33;
        $numEntropyBits = strlen($bitstring) - $numChecksumBits;

        // Get checksum bits from the end of the string
        $checksumBits = substr($bitstring, -1 * $numChecksumBits);
        $checksumHex = static::bitstringToHex($checksumBits);

        // Remaining bits are the entropy
        $entropyBits = substr($bitstring, 0, $numEntropyBits);
        $entropyHex = static::bitstringToHex($entropyBits);

        $entropyBytes = hex2bin($entropyHex);

        if ($checksumHex !== static::getEntropyChecksumHex($entropyBytes)) {
            throw new \InvalidArgumentException('Invalid checksum');
        }

        return $entropyBytes;
    }

    /**
     * Converts a mnemonic and optional passphrase to a 64-byte string for use
     * as entropy.
     *
     * Note that this is specific to the wordlist being used and is NOT portable
     * across wordlists.
     *
     * In most cases, mnemonicToSeedBytesWithErrorChecking should be used since
     * it will fail if there's a checksum error in the mnemonic
     *
     * @param string $mnemonic
     * @param string $passphrase
     * @return string
     */
    public function mnemonicToSeedBytes(string $mnemonic, string $passphrase = '') : string
    {
        $salt = 'mnemonic' . $passphrase;
        return hash_pbkdf2("sha512", $mnemonic, $salt, 2048, 64, true);
    }

    /**
     * Converts $mnemonic to seed bytes suitable for creating a new HDNode
     *
     * If the mnemonic is invalid, an exception is thrown
     *
     * @param string $mnemonic
     * @param string $passphrase
     * @return string raw bytes
     */
    public function mnemonicToSeedBytesWithErrorChecking(string $mnemonic, string $passphrase = '') : string
    {
        // This will throw an exception if the embedded checksum is incorrect
        $this->mnemonicToEntropy($mnemonic);

        return $this->mnemonicToSeedBytes($mnemonic, $passphrase);
    }

    /**
     * Parses a string of words and returns a string representing the binary
     * encoding of the mnemonic (including checksum)
     *
     * Note that this is a literal string of "101101110" and not raw bytes!
     *
     * @param string $mnemonic
     * @return string
     */
    protected function parseMnemonic(string $mnemonic): string
    {
        $words = explode(' ', $mnemonic);
        if (count($words) %3 !== 0) throw new \InvalidArgumentException('Invalid mnemonic (number of words must be a multiple of 3)');

        $wordBitstrings = [];
        foreach ($words as $word) {
            $wordIdx = $this->getWordIndex($word);

            // Convert $wordIdx to an 11-bit number (preserving 0s)
            $wordBitstrings[] = str_pad(decbin($wordIdx), 11, '0', STR_PAD_LEFT);
        }

        // Return a string representing each bit
        return join('', $wordBitstrings);
    }

    /**
     * @param string $word
     * @return int
     */
    protected function getWordIndex(string $word) : int
    {
        $index = 0;

        foreach ($this->words as $wordInList) {
            if ($wordInList === $word) return $index;

            $index++;
        }

        throw new \InvalidArgumentException(sprintf('Word "%s" not found in wordlist', $word));
    }

    /**
     * @param string $wordlistPath
     * @return array
     */
    protected function loadWordlist(string $wordlistPath): array
    {
        $this->words = [];
        if (!file_exists($wordlistPath)) throw new \InvalidArgumentException('Cannot load wordlist from "%s"', $wordlistPath);

        foreach (file($wordlistPath) as $word) {
            $this->words[] = trim($word);
        }

        return $this->words;
    }
}