# SEP-05: Key derivation for Stellar

SEP-05 defines how to generate Stellar keypairs from mnemonic phrases using hierarchical deterministic (HD) key derivation. Users can backup their entire wallet with a simple word list and derive multiple accounts from a single seed using the path `m/44'/148'/index'`.

**When to use:** Building wallets that support mnemonic backup phrases, recovering accounts from seed words, or generating multiple related accounts from a single master seed.

See the [SEP-05 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md) for protocol details.

## Quick example

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Generate a new 24-word mnemonic
$mnemonic = Mnemonic::generate24WordsMnemonic();
echo implode(' ', $mnemonic->words) . PHP_EOL;

// Derive the first account
$keyPair = KeyPair::fromMnemonic($mnemonic, 0);
echo "Account: " . $keyPair->getAccountId() . PHP_EOL;
```

## Generating mnemonics

The SDK supports generating mnemonics with 12, 15, 18, 21, or 24 words using cryptographically secure entropy.

### 12-word mnemonic

Standard security for most use cases (128 bits entropy):

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::generate12WordsMnemonic();
echo implode(' ', $mnemonic->words) . PHP_EOL;
// bind struggle sausage repair machine fee setup finish transfer stamp benefit economy
```

### 24-word mnemonic

Higher security for larger holdings (256 bits entropy, recommended for production):

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::generate24WordsMnemonic();
echo implode(' ', $mnemonic->words) . PHP_EOL;
// cabbage verb depart erase cable eye crowd approve tower umbrella violin tube 
// island tortoise suspect resemble harbor twelve romance away rug current robust practice
```

### 15-word mnemonic

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::generate15WordsMnemonic();
echo implode(' ', $mnemonic->words) . PHP_EOL;
```

### Custom entropy

Generate mnemonics from your own entropy (must be 128, 160, 192, 224, or 256 bits):

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\BIP39;

// Using 256 bits of entropy (64 hex characters)
$entropy = '15c5e7a9a97b1aa6db8e5c83a21a1e4e6ed7c0b4b73b7c2e2b7d4a7e0f0e7e7e';
$mnemonic = BIP39::Entropy($entropy);

echo "Words: " . implode(' ', $mnemonic->words) . PHP_EOL;
echo "Entropy: " . $mnemonic->entropy . PHP_EOL;
```

## Mnemonics in other languages

The SDK supports BIP-39 word lists in multiple languages:

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// French
$french = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_FRENCH);
echo implode(' ', $french->words) . PHP_EOL;
// traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert

// Korean
$korean = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_KOREAN);
echo implode(' ', $korean->words) . PHP_EOL;

// Spanish
$spanish = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_SPANISH);
echo implode(' ', $spanish->words) . PHP_EOL;
```

**Supported languages:**
- `WordList::LANGUAGE_ENGLISH` (default)
- `WordList::LANGUAGE_FRENCH`
- `WordList::LANGUAGE_SPANISH`
- `WordList::LANGUAGE_ITALIAN`
- `WordList::LANGUAGE_KOREAN`
- `WordList::LANGUAGE_JAPANESE`
- `WordList::LANGUAGE_CHINESE_SIMPLIFIED`
- `WordList::LANGUAGE_CHINESE_TRADITIONAL`
- `WordList::LANGUAGE_MALAY`

## Deriving keypairs from mnemonics

All derivation follows the SEP-05 path `m/44'/148'/index'` where 44 is the BIP-44 purpose, 148 is Stellar's registered coin type, and index is the account number.

### Basic derivation

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$words = 'shell green recycle learn purchase able oxygen right echo claim hill again hidden evidence nice decade panic enemy cake version say furnace garment glue';
$mnemonic = Mnemonic::mnemonicFromWords($words);

// First account (index 0)
$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
echo "Account 0: " . $keyPair0->getAccountId() . PHP_EOL;
// GCVSEBHB6CTMEHUHIUY4DDFMWQ7PJTHFZGOK2JUD5EG2ARNVS6S22E3K

echo "Secret 0: " . $keyPair0->getSecretSeed() . PHP_EOL;
// SATLGMF3SP2V47SJLBFVKZZJQARDOBDQ7DNSSPUV7NLQNPN3QB7M74XH

