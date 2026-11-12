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
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
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

    #[DataProvider('otherPositionSites')]
    #[DataProvider('jsonHelperSites')]
    #[DataProvider('txRepFieldKeySites')]
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

    public function testDuplicateMapKeyTooDeepForJsonIsNotShown(): void
    {
        // Each level of the key adds frames to the JSON conversion; Xdebug, when loaded, limits
        // the call depth, so its limit is raised for this key and restored afterwards.
        $previousLimit = ini_get('xdebug.max_nesting_level');
        ini_set('xdebug.max_nesting_level', '10000');
        try {
            $key = XdrSCVal::forString(SecretSeedVariants::create()->seed);
            for ($level = 0; $level < 300; $level++) {
                $key = XdrSCVal::forVec([$key]);
            }
            $error = $this->thrown(fn (string $v) => XdrSCVal::forMap([
                new XdrSCMapEntry($key, XdrSCVal::forVoid()),
                new XdrSCMapEntry($key, XdrSCVal::forTrue()),
            ]), '', 'deep key');
        } finally {
            if ($previousLimit !== false) {
                ini_set('xdebug.max_nesting_level', $previousLimit);
            }
        }
        $this->assertInstanceOf(InvalidArgumentException::class, $error);
        $this->assertSame('Duplicate SCMap key (not shown, it may hold a secret seed)', $error->getMessage());
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
            'symbol' => [fn (string $c) => XdrSCVal::forSymbol($c)],
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
