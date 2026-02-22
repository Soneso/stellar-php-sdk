# Security Best Practices

## Secret Key Management

### Never Hardcode Secrets

```php
<?php declare(strict_types=1);

// WRONG -- secret key exposed in source code
$keyPair = \Soneso\StellarSDK\Crypto\KeyPair::fromSeed(
    'SCZANGBA5YHTNYVVV3C7CAZMCLXPILHSE7HG3EQOVLU7BFXQMB3AVJY'
);

// CORRECT -- load from environment variable
$seed = getenv('STELLAR_SECRET_KEY');
if ($seed === false || $seed === '') {
    throw new \RuntimeException('STELLAR_SECRET_KEY environment variable is not set');
}
$keyPair = \Soneso\StellarSDK\Crypto\KeyPair::fromSeed($seed);
```

### Environment Variable Patterns

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

// Load configuration from environment
$stellarNetwork = getenv('STELLAR_NETWORK') ?: 'testnet';
$horizonUrl = getenv('STELLAR_HORIZON_URL') ?: 'https://horizon-testnet.stellar.org';
$secretKey = getenv('STELLAR_SECRET_KEY');

if ($secretKey === false || $secretKey === '') {
    throw new \RuntimeException('STELLAR_SECRET_KEY must be set');
}

$keyPair = KeyPair::fromSeed($secretKey);
$network = $stellarNetwork === 'public' ? Network::public() : Network::testnet();
$sdk = new StellarSDK($horizonUrl);

// Clear the seed from memory after creating the KeyPair
$secretKey = str_repeat("\0", strlen($secretKey));
unset($secretKey);
```

### Laravel Vault Integration

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// In config/services.php:
// 'stellar' => [
//     'secret_key' => env('STELLAR_SECRET_KEY'),
//     'horizon_url' => env('STELLAR_HORIZON_URL'),
// ],

// In a Laravel service class:
class StellarKeyService
{
    public function getSigningKeyPair(): KeyPair
    {
        $seed = config('services.stellar.secret_key');
        if (!is_string($seed) || $seed === '') {
            throw new \RuntimeException(
                'Stellar secret key not configured. Set STELLAR_SECRET_KEY in .env'
            );
        }

        return KeyPair::fromSeed($seed);
    }
}
```

### Production Key Storage Recommendations

| Method | Suitability | Notes |
|--------|-------------|-------|
| Environment variables | Good | Use for single-server deployments |
| Laravel encrypted `.env` | Good | Use `php artisan env:encrypt` |
| HashiCorp Vault | Best | Use for multi-server / high-value |
| AWS Secrets Manager | Best | Use with AWS deployments |
| PHP-FPM pool config | Good | Isolated per pool, not in source |
| Hardware Security Module | Best | For custody and high-value signing |

## Input Validation

### Validate Stellar Addresses

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

function validateStellarAddress(string $address): void
{
    if (!StrKey::isValidAccountId($address)) {
        throw new \InvalidArgumentException(
            'Invalid Stellar account ID. Must be a valid G-address.'
        );
    }
}

function validateMuxedAddress(string $address): void
{
    if (!StrKey::isValidMuxedAccountId($address)) {
        throw new \InvalidArgumentException(
            'Invalid muxed account ID. Must be a valid M-address.'
        );
    }
}

function validateSecretSeed(string $seed): void
{
    if (!StrKey::isValidSeed($seed)) {
        throw new \InvalidArgumentException(
            'Invalid secret seed. Must be a valid S-address.'
        );
    }
}

// Usage
$destinationId = $_POST['destination'] ?? '';
validateStellarAddress($destinationId);
```

### Validate Asset Codes

```php
<?php declare(strict_types=1);

function validateAssetCode(string $code): void
{
    $length = strlen($code);
    if ($length < 1 || $length > 12) {
        throw new \InvalidArgumentException(
            'Asset code must be between 1 and 12 characters.'
        );
    }

    if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
        throw new \InvalidArgumentException(
            'Asset code must contain only alphanumeric characters.'
        );
    }
}
```

### Validate Amounts

```php
<?php declare(strict_types=1);

function validateStellarAmount(string $amount): void
{
    // Must be a valid decimal number
    if (!preg_match('/^\d+(\.\d{1,7})?$/', $amount)) {
        throw new \InvalidArgumentException(
            'Amount must be a positive decimal with at most 7 decimal places.'
        );
    }

    // Must be positive
    if (bccomp($amount, '0', 7) <= 0) {
        throw new \InvalidArgumentException('Amount must be greater than zero.');
    }

    // Must not exceed maximum (922,337,203,685.4775807 XLM)
    if (bccomp($amount, '922337203685.4775807', 7) > 0) {
        throw new \InvalidArgumentException('Amount exceeds Stellar maximum.');
    }
}
```

### Validate Memo

```php
<?php declare(strict_types=1);