// Second account (index 1)  
$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
echo "Account 1: " . $keyPair1->getAccountId() . PHP_EOL;
// GBPHPX7SZKYEDV5CVOA5JOJE2RHJJDCJMRWMV4KBOIE5VSDJ6VAESR2W
```

### Derivation with passphrase

An optional passphrase adds extra security. Different passphrases produce completely different accounts:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$words = 'cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube';
$mnemonic = Mnemonic::mnemonicFromWords($words);
$passphrase = 'p4ssphr4se';

// With passphrase
$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);
echo "Account: " . $keyPair0->getAccountId() . PHP_EOL;
// GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ

$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1, $passphrase);
echo "Account: " . $keyPair1->getAccountId() . PHP_EOL;
// GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC
```

### Derivation from non-English mnemonic

Generate a mnemonic in another language and derive keypairs from it:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// Generate a Korean mnemonic
$korean = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_KOREAN);
echo implode(' ', $korean->words) . PHP_EOL;

// Derive keypairs from it
$keyPair = KeyPair::fromMnemonic($korean, 0);
echo "Account: " . $keyPair->getAccountId() . PHP_EOL;
```

### Restoring from non-English mnemonic

Restore an existing mnemonic in another language:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

// Restore from existing Japanese mnemonic (all-zeros entropy, equivalent to English Test 5)
$words = 'あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あいこくしん あおぞら';
$mnemonic = Mnemonic::mnemonicFromWords($words, WordList::LANGUAGE_JAPANESE);

$keyPair = KeyPair::fromMnemonic($mnemonic, 0);
echo "Account: " . $keyPair->getAccountId() . PHP_EOL;
// Note: Produces a different account than the English equivalent because
// BIP-39 uses the actual words (not entropy) to derive the seed
```

> **Note:** When restoring from non-English mnemonics, the words must match the exact encoding used by the SDK's BIP-39 wordlists. Some languages like Korean and Japanese may use different Unicode normalization forms (NFD vs NFC), which can cause validation failures with copy-pasted text.

### Advanced: direct HDNode usage

For advanced users who need lower-level access to the hierarchical derivation:

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\HDNode;
use Soneso\StellarSDK\Crypto\KeyPair;

$mnemonic = Mnemonic::generate24WordsMnemonic();
$seedBytes = $mnemonic->generateSeed("", 64);

// Create master node from seed
$masterNode = HDNode::newMasterNode($seedBytes);

// Derive to m/44'/148' (the Stellar parent key)
$stellarNode = $masterNode->derivePath("m/44'/148'");

// Get raw private key bytes (32 bytes)
$parentPrivateKey = $stellarNode->getPrivateKeyBytes();
echo "Parent key: " . bin2hex($parentPrivateKey) . PHP_EOL;

// Derive individual accounts
$account0Node = $stellarNode->derive(0);
$keyPair0 = KeyPair::fromPrivateKey($account0Node->getPrivateKeyBytes());

$account1Node = $stellarNode->derive(1);
$keyPair1 = KeyPair::fromPrivateKey($account1Node->getPrivateKeyBytes());
```

## Working with BIP-39 seeds

The 512-bit seed is derived from the mnemonic using PBKDF2 with 2048 iterations. Use these methods when interoperating with other wallets or tools.

### Getting the seed hex

Extract the 512-bit BIP-39 seed as a hex string:

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

$mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_ITALIAN);

// BIP-39 seed (128 hex characters = 512 bits)
$seedHex = $mnemonic->bip39SeedHex();
echo "Seed: " . $seedHex . PHP_EOL;

// With passphrase (creates completely different seed)
$seedWithPassphrase = $mnemonic->bip39SeedHex('p4ssphr4se');
echo "Seed (with passphrase): " . $seedWithPassphrase . PHP_EOL;
```

### Getting the m/44'/148' key

The Stellar derivation path key:

```php
<?php

use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$mnemonic = Mnemonic::generate24WordsMnemonic();

$keyHex = $mnemonic->m44148keyHex();
echo "Key: " . $keyHex . PHP_EOL;

// With passphrase
$keyWithPassphrase = $mnemonic->m44148keyHex('p4ssphr4se');
echo "Key (with passphrase): " . $keyWithPassphrase . PHP_EOL;
```

### Deriving from seed hex directly

Use this when you have a BIP-39 seed from another source (like a hardware wallet):

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;

$bip39SeedHex = 'e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497ee4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186';

$keyPair0 = KeyPair::fromBip39SeedHex($bip39SeedHex, 0);
echo "Account: " . $keyPair0->getAccountId() . PHP_EOL;
// GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6

