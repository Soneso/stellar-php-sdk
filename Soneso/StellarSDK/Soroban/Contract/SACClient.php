<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Exception;
use GMP;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\MuxedContract;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\ContractAddressGuard;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SecretSeedRedaction;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Throwable;

/**
 * Typed client for a Stellar Asset Contract (SAC)
 *
 * A Stellar Asset Contract is the built-in token contract of a classic asset, the native
 * asset included. Its id derives from the asset and the network (Asset::getContractId()), and
 * it runs in the host without wasm, so this client uses the embedded spec (SACSpec) and opens
 * without an RPC request.
 *
 * Open a client with forAsset() or forContractId() and the keypair of the account that
 * submits, or with readOnlyForAsset() or readOnlyForContractId() without a keypair. The
 * client offers typed balance(), decimals(), name(), symbol(), transfer() and
 * transferAndSend(); getSorobanClient() reaches the other functions of the spec.
 *
 * Amounts are i128 base units. A SAC has 7 decimals, so 1 unit of the asset is 10,000,000
 * base units; toBaseUnits() converts a decimal amount.
 *
 * A read-only client builds its transactions for the placeholder source account
 * READ_ONLY_SOURCE_ACCOUNT_ID, the all-zero ed25519 public key that no secret key produces.
 * Its transactions simulate without loading an account and refuse to sign and to restore
 * archived state. Reads never sign, send, or restore, on any client.
 *
 * A transfer from an account other than the source account needs that account's
 * authorization: sign its entries with AssembledTransaction::signAuthEntries() before
 * signAndSend().
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see SorobanClient
 * @see AssembledTransaction::signAuthEntries()
 * @see Asset::getContractId()
 */
class SACClient
{
    /**
     * The source account of the read-only clients: the all-zero ed25519 public key, which no
     * secret key produces. Simulation needs no account entry for it.
     */
    public const READ_ONLY_SOURCE_ACCOUNT_ID = 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF';

    private const MAX_I128 = '170141183460469231731687303715884105727';
    private const DECIMALS = 7;

    private SorobanClient $sorobanClient;
    private bool $readOnly;

    /**
     * Use the factories forAsset(), forContractId(), readOnlyForAsset() and readOnlyForContractId().
     *
     * @param SorobanClient $sorobanClient the client over the embedded spec
     * @param bool $readOnly whether the client is read-only
     */
    private function __construct(SorobanClient $sorobanClient, bool $readOnly)
    {
        $this->sorobanClient = $sorobanClient;
        $this->readOnly = $readOnly;
    }

    /**
     * Opens the Stellar Asset Contract of an asset, without a request
     *
     * @param Asset $asset the native asset or an issued asset
     * @param KeyPair $sourceAccountKeyPair the account that builds and submits the transactions;
     *  it needs a private key to sign
     * @param Network $network the network the contract lives on
     * @param string $rpcUrl the Soroban RPC URL
     * @param LoggerInterface|null $logger PSR-3 logger for debug output (optional)
     * @param SorobanServer|null $server the server to use instead of one for $rpcUrl (optional)
     * @return SACClient the client
     * @throws RuntimeException if the asset is a liquidity pool share
     * @see Asset::getContractId()
     */
    public static function forAsset(Asset $asset, KeyPair $sourceAccountKeyPair, Network $network,
        string $rpcUrl, ?LoggerInterface $logger = null, ?SorobanServer $server = null): SACClient
    {
        return self::open($asset->getContractId($network), $sourceAccountKeyPair, $network, $rpcUrl,
            $logger, $server, false);
    }

    /**
     * Opens a Stellar Asset Contract by its contract id, without a request
     *
     * @param string $contractId the contract id as a "C..." strkey or 64 hexadecimal characters
     * @param KeyPair $sourceAccountKeyPair the account that builds and submits the transactions;
     *  it needs a private key to sign
     * @param Network $network the network the contract lives on
     * @param string $rpcUrl the Soroban RPC URL
     * @param LoggerInterface|null $logger PSR-3 logger for debug output (optional)
     * @param SorobanServer|null $server the server to use instead of one for $rpcUrl (optional)
     * @return SACClient the client
     * @throws InvalidArgumentException if the id is not a contract address
     */
    public static function forContractId(string $contractId, KeyPair $sourceAccountKeyPair, Network $network,
        string $rpcUrl, ?LoggerInterface $logger = null, ?SorobanServer $server = null): SACClient
    {
        return self::open(self::contractStrKey($contractId), $sourceAccountKeyPair, $network, $rpcUrl,
            $logger, $server, false);
    }

