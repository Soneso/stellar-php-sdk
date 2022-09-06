<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Derivation;

use Exception;

/**
 * Class WordList
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
     * @param string $lang
     * @return WordList
     * @throws Exception
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
     * @param string $language
     * @throws Exception
     */
    public function __construct(string $language = WordList::LANGUAGE_ENGLISH)
    {
        $this->language = trim($language);
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
     * @return array
     */
    public function __debugInfo()
    {
        return [sprintf('BIP39 wordlist for "%s" Language', ucfirst($this->language))];
    }

    /**
     * @return string
     */
    public function which(): string
    {
        return $this->language;
    }

    /**
     * @param int $index
     * @return string|null
     */
    public function getWord(int $index): ?string
    {
        return $this->words[$index] ?? null;
    }

    /**
     * @param string $search
     * @return int|null
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