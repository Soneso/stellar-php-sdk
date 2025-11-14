<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Derivation;

use Exception;

/**
 * BIP-39 implementation for mnemonic phrase generation and validation.
 *
 * This class implements the BIP-39 standard for generating mnemonic phrases
 * from entropy and reconstructing entropy from mnemonic phrases. It handles
 * entropy generation, checksum calculation, and word list operations for
 * creating deterministic wallet seeds.
 *
 * @package Soneso\StellarSDK\SEP\Derivation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
 * @see https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki
 * @see Mnemonic
 * @see WordList
 */
class BIP39
{

    private int $wordsCount;
    private int $overallBits;
    private int $checksumBits;
    private int $entropyBits;
    private ?string $entropy = null;
    private ?array $rawBinaryChunks = null;

    /** @var null|WordList */
    private ?WordList $wordList = null;

    /**
     * Creates a mnemonic from the provided hexadecimal entropy.
     *
     * @param string $entropy Hexadecimal entropy string (128-256 bits).
     * @return Mnemonic The generated mnemonic.
     * @throws Exception If entropy is invalid.
     * @security Minimum entropy requirement is 128 bits (32 hex characters). SEP-5 recommends 256 bits (64 hex characters) for maximum security.
     */
    public static function Entropy(string $entropy): Mnemonic
    {
        self::validateEntropy($entropy);

        $entropyBits = strlen($entropy) * 4;
        $checksumBits = (($entropyBits - 128) / 32) + 4;
        $wordsCount = ($entropyBits + $checksumBits) / 11;
        return (new self($wordsCount))
            ->useEntropy($entropy)
            ->wordlist(WordList::English())
            ->mnemonic();
    }

    /**
     * Generates a new mnemonic with cryptographically secure entropy.
     *
     * @param int $wordCount Number of words in the mnemonic (12, 15, 18, 21, or 24). Default is 12.
     * @return Mnemonic The generated mnemonic.
     * @throws Exception If word count is invalid.
     * @security Uses cryptographically secure random_bytes() for entropy generation. SEP-5 recommends 24-word mnemonics (256 bits) for production use.
     */
    public static function Generate(int $wordCount = 12): Mnemonic
    {
        return (new self($wordCount))
            ->generateSecureEntropy()
            ->wordlist(WordList::English())
            ->mnemonic();
    }

    /**
     * Reconstructs a mnemonic from words.
     *
     * @param string|array<string> $words Mnemonic words as string or array.
     * @param WordList|null $wordList Word list to use. Default is English.
     * @param bool $verifyChecksum Whether to verify entropy checksum. Default is true.
     * @return Mnemonic The reconstructed mnemonic.
     * @throws Exception If words are invalid or checksum verification fails.
     */
    public static function Words($words, ?WordList $wordList = null, bool $verifyChecksum = true): Mnemonic
    {
        if (is_string($words)) {
            $words = explode(" ", $words);
        }

        if (!is_array($words)) {
            throw new Exception('Mnemonic constructor requires an Array of words');
        }

        $wordCount = count($words);
        return (new self($wordCount))
            ->wordlist($wordList ?? WordList::English())
            ->reverse($words, $verifyChecksum);
    }

    /**
     * BIP39 constructor.
     *
     * @param int $wordCount Number of words (must be between 12-24 and divisible by 3). Default is 12.
     * @throws Exception If word count is invalid.
     */
    public function __construct(int $wordCount = 12)
    {
        if ($wordCount < 12 || $wordCount > 24) {
            throw new Exception('Mnemonic words count must be between 12-24');
        } elseif ($wordCount % 3 !== 0) {
            throw new Exception('Words count must be generated in multiples of 3');
        }

        // Actual words count
        $this->wordsCount = $wordCount;
        // Overall entropy bits (ENT+CS)
        $this->overallBits = $this->wordsCount * 11;
        // Checksum Bits are 1 bit per 3 words, starting from 12 words with 4 CS bits
        $this->checksumBits = (($this->wordsCount - 12) / 3) + 4;
        // Entropy Bits (ENT)
        $this->entropyBits = $this->overallBits - $this->checksumBits;
    }

    /**
     * Uses the provided entropy for mnemonic generation.
     *
     * @param string $entropy Hexadecimal entropy string.
     * @return BIP39 This instance for method chaining.
     * @throws Exception If entropy is invalid.
     */
    public function useEntropy(string $entropy): self
    {
        self::validateEntropy($entropy);
        $this->entropy = $entropy;
        $checksum = $this->checksum($entropy, $this->checksumBits);
        $this->rawBinaryChunks = str_split($this->hex2bits($this->entropy) . $checksum, 11);
        return $this;
    }

    /**
     * Generates cryptographically secure entropy.
     *
     * @return BIP39 This instance for method chaining.
     * @throws Exception If entropy generation fails.
     */
    public function generateSecureEntropy(): self
    {
        $this->useEntropy(bin2hex(random_bytes($this->entropyBits / 8)));
        return $this;
    }

