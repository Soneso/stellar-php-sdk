# SEP-05: Key Derivation for Stellar (BIP-39)

**Purpose:** Derive Stellar keypairs from mnemonic phrases using hierarchical deterministic (HD) key derivation.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\SEP\Derivation`

## Table of Contents

1. [Quick Start](#quick-start)
2. [Generating Mnemonics](#generating-mnemonics)
3. [Mnemonics in Other Languages](#mnemonics-in-other-languages)
4. [Deriving Keypairs from a Mnemonic](#deriving-keypairs-from-a-mnemonic)
5. [Derivation with Passphrase](#derivation-with-passphrase)
6. [Restoring a Mnemonic from Words](#restoring-a-mnemonic-from-words)
7. [Multiple Account Derivation](#multiple-account-derivation)
8. [BIP-39 Seed and m/44'/148' Key](#bip-39-seed-and-m44148-key)
9. [Deriving from a Seed Hex Directly](#deriving-from-a-seed-hex-directly)
10. [Advanced: Direct HDNode Usage](#advanced-direct-hdnode-usage)
11. [Mnemonic Validation and Error Handling](#mnemonic-validation-and-error-handling)
12. [WordList Reference](#wordlist-reference)
13. [Common Pitfalls](#common-pitfalls)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Generate a new 24-word mnemonic (recommended for production)
$mnemonic = Mnemonic::generate24WordsMnemonic();
echo implode(' ', $mnemonic->words) . PHP_EOL;

// Derive the first Stellar account (m/44'/148'/0')
$keyPair = KeyPair::fromMnemonic($mnemonic, 0);
echo 'Public:  ' . $keyPair->getAccountId() . PHP_EOL;  // G...
echo 'Private: ' . $keyPair->getSecretSeed()  . PHP_EOL; // S...
```

---

## Generating Mnemonics

The SDK generates mnemonics from cryptographically secure entropy via `random_bytes()`. All methods are static on the `Mnemonic` class.

### 12-word mnemonic (128-bit entropy)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::generate12WordsMnemonic();
echo $mnemonic->wordsCount . PHP_EOL;         // 12
echo implode(' ', $mnemonic->words) . PHP_EOL; // e.g. "bind struggle sausage ..."
echo $mnemonic->entropy . PHP_EOL;             // hex string (32 chars = 128 bits)
```

### 15-word mnemonic (160-bit entropy)

```php
$mnemonic = Mnemonic::generate15WordsMnemonic();
echo $mnemonic->wordsCount . PHP_EOL; // 15
```

### 24-word mnemonic (256-bit entropy — recommended for production)

```php
$mnemonic = Mnemonic::generate24WordsMnemonic();
echo $mnemonic->wordsCount . PHP_EOL; // 24
```

### Generic generate() — any supported word count

```php
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Supported word counts: 12, 15, 18, 21, 24
$mnemonic = Mnemonic::generate(18);
echo $mnemonic->wordsCount . PHP_EOL; // 18
```

### Mnemonic object properties

```php
$mnemonic->words;           // array<string>  — the mnemonic words
$mnemonic->wordsCount;      // int            — total number of words (12/15/18/21/24)
$mnemonic->wordsIndex;      // array<int>     — BIP-39 word list indices (0–2047) per word
$mnemonic->entropy;         // string|null    — hex entropy (set after generation or restoration)
$mnemonic->rawBinaryChunks; // array<string>  — 11-bit binary chunks (internal)
```

---

## Mnemonics in Other Languages

Pass a `WordList::LANGUAGE_*` constant to any generate method. The constant value is the language string passed to `WordList::getLanguage()`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// English (default — same as passing no language argument)
$en = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_ENGLISH);

// French
$fr = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_FRENCH);
echo implode(' ', $fr->words) . PHP_EOL;

// Spanish
$es = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_SPANISH);

// Italian
$it = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_ITALIAN);

// Japanese
$ja = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_JAPANESE);

// Korean
$ko = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_KOREAN);

// Chinese Simplified
$zhS = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_CHINESE_SIMPLIFIED);

// Chinese Traditional
$zhT = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_CHINESE_TRADITIONAL);

// Malay
$ms = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_MALAY);
```

All supported language constants in `WordList`:

