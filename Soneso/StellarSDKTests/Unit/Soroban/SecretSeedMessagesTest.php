<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\AssetTypePoolShare;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\MuxedContract;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Federation\Federation;
use Soneso\StellarSDK\SEP\TxRep\TxRep;
use Soneso\StellarSDK\SignedPayloadSigner;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\Contract\DeployFromExternalRefRequest;
use Soneso\StellarSDK\Soroban\Contract\NativeUnionVal;
use Soneso\StellarSDK\Soroban\Contract\SACClient;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\ContractAddressGuard;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Util\StellarAmount;
use Soneso\StellarSDK\Xdr\TxRepHelper;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrClaimantType;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrCryptoKeyType;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLiquidityPoolType;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrPublicKeyType;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Kind;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSignerKeyTypeBase;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Throwable;

/**
 * The error messages of the SDK functions that take a caller's string, for a secret seed passed
 * by mistake and its variants. A key position, whose valid values never start with "S" or "s",
 * names such a value by its kind; any other position replaces whatever may be a seed.
 */
class SecretSeedMessagesTest extends TestCase
{
    private const POOL_ORDER = ' are in wrong order. Sort by type, code bytes, then raw issuer public key bytes.';
    private const U64_RANGE = "; expected an integer or decimal string in [0, 18446744073709551615]";

    private static function account(): string
    {
        return StrKey::encodeAccountId(str_repeat("\x01", 32));
    }

    private static function otherAccount(): string
    {
        return StrKey::encodeAccountId(str_repeat("\x09", 32));
    }

    private static function contract(): string
    {
        return StrKey::encodeContractId(str_repeat("\x03", 32));
    }

    private static function invocation(): SorobanAuthorizedInvocation
    {
        $contract = Address::fromContractId(StrKey::decodeContractIdHex(self::contract()));
        $function = SorobanAuthorizedFunction::forContractFunction($contract, 'hello', [XdrSCVal::forU64(1234)]);
        return new SorobanAuthorizedInvocation($function, []);
    }

    private static function legacyEntry(): SorobanAuthorizationEntry
    {
        $credentials = SorobanCredentials::forAddressLegacy(Address::fromAccountId(self::account()), 1, 100,
            XdrSCVal::forVoid());
        return new SorobanAuthorizationEntry($credentials, self::invocation());
    }

    private static function addressV2Entry(): SorobanAuthorizationEntry
    {
        $credentials = SorobanCredentials::forAddressCredentialsV2(
            new SorobanAddressCredentials(Address::fromAccountId(self::account()), 1, 100, XdrSCVal::forVoid()));
        return new SorobanAuthorizationEntry($credentials, self::invocation());
    }

    private static function udt(string $name): XdrSCSpecTypeDef
    {
        $type = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $type->udt = new XdrSCSpecTypeUDT($name);
        return $type;
    }