function validateMemoText(string $text): void
{
    if (strlen($text) > 28) {
        throw new \InvalidArgumentException(
            'Memo text must not exceed 28 bytes.'
        );
    }
}

function validateMemoId(string $id): void
{
    if (!ctype_digit($id)) {
        throw new \InvalidArgumentException('Memo ID must be a positive integer.');
    }

    // Must fit in uint64
    if (bccomp($id, '18446744073709551615') > 0) {
        throw new \InvalidArgumentException('Memo ID exceeds uint64 maximum.');
    }
}
```

## Transaction Verification Before Signing

Always inspect a transaction before signing, especially when the transaction was constructed by a third party (e.g., SEP-10 challenge, multi-sig coordination).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\CreateAccountOperation;

/**
 * Verifies a transaction envelope is safe to sign.
 *
 * @param string $envelopeXdrBase64 Base64-encoded transaction envelope
 * @param string $expectedSourceAccountId Expected source account G-address
 * @param string $expectedNetwork Expected network passphrase
 * @return Transaction The verified transaction
 */
function verifyTransactionBeforeSigning(
    string $envelopeXdrBase64,
    string $expectedSourceAccountId,
    string $expectedNetwork,
): Transaction {
    $decoded = AbstractTransaction::fromEnvelopeBase64XdrString($envelopeXdrBase64);

    if (!($decoded instanceof Transaction)) {
        throw new \RuntimeException('Expected a Transaction, got ' . get_class($decoded));
    }

    // Verify source account
    $sourceAccountId = $decoded->getSourceAccount()->getAccountId();
    if ($sourceAccountId !== $expectedSourceAccountId) {
        throw new \RuntimeException(
            "Unexpected source account: {$sourceAccountId}"
        );
    }

    // Verify operations are expected types
    $operations = $decoded->getOperations();
    foreach ($operations as $op) {
        if ($op instanceof PaymentOperation || $op instanceof CreateAccountOperation) {
            // Known safe operation types
            continue;
        }
        throw new \RuntimeException(
            'Unexpected operation type: ' . get_class($op)
        );
    }

    // Verify memo is not malicious (no unexpected data)
    $memo = $decoded->getMemo();
    // Log memo type and value for audit trail

    // Verify fee is reasonable
    $fee = $decoded->getFee();
    $maxAcceptableFee = count($operations) * 10000; // 0.001 XLM per op max
    if ($fee > $maxAcceptableFee) {
        throw new \RuntimeException("Fee {$fee} stroops exceeds acceptable limit");
    }

    return $decoded;
}
```

## SEP-10 Authentication Security

### Secure SEP-10 Flow

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$domain = 'anchor.example.com';
$network = Network::public();
$clientKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_KEY'));

$webAuth = WebAuth::fromDomain($domain, $network);

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $clientKeyPair->getAccountId(),
    signers: [$clientKeyPair],
);

// Store JWT securely -- never log or expose it
// Use the JWT for authenticated SEP-24/SEP-31 requests
```

### SEP-10 Security Checklist

The `WebAuth` class handles most validation internally, but when integrating:

- **Verify the domain:** Ensure the domain in `WebAuth::fromDomain()` matches the anchor you intend to authenticate with. A typo or DNS hijack would direct authentication to a different server.
- **Use HTTPS exclusively:** All SEP-10 communication must use HTTPS. Never use HTTP endpoints in production.
- **Store JWT tokens securely:** Treat JWT tokens as credentials. Store them in encrypted session storage, never in cookies or local storage without encryption.
- **Respect token expiry:** JWT tokens have limited validity. Request new authentication when tokens expire. Do not cache tokens indefinitely.
- **Use the correct network:** Pass `Network::public()` for mainnet or `Network::testnet()` for testnet. Mixing networks causes signature validation failures.

## Safe Error Handling

### Never Expose Secrets in Errors or Logs

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

// WRONG -- secret key could appear in stack trace or error message
function unsafeSubmit(string $secretKey): void
{
    $keyPair = KeyPair::fromSeed($secretKey);
    // If this throws, $secretKey appears in the stack trace
}

// CORRECT -- isolate secret key handling
function safeSubmit(): void
{
    $seed = getenv('STELLAR_SECRET_KEY');
    if ($seed === false || $seed === '') {
        throw new \RuntimeException('Signing key not configured');
    }

    try {
        $keyPair = KeyPair::fromSeed($seed);
        // ... build and sign transaction
    } catch (HorizonRequestException $e) {
        // Log only safe information
        error_log(sprintf(
            'Stellar transaction failed: status=%d url=%s detail=%s',
            $e->getStatusCode() ?? 0,
            $e->getRequestedUrl(),
            $e->getHorizonErrorResponse()?->getDetail() ?? $e->getMessage(),
        ));
        // Re-throw with sanitized message
        throw new \RuntimeException(
            'Payment processing failed. Please try again.',
            previous: $e,
        );
    } finally {
        // Clear sensitive data
        $seed = str_repeat("\0", strlen($seed));
        unset($seed);
    }
}
```