$keyPair1 = KeyPair::fromBip39SeedHex($bip39SeedHex, 1);
echo "Account: " . $keyPair1->getAccountId() . PHP_EOL;
// GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX
```

## Restoring from words

Convert a space-separated word string back to a Mnemonic object:

```php
<?php

use Exception;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

$words = 'illness beef work lemon dove route health way penalty sort merge purchase';

try {
    // Third parameter enables checksum verification (default: true)
    $mnemonic = Mnemonic::mnemonicFromWords($words);
    
    $keyPair = KeyPair::fromMnemonic($mnemonic, 0);
    echo "Recovered account: " . $keyPair->getAccountId() . PHP_EOL;
} catch (Exception $e) {
    echo "Invalid mnemonic: " . $e->getMessage() . PHP_EOL;
}
```

## Error handling

The SDK validates mnemonics and entropy according to BIP-39 standards:

```php
<?php

use Exception;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;
use Soneso\StellarSDK\SEP\Derivation\BIP39;

// Invalid word in mnemonic (must have 12+ words to trigger word validation)
try {
    $mnemonic = Mnemonic::mnemonicFromWords('invalid words that are not in the wordlist and need more here');
} catch (Exception $e) {
    echo "Invalid words: " . $e->getMessage() . PHP_EOL;
    // Output: Invalid/unknown word at position 1
}

// Wrong word count
try {
    $mnemonic = Mnemonic::mnemonicFromWords('one two three');
} catch (Exception $e) {
    echo "Invalid word count: " . $e->getMessage() . PHP_EOL;
}

// Invalid checksum (words are valid but combination is wrong)
try {
    $mnemonic = Mnemonic::mnemonicFromWords(
        'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon',
        WordList::LANGUAGE_ENGLISH,
        true  // Enable checksum verification
    );
} catch (Exception $e) {
    echo "Checksum failed: " . $e->getMessage() . PHP_EOL;
}

// Invalid entropy length
try {
    $badEntropy = 'deadbeef'; // Only 32 bits, need 128+ bits
    $mnemonic = BIP39::Entropy($badEntropy);
} catch (Exception $e) {
    echo "Entropy error: " . $e->getMessage() . PHP_EOL;
    // Output: Invalid entropy length
}

// Invalid word count  
try {
    $mnemonic = Mnemonic::generate(13); // Not divisible by 3
} catch (Exception $e) {
    echo "Word count error: " . $e->getMessage() . PHP_EOL;
    // Output: Words count must be generated in multiples of 3
}

// Unsupported language
try {
    $mnemonic = Mnemonic::generate12WordsMnemonic('klingon');
} catch (Exception $e) {
    echo "Language error: " . $e->getMessage() . PHP_EOL;
}
```

## Entropy and security requirements

### Entropy standards

The SDK enforces BIP-39 entropy requirements:
- **Minimum**: 128 bits (12 words) - acceptable for most use cases
- **Recommended**: 256 bits (24 words) - recommended for production
- **Supported**: 128, 160, 192, 224, 256 bits (12, 15, 18, 21, 24 words)
- **Source**: Cryptographically secure random_bytes() function

### Checksum validation

Each mnemonic includes a checksum to detect errors:
- **12 words**: 4-bit checksum (1 in 16 chance random words pass)
- **24 words**: 8-bit checksum (1 in 256 chance random words pass)
- **Validation**: Automatic by default, can be disabled for debugging

## Security notes

- **Never share your mnemonic** - Anyone with your words can access all derived accounts
- **Store mnemonics offline** - Write them on paper, use a hardware wallet, or use encrypted storage
- **Use passphrases for extra security** - A passphrase creates a completely different set of accounts
- **Verify checksums** - The SDK validates mnemonics by default to catch typos
- **Test recovery** - Before using an account for real funds, verify you can recover it from the mnemonic
- **Hardware security** - Consider using hardware wallets for high-value accounts

## Compatibility

The SDK is compatible with BIP-39 wallets and uses the standard Stellar derivation path `m/44'/148'/index'`.

## Test vectors

The SEP-05 specification includes detailed test vectors for validating implementations. Use these to verify your integration produces correct results across different mnemonic lengths, languages, and passphrases.

See the [official SEP-05 test vectors](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-cases) in the specification.

## Related SEPs

- [SEP-30 Account Recovery](sep-30.md) - Uses mnemonics for account recovery flows

---

[Back to SEP Overview](README.md)