    /**
     * A spec holding the union Option with the single void case None.
     */
    private static function optionSpec(): ContractSpec
    {
        $case = new XdrSCSpecUDTUnionCaseV0(
            new XdrSCSpecUDTUnionCaseV0Kind(XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_VOID_V0));
        $case->voidCase = new XdrSCSpecUDTUnionCaseVoidV0('', 'None');
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0));
        $entry->udtUnionV0 = new XdrSCSpecUDTUnionV0('', '', 'Option', [$case]);
        return new ContractSpec([$entry]);
    }

    /**
     * TxRep texts the public TxRep parser reads an asset from: a change trust operation, the
     * revoke sponsorship of a trust line, and a footprint holding a trust line key. Each holds
     * `{asset}` in place of the asset value.
     *
     * @return array<string, string>
     */
    private static function txRepAssetTemplates(): array
    {
        $account = self::account();
        $head = "type: ENVELOPE_TYPE_TX\ntx.sourceAccount: $account\ntx.fee: 100\ntx.seqNum: 2\n"
            . "tx.cond.type: PRECOND_NONE\ntx.memo.type: MEMO_NONE\ntx.operations.len: 1\n"
            . "tx.operations[0].sourceAccount._present: false\n";
        $revoke = 'tx.operations[0].body.revokeSponsorshipOp';
        $footprint = 'tx.sorobanData.resources.footprint';
        return [
            'change trust' => $head
                . "tx.operations[0].body.type: CHANGE_TRUST\n"
                . "tx.operations[0].body.changeTrustOp.line: {asset}\n"
                . "tx.operations[0].body.changeTrustOp.limit: 100000000000\n"
                . "tx.ext.v: 0\nsignatures.len: 0",
            'revoke sponsorship trust line' => $head
                . "tx.operations[0].body.type: REVOKE_SPONSORSHIP\n"
                . "$revoke.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY\n"
                . "$revoke.ledgerKey.type: TRUSTLINE\n"
                . "$revoke.ledgerKey.trustLine.accountID: $account\n"
                . "$revoke.ledgerKey.trustLine.asset: {asset}\n"
                . "tx.ext.v: 0\nsignatures.len: 0",
            'footprint trust line' => $head
                . "tx.operations[0].body.type: RESTORE_FOOTPRINT\n"
                . "tx.operations[0].body.restoreFootprintOp.ext.v: 0\n"
                . "tx.ext.v: 1\ntx.sorobanData.ext.v: 0\n"
                . "$footprint.readOnly.len: 1\n"
                . "$footprint.readOnly[0].type: TRUSTLINE\n"
                . "$footprint.readOnly[0].trustLine.accountID: $account\n"
                . "$footprint.readOnly[0].trustLine.asset: {asset}\n"
                . "$footprint.readWrite.len: 0\n"
                . "tx.sorobanData.resources.instructions: 0\n"
                . "tx.sorobanData.resources.diskReadBytes: 0\n"
                . "tx.sorobanData.resources.writeBytes: 0\n"
                . "tx.sorobanData.resourceFee: 0\nsignatures.len: 0",
        ];
    }

    /**
     * Parses $template with $asset in place of `{asset}` through the public TxRep parser.
     */
    private static function parseTxRepAsset(string $template, string $asset): string
    {
        return TxRep::transactionEnvelopeXdrBase64FromTxRep(str_replace('{asset}', $asset, $template));
    }

    private static function mockedClient(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        return new Client(['handler' => $stack]);
    }

    /**
     * Key positions: the call, the message for a kind, an invalid input that does not start with
     * "S" or "s", and the message for that input, which is the same as before the change.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string}>
     */
    public static function keyPositionSites(): array
    {
        $contractId = 'Contract id must be a "C..." strkey or a hexadecimal string, ';
        $balanceId = 'Claimable balance id must be a "B..." strkey or a hexadecimal string, ';
        $poolId = 'Liquidity pool id must be an "L..." strkey or a hexadecimal string, ';
        $address = '; Address takes an account (G...) or contract (C...) address';
        $muxedAddress = '; MuxedAddress takes an account (G...), muxed account (M...), contract (C...) or muxed '
            . 'contract (W...) address';
        $noNode = '" matched no node in this authorization entry';
        return [
            'MuxedAccount::fromAccountId' => [
                fn (string $v) => MuxedAccount::fromAccountId($v),
                fn (string $kind) => 'invalid accountId: ' . $kind,
                'XABC', 'invalid accountId: XABC',
            ],
            'XdrSCAddress::forAccountId' => [
                fn (string $v) => XdrSCAddress::forAccountId($v),
                fn (string $kind) => 'invalid account id: ' . $kind,
                'XABC', 'invalid account id: XABC',
            ],
            'Address::fromStrKey' => [
                fn (string $v) => Address::fromStrKey($v),
                fn (string $kind) => 'Not a valid address strkey: ' . $kind,
                'XABC', 'Not a valid address strkey: XABC',
            ],
            'MuxedContract constructor' => [
                fn (string $v) => new MuxedContract($v, 1),
                fn (string $kind) => 'Invalid contract id "' . $kind . '": ' . $contractId . $kind . ' given',
                'XYZ', 'Invalid contract id "XYZ": ' . $contractId . '"XYZ" given',
            ],
            'MuxedContract::fromAddress' => [
                fn (string $v) => MuxedContract::fromAddress($v),
                fn (string $kind) => 'Expected a contract (C...) or muxed contract (W...) address, got: ' . $kind,
                'XYZ', 'Expected a contract (C...) or muxed contract (W...) address, got: XYZ',
            ],
            'ContractSpec Address parameter' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal($v, XdrSCSpecTypeDef::ADDRESS()),
                fn (string $kind) => 'Invalid address format: ' . $kind . $address,
                'XYZ', 'Invalid address format: XYZ' . $address,
            ],
            'ContractSpec MuxedAddress parameter' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal($v, XdrSCSpecTypeDef::MUXED_ADDRESS()),
                fn (string $kind) => 'Invalid address format: ' . $kind . $muxedAddress,
                'XYZ', 'Invalid address format: XYZ' . $muxedAddress,
            ],
            'SorobanAuthorizationEntry::sign forAddress' => [
                fn (string $v) => self::addressV2Entry()->sign(KeyPair::random(), Network::testnet(), null, $v),
                fn (string $kind) => 'forAddress "' . $kind . $noNode,
                'GABC', 'forAddress "GABC' . $noNode,
            ],
            'SorobanAuthorizationEntry::withDelegates' => [
                fn (string $v) => SorobanAuthorizationEntry::withDelegates(self::legacyEntry(), 100,
                    [new SorobanDelegateDescriptor($v)]),
                fn (string $kind) => 'Delegate address must be a G- or C-prefixed strkey; got: ' . $kind,
                'XYZ', 'Delegate address must be a G- or C-prefixed strkey; got: XYZ',
            ],
            'XdrClaimableBalanceID::getCanonicalHashHex' => [
                fn (string $v) => XdrClaimableBalanceID::forClaimableBalanceId($v)->getCanonicalHashHex(),
                fn (string $kind) => $balanceId . $kind . ' given',
                'xyz', $balanceId . '"xyz" given',
            ],
            'ContractAddressGuard::requireContractId' => [
                fn (string $v) => ContractAddressGuard::requireContractId($v, ContractAddressGuard::CONTRACT_TO_LOAD),
                fn (string $kind) => 'The contract to load must be a contract (C...) address, got ' . $kind
                    . ' instead of an address',
                'xyz', 'The contract to load must be a contract (C...) address: xyz',
            ],
            'XdrSCAddress::getCanonicalContractIdHex' => [
                fn (string $v) => XdrSCAddress::forContractId($v)->getCanonicalContractIdHex(),
                fn (string $kind) => $contractId . $kind . ' given',
                'xyz', $contractId . '"xyz" given',
            ],
            'XdrSCAddress::getCanonicalLiquidityPoolIdHex' => [
                fn (string $v) => XdrSCAddress::forLiquidityPoolId($v)->getCanonicalLiquidityPoolIdHex(),
                fn (string $kind) => $poolId . $kind . ' given',
                'xyz', $poolId . '"xyz" given',
            ],
            'TxRepHelper::hexToBytes' => [
                fn (string $v) => TxRepHelper::hexToBytes($v),
                fn (string $kind) => 'Invalid hex string: ' . $kind,
                'ZZZZ', 'Invalid hex string: ZZZZ',
            ],
            'TxRepHelper::parseAccountId' => [
                fn (string $v) => TxRepHelper::parseAccountId($v),
                fn (string $kind) => 'Invalid account ID: ' . $kind,
                'XYZ', 'Invalid account ID: XYZ',
            ],
            'TxRepHelper::parseMuxedAccount' => [
                fn (string $v) => TxRepHelper::parseMuxedAccount($v),
                fn (string $kind) => 'Invalid muxed account: ' . $kind,
                'XYZ', 'Invalid muxed account: XYZ',
            ],
            'TxRepHelper::parseAsset issuer' => [
                fn (string $v) => TxRepHelper::parseAsset('USD:' . $v),
                fn (string $kind) => 'Invalid asset issuer: ' . $kind,
                'XYZ', 'Invalid asset issuer: XYZ',
            ],
            'TxRepHelper::parseChangeTrustAsset issuer' => [
                fn (string $v) => TxRepHelper::parseChangeTrustAsset('USD:' . $v),
                fn (string $kind) => 'Invalid change trust asset issuer: ' . $kind,
                'XYZ', 'Invalid change trust asset issuer: XYZ',
            ],
            'TxRepHelper::parseTrustlineAsset issuer' => [
                fn (string $v) => TxRepHelper::parseTrustlineAsset('USD:' . $v),
                fn (string $kind) => 'Invalid trustline asset issuer: ' . $kind,
                'XYZ', 'Invalid trustline asset issuer: XYZ',
            ],
            'TxRepHelper::parseSignerKey' => [
                fn (string $v) => TxRepHelper::parseSignerKey($v),
                fn (string $kind) => 'Unknown signer key prefix: ' . $kind,
                'YABC', 'Unknown signer key prefix: YABC',
            ],
            'XdrContractExecutable::getCanonicalWasmIdHex' => [
                fn (string $v) => XdrContractExecutable::forWasmId($v)->getCanonicalWasmIdHex(),
                fn (string $kind) => 'Wasm hash must be a hexadecimal string, ' . $kind . ' given',
                'xyz', 'Wasm hash must be a hexadecimal string, "xyz" given',
            ],
            'CreateContractHostFunction encoding' => [
                fn (string $v) => (new CreateContractHostFunction(Address::fromAccountId(self::account()), $v))
                    ->toXdr()->encode(),
                fn (string $kind) => 'Wasm hash must be a hexadecimal string, ' . $kind . ' given',
                'xyz', 'Wasm hash must be a hexadecimal string, "xyz" given',
            ],
        ];
    }

    #[DataProvider('keyPositionSites')]
    #[DataProvider('txRepAssetKeySites')]
    #[DataProvider('txRepAccountKeySites')]
    public function testKeyPositionNamesTheKindOnly(Closure $call, Closure $message, string $otherInput,
        string $otherMessage): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown($call, $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame($message($variant['kind']), $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
        }
        $error = $this->thrown($call, $otherInput, 'other input');
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame($otherMessage, $error->getMessage());
    }

    public function testPoolIdOfTrustlineAssetNamesTheKindOfAPaddedSeed(): void
    {
        // A value of exactly 64 characters is read as a pool id, so a seed padded with spaces
        // reaches the hex check.
        $prefix = 'Invalid trustline asset: expected 64-char hex pool ID but got invalid hex: ';
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown(fn (string $v) => TxRepHelper::parseTrustlineAsset(str_pad($v, 64)),
                $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame($prefix . $variant['kind'], $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
        }
        $other = str_pad('XYZ', 64);
        $error = $this->thrown(fn (string $v) => TxRepHelper::parseTrustlineAsset($v), $other, 'other input');
        $this->assertSame($prefix . $other, $error->getMessage());
    }

    /**
     * @return array<string, array{string}> the forms of SecretSeedVariants::insideLongerValues(), labels only
     */
    public static function seedInsideLongerValueForms(): array
    {
        $forms = [];
        foreach (array_keys(SecretSeedVariants::create()->insideLongerValues()) as $form) {
            $forms[$form] = [$form];
        }
        return $forms;
    }

    #[DataProvider('seedInsideLongerValueForms')]
    public function testHexToBytesReplacesASeedInsideTheValue(string $form): void
    {
        // The test asserts with fixed failure texts, so a failing case prints no seed.
        $variants = SecretSeedVariants::create();
        $value = $variants->insideLongerValues()[$form];
        $error = $this->thrown(fn (string $v) => TxRepHelper::hexToBytes($v), $value['input'], $form);
        $this->assertTrue($error instanceof InvalidArgumentException, $form . ': no InvalidArgumentException');
        $this->assertTrue($error->getMessage() === 'Invalid hex string: ' . $value['redacted'],
            $form . ': the message does not show the value with the seed replaced');
        $variants->assertNotShown($error, $form);
    }

    public function testMuxedAccountFromTxRepNamesTheKindOnly(): void
    {
        // The message keeps the first letter of the value, which the reader trims, and names
        // the value by its kind.
        $key = 'tx.operations[0].body.paymentOp.destination';
        $parse = fn (string $v) => XdrMuxedAccount::fromTxRep([$key => $v], $key);
        $prefix = 'Unrecognized muxed account address prefix "';
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown($parse, $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame($prefix . trim($variant['input'])[0] . '": ' . $variant['kind'], $error->getMessage(),
                $name);
            $variants->assertNotShown($error, $name);
        }
        $error = $this->thrown($parse, 'XYZ', 'other input');
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame($prefix . 'X": XYZ', $error->getMessage());
    }

    public function testTxRepPoolIdOfTrustlineAssetNamesAValueStartingWithS(): void
    {
        // The public TxRep parser reads a trust line asset of exactly 64 characters without a
        // colon as a pool id. It trims the value, so a seed reaches the hex check only with
        // other characters after it, and the error names it as a value starting with S. A value
        // that is not hexadecimal is rejected by the exception alone, without a PHP warning.
        $prefix = 'Invalid trustline asset: expected 64-char hex pool ID but got invalid hex: ';
        $templates = self::txRepAssetTemplates();
        $variants = SecretSeedVariants::create();
        $raised = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$raised): bool {
            $raised[] = $errstr;
            return true;
        });
        try {
            foreach (['revoke sponsorship trust line', 'footprint trust line'] as $site) {
                $parse = fn (string $v) => self::parseTxRepAsset($templates[$site], $v);
                foreach ($variants->variants as $name => $variant) {
                    $context = $site . ', ' . $name;
                    $error = $this->thrown($parse, str_pad(trim($variant['input']), 64, 'Z'), $context);
                    $this->assertInstanceOf(InvalidArgumentException::class, $error, $context);
                    $this->assertSame($prefix . SecretSeedVariants::VALUE_STARTING_WITH_S, $error->getMessage(),
                        $context);
                    $variants->assertNotShown($error, $context);
                }
                $other = str_repeat('Z', 64);
                $error = $this->thrown($parse, $other, $site . ', other input');
                $this->assertInstanceOf(InvalidArgumentException::class, $error, $site);
                $this->assertSame($prefix . $other, $error->getMessage(), $site);
            }
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $raised);
    }

    public function testSignedPayloadSignerNamesASeedWithoutShowingIt(): void
    {
        $payload = str_repeat("\x07", 32);
        $secretSeed = 'a signed payload signer takes an ed25519 account id (G...), not a secret seed (S...)';
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown(fn (string $v) => SignedPayloadSigner::fromAccountId($v, $payload),
                $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $expected = str_starts_with($variant['input'], 'S')
                ? $secretSeed
                : 'invalid ed25519 account id: ' . $variant['kind'];
            $this->assertSame($expected, $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
        }
        $error = $this->thrown(fn (string $v) => SignedPayloadSigner::fromAccountId($v, $payload), 'XYZ', 'other');
        $this->assertSame('invalid ed25519 account id: XYZ', $error->getMessage());
    }

    /**
     * Other positions: the call, the message for the text rule F leaves of the input, an input
     * holding no seed, the message for that input, which is the same as before the change, and
     * the exception class.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string, class-string}>
     */
    public static function otherPositionSites(): array
    {
        $codeLength = 'Asset code length must be 1–12 characters: ';
        $account = self::account();
        return [
            'StellarAmount::fromString with a comma' => [
                fn (string $v) => StellarAmount::fromString($v . ',5'),
                fn (string $text) => 'Amount must not contain a comma: ' . $text . ',5',
                '1', 'Amount must not contain a comma: 1,5', InvalidArgumentException::class,
            ],
            'StellarAmount::fromString' => [
                fn (string $v) => StellarAmount::fromString($v),
                fn (string $text) => 'Amount is not a decimal number: ' . $text,
                'abc', 'Amount is not a decimal number: abc', InvalidArgumentException::class,
            ],
            'AllowTrustOperationBuilder' => [
                fn (string $v) => new AllowTrustOperationBuilder($account, $v, true, false),
                fn (string $text) => 'invalid asset code: ' . $text,
                'ABCDEFGHIJKLM', 'invalid asset code: ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'XdrAllowTrustOperationAsset::fromAlphaNumAssetCode' => [
                fn (string $v) => XdrAllowTrustOperationAsset::fromAlphaNumAssetCode($v),
                fn (string $text) => 'invalid asset code ' . $text,
                'ABCDEFGHIJKLM', 'invalid asset code ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'Federation::resolveStellarAddress' => [
                function (string $v) {
                    $history = [];
                    return Federation::resolveStellarAddress($v, self::mockedClient([], $history));
                },
                fn (string $text) => 'Invalid federation address: ' . $text,
                'sam.soneso.com', 'Invalid federation address: sam.soneso.com', InvalidArgumentException::class,
            ],
            'ContractSpec::funcArgsToXdrSCValues' => [
                fn (string $v) => (new ContractSpec([]))->funcArgsToXdrSCValues($v, []),
                fn (string $text) => 'Function ' . $text . ' does not exist.',
                'hello', 'Function hello does not exist.', InvalidArgumentException::class,
            ],
            'ContractSpec u64 value' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal($v, XdrSCSpecTypeDef::U64()),
                fn (string $text) => "Invalid u64 value '" . $text . "'" . self::U64_RANGE,
                'abc', "Invalid u64 value 'abc'" . self::U64_RANGE, InvalidArgumentException::class,
            ],
            'ContractSpec type name' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal(1, self::udt($v)),
                fn (string $text) => 'entry not found for ' . $text,
                'Point', 'entry not found for Point', InvalidArgumentException::class,
            ],
            'ContractSpec union case' => [
                fn (string $v) => self::optionSpec()->nativeToXdrSCVal(new NativeUnionVal($v), self::udt('Option')),
                fn (string $text) => 'no such enum entry: ' . $text . ' in Option',
                'Invalid', 'no such enum entry: Invalid in Option', InvalidArgumentException::class,
            ],
            'SorobanClient::buildInvokeMethodTx' => [
                fn (string $v) => SorobanClient::forSpecEntries([], new ClientOptions(KeyPair::random(),
                    self::contract(), Network::testnet(), 'https://rpc.example.com'))->buildInvokeMethodTx($v),
                fn (string $text) => "Method '" . $text . "' does not exist",
                'hello', "Method 'hello' does not exist", Exception::class,
            ],
            'TxRepHelper::parseAmount' => [
                fn (string $v) => TxRepHelper::parseAmount($v),
                fn (string $text) => 'Cannot parse amount: ' . ltrim($text),
                'abc', 'Cannot parse amount: abc', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseAsset' => [
                fn (string $v) => TxRepHelper::parseAsset($v),
                fn (string $text) => 'Invalid asset: ' . $text,
                'USD', 'Invalid asset: USD', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseAsset code' => [
                fn (string $v) => TxRepHelper::parseAsset($v . ':' . $account),
                fn (string $text) => $codeLength . ltrim($text),
                'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseChangeTrustAsset' => [
                fn (string $v) => TxRepHelper::parseChangeTrustAsset($v),
                fn (string $text) => 'Invalid change trust asset: ' . $text,
                'USD', 'Invalid change trust asset: USD', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseChangeTrustAsset code' => [
                fn (string $v) => TxRepHelper::parseChangeTrustAsset($v . ':' . $account),
                fn (string $text) => $codeLength . ltrim($text),
                'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseTrustlineAsset' => [
                fn (string $v) => TxRepHelper::parseTrustlineAsset($v),
                fn (string $text) => 'Invalid trustline asset: ' . $text,
                'USD', 'Invalid trustline asset: USD', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseTrustlineAsset code' => [
                fn (string $v) => TxRepHelper::parseTrustlineAsset($v . ':' . $account),
                fn (string $text) => $codeLength . ltrim($text),
                'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseAllowTrustAsset' => [
                fn (string $v) => TxRepHelper::parseAllowTrustAsset($v),
                fn (string $text) => $codeLength . $text,
                'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
            ],
            'TxRepHelper::parseInt' => [
                fn (string $v) => TxRepHelper::parseInt($v),
                fn (string $text) => 'Cannot parse integer: ' . $text,
                'abc', 'Cannot parse integer: abc', InvalidArgumentException::class,
            ],
            'XdrSCVal::forMap duplicate key' => [
                fn (string $v) => XdrSCVal::forMap([
                    new XdrSCMapEntry(XdrSCVal::forString($v), XdrSCVal::forVoid()),
                    new XdrSCMapEntry(XdrSCVal::forString($v), XdrSCVal::forTrue()),
                ]),
                fn (string $text) => 'Duplicate SCMap key (XDR JSON): {"string":"' . $text . '"}',
                'k', 'Duplicate SCMap key (XDR base64): ' . XdrSCVal::forString('k')->toBase64Xdr(),
                InvalidArgumentException::class,
            ],
            'AssetTypePoolShare order' => [
                fn (string $v) => new AssetTypePoolShare(new AssetTypeCreditAlphanum12('ABCDE', $v),
                    new AssetTypeCreditAlphanum4('USD', $account)),
                fn (string $text) => 'Assets ABCDE:' . $text . ' and USD:' . $account . self::POOL_ORDER,
                self::otherAccount(), 'Assets ABCDE:' . self::otherAccount() . ' and USD:' . $account . self::POOL_ORDER,
                RuntimeException::class,
            ],
            'Asset::create type' => [
                fn (string $v) => Asset::create($v),
                fn (string $text) => 'unsupported asset type: ' . $text,
                'foo', 'unsupported asset type: foo', RuntimeException::class,
            ],
            'SorobanClient::deployFromExternalRef tag' => [
                function (string $v) {
                    $history = [];
                    $server = new SorobanServer('https://rpc.example.com', self::mockedClient([
                        new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1,
                            'result' => ['entries' => [], 'latestLedger' => 1000]])),
                    ], $history));
                    return SorobanClient::deployFromExternalRef(new DeployFromExternalRefRequest(
                        'https://rpc.example.com', Network::testnet(), KeyPair::random(),
                        Address::fromContractId(StrKey::decodeContractIdHex(self::contract())), $v,
                        server: $server));
                },
                fn (string $text) => 'external reference does not resolve: owner contract ' . self::contract()
                    . ' holds no entry under tag ' . $text,
                'v1', 'external reference does not resolve: owner contract ' . self::contract()
                    . ' holds no entry under tag v1',
                Exception::class,
            ],
        ];
    }

    /**
     * The public SEP-51 JSON helpers that show a caller's text or label (type name, canonical
     * key, alias key), in the shape of otherPositionSites().
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string, class-string}>
     */
    public static function jsonHelperSites(): array
    {
        $int64 = fn (string $text) => 'SEP-51 int64 string must contain only decimal digits (with optional leading '
            . 'minus); got "' . $text . '"';
        $uint64 = fn (string $text) => 'SEP-51 uint64 string must contain only decimal digits (no sign); got "'
            . $text . '"';
        $wrap = fn (string $text) => 'wrapUnsignedToSignedInt: input must be unsigned decimal digits; got "'
            . $text . '"';
        $converters = [
            'stringToInt64' => [fn (string $v) => XdrJsonHelper::stringToInt64($v), $int64],
            'stringToUint64' => [fn (string $v) => XdrJsonHelper::stringToUint64($v), $uint64],
            'stringToInt128Parts' => [fn (string $v) => XdrJsonHelper::stringToInt128Parts($v), $int64],
            'stringToUint128Parts' => [fn (string $v) => XdrJsonHelper::stringToUint128Parts($v), $uint64],
            'stringToInt256Parts' => [fn (string $v) => XdrJsonHelper::stringToInt256Parts($v), $int64],
            'stringToUint256Parts' => [fn (string $v) => XdrJsonHelper::stringToUint256Parts($v), $uint64],
            'int128PartsToString hi' => [fn (string $v) => XdrJsonHelper::int128PartsToString($v, '0'), $int64],
            'int128PartsToString lo' => [fn (string $v) => XdrJsonHelper::int128PartsToString('0', $v), $int64],
            'uint128PartsToString' => [fn (string $v) => XdrJsonHelper::uint128PartsToString('0', $v), $int64],
            'int256PartsToString' => [fn (string $v) => XdrJsonHelper::int256PartsToString($v, '0', '0', '0'),
                $int64],
            'uint256PartsToString' => [fn (string $v) => XdrJsonHelper::uint256PartsToString('0', '0', '0', $v),
                $int64],
            'wrapUnsignedToSignedInt' => [fn (string $v) => XdrJsonHelper::wrapUnsignedToSignedInt($v), $wrap],
        ];
        $sites = [];
        foreach ($converters as $name => [$call, $message]) {
            $sites['XdrJsonHelper::' . $name] = [$call, $message, '12a', $message('12a'),
                InvalidArgumentException::class];
        }
        $unknown = fn (string $text) => "Unknown field in JSON input for XdrExample: '" . $text . "'";
        $sites['XdrJsonHelper::rejectUnknownFields'] = [
            fn (string $v) => XdrJsonHelper::rejectUnknownFields(['a' => 1, $v => 2], ['a'], 'XdrExample'),
            $unknown, 'b', $unknown('b'), InvalidArgumentException::class,
        ];
        $duplicate = fn (string $text) => "Duplicate key in JSON input: '" . $text . "'";
        $sites['XdrJsonHelper::rejectDuplicateKeys'] = [
            fn (string $v) => XdrJsonHelper::rejectDuplicateKeys('{"' . $v . '":1,"' . $v . '":2}'),
            $duplicate, 'k', $duplicate('k'), InvalidArgumentException::class,
        ];
        $sites['XdrJsonHelper::decodeText'] = [
            fn (string $v) => XdrJsonHelper::decodeText('{"a":{"' . $v . '":1,"' . $v . '":2}}'),
            $duplicate, 'k', $duplicate('k'), InvalidArgumentException::class,
        ];
        $unknownIn = fn (string $text) => 'Unknown field in JSON input for ' . $text . ": 'b'";
        $sites['XdrJsonHelper::rejectUnknownFields type name'] = [
            fn (string $v) => XdrJsonHelper::rejectUnknownFields(['b' => 1], ['a'], $v),
            $unknownIn, 'XdrExample', $unknownIn('XdrExample'), InvalidArgumentException::class,
        ];
        $alias = fn (string $canonical, string $alias, string $type) => "Duplicate field '" . $canonical
            . "' for " . $type . ": '" . $canonical . "' and its input alias '" . $alias
            . "' cannot both be supplied";
        $canonicalSite = fn (string $text) => $alias($text, 'alias', 'XdrExample');
        $sites['XdrJsonHelper::normalizeFieldAlias canonical key'] = [
            fn (string $v) => XdrJsonHelper::normalizeFieldAlias([$v => 1, 'alias' => 2], $v, 'alias', 'XdrExample'),
            $canonicalSite, 'field', $canonicalSite('field'), InvalidArgumentException::class,
        ];
        $aliasSite = fn (string $text) => $alias('field', $text, 'XdrExample');
        $sites['XdrJsonHelper::normalizeFieldAlias alias key'] = [
            fn (string $v) => XdrJsonHelper::normalizeFieldAlias(['field' => 1, $v => 2], 'field', $v, 'XdrExample'),
            $aliasSite, 'alias', $aliasSite('alias'), InvalidArgumentException::class,
        ];
        $typeSite = fn (string $text) => $alias('field', 'alias', $text);
        $sites['XdrJsonHelper::normalizeFieldAlias type name'] = [
            fn (string $v) => XdrJsonHelper::normalizeFieldAlias(['field' => 1, 'alias' => 2], 'field', 'alias', $v),
            $typeSite, 'XdrExample', $typeSite('XdrExample'), InvalidArgumentException::class,
        ];
        return $sites;
    }

    /**
     * The public TxRep integer parsers, which name the caller's field key in their errors, in the
     * shape of otherPositionSites(): the seed and its variants are passed as the key.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string, class-string}>
     */
    public static function txRepFieldKeySites(): array
    {
        $parsers = [
            'parseUint32' => [fn (string $s, string $k) => TxRepHelper::parseUint32($s, $k), '4294967296',
                '[0, 4294967295]'],
            'parseInt32' => [fn (string $s, string $k) => TxRepHelper::parseInt32($s, $k), '2147483648',
                '[-2147483648, 2147483647]'],
            'parseUint64' => [fn (string $s, string $k) => TxRepHelper::parseUint64($s, $k), '18446744073709551616',
                '[0, 18446744073709551615]'],
            'parseInt64' => [fn (string $s, string $k) => TxRepHelper::parseInt64($s, $k), '9223372036854775808',
                '[-9223372036854775808, 9223372036854775807]'],
        ];
        $sites = [];
        foreach ($parsers as $name => [$parse, $outside, $range]) {
            $syntax = fn (string $key) => 'Cannot parse integer for ' . $key . ': abc';
            $sites['TxRepHelper::' . $name . ' key, invalid syntax'] = [
                fn (string $v) => $parse('abc', $v), $syntax, 'tx.fee', $syntax('tx.fee'),
                InvalidArgumentException::class,
            ];
            $bound = fn (string $key) => 'Integer value ' . $outside . ' for ' . $key . ' is outside ' . $range;
            $sites['TxRepHelper::' . $name . ' key, out of range'] = [
                fn (string $v) => $parse($outside, $v), $bound, 'tx.fee', $bound('tx.fee'),
                InvalidArgumentException::class,
            ];
        }
        return $sites;
    }

    /**
     * The asset issuer the public TxRep parser reads, in the shape of keyPositionSites().
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string}>
     */
    public static function txRepAssetKeySites(): array
    {
        $sites = [];
        foreach (self::txRepAssetTemplates() as $name => $template) {
            $issuer = $name === 'change trust' ? 'Invalid change trust asset issuer: ' : 'Invalid trustline asset issuer: ';
            $sites['TxRep ' . $name . ' issuer'] = [
                fn (string $v) => self::parseTxRepAsset($template, 'USD:' . $v),
                fn (string $kind) => $issuer . $kind,
                'XYZ', $issuer . 'XYZ',
            ];
        }
        return $sites;
    }

    /**
     * The account id XdrAccountID reads from a TxRep map, in the shape of keyPositionSites().
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string}>
     */
    public static function txRepAccountKeySites(): array
    {
        $key = 'tx.operations[0].body.createAccountOp.destination';
        $invalid = 'Invalid account ID in TxRep for key "' . $key . '": ';
        return [
            'XdrAccountID::fromTxRep' => [
                fn (string $v) => XdrAccountID::fromTxRep([$key => $v], $key),
                fn (string $kind) => $invalid . $kind,
                'XYZ', $invalid . 'XYZ',
            ],
        ];
    }

    /**
     * The asset value and asset code the public TxRep parser reads, and the asset code of an
     * allow trust asset read from a TxRep map, in the shape of otherPositionSites(). The parser
     * trims each value, so leading blanks do not reach the message.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string, class-string}>
     */
    public static function txRepAssetOtherSites(): array
    {
        $codeLength = 'Asset code length must be 1–12 characters: ';
        $account = self::account();
        $sites = [];
        foreach (self::txRepAssetTemplates() as $name => $template) {
            $invalid = $name === 'change trust' ? 'Invalid change trust asset: ' : 'Invalid trustline asset: ';
            $sites['TxRep ' . $name . ' value'] = [
                fn (string $v) => self::parseTxRepAsset($template, $v),
                fn (string $text) => $invalid . ltrim($text),
                'USD', $invalid . 'USD', InvalidArgumentException::class,
            ];
            $sites['TxRep ' . $name . ' code'] = [
                fn (string $v) => self::parseTxRepAsset($template, $v . ':' . $account),
                fn (string $text) => $codeLength . ltrim($text),
                'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
            ];
        }
        $key = 'tx.operations[0].body.allowTrustOp.asset';
        $sites['XdrAllowTrustOperationAsset::fromTxRep'] = [
            fn (string $v) => XdrAllowTrustOperationAsset::fromTxRep([$key => $v], $key),
            fn (string $text) => $codeLength . ltrim($text),
            'ABCDEFGHIJKLM', $codeLength . 'ABCDEFGHIJKLM', InvalidArgumentException::class,
        ];
        return $sites;
    }

    #[DataProvider('otherPositionSites')]
    #[DataProvider('jsonHelperSites')]
    #[DataProvider('txRepFieldKeySites')]
    #[DataProvider('txRepAssetOtherSites')]
    public function testOtherPositionReplacesWhatMayBeASeed(Closure $call, Closure $message, string $otherInput,
        string $otherMessage, string $exceptionClass): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown($call, $variant['input'], $name);
            $this->assertInstanceOf($exceptionClass, $error, $name);
            $this->assertSame($message($variant['redacted']), $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
        }
        $error = $this->thrown($call, $otherInput, 'other input');
        $this->assertInstanceOf($exceptionClass, $error);
        $this->assertSame($otherMessage, $error->getMessage());
    }

    public function testAmountWithTooManyDecimalPlacesKeepsItsText(): void
    {
        $error = $this->thrown(fn (string $v) => StellarAmount::fromString($v), '0.12345678', 'amount');
        $this->assertSame('Amount cannot have more than 7 decimal places: 0.12345678', $error->getMessage());
    }

    public function testSacAmountsReplaceWhatMayBeASeed(): void
    {
        $decimalTail = "': expected decimal digits with at most 7 fractional digits, such as 12.5";
        $transferTail = "'; convert a decimal amount with SACClient::toBaseUnits()";
        $variants = SecretSeedVariants::create();
        $keyPair = KeyPair::random();
        foreach ($variants->variants as $name => $variant) {
            $exactSeed = $variant['input'] === $variants->seed;
            $error = $this->thrown(fn (string $v) => SACClient::toBaseUnits($v), $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame($exactSeed
                ? 'Invalid amount, got a secret seed (S...) instead of decimal digits with at most 7 fractional '
                    . 'digits, such as 12.5'
                : "Invalid amount '" . $variant['redacted'] . $decimalTail, $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);

            $history = [];
            $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), 'https://rpc.example.com',
                null, new SorobanServer('https://rpc.example.com', self::mockedClient([], $history)));
            $error = $this->thrown(fn (string $v) => $client->transfer(Address::fromAccountId($keyPair->getAccountId()),
                Address::fromAccountId(self::account()), $v), $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame($exactSeed
                ? 'The transfer amount must be a whole number of base units, got a secret seed (S...) instead of '
                    . 'an amount'
                : "The transfer amount must be a whole number of base units, got '" . $variant['redacted']
                    . $transferTail, $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
            $this->assertSame([], $history, $name);
        }
        $error = $this->thrown(fn (string $v) => SACClient::toBaseUnits($v), '1.5x', 'other input');
        $this->assertSame("Invalid amount '1.5x" . $decimalTail, $error->getMessage());
    }

    public function testDuplicateDeepMapKeyIsShownOnlyWhenItsCopyDecodes(): void
    {
        // Each level of the key adds frames to the JSON conversion; Xdebug, when loaded, limits
        // the call depth, so its limit is raised for these keys and restored afterwards.
        $previousLimit = ini_get('xdebug.max_nesting_level');
        ini_set('xdebug.max_nesting_level', '10000');
        try {
            $seed = SecretSeedVariants::create()->seed;
            // 300 vectors nest 601 JSON levels; the deeper key nests more than the JSON limit
            // allows and more recursive values than the binary decoder accepts for its copy.
            foreach ([300, intdiv(XdrJsonHelper::JSON_DEPTH_LIMIT + 1, 2)] as $levels) {
                $key = XdrSCVal::forString($seed);
                $redacted = XdrSCVal::forString(SecretSeedVariants::SECRET_SEED);
                for ($level = 0; $level < $levels; $level++) {
                    $key = XdrSCVal::forVec([$key]);
                    $redacted = XdrSCVal::forVec([$redacted]);
                }
                $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
                    new XdrSCMapEntry($key, XdrSCVal::forVoid()),
                    new XdrSCMapEntry($key, XdrSCVal::forTrue()),
                ]), '', $levels . ' vectors');
                $this->assertInstanceOf(InvalidArgumentException::class, $error);
                $this->assertSame($levels === 300 ? 'Duplicate SCMap key (XDR JSON): ' . $redacted->toJson()
                    : 'Duplicate SCMap key (not shown, it may hold a secret seed)', $error->getMessage());
                $this->assertStringNotContainsString($seed, $error->getMessage());
            }
        } finally {
            if ($previousLimit !== false) {
                ini_set('xdebug.max_nesting_level', $previousLimit);
            }
        }
    }

    /**
     * Map keys whose content a caller fills: each builds the key around the given content.
     *
     * @return array<string, array{Closure(string): XdrSCVal}>
     */
    public static function mapKeyBuilders(): array
    {
        $owner = XdrSCAddress::forContractId(StrKey::decodeContractIdHex(self::contract()));
        return [
            'bytes' => [fn (string $c) => XdrSCVal::forBytes($c)],
            'string after a line break' => [fn (string $c) => XdrSCVal::forString("\n" . $c)],
            'string after a tab' => [fn (string $c) => XdrSCVal::forString("\t" . $c)],
            'executable tag' => [fn (string $c) => XdrSCVal::forExecutableTag($c)],
            'bytes in a map in a vector' => [fn (string $c) => XdrSCVal::forVec([XdrSCVal::forU32(1),
                XdrSCVal::forMap([new XdrSCMapEntry(XdrSCVal::forSymbol('k'), XdrSCVal::forBytes($c))])])],
            'tag of a contract instance' => [fn (string $c) => XdrSCVal::forContractInstance(
                new XdrSCContractInstance(XdrContractExecutable::forExternalRef($owner, $c)))],
            'storage of a contract instance' => [fn (string $c) => XdrSCVal::forContractInstance(
                new XdrSCContractInstance(XdrContractExecutable::forToken(),
                    [new XdrSCMapEntry(XdrSCVal::forSymbol('k'), XdrSCVal::forString($c))]))],
        ];
    }

    #[DataProvider('mapKeyBuilders')]
    public function testDuplicateMapKeyReplacesWhatMayBeASeedBeforeAnyEncoding(Closure $build): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $key = $build($variant['input']);
            $original = $key->toBase64Xdr();
            $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
                new XdrSCMapEntry($key, XdrSCVal::forVoid()),
                new XdrSCMapEntry($build($variant['input']), XdrSCVal::forTrue()),
            ]), '', $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame('Duplicate SCMap key (XDR JSON): ' . $build($variant['redacted'])->toJson(),
                $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
            foreach ([base64_encode($variant['input']), bin2hex($variant['input']), $original] as $encoded) {
                $this->assertStringNotContainsString($encoded, $error->getMessage(), $name);
            }
            $this->assertSame($original, $key->toBase64Xdr(), $name . ': the key passed in is unchanged');
        }
    }

    public function testDuplicateSymbolKeyLongerThanASymbolIsNotShown(): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
                new XdrSCMapEntry(XdrSCVal::forSymbol($v), XdrSCVal::forVoid()),
                new XdrSCMapEntry(XdrSCVal::forSymbol($v), XdrSCVal::forTrue()),
            ]), $variant['input'], $name);
            $this->assertInstanceOf(InvalidArgumentException::class, $error, $name);
            $this->assertSame('Duplicate SCMap key (not shown, it may hold a secret seed)', $error->getMessage(), $name);
            $variants->assertNotShown($error, $name);
        }
    }

    /**
     * Map keys whose content holds nothing that may be a secret seed.
     *
     * @return array<string, array{XdrSCVal}>
     */
    public static function mapKeysWithoutASeed(): array
    {
        $owner = XdrSCAddress::forContractId(StrKey::decodeContractIdHex(self::contract()));
        return [
            'bytes' => [XdrSCVal::forBytes('plain bytes')],
            'bytes of 50 letters' => [XdrSCVal::forBytes(str_repeat('A', 50))],
            'string after a line break' => [XdrSCVal::forString("\nline")],
            'string with a run of 39 starting with S' => [XdrSCVal::forString('S' . str_repeat('A', 38))],
            'symbol' => [XdrSCVal::forSymbol('balance')],
            'executable tag' => [XdrSCVal::forExecutableTag('v1')],
            'bytes in a map in a vector' => [XdrSCVal::forVec([XdrSCVal::forU32(1),
                XdrSCVal::forMap([new XdrSCMapEntry(XdrSCVal::forSymbol('k'), XdrSCVal::forBytes('v'))])])],
            'tag of a contract instance' => [XdrSCVal::forContractInstance(
                new XdrSCContractInstance(XdrContractExecutable::forExternalRef($owner, 'v1')))],
            'storage of a contract instance' => [XdrSCVal::forContractInstance(
                new XdrSCContractInstance(XdrContractExecutable::forToken(),
                    [new XdrSCMapEntry(XdrSCVal::forSymbol('k'), XdrSCVal::forString('v'))]))],
        ];
    }

    #[DataProvider('mapKeysWithoutASeed')]
    public function testDuplicateMapKeyWithoutASeedKeepsItsBase64Text(XdrSCVal $key): void
    {
        $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
            new XdrSCMapEntry($key, XdrSCVal::forVoid()),
            new XdrSCMapEntry($key, XdrSCVal::forTrue()),
        ]), '', 'key without a seed');
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame('Duplicate SCMap key (XDR base64): ' . $key->toBase64Xdr(), $error->getMessage());
    }

    public function testDeepDuplicateMapKeyWithoutASeedKeepsItsBase64Text(): void
    {
        // Xdebug, when loaded, limits the call depth of the encoding and the comparison of this
        // key, so its limit is raised for the key and restored afterwards.
        $previousLimit = ini_get('xdebug.max_nesting_level');
        ini_set('xdebug.max_nesting_level', '10000');
        try {
            $key = XdrSCVal::forString('k');
            for ($level = 0; $level < 300; $level++) {
                $key = XdrSCVal::forVec([$key]);
            }
            $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
                new XdrSCMapEntry($key, XdrSCVal::forVoid()),
                new XdrSCMapEntry($key, XdrSCVal::forTrue()),
            ]), '', 'deep key');
            $expected = 'Duplicate SCMap key (XDR base64): ' . $key->toBase64Xdr();
        } finally {
            if ($previousLimit !== false) {
                ini_set('xdebug.max_nesting_level', $previousLimit);
            }
        }
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame($expected, $error->getMessage());
    }

    public function testValidInputsPassAsBefore(): void
    {
        $values = SecretSeedRedactionTest::validKeyValues();
        $g = $values['account (G...)'];
        $m = $values['muxed account (M...)'];
        $c = $values['contract (C...)'];
        $w = $values['muxed contract (W...)'];
        $l = $values['liquidity pool (L...)'];
        $b = $values['claimable balance (B...)'];
        $hex = $values['hex id'];

        foreach ([$g, $m, $c, $w, $l, $b] as $strKey) {
            $this->assertSame($strKey, Address::fromStrKey($strKey)->toXdr()->toStrKey());
        }
        $this->assertSame($g, MuxedAccount::fromAccountId($g)->getAccountId());
        $this->assertSame($m, MuxedAccount::fromAccountId($m)->getAccountId());
        $this->assertSame($g, XdrSCAddress::forAccountId($g)->toStrKey());
        $this->assertSame($m, XdrSCAddress::forAccountId($m)->toStrKey());
        $this->assertSame($c, (new MuxedContract($c, 1))->getContractId());
        $this->assertSame(StrKey::encodeContractIdHex($hex), (new MuxedContract($hex))->getContractId());
        $this->assertSame($w, MuxedContract::fromAddress($w)->getAddress());
        $this->assertSame(StrKey::decodeContractIdHex($c), XdrSCAddress::forContractId($c)->getCanonicalContractIdHex());
        $this->assertSame($hex, XdrSCAddress::forContractId($hex)->getCanonicalContractIdHex());
        $this->assertSame(StrKey::decodeLiquidityPoolIdHex($l),
            XdrSCAddress::forLiquidityPoolId($l)->getCanonicalLiquidityPoolIdHex());
        $this->assertSame($hex, XdrSCAddress::forLiquidityPoolId($hex)->getCanonicalLiquidityPoolIdHex());
        $this->assertSame(substr(StrKey::decodeClaimableBalanceIdHex($b), 2),
            XdrClaimableBalanceID::forClaimableBalanceId($b)->getCanonicalHashHex());
        $this->assertSame($hex, XdrClaimableBalanceID::forClaimableBalanceId($hex)->getCanonicalHashHex());
        ContractAddressGuard::requireContractId($c, ContractAddressGuard::CONTRACT_TO_LOAD);
        ContractAddressGuard::requireContractId($hex, ContractAddressGuard::CONTRACT_TO_LOAD);

        $spec = new ContractSpec([]);
        $this->assertSame($g, $spec->nativeToXdrSCVal($g, XdrSCSpecTypeDef::ADDRESS())->address?->toStrKey());
        $this->assertSame($c, $spec->nativeToXdrSCVal($c, XdrSCSpecTypeDef::ADDRESS())->address?->toStrKey());
        $this->assertSame($m, $spec->nativeToXdrSCVal($m, XdrSCSpecTypeDef::MUXED_ADDRESS())->address?->toStrKey());
        $this->assertSame($w, $spec->nativeToXdrSCVal($w, XdrSCSpecTypeDef::MUXED_ADDRESS())->address?->toStrKey());

        $entry = self::addressV2Entry();
        $entry->sign(KeyPair::random(), Network::testnet(), null, self::account());
        $this->assertCount(1, $entry->credentials->getAddressCredentials()?->signature->vec ?? []);
        $withDelegates = SorobanAuthorizationEntry::withDelegates(self::legacyEntry(), 100,
            [new SorobanDelegateDescriptor($g), new SorobanDelegateDescriptor($c)]);
        $this->assertCount(2, $withDelegates->credentials->addressWithDelegates?->delegates ?? []);

        $this->assertSame('125000000', StellarAmount::fromString('12.5')->getStroopsAsString());
        $this->assertSame('125000000', gmp_strval(SACClient::toBaseUnits('12.5')));
        $this->assertSame(125000000, TxRepHelper::parseAmount('12.5'));
        (new AllowTrustOperationBuilder($g, 'SEED', true, false))->build();
        $this->assertSame('SEED', TxRepHelper::formatAllowTrustAsset(
            XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('SEED')));
        $this->assertSame('SEED', TxRepHelper::formatAllowTrustAsset(TxRepHelper::parseAllowTrustAsset('SEED')));
        $this->assertSame('SEED:' . $g, TxRepHelper::formatAsset(TxRepHelper::parseAsset('SEED:' . $g)));
        $this->assertSame('SEED:' . $g,
            TxRepHelper::formatChangeTrustAsset(TxRepHelper::parseChangeTrustAsset('SEED:' . $g)));
        $this->assertSame('SEED:' . $g,
            TxRepHelper::formatTrustlineAsset(TxRepHelper::parseTrustlineAsset('SEED:' . $g)));
        $this->assertSame($hex, TxRepHelper::formatTrustlineAsset(TxRepHelper::parseTrustlineAsset($hex)));
        foreach (self::txRepAssetTemplates() as $name => $template) {
            $assets = $name === 'change trust' ? ['SEED:' . $g, 'native'] : ['SEED:' . $g, 'SEEDLONG:' . $g, $hex];
            foreach ($assets as $asset) {
                $this->assertSame(str_replace('{asset}', $asset === 'native' ? 'XLM' : $asset, $template),
                    TxRep::fromTransactionEnvelopeXdrBase64(self::parseTxRepAsset($template, $asset)), $name);
            }
        }
        $accountKey = 'tx.operations[0].body.paymentOp.destination';
        $this->assertSame($g, XdrAccountID::fromTxRep([$accountKey => $g], $accountKey)->getAccountId());
        foreach ([$g, $m] as $address) {
            $this->assertSame($address,
                TxRepHelper::formatMuxedAccount(XdrMuxedAccount::fromTxRep([$accountKey => $address], $accountKey)));
        }
        $allowTrustKey = 'tx.operations[0].body.allowTrustOp.asset';
        $this->assertSame('SEED', TxRepHelper::formatAllowTrustAsset(
            XdrAllowTrustOperationAsset::fromTxRep([$allowTrustKey => 'SEED'], $allowTrustKey)));
        $this->assertSame($g, TxRepHelper::formatAccountId(TxRepHelper::parseAccountId($g)));
        $this->assertSame($m, TxRepHelper::formatMuxedAccount(TxRepHelper::parseMuxedAccount($m)));
        $this->assertSame($g, TxRepHelper::formatSignerKey(TxRepHelper::parseSignerKey($g)));
        $this->assertSame(hex2bin($hex), TxRepHelper::hexToBytes($hex));
        $this->assertSame(12, TxRepHelper::parseInt('12'));
        $this->assertSame($g, SignedPayloadSigner::fromAccountId($g, str_repeat("\x07", 32))
            ->getSignerAccountId()->getAccountId());

        $map = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forString('SEED'), XdrSCVal::forVoid()),
            new XdrSCMapEntry(XdrSCVal::forString('sam*soneso.com'), XdrSCVal::forVoid()),
        ]);
        $this->assertCount(2, $map->map ?? []);
        $pool = new AssetTypePoolShare(new AssetTypeCreditAlphanum4('SEED', $g), new AssetTypeCreditAlphanum12('SEEDLONG', $g));
        $this->assertSame('SEED:' . $g, Asset::canonicalForm($pool->getAssetA()));
        $byteKeys = XdrSCVal::forMap([
            new XdrSCMapEntry(XdrSCVal::forBytes('SEED'), XdrSCVal::forVoid()),
            new XdrSCMapEntry(XdrSCVal::forSymbol('SEED'), XdrSCVal::forVoid()),
            new XdrSCMapEntry(XdrSCVal::forExecutableTag('SEED'), XdrSCVal::forVoid()),
        ]);
        $this->assertCount(3, $byteKeys->map ?? []);

        $this->assertSame($hex, XdrContractExecutable::forWasmId($hex)->getCanonicalWasmIdHex());
        $this->assertSame($hex, XdrContractExecutable::forWasmId(strtoupper($hex))->getCanonicalWasmIdHex());
        $hostFunction = (new CreateContractHostFunction(Address::fromAccountId($g), $hex))->toXdr();
        $this->assertSame($hex, XdrHostFunction::fromBase64Xdr($hostFunction->toBase64Xdr())
            ->createContract?->executable->getCanonicalWasmIdHex());
        $this->assertInstanceOf(AssetTypeNative::class, Asset::create(Asset::TYPE_NATIVE));
        $this->assertSame('SEED:' . $g, Asset::canonicalForm(Asset::create(Asset::TYPE_CREDIT_ALPHANUM_4, 'SEED', $g)));

        $this->assertSame(-12, XdrJsonHelper::stringToInt64('-12'));
        $this->assertSame(-1, XdrJsonHelper::stringToUint64('18446744073709551615'));
        $this->assertSame(['hi' => '-1', 'lo' => '18446744073709551615'], XdrJsonHelper::stringToInt128Parts('-1'));
        $this->assertSame('-1', XdrJsonHelper::int128PartsToString('-1', '18446744073709551615'));
        $this->assertSame(-1, XdrJsonHelper::wrapUnsignedToSignedInt('18446744073709551615'));
        XdrJsonHelper::rejectUnknownFields(['SEED' => 1], ['SEED'], 'XdrExample');
        $this->assertSame(['SEED' => ['SEED' => 1]], XdrJsonHelper::decodeText('{"SEED":{"SEED":1}}'));

        $this->assertSame(12, TxRepHelper::parseUint32('12', 'tx.fee'));
        $this->assertSame(-5, TxRepHelper::parseInt32('-5', 'tx.operation[0].body.value'));
        $this->assertSame(-1, TxRepHelper::parseUint64('18446744073709551615', 'tx.seqNum'));
        $this->assertSame(-5, TxRepHelper::parseInt64('-5', 'tx.seqNum'));
    }

    public function testFederationAddressStartingWithSResolves(): void
    {
        $account = self::account();
        $history = [];
        $client = self::mockedClient([
            new Response(200, [], 'FEDERATION_SERVER="https://federation.example.com/federation"'),
            new Response(200, [], json_encode(['stellar_address' => 'sam*soneso.com', 'account_id' => $account])),
        ], $history);

        $response = Federation::resolveStellarAddress('sam*soneso.com', $client);

        $this->assertSame($account, $response->getAccountId());
        $this->assertCount(2, $history);
        parse_str($history[1]['request']->getUri()->getQuery(), $query);
        $this->assertSame('sam*soneso.com', $query['q'] ?? null);
    }

    /**
     * Minimal V1 transaction TxRep template with `{memoType}` in place of `tx.memo.type`.
     */
    private static function txRepMemoTemplate(): string
    {
        $account = self::account();
        return "type: ENVELOPE_TYPE_TX\ntx.sourceAccount: $account\ntx.fee: 100\ntx.seqNum: 2\n"
            . "tx.cond.type: PRECOND_NONE\ntx.memo.type: {memoType}\ntx.operations.len: 0\n"
            . "tx.ext.v: 0\nsignatures.len: 0";
    }

    /**
     * XDR text reader sites (generated enum/union readers, SEP-51 preview readers and
     * TxRep::transactionEnvelopeXdrBase64FromTxRep) together with hand-written XDR sites that
     * interpolate a caller-supplied value or prefix.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string}>
     */
    public static function xdrTextReaderSites(): array
    {
        $memoTemplate = self::txRepMemoTemplate();
        return [
            'XdrMemoType::fromTxRepName' => [
                fn (string $v) => XdrMemoType::fromTxRepName($v),
                fn (string $text) => 'Unknown enum value: ' . $text,
                'MEMO_BOGUS', 'Unknown enum value: MEMO_BOGUS',
            ],
            'XdrMemoType::fromTxRep value' => [
                fn (string $v) => XdrMemoType::fromTxRep(['k' => $v], 'k'),
                fn (string $text) => 'Unknown enum value: ' . ltrim($text),
                'MEMO_BOGUS', 'Unknown enum value: MEMO_BOGUS',
            ],
            'XdrMemoType::fromTxRep missing prefix' => [
                fn (string $v) => XdrMemoType::fromTxRep([], $v),
                fn (string $text) => 'Missing TxRep value for: ' . $text,
                'tx.memo.type', 'Missing TxRep value for: tx.memo.type',
            ],
            'XdrMemoType::fromJsonValue' => [
                fn (string $v) => XdrMemoType::fromJsonValue($v),
                fn (string $text) => 'Unknown XdrMemoType JSON value: ' . $text,
                'bogus', 'Unknown XdrMemoType JSON value: bogus',
            ],
            'TxRep::transactionEnvelopeXdrBase64FromTxRep memo type' => [
                fn (string $v) => TxRep::transactionEnvelopeXdrBase64FromTxRep(
                    str_replace('{memoType}', $v, $memoTemplate)
                ),
                fn (string $text) => 'Unknown memo type: ' . ltrim($text),
                'MEMO_BOGUS', 'Unknown memo type: MEMO_BOGUS',
            ],
            'XdrContractExecutable::fromJsonValue wasm' => [
                fn (string $v) => XdrContractExecutable::fromJsonValue(['wasm' => $v]),
                fn (string $text) => 'Wasm hash must be a hexadecimal string, "' . $text . '" given',
                'xyz', 'Wasm hash must be a hexadecimal string, "xyz" given',
            ],
            'XdrContractExecutable::fromTxRep wasm_hash' => [
                fn (string $v) => XdrContractExecutable::fromTxRep(
                    ['x.type' => 'CONTRACT_EXECUTABLE_WASM', 'x.wasm_hash' => $v],
                    'x'
                ),
                fn (string $text) => 'Wasm hash must be a hexadecimal string, "' . ltrim($text) . '" given',
                'xyz', 'Wasm hash must be a hexadecimal string, "xyz" given',
            ],
            'XdrContractExecutable::fromJsonValue void arm' => [
                fn (string $v) => XdrContractExecutable::fromJsonValue($v),
                fn (string $text) => 'Unknown XdrContractExecutableBase void arm string: ' . $text,
                'bogus', 'Unknown XdrContractExecutableBase void arm string: bogus',
            ],
            'XdrContractExecutable::fromJsonValue arm key' => [
                fn (string $v) => XdrContractExecutable::fromJsonValue([$v => null]),
                fn (string $text) => 'Unknown arm key for XdrContractExecutableBase: ' . $text,
                'bogus', 'Unknown arm key for XdrContractExecutableBase: bogus',
            ],
            'XdrAccountID::fromTxRep missing prefix' => [
                fn (string $v) => XdrAccountID::fromTxRep([], $v),
                fn (string $text) => 'Missing TxRep value for: ' . $text,
                'tx.sourceAccount', 'Missing TxRep value for: tx.sourceAccount',
            ],
            'XdrAccountID::fromTxRep invalid key prefix' => [
                fn (string $v) => XdrAccountID::fromTxRep([$v => 'XYZ'], $v),
                fn (string $text) => 'Invalid account ID in TxRep for key "' . $text . '": XYZ',
                'tx.sourceAccount', 'Invalid account ID in TxRep for key "tx.sourceAccount": XYZ',
            ],
            'XdrAllowTrustOperationAsset::fromTxRep missing prefix' => [
                fn (string $v) => XdrAllowTrustOperationAsset::fromTxRep([], $v),
                fn (string $text) => 'Missing TxRep value for: ' . $text,
                'tx.asset', 'Missing TxRep value for: tx.asset',
            ],
            'XdrMuxedAccount::fromTxRep missing prefix' => [
                fn (string $v) => XdrMuxedAccount::fromTxRep([], $v),
                fn (string $text) => 'Missing TxRep value for: ' . $text,
                'tx.sourceAccount', 'Missing TxRep value for: tx.sourceAccount',
            ],
            'XdrMuxedAccount::fromTxRep empty prefix' => [
                fn (string $v) => XdrMuxedAccount::fromTxRep([$v => ''], $v),
                fn (string $text) => 'Empty muxed account address for: ' . $text,
                'tx.sourceAccount', 'Empty muxed account address for: tx.sourceAccount',
            ],
            'XdrTrustlineAsset::fromTxRep missing prefix' => [
                fn (string $v) => XdrTrustlineAsset::fromTxRep([], $v),
                fn (string $text) => 'Missing TxRep value for: ' . $text,
                'tx.asset', 'Missing TxRep value for: tx.asset',
            ],
            'XdrEncoder::assertMaxLength name' => [
                fn (string $v) => XdrEncoder::assertMaxLength(29, 28, $v . '.text'),
                fn (string $text) => $text . '.text length 29 exceeds the maximum of 28',
                'tx.memo', 'tx.memo.text length 29 exceeds the maximum of 28',
            ],
            'XdrMemo::fromTxRep overlong text prefix' => [
                fn (string $v) => XdrMemo::fromTxRep(
                    [$v . '.type' => 'MEMO_TEXT', $v . '.text' => str_repeat('m', 29)],
                    $v
                ),
                fn (string $text) => $text . '.text length 29 exceeds the maximum of 28',
                'tx.memo', 'tx.memo.text length 29 exceeds the maximum of 28',
            ],
            'XdrBuffer::assertFullyConsumed typeName' => [
                fn (string $v) => (new XdrBuffer("\x00"))->assertFullyConsumed($v),
                fn (string $text) => $text . ' leaves 1 of 1 bytes unread',
                'XdrMemo', 'XdrMemo leaves 1 of 1 bytes unread',
            ],
            'XdrBuffer::readExtensionPoint typeName' => [
                fn (string $v) => (new XdrBuffer("\x00\x00\x00\x01"))->readExtensionPoint($v),
                fn (string $text) => $text . ' extension point must be 0, got 1',
                'XdrTransaction', 'XdrTransaction extension point must be 0, got 1',
            ],
            'XdrBuffer::readOptionalPresence fieldName' => [
                fn (string $v) => (new XdrBuffer("\x00\x00\x00\x02"))->readOptionalPresence($v),
                fn (string $text) => $text . ' presence flag must be 0 or 1, got 2',
                'XdrOperation.sourceAccount', 'XdrOperation.sourceAccount presence flag must be 0 or 1, got 2',
            ],
        ];
    }

    #[DataProvider('xdrTextReaderSites')]
    public function testXdrTextReadersRedactSeedsAndKeepControls(
        Closure $call,
        Closure $message,
        string $otherInput,
        string $otherMessage
    ): void {
        // Secret-bearing assertions use fixed failure messages so a failure prints no seed.
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $error = $this->thrown($call, $variant['input'], $name);
            $this->assertTrue($error instanceof InvalidArgumentException, $name . ': no InvalidArgumentException');
            $this->assertTrue(
                $error->getMessage() === $message($variant['redacted']),
                $name . ': the message does not match the redacted expectation'
            );
            $variants->assertNotShown($error, $name);
        }
        foreach ($variants->insideLongerValues() as $form => $value) {
            $error = $this->thrown($call, $value['input'], $form);
            $this->assertTrue($error instanceof InvalidArgumentException, $form . ': no InvalidArgumentException');
            $this->assertTrue(
                $error->getMessage() === $message($value['redacted']),
                $form . ': the message does not redact the embedded seed'
            );
            $variants->assertNotShown($error, $form);
        }
        $error = $this->thrown($call, $otherInput, 'other input');
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame($otherMessage, $error->getMessage());
    }

    public function testHandWrittenXdrKeySitesRedactEmbeddedSeeds(): void
    {
        // Key-position readers in Xdr/ must also redact seeds embedded behind a non-S prefix
        // while keeping ordinary invalid inputs byte-identical.
        $variants = SecretSeedVariants::create();
        $seed = $variants->seed;
        $marker = SecretSeedVariants::SECRET_SEED;
        $poolPrefix = 'Invalid trustline asset: expected 64-char hex pool ID but got invalid hex: ';

        $cases = [
            'TxRepHelper::parseSignerKey G-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseSignerKey($v),
                'G' . $seed,
                'Invalid Ed25519 signer key: G' . $marker,
                'GINVALID',
                'Invalid Ed25519 signer key: GINVALID',
            ],
            'TxRepHelper::parseSignerKey T-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseSignerKey($v),
                'T' . $seed,
                'Invalid pre-auth tx signer key: T' . $marker,
                'TINVALID',
                'Invalid pre-auth tx signer key: TINVALID',
            ],
            'TxRepHelper::parseSignerKey X-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseSignerKey($v),
                'X' . $seed,
                'Invalid sha256-hash signer key: X' . $marker,
                'XINVALID',
                'Invalid sha256-hash signer key: XINVALID',
            ],
            'TxRepHelper::parseSignerKey unknown prefix + seed' => [
                fn (string $v) => TxRepHelper::parseSignerKey($v),
                'Y' . $seed,
                'Unknown signer key prefix: Y' . $marker,
                'YABC',
                'Unknown signer key prefix: YABC',
            ],
            'TxRepHelper::parseAccountId G-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseAccountId($v),
                'G' . $seed,
                'Invalid account ID: G' . $marker,
                'GINVALID',
                'Invalid account ID: GINVALID',
            ],
            'TxRepHelper::parseAsset issuer G-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseAsset('USD:' . $v),
                'G' . $seed,
                'Invalid asset issuer: G' . $marker,
                'GINVALID',
                'Invalid asset issuer: GINVALID',
            ],
            'TxRepHelper::parseChangeTrustAsset issuer G-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseChangeTrustAsset('USD:' . $v),
                'G' . $seed,
                'Invalid change trust asset issuer: G' . $marker,
                'GINVALID',
                'Invalid change trust asset issuer: GINVALID',
            ],
            'TxRepHelper::parseTrustlineAsset issuer G-prefixed seed' => [
                fn (string $v) => TxRepHelper::parseTrustlineAsset('USD:' . $v),
                'G' . $seed,
                'Invalid trustline asset issuer: G' . $marker,
                'GINVALID',
                'Invalid trustline asset issuer: GINVALID',
            ],
            'TxRepHelper::parseTrustlineAsset 64-char pool ID with seed' => [
                fn (string $v) => TxRepHelper::parseTrustlineAsset($v),
                'aaaaaaaa' . $seed,
                $poolPrefix . 'aaaaaaaa' . $marker,
                str_repeat('Z', 64),
                $poolPrefix . str_repeat('Z', 64),
            ],
            'XdrMuxedAccount::fromTxRep G-prefixed seed' => [
                fn (string $v) => XdrMuxedAccount::fromTxRep(['k' => $v], 'k'),
                'G' . $seed,
                'Invalid account ID: G' . $marker,
                'GINVALID',
                'Invalid account ID: GINVALID',
            ],
            'XdrMuxedAccount::fromTxRep X-prefixed seed' => [
                fn (string $v) => XdrMuxedAccount::fromTxRep(['k' => $v], 'k'),
                'X' . $seed,
                'Unrecognized muxed account address prefix "X": X' . $marker,
                'XINVALID',
                'Unrecognized muxed account address prefix "X": XINVALID',
            ],
            'XdrAccountID::fromTxRep G-prefixed seed' => [
                fn (string $v) => XdrAccountID::fromTxRep(['k' => $v], 'k'),
                'G' . $seed,
                'Invalid account ID in TxRep for key "k": G' . $marker,
                'GINVALID',
                'Invalid account ID in TxRep for key "k": GINVALID',
            ],
            'XdrTrustlineAsset::fromTxRep 64-char pool ID with seed' => [
                fn (string $v) => XdrTrustlineAsset::fromTxRep(['k' => $v], 'k'),
                'aaaaaaaa' . $seed,
                $poolPrefix . 'aaaaaaaa' . $marker,
                str_repeat('Z', 64),
                $poolPrefix . str_repeat('Z', 64),
            ],
            'XdrTrustlineAsset::fromTxRep issuer G-prefixed seed' => [
                fn (string $v) => XdrTrustlineAsset::fromTxRep(['k' => 'USD:' . $v], 'k'),
                'G' . $seed,
                'Invalid trustline asset issuer: G' . $marker,
                'GINVALID',
                'Invalid trustline asset issuer: GINVALID',
            ],
            'XdrChangeTrustAsset::fromTxRep issuer G-prefixed seed' => [
                fn (string $v) => XdrChangeTrustAsset::fromTxRep(['k' => 'USD:' . $v], 'k'),
                'G' . $seed,
                'Invalid change trust asset issuer: G' . $marker,
                'GINVALID',
                'Invalid change trust asset issuer: GINVALID',
            ],
            'XdrSCAddress::forAccountId X-prefixed seed' => [
                fn (string $v) => XdrSCAddress::forAccountId($v),
                'X' . $seed,
                'invalid account id: X' . $marker,
                'XABC',
                'invalid account id: XABC',
            ],
            'XdrSCAddress::getCanonicalContractIdHex embedded seed' => [
                fn (string $v) => XdrSCAddress::forContractId($v)->getCanonicalContractIdHex(),
                'x' . $seed,
                'Contract id must be a "C..." strkey or a hexadecimal string, "x' . $marker . '" given',
                'xyz',
                'Contract id must be a "C..." strkey or a hexadecimal string, "xyz" given',
            ],
            'XdrSCAddress::getCanonicalLiquidityPoolIdHex embedded seed' => [
                fn (string $v) => XdrSCAddress::forLiquidityPoolId($v)->getCanonicalLiquidityPoolIdHex(),
                'x' . $seed,
                'Liquidity pool id must be an "L..." strkey or a hexadecimal string, "x' . $marker . '" given',
                'xyz',
                'Liquidity pool id must be an "L..." strkey or a hexadecimal string, "xyz" given',
            ],
            'XdrClaimableBalanceID::getCanonicalHashHex embedded seed' => [
                fn (string $v) => XdrClaimableBalanceID::forClaimableBalanceId($v)->getCanonicalHashHex(),
                'x' . $seed,
                'Claimable balance id must be a "B..." strkey or a hexadecimal string, "x' . $marker . '" given',
                'xyz',
                'Claimable balance id must be a "B..." strkey or a hexadecimal string, "xyz" given',
            ],
            'MuxedAccount::fromAccountId X-prefixed seed' => [
                fn (string $v) => MuxedAccount::fromAccountId($v),
                'X' . $seed,
                'invalid accountId: X' . $marker,
                'XABC',
                'invalid accountId: XABC',
            ],
        ];

        foreach ($cases as $name => [$call, $secretInput, $expectedRedacted, $otherInput, $otherMessage]) {
            $error = $this->thrown($call, $secretInput, $name);
            $this->assertTrue($error instanceof InvalidArgumentException, $name . ': no InvalidArgumentException');
            $this->assertTrue(
                $error->getMessage() === $expectedRedacted,
                $name . ': the message does not redact the embedded seed'
            );
            $variants->assertNotShown($error, $name);

            $control = $this->thrown($call, $otherInput, $name . ' control');
            $this->assertInstanceOf(InvalidArgumentException::class, $control, $name . ' control');
            $this->assertSame($otherMessage, $control->getMessage(), $name . ' control');
        }

        // TxRepHelper::parseMuxedAccount wraps MuxedAccount::fromAccountId; verify the entire
        // exception chain redacts an embedded seed while preserving the control message.
        $muxError = $this->thrown(fn (string $v) => TxRepHelper::parseMuxedAccount($v), 'X' . $seed, 'parseMuxedAccount');
        $this->assertTrue($muxError instanceof InvalidArgumentException, 'parseMuxedAccount: no InvalidArgumentException');
        $this->assertTrue(
            $muxError->getMessage() === 'Invalid muxed account: X' . $marker,
            'parseMuxedAccount: the message does not redact the embedded seed'
        );
        $this->assertTrue(
            $muxError->getPrevious() instanceof InvalidArgumentException
                && $muxError->getPrevious()->getMessage() === 'invalid accountId: X' . $marker,
            'parseMuxedAccount: the previous exception does not redact the embedded seed'
        );
        $variants->assertNotShown($muxError, 'parseMuxedAccount');
        $muxControl = $this->thrown(fn (string $v) => TxRepHelper::parseMuxedAccount($v), 'XYZ', 'parseMuxedAccount control');
        $this->assertInstanceOf(InvalidArgumentException::class, $muxControl);
        $this->assertSame('Invalid muxed account: XYZ', $muxControl->getMessage());
        $this->assertInstanceOf(InvalidArgumentException::class, $muxControl->getPrevious());
        $this->assertSame('invalid accountId: XYZ', $muxControl->getPrevious()->getMessage());
    }

    /**
     * All 25 generated XDR enum classes with TxRep readers and their exact error message
     * when `fromTxRep()` is called with an empty map and plain prefix `tx.x`.
     *
     * @return array<string, array{class-string, string}>
     */
    public static function generatedEnumMissingTxRepClasses(): array
    {
        return [
            'XdrAssetType' => [XdrAssetType::class, 'Missing TxRep value for: tx.x'],
            'XdrClaimPredicateType' => [XdrClaimPredicateType::class, 'Missing TxRep value for: tx.x'],
            'XdrClaimableBalanceIDType' => [XdrClaimableBalanceIDType::class, 'Missing TxRep value for: tx.x'],
            'XdrClaimantType' => [XdrClaimantType::class, 'Missing TxRep value for: tx.x'],
            'XdrConfigSettingID' => [XdrConfigSettingID::class, 'Missing TxRep value for: tx.x'],
            'XdrContractDataDurability' => [XdrContractDataDurability::class, 'Missing TxRep value for: tx.x'],
            'XdrContractExecutableType' => [XdrContractExecutableType::class, 'Missing TxRep value for: tx.x'],
            'XdrContractIDPreimageType' => [XdrContractIDPreimageType::class, 'Missing TxRep value for: tx.x'],
            'XdrCryptoKeyType' => [XdrCryptoKeyType::class, 'Missing TxRep value for: tx.x'],
            'XdrEnvelopeType' => [XdrEnvelopeType::class, 'Missing TxRep value for: tx.x'],
            'XdrHostFunctionType' => [XdrHostFunctionType::class, 'Missing TxRep value for: tx.x'],
            'XdrLedgerEntryType' => [XdrLedgerEntryType::class, 'Missing TxRep value for: tx.x'],
            'XdrLiquidityPoolType' => [XdrLiquidityPoolType::class, 'Missing TxRep value for: tx.x'],
            'XdrMemoType' => [XdrMemoType::class, 'Missing TxRep value for: tx.x'],
            'XdrOperationType' => [XdrOperationType::class, 'Missing TxRep value for: tx.x'],
            'XdrPreconditionType' => [XdrPreconditionType::class, 'Missing TxRep value for: tx.x'],
            'XdrPublicKeyType' => [XdrPublicKeyType::class, 'Missing TxRep value for: tx.x'],
            'XdrRevokeSponsorshipType' => [XdrRevokeSponsorshipType::class, 'Missing TxRep value for: tx.x'],
            'XdrSCAddressType' => [XdrSCAddressType::class, 'Missing TxRep value for: tx.x'],
            'XdrSCErrorCode' => [XdrSCErrorCode::class, 'Missing TxRep value for: tx.x'],
            'XdrSCErrorType' => [XdrSCErrorType::class, 'Missing TxRep value for: tx.x'],
            'XdrSCValType' => [XdrSCValType::class, 'Missing TxRep value for: tx.x'],
            'XdrSignerKeyTypeBase' => [XdrSignerKeyTypeBase::class, 'Missing TxRep value for: tx.x'],
            'XdrSorobanAuthorizedFunctionType' => [XdrSorobanAuthorizedFunctionType::class, 'Missing TxRep value for: tx.x'],
            'XdrSorobanCredentialsType' => [XdrSorobanCredentialsType::class, 'Missing TxRep value for: tx.x'],
        ];
    }

    /**
     * @param class-string $enumClass
     */
    #[DataProvider('generatedEnumMissingTxRepClasses')]
    public function testGeneratedEnumFromTxRepMissingFieldRedactsEmbeddedSeed(
        string $enumClass,
        string $expectedPlainMessage
    ): void {
        $variants = SecretSeedVariants::create();
        $prefix = 'tx.' . $variants->seed . '.x';
        $error = $this->thrown(
            fn (string $v) => $enumClass::fromTxRep([], $v),
            $prefix,
            $enumClass . '::fromTxRep'
        );
        $this->assertTrue($error instanceof InvalidArgumentException, $enumClass . ': no InvalidArgumentException');
        $this->assertTrue(
            $error->getMessage() === 'Missing TxRep value for: tx.' . SecretSeedVariants::SECRET_SEED . '.x',
            $enumClass . ': the message does not redact the embedded seed'
        );
        $variants->assertNotShown($error, $enumClass . '::fromTxRep');

        $plainError = $this->thrown(
            fn (string $v) => $enumClass::fromTxRep([], $v),
            'tx.x',
            $enumClass . '::fromTxRep plain'
        );
        $this->assertInstanceOf(InvalidArgumentException::class, $plainError);
        $this->assertSame($expectedPlainMessage, $plainError->getMessage());
    }

    /**
     * Call sites that format an invalid caller key with SecretSeedRedaction::keyForMessage()
     * directly, tested with seeds embedded inside longer strings.
     *
     * @return array<string, array{Closure(string): mixed, Closure(string): string, string, string, string, string}>
     */
    public static function embeddedSeedKeyMessageSites(): array
    {
        $contractId = 'Contract id must be a "C..." strkey or a hexadecimal string, ';
        $address = '; Address takes an account (G...) or contract (C...) address';
        $muxedAddress = '; MuxedAddress takes an account (G...), muxed account (M...), contract (C...) or muxed '
            . 'contract (W...) address';
        $noNode = '" matched no node in this authorization entry';
        $startingWithS = SecretSeedVariants::VALUE_STARTING_WITH_S;
        $payload = str_repeat("\x07", 32);

        return [
            'Address::fromStrKey' => [
                fn (string $v) => Address::fromStrKey($v),
                fn (string $text) => 'Not a valid address strkey: ' . $text,
                'Not a valid address strkey: ' . $startingWithS,
                'Not a valid address strkey: ' . $startingWithS,
                'prefix XABC suffix',
                'Not a valid address strkey: prefix XABC suffix',
            ],
            'ContractSpec Address parameter' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal($v, XdrSCSpecTypeDef::ADDRESS()),
                fn (string $text) => 'Invalid address format: ' . $text . $address,
                'Invalid address format: ' . $startingWithS . $address,
                'Invalid address format: ' . $startingWithS . $address,
                'prefix XYZ suffix',
                'Invalid address format: prefix XYZ suffix' . $address,
            ],
            'ContractSpec MuxedAddress parameter' => [
                fn (string $v) => (new ContractSpec([]))->nativeToXdrSCVal($v, XdrSCSpecTypeDef::MUXED_ADDRESS()),
                fn (string $text) => 'Invalid address format: ' . $text . $muxedAddress,
                'Invalid address format: ' . $startingWithS . $muxedAddress,
                'Invalid address format: ' . $startingWithS . $muxedAddress,
                'prefix XYZ suffix',
                'Invalid address format: prefix XYZ suffix' . $muxedAddress,
            ],
            'SignedPayloadSigner::fromAccountId' => [
                fn (string $v) => SignedPayloadSigner::fromAccountId($v, $payload),
                fn (string $text) => 'invalid ed25519 account id: ' . $text,
                'a signed payload signer takes an ed25519 account id (G...), not a secret seed (S...)',
                'invalid ed25519 account id: ' . $startingWithS,
                'prefix XYZ suffix',
                'invalid ed25519 account id: prefix XYZ suffix',
            ],
            'MuxedContract constructor' => [
                fn (string $v) => new MuxedContract($v, 1),
                fn (string $text) => 'Invalid contract id "' . $text . '": ' . $contractId . '"' . $text . '" given',
                'Invalid contract id "' . $startingWithS . '": ' . $contractId . $startingWithS . ' given',
                'Invalid contract id "' . $startingWithS . '": ' . $contractId . $startingWithS . ' given',
                'prefix XYZ suffix',
                'Invalid contract id "prefix XYZ suffix": ' . $contractId . '"prefix XYZ suffix" given',
            ],
            'MuxedContract::fromAddress' => [
                fn (string $v) => MuxedContract::fromAddress($v),
                fn (string $text) => 'Expected a contract (C...) or muxed contract (W...) address, got: ' . $text,
                'Expected a contract (C...) or muxed contract (W...) address, got: ' . $startingWithS,
                'Expected a contract (C...) or muxed contract (W...) address, got: ' . $startingWithS,
                'prefix XYZ suffix',
                'Expected a contract (C...) or muxed contract (W...) address, got: prefix XYZ suffix',
            ],
            'SorobanAuthorizationEntry::sign forAddress' => [
                fn (string $v) => self::addressV2Entry()->sign(KeyPair::random(), Network::testnet(), null, $v),
                fn (string $text) => 'forAddress "' . $text . $noNode,
                'forAddress "' . $startingWithS . $noNode,
                'forAddress "' . $startingWithS . $noNode,
                'prefix GABC suffix',
                'forAddress "prefix GABC suffix' . $noNode,
            ],
            'SorobanAuthorizationEntry::withDelegates' => [
                fn (string $v) => SorobanAuthorizationEntry::withDelegates(
                    self::legacyEntry(),
                    100,
                    [new SorobanDelegateDescriptor($v)]
                ),
                fn (string $text) => 'Delegate address must be a G- or C-prefixed strkey; got: ' . $text,
                'Delegate address must be a G- or C-prefixed strkey; got: ' . $startingWithS,
                'Delegate address must be a G- or C-prefixed strkey; got: ' . $startingWithS,
                'prefix XYZ suffix',
                'Delegate address must be a G- or C-prefixed strkey; got: prefix XYZ suffix',
            ],
        ];
    }

    #[DataProvider('embeddedSeedKeyMessageSites')]
    public function testKeyMessageSitesRedactEmbeddedSeeds(
        Closure $call,
        Closure $message,
        string $leadingUpperMessage,
        string $leadingLowerMessage,
        string $otherInput,
        string $otherMessage
    ): void {
        // Every assertion on a secret-bearing input uses a boolean and a fixed failure message
        // so a failing run never prints a secret seed.
        $variants = SecretSeedVariants::create();
        $seed = $variants->seed;
        $cases = [
            'embedded' => [
                'prefix ' . $seed . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'lowercase embedded' => [
                'prefix ' . strtolower($seed) . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
            'glued' => [
                'ab' . $seed . 'cd',
                'aba secret seed (S...)cd',
            ],
            'lowercase glued' => [
                'x' . strtolower($seed),
                'xa secret seed (S...)',
            ],
            'truncated run' => [
                'prefix ' . substr($seed, 0, 45) . ' suffix',
                'prefix a secret seed (S...) suffix',
            ],
        ];

        foreach ($cases as $name => [$input, $redacted]) {
            $error = $this->thrown($call, $input, $name);
            $this->assertTrue($error instanceof InvalidArgumentException, $name . ': no InvalidArgumentException');
            for ($wrapped = $error; $wrapped !== null; $wrapped = $wrapped->getPrevious()) {
                $msg = $wrapped->getMessage();
                $this->assertFalse(
                    str_contains($msg, $seed),
                    $name . ': exception message contains the secret seed'
                );
                $this->assertFalse(
                    stripos($msg, $seed) !== false,
                    $name . ': exception message contains the secret seed in another letter case'
                );
            }
            $variants->assertNotShown($error, $name);
            $this->assertTrue(
                $error->getMessage() === $message($redacted),
                $name . ': exception message does not match the redacted expectation'
            );
        }

        $leadingCases = [
            'leading uppercase S' => [$seed . ' tail', $leadingUpperMessage],
            'leading lowercase s' => [strtolower($seed) . ' tail', $leadingLowerMessage],
        ];
        foreach ($leadingCases as $name => [$input, $expected]) {
            $error = $this->thrown($call, $input, $name);
            $this->assertTrue($error instanceof InvalidArgumentException, $name . ': no InvalidArgumentException');
            for ($wrapped = $error; $wrapped !== null; $wrapped = $wrapped->getPrevious()) {
                $msg = $wrapped->getMessage();
                $this->assertFalse(
                    stripos($msg, $seed) !== false,
                    $name . ': exception message contains the secret seed'
                );
            }
            $variants->assertNotShown($error, $name);
            $this->assertTrue(
                $error->getMessage() === $expected,
                $name . ': exception message does not match the starting-with-S expectation'
            );
        }

        $control = $this->thrown($call, $otherInput, 'other input');
        $this->assertInstanceOf(InvalidArgumentException::class, $control);
        $this->assertSame($otherMessage, $control->getMessage());
    }

    /**
     * Runs $call with $input and returns what it throws.
     */
    private function thrown(Closure $call, string $input, string $context): Throwable
    {
        try {
            $call($input);
        } catch (Throwable $error) {
            return $error;
        }
        $this->fail($context . ': no exception');
    }
}