| Constant | Value |
|---|---|
| `WordList::LANGUAGE_ENGLISH` | `"english"` |
| `WordList::LANGUAGE_FRENCH` | `"french"` |
| `WordList::LANGUAGE_SPANISH` | `"spanish"` |
| `WordList::LANGUAGE_ITALIAN` | `"italian"` |
| `WordList::LANGUAGE_JAPANESE` | `"japanese"` |
| `WordList::LANGUAGE_KOREAN` | `"korean"` |
| `WordList::LANGUAGE_CHINESE_SIMPLIFIED` | `"chinese_simplified"` |
| `WordList::LANGUAGE_CHINESE_TRADITIONAL` | `"chinese_traditional"` |
| `WordList::LANGUAGE_MALAY` | `"malay"` |

Keypair derivation works identically regardless of language — pass the `$mnemonic` object to `KeyPair::fromMnemonic()` as usual:

```php
$keyPair = KeyPair::fromMnemonic($fr, 0);
echo $keyPair->getAccountId() . PHP_EOL; // G...
```

---

## Deriving Keypairs from a Mnemonic

`KeyPair::fromMnemonic()` follows the SEP-05 derivation path `m/44'/148'/index'`.

**Signature:**
```php
KeyPair::fromMnemonic(Mnemonic $mnemonic, int $index, ?string $passphrase = ''): KeyPair
```

- `$mnemonic` — a `Mnemonic` object (from generate or restore)
- `$index` — account index (0 = first account, 1 = second, etc.)
- `$passphrase` — optional BIP-39 passphrase (default: empty string)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// SEP-05 Test Vector 1
$words = 'illness spike retreat truth genius clock brain pass fit cave bargain toe';
$mnemonic = Mnemonic::mnemonicFromWords($words);

$kp0 = KeyPair::fromMnemonic($mnemonic, 0);
echo $kp0->getAccountId()  . PHP_EOL; // GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6
echo $kp0->getSecretSeed() . PHP_EOL; // SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2G4O34RMTB343OYPXU5DJDVMN

$kp1 = KeyPair::fromMnemonic($mnemonic, 1);
echo $kp1->getAccountId()  . PHP_EOL; // GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX
echo $kp1->getSecretSeed() . PHP_EOL; // SCEPFFWGAG5P2VX5DHIYK3XEMZYLTYWIPWYEKXFHSK25RVMIUNJ7CTIS
```

---

## Derivation with Passphrase

An optional BIP-39 passphrase creates a completely different set of accounts from the same mnemonic. Different passphrases (including the empty string) produce different keys.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// SEP-05 Test Vector 4
$words = 'cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube';
$mnemonic = Mnemonic::mnemonicFromWords($words);
$passphrase = 'p4ssphr4se';

$kp0 = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);
echo $kp0->getAccountId()  . PHP_EOL; // GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ
echo $kp0->getSecretSeed() . PHP_EOL; // SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X

$kp1 = KeyPair::fromMnemonic($mnemonic, 1, $passphrase);
echo $kp1->getAccountId()  . PHP_EOL; // GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC
echo $kp1->getSecretSeed() . PHP_EOL; // SBQPDFUGLMWJYEYXFRM5TQX3AX2BR47WKI4FDS7EJQUSEUUVY72MZPJF
```

---

## Restoring a Mnemonic from Words

Use `Mnemonic::mnemonicFromWords()` to reconstruct a `Mnemonic` object from an existing phrase.

**Signature:**
```php
Mnemonic::mnemonicFromWords(
    string $words,
    ?string $language = WordList::LANGUAGE_ENGLISH,
    ?bool $verifyChecksum = true
): Mnemonic
```

- `$words` — space-separated mnemonic phrase string
- `$language` — language constant (default: `WordList::LANGUAGE_ENGLISH`)
- `$verifyChecksum` — validate the BIP-39 checksum (default: `true`; set `false` only for debugging)

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// Restore English mnemonic (default language)
try {
    $mnemonic = Mnemonic::mnemonicFromWords(
        'illness spike retreat truth genius clock brain pass fit cave bargain toe'
    );
    $kp = KeyPair::fromMnemonic($mnemonic, 0);
    echo $kp->getAccountId() . PHP_EOL;
} catch (Exception $e) {
    echo 'Invalid mnemonic: ' . $e->getMessage() . PHP_EOL;
}