    /**
     * Opens the Stellar Asset Contract of an asset read-only, without a keypair or a request
     *
     * The transactions use READ_ONLY_SOURCE_ACCOUNT_ID as source account. They simulate, and
     * refuse to sign and to restore archived state.
     *
     * @param Asset $asset the native asset or an issued asset
     * @param Network $network the network the contract lives on
     * @param string $rpcUrl the Soroban RPC URL
     * @param LoggerInterface|null $logger PSR-3 logger for debug output (optional)
     * @param SorobanServer|null $server the server to use instead of one for $rpcUrl (optional)
     * @return SACClient the read-only client
     * @throws RuntimeException if the asset is a liquidity pool share
     * @see Asset::getContractId()
     */
    public static function readOnlyForAsset(Asset $asset, Network $network, string $rpcUrl,
        ?LoggerInterface $logger = null, ?SorobanServer $server = null): SACClient
    {
        return self::open($asset->getContractId($network),
            KeyPair::fromAccountId(self::READ_ONLY_SOURCE_ACCOUNT_ID), $network, $rpcUrl, $logger, $server, true);
    }

    /**
     * Opens a Stellar Asset Contract by its contract id read-only, without a keypair or a request
     *
     * The transactions use READ_ONLY_SOURCE_ACCOUNT_ID as source account. They simulate, and
     * refuse to sign and to restore archived state.
     *
     * @param string $contractId the contract id as a "C..." strkey or 64 hexadecimal characters
     * @param Network $network the network the contract lives on
     * @param string $rpcUrl the Soroban RPC URL
     * @param LoggerInterface|null $logger PSR-3 logger for debug output (optional)
     * @param SorobanServer|null $server the server to use instead of one for $rpcUrl (optional)
     * @return SACClient the read-only client
     * @throws InvalidArgumentException if the id is not a contract address
     */
    public static function readOnlyForContractId(string $contractId, Network $network,
        string $rpcUrl, ?LoggerInterface $logger = null, ?SorobanServer $server = null): SACClient
    {
        return self::open(self::contractStrKey($contractId),
            KeyPair::fromAccountId(self::READ_ONLY_SOURCE_ACCOUNT_ID), $network, $rpcUrl, $logger, $server, true);
    }

    /**
     * Decodes the embedded Stellar Asset Contract spec
     *
     * Every call returns fresh objects. The decoding is strict: the digest must match, and every
     * byte must belong to an entry that re-encodes to the same bytes.
     *
     * @return array<XdrSCSpecEntry> the spec entries, in upstream order
     * @throws RuntimeException if the embedded spec is not valid base64, has another digest, or
     *  does not decode
     * @see SACSpec
     */
    public static function specEntries(): array
    {
        return self::decodeSpec(SACSpec::XDR_BASE64, SACSpec::SHA256);
    }