### Production Error Logging Pattern

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Psr\Log\LoggerInterface;

/**
 * Logs Stellar errors safely without exposing secrets.
 */
function logStellarError(
    LoggerInterface $logger,
    HorizonRequestException $e,
    string $context,
): void {
    $errorData = [
        'context' => $context,
        'status_code' => $e->getStatusCode(),
        'url' => $e->getRequestedUrl(),
        'method' => $e->getHttpMethod(),
    ];

    $errorResponse = $e->getHorizonErrorResponse();
    if ($errorResponse !== null) {
        $errorData['title'] = $errorResponse->getTitle();
        $errorData['detail'] = $errorResponse->getDetail();

        $extras = $errorResponse->getExtras();
        if ($extras !== null) {
            $errorData['tx_result_code'] = $extras->getResultCodesTransaction();
            $errorData['op_result_codes'] = $extras->getResultCodesOperation();
            // DO NOT log envelope_xdr -- it may contain signed transaction data
        }
    }

    $logger->error('Stellar API error', $errorData);
}
```

## Rate Limiting and Abuse Prevention

### Horizon Rate Limit Handling

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

class RateLimitedHorizonClient
{
    private StellarSDK $sdk;
    private int $maxRetries;

    public function __construct(StellarSDK $sdk, int $maxRetries = 3)
    {
        $this->sdk = $sdk;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Executes a Horizon request with automatic rate limit handling.
     *
     * @param callable $request Callable that performs the Horizon request
     * @return mixed The request result
     * @throws HorizonRequestException If all retries are exhausted
     */
    public function execute(callable $request): mixed
    {
        $attempt = 0;
        while (true) {
            try {
                return $request($this->sdk);
            } catch (HorizonRequestException $e) {
                $attempt++;

                if ($e->getStatusCode() === 429 && $attempt <= $this->maxRetries) {
                    $retryAfter = (int) ($e->getRetryAfter() ?? (2 ** $attempt));
                    sleep($retryAfter);
                    continue;
                }

                throw $e;
            }
        }
    }
}

// Usage
$client = new RateLimitedHorizonClient(StellarSDK::getPublicNetInstance());
$account = $client->execute(
    fn(StellarSDK $sdk) => $sdk->requestAccount('GABC...')
);
```

### Application-Level Rate Limiting for Payment Endpoints

```php
<?php declare(strict_types=1);

/**
 * Simple in-memory rate limiter for payment submission endpoints.
 * In production, use Redis or a dedicated rate limiting library.
 */
class PaymentRateLimiter
{
    /** @var array<string, array{count: int, window_start: int}> */
    private array $counters = [];
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 5, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Checks whether a request from the given identifier is allowed.
     *
     * @param string $identifier User ID, IP address, or account ID
     * @return bool True if the request is within rate limits
     */
    public function isAllowed(string $identifier): bool
    {
        $now = time();

        if (!isset($this->counters[$identifier])) {
            $this->counters[$identifier] = ['count' => 0, 'window_start' => $now];
        }

        $entry = &$this->counters[$identifier];

        // Reset window if expired
        if ($now - $entry['window_start'] >= $this->windowSeconds) {
            $entry['count'] = 0;
            $entry['window_start'] = $now;
        }

        if ($entry['count'] >= $this->maxRequests) {
            return false;
        }

        $entry['count']++;
        return true;
    }
}
```

## Network Validation

### Verify Correct Network Before Signing

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;

/**
 * Creates an SDK instance with validated network configuration.
 * Prevents signing testnet transactions with mainnet keys and vice versa.
 */