// Restore non-English mnemonic — must specify the language
$words = 'traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert';
$mnemonic = Mnemonic::mnemonicFromWords($words, WordList::LANGUAGE_FRENCH);
$kp = KeyPair::fromMnemonic($mnemonic, 0);
echo $kp->getAccountId() . PHP_EOL;
```

---

## Multiple Account Derivation

Derive multiple accounts from a single mnemonic using sequential indices. Each index yields a completely independent keypair.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::mnemonicFromWords(
    'bench hurt jump file august wise shallow faculty impulse spring exact slush thunder author capable act festival slice deposit sauce coconut afford frown better'
);

// Derive accounts 0 through 9
for ($i = 0; $i < 10; $i++) {
    $kp = KeyPair::fromMnemonic($mnemonic, $i);
    echo sprintf("m/44'/148'/%d': %s\n", $i, $kp->getAccountId());
}
// m/44'/148'/0': GC3MMSXBWHL6CPOAVERSJITX7BH76YU252WGLUOM5CJX3E7UCYZBTPJQ
// m/44'/148'/1': GB3MTYFXPBZBUINVG72XR7AQ6P2I32CYSXWNRKJ2PV5H5C7EAM5YYISO
// ...
```

---

## BIP-39 Seed and m/44'/148' Key

These methods expose intermediate derivation values for interoperability with other BIP-39 tools (hardware wallets, other SDKs).

### bip39SeedHex()

Returns the 512-bit BIP-39 seed as a 128-character hex string (PBKDF2-SHA512, 2048 iterations).

**Signature:**
```php
$mnemonic->bip39SeedHex(?string $passphrase = ''): string
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// SEP-05 Test Vector 1
$mnemonic = Mnemonic::mnemonicFromWords(
    'illness spike retreat truth genius clock brain pass fit cave bargain toe'
);

$seedHex = $mnemonic->bip39SeedHex();
echo $seedHex . PHP_EOL;
// e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497e
// e4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186

// With passphrase (produces a different 512-bit seed)
$seedWithPassphrase = $mnemonic->bip39SeedHex('p4ssphr4se');
```

### m44148keyHex()

Returns the private key at the Stellar derivation path `m/44'/148'` as a 64-character hex string.

**Signature:**
```php
$mnemonic->m44148keyHex(?string $passphrase = ''): string
```

```php
$keyHex = $mnemonic->m44148keyHex();
echo $keyHex . PHP_EOL;
// e0eec84fe165cd427cb7bc9b6cfdef0555aa1cb6f9043ff1fe986c3c8ddd22e3

$keyWithPassphrase = $mnemonic->m44148keyHex('p4ssphr4se');
```

### generateSeed()

Returns the raw binary seed bytes (used internally by `bip39SeedHex()` and `KeyPair::fromMnemonic()`).

**Signature:**
```php
$mnemonic->generateSeed(?string $passphrase = '', ?int $bytes = 0): string
```

- `$bytes = 0` returns the full hash output (64 bytes for sha512)
- `$bytes = 64` returns exactly 64 bytes (same as full sha512 output)

```php
$seedBytes = $mnemonic->generateSeed('', 64); // 64 raw bytes
$seedHex   = bin2hex($seedBytes);             // same as bip39SeedHex()
```

---

## Deriving from a Seed Hex Directly

Use `KeyPair::fromBip39SeedHex()` when you already have the 512-bit BIP-39 seed (e.g., from a hardware wallet or another tool).

**Signature:**
```php
KeyPair::fromBip39SeedHex(string $bip39SeedHex, int $index): KeyPair
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// 128-character hex string (512 bits)
$bip39SeedHex = 'e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497e' .
                'e4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186';

$kp0 = KeyPair::fromBip39SeedHex($bip39SeedHex, 0);
echo $kp0->getAccountId() . PHP_EOL;  // GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6

$kp1 = KeyPair::fromBip39SeedHex($bip39SeedHex, 1);
echo $kp1->getAccountId() . PHP_EOL;  // GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX

// Verify consistency with fromMnemonic:
$mnemonic = Mnemonic::mnemonicFromWords(
    'illness spike retreat truth genius clock brain pass fit cave bargain toe'
);
$kpFromMnemonic = KeyPair::fromMnemonic($mnemonic, 0);
// $kpFromMnemonic->getAccountId() === $kp0->getAccountId()  — true
```

> Note: `fromBip39SeedHex()` does NOT accept a passphrase parameter. The passphrase must already be incorporated into the seed hex (via `$mnemonic->bip39SeedHex($passphrase)`).

---

## Advanced: Direct HDNode Usage

`HDNode` provides lower-level access to SLIP-0010 ed25519 hierarchical key derivation. Use this when you need chain codes, intermediate nodes, or custom derivation paths.

**HDNode public API:**