    /**
     * Generates the final mnemonic from entropy and word list.
     *
     * @return Mnemonic The generated mnemonic.
     * @throws Exception If entropy or word list is not defined.
     */
    public function mnemonic(): Mnemonic
    {
        if (!$this->entropy) {
            throw new Exception('Entropy is not defined');
        }

        if (!$this->wordList) {
            throw new Exception('Word list is not defined');
        }

        $mnemonic = new Mnemonic($this->entropy);
        foreach ($this->rawBinaryChunks as $bit) {
            $index = bindec($bit);
            $mnemonic->wordsIndex[] = $index;
            $mnemonic->words[] = $this->wordList->getWord($index);
            $mnemonic->rawBinaryChunks[] = $bit;
            $mnemonic->wordsCount++;
        }

        return $mnemonic;
    }

    /**
     * Sets the word list to use for mnemonic generation.
     *
     * @param WordList $wordList The word list to use.
     * @return BIP39 This instance for method chaining.
     */
    public function wordList(WordList $wordList): self
    {
        $this->wordList = $wordList;
        return $this;
    }

    /**
     * Reconstructs a mnemonic from an array of words.
     *
     * @param array<string> $words Array of mnemonic words.
     * @param bool $verifyChecksum Whether to verify the entropy checksum. Default is true.
     * @return Mnemonic The reconstructed mnemonic.
     * @throws Exception If words are invalid or checksum verification fails.
     */
    public function reverse(array $words, bool $verifyChecksum = true): Mnemonic
    {
        if (!$this->wordList) {
            throw new Exception('Wordlist is not defined');
        }

        $mnemonic = new Mnemonic();
        $pos = 0;
        foreach ($words as $word) {
            $pos++;
            $index = $this->wordList->findIndex($word);
            if (is_null($index)) {
                throw new Exception(sprintf('Invalid/unknown word at position %d', $pos));
            }

            $mnemonic->words[] = $word;
            $mnemonic->wordsIndex[] = $index;
            $mnemonic->wordsCount++;
            $mnemonic->rawBinaryChunks[] = str_pad(decbin($index), 11, '0', STR_PAD_LEFT);
        }

        $rawBinary = implode('', $mnemonic->rawBinaryChunks);
        $entropyBits = substr($rawBinary, 0, $this->entropyBits);
        $checksumBits = substr($rawBinary, $this->entropyBits, $this->checksumBits);

        $mnemonic->entropy = $this->bits2hex($entropyBits);

        // Verify Checksum?
        if ($verifyChecksum) {
            if (!hash_equals($checksumBits, $this->checksum($mnemonic->entropy, $this->checksumBits))) {
                throw new Exception('Entropy checksum match failed');
            }
        }

        return $mnemonic;
    }

    /**
     * Converts a hexadecimal string to binary representation.
     *
     * @param string $hex Hexadecimal string to convert.
     * @return string Binary string representation (4 bits per hex character).
     */
    private function hex2bits(string $hex): string
    {
        $bits = "";
        for ($i = 0; $i < strlen($hex); $i++) {
            $bits .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        return $bits;
    }

    /**
     * Converts a binary string to hexadecimal representation.
     *
     * @param string $bits Binary string to convert (groups of 4 bits).
     * @return string Hexadecimal string representation.
     */
    private function bits2hex(string $bits): string
    {
        $hex = "";
        foreach (str_split($bits, 4) as $chunk) {
            $hex .= base_convert($chunk, 2, 16);
        }

        return $hex;
    }

    /**
     * Calculates the BIP-39 checksum for the given entropy.
     *
     * @param string $entropy Hexadecimal entropy string.
     * @param int $bits Number of checksum bits to generate (1 bit per 3 words, starting from 4 bits for 12 words).
     * @return string Binary string of the checksum bits.
     */
    private function checksum(string $entropy, int $bits): string
    {
        $checksumChar = ord(hash("sha256", hex2bin($entropy), true)[0]);
        $checksum = '';
        for ($i = 0; $i < $bits; $i++) {
            $checksum .= $checksumChar >> (7 - $i) & 1;
        }

        return $checksum;
    }

    /**
     * Validates entropy format and length according to BIP-39 requirements.
     *
     * @param string $entropy Hexadecimal entropy string to validate.
     * @throws Exception If entropy is not valid hexadecimal or has invalid length (must be 128, 160, 192, 224, or 256 bits).
     */
    private static function validateEntropy(string $entropy): void
    {
        if (!preg_match('/^[a-f0-9]{2,}$/', $entropy)) {
            throw new Exception('Invalid entropy (requires hexadecimal)');
        }

        $entropyBits = strlen($entropy) * 4;
        if (!in_array($entropyBits, [128, 160, 192, 224, 256])) {
            throw new Exception('Invalid entropy length');
        }
    }
}