function createValidatedSdk(string $horizonUrl, Network $network): StellarSDK
{
    $sdk = new StellarSDK($horizonUrl);

    // Verify the Horizon server matches the expected network
    $root = $sdk->root();
    $serverPassphrase = $root->getNetworkPassphrase();

    if ($serverPassphrase !== $network->getNetworkPassphrase()) {
        throw new \RuntimeException(sprintf(
            'Network mismatch: expected "%s" but Horizon reports "%s"',
            $network->getNetworkPassphrase(),
            $serverPassphrase,
        ));
    }

    return $sdk;
}

// Usage -- will throw if Horizon URL and Network do not match
$sdk = createValidatedSdk(
    'https://horizon.stellar.org',
    Network::public(),
);
```

## PHP-Specific Security Considerations

### Disable Error Display in Production

```php
<?php declare(strict_types=1);

// In production, never display errors to users
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Ensure stack traces with potential key material are only logged, not displayed
set_exception_handler(function (\Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit(1);
});
```

### Secure Session Handling for JWT Tokens

```php
<?php declare(strict_types=1);

// Configure secure session settings before session_start()
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

session_start();

// Store SEP-10 JWT in session (not in cookies directly)
function storeStellarJwt(string $jwt): void
{
    $_SESSION['stellar_jwt'] = $jwt;
    $_SESSION['stellar_jwt_stored_at'] = time();
}

function getStellarJwt(int $maxAgeSeconds = 3600): ?string
{
    if (!isset($_SESSION['stellar_jwt'], $_SESSION['stellar_jwt_stored_at'])) {
        return null;
    }

    // Check expiry
    $storedAt = (int) $_SESSION['stellar_jwt_stored_at'];
    if (time() - $storedAt > $maxAgeSeconds) {
        unset($_SESSION['stellar_jwt'], $_SESSION['stellar_jwt_stored_at']);
        return null;
    }

    return $_SESSION['stellar_jwt'];
}
```

### CSRF Protection for Payment Forms

```php
<?php declare(strict_types=1);

/**
 * Generates a CSRF token and stores it in the session.
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates that the submitted CSRF token matches the session token.
 *
 * @param string $submittedToken Token from the form submission
 * @throws \RuntimeException If token is invalid
 */
function validateCsrfToken(string $submittedToken): void
{
    if (!isset($_SESSION['csrf_token'])) {
        throw new \RuntimeException('No CSRF token in session');
    }

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        throw new \RuntimeException('CSRF token validation failed');
    }

    // Regenerate token after successful validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In your payment form HTML:
// <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

// In your payment handler:
// validateCsrfToken($_POST['csrf_token'] ?? '');
```

### Webhook Signature Verification

When receiving callbacks from anchors (SEP-6, SEP-24, SEP-31), verify the request signature.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

/**
 * Verifies a webhook payload was signed by the expected Stellar account.
 *
 * @param string $payload Raw request body
 * @param string $signatureBase64 Base64-encoded Ed25519 signature from the request header
 * @param string $signerAccountId G-address of the expected signer
 * @return bool True if the signature is valid
 */
function verifyWebhookSignature(
    string $payload,
    string $signatureBase64,
    string $signerAccountId,
): bool {
    $keyPair = KeyPair::fromAccountId($signerAccountId);
    $signature = base64_decode($signatureBase64, true);

    if ($signature === false) {
        return false;
    }

    return $keyPair->verifySignature($signature, $payload);
}

// Usage in a webhook handler
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_STELLAR_SIGNATURE'] ?? '';
$signerAccountId = 'GABC...'; // Anchor's signing key from stellar.toml

if (!verifyWebhookSignature($body, $signature, $signerAccountId)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

## Security Checklist

- [ ] Secret keys loaded from environment variables or secure vault, never hardcoded
- [ ] Secret keys cleared from memory after KeyPair creation
- [ ] All user-supplied Stellar addresses validated with `StrKey::isValidAccountId()`
- [ ] Amounts validated as positive decimals with at most 7 decimal places
- [ ] Transactions inspected before signing (source account, operations, fee, memo)
- [ ] Network passphrase verified to match Horizon/RPC endpoint
- [ ] Error messages sanitized before returning to users -- no secrets in responses
- [ ] Stack traces logged to files only, not displayed to users
- [ ] JWT tokens stored in encrypted sessions, not plain cookies
- [ ] CSRF protection on all payment forms
- [ ] HTTPS enforced for all Stellar API communication
- [ ] Rate limiting applied to payment submission endpoints
- [ ] Webhook signatures verified before processing callbacks
- [ ] `display_errors` disabled in production PHP configuration