```php
HDNode::newMasterNode(string $entropy): HDNode
// $entropy — raw binary seed bytes (typically 64 bytes from generateSeed())

$node->derive(int $index): HDNode
// Derives a hardened child node. Index is auto-hardened (adds 0x80000000).
// IMPORTANT: Only hardened derivation is supported (ed25519 requirement).

$node->derivePath(string $path): HDNode
// $path — e.g. "m/44'/148'" — ALL components must use hardened notation (trailing ')

$node->getPrivateKeyBytes(): string  // 32 raw bytes
$node->getChainCodeBytes(): string   // 32 raw bytes
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\HDNode;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::mnemonicFromWords(
    'illness spike retreat truth genius clock brain pass fit cave bargain toe'
);
$seedBytes = $mnemonic->generateSeed('', 64); // 64 binary bytes

// Create master node from seed
$masterNode = HDNode::newMasterNode($seedBytes);

// Derive the Stellar parent key m/44'/148'
$stellarNode = $masterNode->derivePath("m/44'/148'");
echo 'Parent key: ' . bin2hex($stellarNode->getPrivateKeyBytes()) . PHP_EOL;
// e0eec84fe165cd427cb7bc9b6cfdef0555aa1cb6f9043ff1fe986c3c8ddd22e3

// Derive individual accounts from the parent node
// $stellarNode->derive(0) is equivalent to m/44'/148'/0' from master
$account0Node = $stellarNode->derive(0);
$kp0 = KeyPair::fromPrivateKey($account0Node->getPrivateKeyBytes());
echo $kp0->getAccountId() . PHP_EOL; // GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6

$account1Node = $stellarNode->derive(1);
$kp1 = KeyPair::fromPrivateKey($account1Node->getPrivateKeyBytes());
echo $kp1->getAccountId() . PHP_EOL; // GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX

// Alternatively, derive the full path from master in one call
$account0Direct = $masterNode->derivePath("m/44'/148'/0'");
$kp0Direct = KeyPair::fromPrivateKey($account0Direct->getPrivateKeyBytes());
// $kp0Direct->getAccountId() === $kp0->getAccountId() — true
```

---

## Mnemonic Validation and Error Handling

