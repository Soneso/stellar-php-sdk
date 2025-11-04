<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Derivation;

use Exception;

/**
 * BIP-39 compliant mnemonic phrase for hierarchical deterministic key derivation.
 *
 * This class represents a mnemonic phrase used for generating deterministic wallets
 * following the BIP-39 standard. It can generate secure mnemonics of 12, 15, or 24
 * words and derive seeds for use with SEP-0005 key derivation paths.
 *
 * @package Soneso\StellarSDK\SEP\Derivation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
 * @see BIP39
 * @see WordList
 * @see HDNode
 */
class Mnemonic
{

    /**
     * @var string|null $entropy The entropy bytes used to generate the mnemonic
     */
    public ?string $entropy = null;

    /**
     * @var int $wordsCount The number of mnemonic words
     */
    public int $wordsCount;

    /**
     * @var array $wordsIndex The index values for each word
     */
    public array $wordsIndex;

    /**
     * @var array $words The mnemonic words array
     */
    public array $words;

    /**
     * @var array $rawBinaryChunks The raw binary chunks
     */
    public array $rawBinaryChunks;

    /**
     * Mnemonic constructor.
     *
     * @param string|null $entropy Hexadecimal entropy string. Optional.
     */
    public function __construct(?string $entropy = null)
    {
        $this->entropy = $entropy;
        $this->wordsCount = 0;
        $this->wordsIndex = [];
        $this->words = [];
        $this->rawBinaryChunks = [];
    }

    /**
     * Generates a new 12-word mnemonic phrase.
     *
     * @param string|null $language Word list language. Default is English.
     * @return Mnemonic A new mnemonic with 12 words.
     * @throws Exception If mnemonic generation fails.
     */
    public static function generate12WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic {

        return self::generate(12, $language);
    }

    /**
     * Generates a new 15-word mnemonic phrase.
     *
     * @param string|null $language Word list language. Default is English.
     * @return Mnemonic A new mnemonic with 15 words.
     * @throws Exception If mnemonic generation fails.
     */
    public static function generate15WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic {

        return self::generate(15, $language);
    }

    /**
     * Generates a new 24-word mnemonic phrase.
     *
     * @param string|null $language Word list language. Default is English.
     * @return Mnemonic A new mnemonic with 24 words.
     * @throws Exception If mnemonic generation fails.
     */
    public static function generate24WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic  {
        return self::generate(24, $language);
    }

    /**
     * Generates a new mnemonic phrase with the specified word count.
     *
     * @param int $wordCount Number of words (12, 15, 18, 21, or 24).
     * @param string|null $language Word list language. Default is English.
     * @return Mnemonic A new mnemonic with the specified number of words.
     * @throws Exception If word count is invalid or generation fails.
     */
    public static function generate(int $wordCount, ?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic{
        // Generate mnemonic

        return (new BIP39($wordCount))
            ->generateSecureEntropy() // Generate cryptographically secure entropy
            ->wordlist(Wordlist::getLanguage($language))
            ->mnemonic();
    }

    /**
     * Generates Mnemonic from a space-separated list of words.
     *
     * @param string $words Space-separated mnemonic words.
     * @param string|null $language Word list language. Default is English.
     * @param bool|null $verifyChecksum Whether to verify entropy checksum. Default is true.
     * @return Mnemonic The reconstructed mnemonic.
     * @throws Exception If words are invalid or checksum verification fails.
     */
    public static function mnemonicFromWords(string $words, ?string $language =  WordList::LANGUAGE_ENGLISH, ?bool $verifyChecksum = true): Mnemonic {
        return BIP39::Words($words, WordList::getLanguage($language), $verifyChecksum);
    }

    /**
     * Generates a binary seed from the mnemonic using PBKDF2.
     *
     * @param string|null $passphrase Optional passphrase for additional security. Default is empty string.
     * @param int|null $bytes Number of bytes to return. Default is 0 (returns full hash).
     * @return string The generated binary seed.
     */
    public function generateSeed(?string $passphrase = "", ?int $bytes = 0) : string
    {
        return hash_pbkdf2(
            "sha512",
            implode(" ", $this->words),
            "mnemonic" . $passphrase,
            2048,
            $bytes,
            true
        );
    }

    /**
     * Generates a hexadecimal BIP-39 seed from the mnemonic.
     *
     * @param string|null $passphrase Optional passphrase for additional security. Default is empty string.
     * @return string The generated seed as a hexadecimal string (128 characters).
     */
    public function bip39SeedHex(?string $passphrase = "") : string {
        $seed = $this->generateSeed($passphrase, 64);
        return bin2hex($seed);
    }

    /**
     * Derives a Stellar private key following the m/44'/148' derivation path.
     *
     * @param string|null $passphrase Optional passphrase for additional security. Default is empty string.
     * @return string The private key as a hexadecimal string (64 characters).
     */
    public function m44148keyHex(?string $passphrase = "") : string {
        $seedBytes = $this->generateSeed($passphrase, 64);
        $masterNode = HDNode::newMasterNode($seedBytes);
        $key = $masterNode->derivePath("m/44'/148'");
        return bin2hex($key->getPrivateKeyBytes());
    }
}
