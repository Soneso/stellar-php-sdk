<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\Derivation;

use Exception;

/**
 * Class Mnemonic
 */
class Mnemonic
{

    public ?string $entropy = null;
    public int $wordsCount;
    public array $wordsIndex;
    public array $words;
    public array $rawBinaryChunks;

    /**
     * Mnemonic constructor.
     * @param string|null $entropy
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
     * @throws Exception
     */
    public static function generate12WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic {

        return self::generate(12, $language);
    }

    /**
     * @throws Exception
     */
    public static function generate15WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic {

        return self::generate(15, $language);
    }

    /**
     * @throws Exception
     */
    public static function generate24WordsMnemonic(?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic  {
        return self::generate(24, $language);
    }

    /**
     * @throws Exception
     */
    public static function generate(int $wordCount, ?string $language =  WordList::LANGUAGE_ENGLISH) : Mnemonic{
        // Generate mnemonic

        return (new BIP39($wordCount))
            ->generateSecureEntropy() // Generate cryptographically secure entropy
            ->wordlist(Wordlist::getLanguage($language))
            ->mnemonic();
    }

    /**
     * Generates Mnemonic from a list of blank separated words.
     * @param string $words
     * @param string|null $language
     * @param bool|null $verifyChecksum
     * @return Mnemonic
     * @throws Exception
     */
    public static function mnemonicFromWords(string $words, ?string $language =  WordList::LANGUAGE_ENGLISH, ?bool $verifyChecksum = true): Mnemonic {
        return BIP39::Words($words, WordList::getLanguage($language), $verifyChecksum);
    }

    /**
     * @param string|null $passphrase
     * @param int|null $bytes
     * @return string
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

    public function bip39SeedHex(?string $passphrase = "") : string {
        $seed = $this->generateSeed($passphrase, 64);
        return bin2hex($seed);
    }

    public function m44148keyHex(?string $passphrase = "") : string {
        $seedBytes = $this->generateSeed($passphrase, 64);
        $masterNode = HDNode::newMasterNode($seedBytes);
        $key = $masterNode->derivePath("m/44'/148'");
        return bin2hex($key->getPrivateKeyBytes());
    }
}