`Mnemonic::mnemonicFromWords()` and `BIP39::Entropy()` throw `Exception` on invalid input.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Derivation\BIP39;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// --- Word count validation ---
// Throws: "Mnemonic words count must be between 12-24"
try {
    Mnemonic::generate(8);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

// Throws: "Words count must be generated in multiples of 3"
try {
    Mnemonic::generate(13);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

// --- Too few words on restore ---
// Throws: "Mnemonic words count must be between 12-24"
try {
    Mnemonic::mnemonicFromWords('one two three');
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

// --- Unknown word ---
// Throws: "Invalid/unknown word at position N"
try {
    Mnemonic::mnemonicFromWords('illness spike NOTAWORD truth genius clock brain pass fit cave bargain toe');
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL; // Invalid/unknown word at position 3
}

// --- Invalid checksum (valid words but wrong combination) ---
// Throws: "Entropy checksum match failed"
try {
    Mnemonic::mnemonicFromWords(
        'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon',
        WordList::LANGUAGE_ENGLISH,
        true // verifyChecksum = true (default)
    );
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL; // Entropy checksum match failed
}

// --- Checksum bypass (for debugging only) ---
$mnemonic = Mnemonic::mnemonicFromWords(
    'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon',
    WordList::LANGUAGE_ENGLISH,
    false // skip checksum verification
);

// --- Invalid entropy for BIP39::Entropy() ---
// Throws: "Invalid entropy length" (must be 128/160/192/224/256 bits = 32/40/48/56/64 hex chars)
try {
    BIP39::Entropy('deadbeef'); // 32 bits — too short
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL; // Invalid entropy length
}

// Throws: "Invalid entropy (requires hexadecimal)"
try {
    BIP39::Entropy('zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz'); // not valid hex
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

// --- Unsupported language ---
// Throws: 'BIP39 wordlist for "Klingon" not found or is not readable'
try {
    Mnemonic::generate12WordsMnemonic('klingon');
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
```

### Using BIP39::Entropy() — custom hex entropy

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Derivation\BIP39;

// Must be 32, 40, 48, 56, or 64 hex characters (128/160/192/224/256 bits)
$entropy = '0000000000000000000000000000000000000000000000000000000000000000'; // 64 hex = 256 bits
$mnemonic = BIP39::Entropy($entropy);
echo implode(' ', $mnemonic->words) . PHP_EOL;
// abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon
// abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon art
```

---

## WordList Reference

`WordList` loads and caches a BIP-39 word list for a given language. Instances are singletons per language (cached in `WordList::getLanguage()`).

**Public API:**

```php
WordList::getLanguage(string $lang = WordList::LANGUAGE_ENGLISH): WordList
// Returns a cached WordList instance for the specified language.

$wordList->getWord(int $index): ?string
// Returns the word at the given BIP-39 index (0–2047), or null if out of range.

$wordList->findIndex(string $search): ?int
// Returns the index of $search in the word list (case-insensitive), or null if not found.

$wordList->which(): string
// Returns the language code string, e.g. "english", "french".
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Derivation\WordList;

$wordList = WordList::getLanguage(WordList::LANGUAGE_ENGLISH);

echo $wordList->which() . PHP_EOL;            // english
echo $wordList->getWord(0) . PHP_EOL;         // abandon
echo $wordList->getWord(2047) . PHP_EOL;      // zoo
echo $wordList->findIndex('abandon') . PHP_EOL; // 0
echo $wordList->findIndex('zoo') . PHP_EOL;     // 2047
var_dump($wordList->findIndex('notaword'));      // NULL
```

---

## Common Pitfalls

**Passing a plain string to `KeyPair::fromMnemonic()` instead of a Mnemonic object:**

```php
// WRONG: fromMnemonic() requires a Mnemonic object, not a string
$kp = KeyPair::fromMnemonic('illness spike retreat truth genius clock brain pass fit cave bargain toe', 0);
// TypeError

// CORRECT: convert the string first
$mnemonic = Mnemonic::mnemonicFromWords('illness spike retreat truth genius clock brain pass fit cave bargain toe');
$kp = KeyPair::fromMnemonic($mnemonic, 0);
```

**Forgetting to specify the language when restoring a non-English mnemonic:**

```php
// WRONG: attempting to restore a French mnemonic without specifying the language
$mnemonic = Mnemonic::mnemonicFromWords('traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert');
// Throws: "Invalid/unknown word at position 1" (words not in English list)

// CORRECT: pass the language constant
$mnemonic = Mnemonic::mnemonicFromWords(
    'traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert',
    WordList::LANGUAGE_FRENCH
);
```

**Using `BIP39::Generate()` instead of `Mnemonic::generate*()`:**

```php
// WRONG: BIP39::Generate() only produces English mnemonics with no language option
$mnemonic = BIP39::Generate(24); // English only

// CORRECT: use Mnemonic static methods for language support
$mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_SPANISH);
```

**Treating `derive()` index as a final path segment when already at m/44'/148':**

```php
// CORRECT: derivePath("m/44'/148'") then derive(0) gives m/44'/148'/0' (all hardened)
$stellarNode = $masterNode->derivePath("m/44'/148'");
$accountNode = $stellarNode->derive(0);  // index 0 is auto-hardened internally

// Also CORRECT: derive the full path in one call
$accountNode = $masterNode->derivePath("m/44'/148'/0'");
```

**Non-hardened path components in `derivePath()`:**

```php
// WRONG: path without hardened marker (') — throws InvalidArgumentException
$node = $masterNode->derivePath("m/44/148/0");
// InvalidArgumentException: "Path can only contain hardened indexes"

// CORRECT: all components must use hardened notation with '
$node = $masterNode->derivePath("m/44'/148'/0'");
```

**`fromBip39SeedHex()` does not accept a passphrase:**

```php
// WRONG: no passphrase parameter exists on fromBip39SeedHex()
$kp = KeyPair::fromBip39SeedHex($seedHex, 0, 'p4ssphr4se'); // TypeError

// CORRECT: incorporate the passphrase when generating the seed hex
$seedHex = $mnemonic->bip39SeedHex('p4ssphr4se');
$kp = KeyPair::fromBip39SeedHex($seedHex, 0);
```

**Word count not divisible by 3:**

```php
// WRONG: 13 is not divisible by 3
Mnemonic::generate(13);
// Exception: "Words count must be generated in multiples of 3"

// CORRECT: use 12, 15, 18, 21, or 24
Mnemonic::generate(12);
```

**`BIP39::Entropy()` always produces English mnemonics:**

```php
// NOTE: BIP39::Entropy() hardcodes WordList::English() — there is no language parameter.
// If you need a non-English mnemonic from custom entropy, use the fluent BIP39 API:
$mnemonic = (new BIP39(24))
    ->useEntropy('your64hexcharsentropy...')
    ->wordList(WordList::getLanguage(WordList::LANGUAGE_FRENCH))
    ->mnemonic();
```

---