    /**
     * Converts a decimal amount to i128 base units of 7 decimals
     *
     * The amount is decimal digits with an optional fraction of 1 to 7 digits, such as "12.5"
     * for 125000000 base units. Signs, exponents, separators and whitespace are rejected.
     *
     * @param string $amount the decimal amount
     * @return GMP the amount in base units
     * @throws InvalidArgumentException if the amount does not match the format or exceeds
     *  the largest i128 value
     */
    public static function toBaseUnits(string $amount): GMP
    {
        if (preg_match('/^([0-9]+)(?:\.([0-9]{1,7}))?\z/', $amount, $matches) !== 1) {
            if (StrKey::isValidSeed($amount)) {
                throw new InvalidArgumentException('Invalid amount, got a secret seed (S...) instead of decimal '
                    . 'digits with at most 7 fractional digits, such as 12.5');
            }
            throw new InvalidArgumentException("Invalid amount '"
                . XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($amount))
                . "': expected decimal digits with at most 7 fractional digits, such as 12.5");
        }
        $fraction = str_pad($matches[2] ?? '', self::DECIMALS, '0', STR_PAD_RIGHT);
        $value = gmp_add(gmp_mul(gmp_init($matches[1], 10), gmp_pow(10, self::DECIMALS)),
            gmp_init($fraction, 10));
        if (gmp_cmp($value, gmp_init(self::MAX_I128, 10)) > 0) {
            throw new InvalidArgumentException("Amount {$amount} exceeds the largest i128 amount, "
                . "17014118346046923173168730371588.4105727");
        }
        return $value;
    }

    /**
     * Returns the generic client over the embedded spec, for the other functions of the contract
     *
     * @return SorobanClient the wrapped client
     */
    public function getSorobanClient(): SorobanClient
    {
        return $this->sorobanClient;
    }

    /**
     * Returns the contract id
     *
     * @return string the contract id as a "C..." strkey
     */
    public function getContractId(): string
    {
        return $this->sorobanClient->getContractId();
    }

    /**
     * Reads the balance of a holder in base units
     *
     * A missing account or trustline is not a zero balance: the simulation fails with the
     * contract error, Error(Contract, #6) for a missing account and Error(Contract, #13) for a
     * missing trustline. A contract holder without a balance reads 0.
     *
     * @param Address $holder an account (G...) or contract (C...) address
     * @return GMP the balance in base units
     * @throws InvalidArgumentException if the holder is a muxed account (M...) or muxed contract
     *  (W...) address, naming its base, or another kind of address, before any request
     * @throws RuntimeException if needed ledger entries are archived, or the contract returns
     *  another type than i128
     * @throws Exception if the simulation fails, carrying the contract error
     * @throws GuzzleException if an RPC request fails
     */
    public function balance(Address $holder): GMP
    {
        if ($holder->type === Address::TYPE_MUXED_ACCOUNT) {
            $muxed = $holder->toStrKey();
            throw new InvalidArgumentException("The balance holder must be an account (G...) or contract (C...) "
                . "address, got a muxed account (M...) address: {$muxed}; query its base account "
                . MuxedAccount::fromAccountId($muxed)->getEd25519AccountId());
        }
        if ($holder->type === Address::TYPE_MUXED_CONTRACT) {
            $muxed = $holder->toStrKey();
            throw new InvalidArgumentException("The balance holder must be an account (G...) or contract (C...) "
                . "address, got a muxed contract (W...) address: {$muxed}; query its base contract "
                . MuxedContract::fromAddress($muxed)->getContractId());
        }
        $args = $this->sorobanClient->getContractSpec()->funcArgsToXdrSCValues('balance', ['id' => $holder]);
        $result = $this->read('balance', $args);
        $balance = $result->type->value === XdrSCValType::SCV_I128 ? $result->toBigInt() : null;
        if ($balance === null) {
            throw $this->unexpectedArm($result, XdrSCValType::SCV_I128, 'balance');
        }
        return $balance;
    }

    /**
     * Reads the number of decimals of the asset, which is 7 for every Stellar Asset Contract
     *
     * @return int the number of decimals
     * @throws RuntimeException if needed ledger entries are archived, or the contract returns
     *  another type than u32
     * @throws Exception if the simulation fails
     * @throws GuzzleException if an RPC request fails
     */
    public function decimals(): int
    {
        $result = $this->read('decimals', []);
        if ($result->type->value !== XdrSCValType::SCV_U32 || $result->u32 === null) {
            throw $this->unexpectedArm($result, XdrSCValType::SCV_U32, 'decimals');
        }
        return $result->u32;
    }

    /**
     * Reads the name of the asset: "CODE:ISSUER" for an issued asset, "native" for the native asset
     *
     * @return string the name
     * @throws RuntimeException if needed ledger entries are archived, or the contract returns
     *  another type than string
     * @throws Exception if the simulation fails
     * @throws GuzzleException if an RPC request fails
     */
    public function name(): string
    {
        return $this->readString('name');
    }

    /**
     * Reads the symbol of the asset: the asset code, or "native" for the native asset
     *
     * @return string the symbol
     * @throws RuntimeException if needed ledger entries are archived, or the contract returns
     *  another type than string
     * @throws Exception if the simulation fails
     * @throws GuzzleException if an RPC request fails
     */
    public function symbol(): string
    {
        return $this->readString('symbol');
    }

    /**
     * Builds and simulates a transfer, unsigned and unsent
     *
     * With $from as the client's source account, signAndSend() on the result submits it. An
     * account $from other than the source appears in needsNonInvokerSigningBy(); sign its
     * entries with signAuthEntries() before signAndSend(). A contract $from is authorized by its
     * own __check_auth: sign() does not block on its entry, and signAuthEntries() does not reach
     * it, so a signature value the contract expects is written into the entry and stored with
     * $tx->tx->setSorobanAuth() before signAndSend(). A muxed destination is credited to its
     * base address, and the transfer event carries its id.
     *
     * @param Address $from the sender, an account (G...) or contract (C...) address
     * @param Address $to the recipient, an account (G...), muxed account (M...), contract (C...)
     *  or muxed contract (W...) address
     * @param GMP|int|string $amount the amount in base units, from 0 to 2^127 - 1; a string
     *  holds decimal digits only
     * @param MethodOptions|null $methodOptions options for simulation, fees, and timeouts (optional)
     * @return AssembledTransaction the simulated transaction
     * @throws InvalidArgumentException if the amount is not a whole number of base units in
     *  range, or an address is of a kind the parameter does not take, before any request
     * @throws Exception if the simulation fails, or a restore would run on a read-only client
     * @throws GuzzleException if an RPC request fails
     * @see AssembledTransaction::signAuthEntries()
     * @see toBaseUnits()
     */
    public function transfer(Address $from, Address $to, GMP|int|string $amount,
        ?MethodOptions $methodOptions = null): AssembledTransaction
    {
        $amountGmp = self::transferAmount($amount);
        $args = $this->sorobanClient->getContractSpec()->funcArgsToXdrSCValues('transfer',
            ['from' => $from, 'to' => $to, 'amount' => $amountGmp]);
        return $this->sorobanClient->buildInvokeMethodTx('transfer', $args, $methodOptions);
    }

    /**
     * Transfers from the client's source account, then signs, sends, and waits for the result
     *
     * @param Address $to the recipient, an account (G...), muxed account (M...), contract (C...)
     *  or muxed contract (W...) address
     * @param GMP|int|string $amount the amount in base units, from 0 to 2^127 - 1; a string
     *  holds decimal digits only
     * @param MethodOptions|null $methodOptions options for simulation, fees, and timeouts (optional)
     * @return GetTransactionResponse the successful transaction response
     * @throws Exception if the client is read-only (before any request), or the transaction
     *  fails or does not succeed
     * @throws InvalidArgumentException if the amount or the recipient is invalid, before any request
     * @throws GuzzleException if an RPC request fails
     * @see transfer()
     */
    public function transferAndSend(Address $to, GMP|int|string $amount,
        ?MethodOptions $methodOptions = null): GetTransactionResponse
    {
        if ($this->readOnly) {
            throw new Exception("transferAndSend needs the sending account's keypair, and this SACClient is "
                . "read-only; open the client with SACClient::forAsset() or SACClient::forContractId()");
        }
        $from = Address::fromAccountId($this->sorobanClient->getOptions()->sourceAccountKeyPair->getAccountId());
        $response = $this->transfer($from, $to, $amount, $methodOptions)->signAndSend();
        if ($response->getStatus() !== GetTransactionResponse::STATUS_SUCCESS) {
            throw new Exception("invoke transfer failed with result: {$response->resultXdr}");
        }
        return $response;
    }

    /**
     * @param string $contractId the contract id as given
     * @return string the "C..." strkey
     * @throws InvalidArgumentException if the id is not a contract address
     */
    private static function contractStrKey(string $contractId): string
    {
        ContractAddressGuard::requireContractId($contractId, ContractAddressGuard::CONTRACT_TO_INVOKE);
        return StrKey::isValidContractId($contractId) ? $contractId : StrKey::encodeContractIdHex($contractId);
    }

    /**
     * Wraps a client over the embedded spec, without a request.
     *
     * @param string $contractId the "C..." strkey
     * @param KeyPair $sourceAccountKeyPair the source account
     * @param Network $network the network
     * @param string $rpcUrl the Soroban RPC URL
     * @param LoggerInterface|null $logger PSR-3 logger
     * @param SorobanServer|null $server the server to use
     * @param bool $readOnly whether the client is read-only
     * @return SACClient the client
     */
    private static function open(string $contractId, KeyPair $sourceAccountKeyPair, Network $network,
        string $rpcUrl, ?LoggerInterface $logger, ?SorobanServer $server, bool $readOnly): SACClient
    {
        $options = new ClientOptions(
            sourceAccountKeyPair: $sourceAccountKeyPair,
            contractId: $contractId,
            network: $network,
            rpcUrl: $rpcUrl,
            logger: $logger,
            server: $server,
        );
        return new SACClient(SorobanClient::forSpecEntries(self::specEntries(), $options, $readOnly), $readOnly);
    }

    /**
     * Decodes a spec strictly: base64, digest, and entries that re-encode to every byte.
     *
     * @param string $base64 the spec bytes in standard base64
     * @param string $sha256 the expected sha256 of the bytes, as lowercase hex
     * @return array<XdrSCSpecEntry> the entries
     * @throws RuntimeException naming the failed check
     */
    private static function decodeSpec(string $base64, string $sha256): array
    {
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            throw new RuntimeException("The embedded SAC spec is not valid base64");
        }
        $actual = hash('sha256', $bytes);
        if ($actual !== $sha256) {
            throw new RuntimeException("The embedded SAC spec has sha256 {$actual}, expected {$sha256}");
        }
        $entries = [];
        $offset = 0;
        $length = strlen($bytes);
        while ($offset < $length) {
            $remaining = substr($bytes, $offset);
            try {
                $entry = XdrSCSpecEntry::decode(new XdrBuffer($remaining));
                $encoded = $entry->encode();
            } catch (Throwable $e) {
                throw new RuntimeException("The embedded SAC spec does not decode at byte offset {$offset}: "
                    . $e->getMessage(), 0, $e);
            }
            if ($encoded === '' || !str_starts_with($remaining, $encoded)) {
                throw new RuntimeException("The embedded SAC spec does not decode at byte offset {$offset}: "
                    . "the entry does not re-encode to the bytes it was decoded from");
            }
            $entries[] = $entry;
            $offset += strlen($encoded);
        }
        return $entries;
    }

    /**
     * @param GMP|int|string $amount the amount in base units
     * @return GMP the amount
     * @throws InvalidArgumentException if the amount is not a whole number from 0 to 2^127 - 1
     */
    private static function transferAmount(GMP|int|string $amount): GMP
    {
        if (is_string($amount)) {
            if (preg_match('/^[0-9]+\z/', $amount) !== 1) {
                if (StrKey::isValidSeed($amount)) {
                    throw new InvalidArgumentException('The transfer amount must be a whole number of base units, '
                        . 'got a secret seed (S...) instead of an amount');
                }
                throw new InvalidArgumentException("The transfer amount must be a whole number of base units, got '"
                    . XdrJsonHelper::safePreview(SecretSeedRedaction::redactSecretSeeds($amount))
                    . "'; convert a decimal amount with SACClient::toBaseUnits()");
            }
            $value = gmp_init($amount, 10);
        } elseif (is_int($amount)) {
            $value = gmp_init($amount);
        } else {
            $value = $amount;
        }
        if (gmp_sign($value) < 0 || gmp_cmp($value, gmp_init(self::MAX_I128, 10)) > 0) {
            throw new InvalidArgumentException("The transfer amount must be from 0 to " . self::MAX_I128
                . " base units, got " . gmp_strval($value));
        }
        return $value;
    }

    /**
     * Simulates a read without restore and returns its value.
     *
     * @param string $function the spec function
     * @param array<XdrSCVal> $args the arguments
     * @return XdrSCVal the returned value
     * @throws RuntimeException if ledger entries the read needs are archived
     * @throws Exception if the simulation fails
     * @throws GuzzleException if an RPC request fails
     */
    private function read(string $function, array $args): XdrSCVal
    {
        $tx = $this->sorobanClient->buildInvokeMethodTx($function, $args, new MethodOptions(restore: false));
        $response = $tx->simulationResponse;
        if ($response !== null && $response->restorePreamble !== null
            && $response->error === null && $response->resultError === null) {
            throw new RuntimeException("The Stellar Asset Contract {$this->getContractId()} cannot run {$function}: "
                . "ledger entries it needs are archived; restore them from a client opened with a funded "
                . "account's keypair");
        }
        return $tx->getSimulationData()->returnedValue;
    }

    /**
     * @param string $function the spec function, which returns a string
     * @return string the returned string
     * @throws RuntimeException if needed ledger entries are archived, or the result is not a string
     * @throws Exception if the simulation fails
     * @throws GuzzleException if an RPC request fails
     */
    private function readString(string $function): string
    {
        $result = $this->read($function, []);
        if ($result->type->value !== XdrSCValType::SCV_STRING || $result->str === null) {
            throw $this->unexpectedArm($result, XdrSCValType::SCV_STRING, $function);
        }
        return $result->str;
    }

    /**
     * Builds the error for a result of another type than the spec function returns.
     *
     * @param XdrSCVal $result the returned value
     * @param int $expected the expected XdrSCValType value
     * @param string $function the spec function
     * @return RuntimeException the error naming both types
     */
    private function unexpectedArm(XdrSCVal $result, int $expected, string $function): RuntimeException
    {
        return new RuntimeException("The Stellar Asset Contract {$this->getContractId()} returned "
            . $result->type->enumName() . " from {$function}, expected "
            . (new XdrSCValType($expected))->enumName());
    }
}
