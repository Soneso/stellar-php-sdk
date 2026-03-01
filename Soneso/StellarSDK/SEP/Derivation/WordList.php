<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Derivation;

use Exception;
use InvalidArgumentException;

/**
 * BIP-39 word list loader for multiple languages.
 *
 * This class manages the loading and lookup of BIP-39 word lists in various
 * languages. Each word list contains exactly 2048 words used for mnemonic
 * phrase generation and validation according to the BIP-39 standard.
 *
 * @package Soneso\StellarSDK\SEP\Derivation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
 * @see https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki
 * @see BIP39
 * @see Mnemonic
 */
class WordList
{
    public const LANGUAGE_ENGLISH = "english";
    public const LANGUAGE_CHINESE_SIMPLIFIED = 'chinese_simplified';
    public const LANGUAGE_CHINESE_TRADITIONAL = 'chinese_traditional';
    public const LANGUAGE_FRENCH = 'french';
    public const LANGUAGE_ITALIAN = 'italian';
    public const LANGUAGE_JAPANESE = 'japanese';
    public const LANGUAGE_KOREAN = 'korean';
    public const LANGUAGE_SPANISH = 'spanish';
    public const LANGUAGE_MALAY = 'malay';

    private static array $instances = [];
    private string $language;
    private array $words;
    private int $count;

    /**
     * Gets a cached word list instance for the specified language.
     *
     * @param string $lang Language code. Default is English.
     * @return WordList The word list instance.
     * @throws Exception If the word list file is not found or invalid.
     */
    public static function getLanguage(string $lang = WordList::LANGUAGE_ENGLISH): self
    {
        $instance = self::$instances[$lang] ?? null;
        if ($instance) {
            return $instance;
        }

        $wordList = new self($lang);
        self::$instances[$lang] = $wordList;
        return self::getLanguage($lang);
    }

    /**
     * WordList constructor.
     *
     * @param string $language Language code for the word list. Default is English.
     * @throws Exception If the word list file is not found or does not contain exactly 2048 words.
     */
    private const ALLOWED_LANGUAGES = [
        self::LANGUAGE_ENGLISH,
        self::LANGUAGE_CHINESE_SIMPLIFIED,
        self::LANGUAGE_CHINESE_TRADITIONAL,
        self::LANGUAGE_FRENCH,
        self::LANGUAGE_ITALIAN,
        self::LANGUAGE_JAPANESE,
        self::LANGUAGE_KOREAN,
        self::LANGUAGE_SPANISH,
        self::LANGUAGE_MALAY,
    ];

    public function __construct(string $language = WordList::LANGUAGE_ENGLISH)
    {
        if (!in_array($language, self::ALLOWED_LANGUAGES, true)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported BIP39 language: "%s"', $language)
            );
        }
        $this->language = $language;
        $this->words = [];
        $this->count = 0;

        $wordListFile = sprintf('%1$s%2$swordlists%2$s%3$s.txt', __DIR__, DIRECTORY_SEPARATOR, $this->language);
        if (!file_exists($wordListFile) || !is_readable($wordListFile)) {
            throw new Exception(
                sprintf('BIP39 wordlist for "%s" not found or is not readable', ucfirst($this->language))
            );
        }

        $wordList = preg_split("/(\r\n|\n|\r)/", file_get_contents($wordListFile));
        foreach ($wordList as $word) {
            $this->words[] = trim($word);
            $this->count++;
        }

        if ($this->count !== 2048) {
            throw new Exception('BIP39 words list file must have precise 2048 entries');
        }
    }

    /**
     * Provides debug information for var_dump and print_r output.
     *
     * Returns a single-element array containing a human-readable description
     * of the word list language for debugging purposes. Invoked automatically
     * by var_dump() and print_r() when inspecting WordList instances.
     *
     * @return array<int, string> Indexed array with a formatted language description
     */
    public function __debugInfo(): array
    {
        return [sprintf('BIP39 wordlist for "%s" Language', ucfirst($this->language))];
    }

    /**
     * Returns the language code of this word list.
     *
     * @return string The language code.
     */
    public function which(): string
    {
        return $this->language;
    }

    /**
     * Gets the word at the specified index.
     *
     * @param int $index Index in the word list. Valid range is 0-2047 (BIP-39 standard word list size).
     * @return string|null The word at the index, or null if index is invalid.
     */
    public function getWord(int $index): ?string
    {
        return $this->words[$index] ?? null;
    }

    /**
     * Finds the index of a word in the word list.
     *
     * @param string $search The word to search for (case-insensitive).
     * @return int|null The index of the word, or null if not found.
     */
    public function findIndex(string $search): ?int
    {
        $search = mb_strtolower($search);
        foreach ($this->words as $pos => $word) {
            if ($search === $word) {
                return $pos;
            }
        }

        return null;
    }
}