<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypePoolShare;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SACClient;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrAccountEntry;
use Soneso\StellarSDK\Xdr\XdrAccountEntryExt;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;

/**
 * Unit tests for SACClient. All RPC requests are served by a Guzzle MockHandler, and the
 * request history decodes what was sent.
 */
class SACClientTest extends TestCase
{
    // Unroutable on purpose: all HTTP is served by the injected mock handler.
    private const TEST_RPC_URL = 'http://localhost:1';
    private const NATIVE_TESTNET_ID = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
    private const TESTASSET_ISSUER = 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX';
    private const TESTASSET_TESTNET_ID = 'CBEFNXB7GMEO2PKBCOSKRUQBDWAH4PVA2RIREKPV335KM7MBK2HRLRGB';
    private const USDC_ISSUER = 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
    private const USDC_PUBLIC_ID = 'CCW67TSZV3SSS2HXMBQ5JFGCKJNXKZM7UQUWUZPUTHXSTZLEO7SJMI75';
    private const MUXED_ACCOUNT = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ';
    private const MUXED_ACCOUNT_BASE = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
    private const MUXED_CONTRACT = 'WA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAAAAAAAAAPCIA6IG';
    private const MUXED_CONTRACT_BASE = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const MAX_I128 = '170141183460469231731687303715884105727';
    private const TX_HASH = 'a4721e2a61e9a6b3f97c6b06427a2f8aacbdcbdbf30bbf52a4ce2c8fcbd2fc10';

    private const FUNCTION_NAMES = [
        'admin', 'allowance', 'approve', 'authorized', 'balance', 'burn', 'burn_from', 'clawback',
        'decimals', 'mint', 'name', 'set_admin', 'set_authorized', 'symbol', 'transfer',
        'transfer_from', 'trust',
    ];

    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    // ---------------------------------------------------------------------
    // factories and accessors
    // ---------------------------------------------------------------------

    public function testFactoriesSendNoRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $server = $this->mockedServer($mock, $history);
        $testnet = Network::testnet();
        $clients = [
            SACClient::forAsset(Asset::native(), KeyPair::random(), $testnet, self::TEST_RPC_URL, null, $server),
            SACClient::forContractId(self::NATIVE_TESTNET_ID, KeyPair::random(), $testnet, self::TEST_RPC_URL,
                null, $server),
            SACClient::readOnlyForAsset(Asset::native(), $testnet, self::TEST_RPC_URL, null, $server),
            SACClient::readOnlyForContractId(self::NATIVE_TESTNET_ID, $testnet, self::TEST_RPC_URL, null, $server),
        ];
        foreach ($clients as $client) {
            $names = $client->getSorobanClient()->getMethodNames();
            sort($names, SORT_STRING);
            $this->assertSame(self::FUNCTION_NAMES, $names);
            $this->assertSame(self::NATIVE_TESTNET_ID, $client->getContractId());
            $this->assertSame(self::NATIVE_TESTNET_ID, $client->getSorobanClient()->getContractId());
            $this->assertSame($server, $client->getSorobanClient()->getOptions()->server);
            $this->assertCount(30, $client->getSorobanClient()->getSpecEntries());
        }
        $this->assertSame([], $history);
        $this->assertNull($mock->getLastRequest());
    }

    public function testAssetFactoriesUseTheAssetContractId(): void
    {
        $testAsset = Asset::createNonNativeAsset('TESTASSET', self::TESTASSET_ISSUER);
        $usdc = Asset::createNonNativeAsset('USDC', self::USDC_ISSUER);

        $this->assertSame(self::TESTASSET_TESTNET_ID,
            SACClient::forAsset($testAsset, KeyPair::random(), Network::testnet(), self::TEST_RPC_URL)->getContractId());
        $this->assertSame(self::TESTASSET_TESTNET_ID,
            SACClient::readOnlyForAsset($testAsset, Network::testnet(), self::TEST_RPC_URL)->getContractId());
        $this->assertSame(self::USDC_PUBLIC_ID,
            SACClient::forAsset($usdc, KeyPair::random(), Network::public(), self::TEST_RPC_URL)->getContractId());
        $this->assertSame(self::USDC_PUBLIC_ID,
            SACClient::readOnlyForAsset($usdc, Network::public(), self::TEST_RPC_URL)->getContractId());
    }

    public function testAssetFactoriesRejectPoolShare(): void
    {
        $poolShare = new AssetTypePoolShare(
            Asset::createNonNativeAsset('USDC', 'GBUACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6CLH'),
            Asset::createNonNativeAsset('USDC', 'GB2ACAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB7BZ4'));
        $message = 'A liquidity pool share has no Stellar Asset Contract; '
            . 'getContractId() takes the native asset or an issued asset';
        $attempts = [
            'forAsset' => fn () => SACClient::forAsset($poolShare, KeyPair::random(), Network::testnet(),
                self::TEST_RPC_URL),
            'readOnlyForAsset' => fn () => SACClient::readOnlyForAsset($poolShare, Network::testnet(),
                self::TEST_RPC_URL),
        ];
        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("$label accepted a pool share");
            } catch (RuntimeException $e) {
                $this->assertSame($message, $e->getMessage(), $label);
            }
        }
    }

    public function testContractIdFactoriesTurnHexIntoStrKey(): void
    {
        $hex = StrKey::decodeContractIdHex(self::NATIVE_TESTNET_ID);
        $this->assertSame(64, strlen($hex));

        $this->assertSame(self::NATIVE_TESTNET_ID,
            SACClient::forContractId($hex, KeyPair::random(), Network::testnet(), self::TEST_RPC_URL)->getContractId());
        $this->assertSame(self::NATIVE_TESTNET_ID,
            SACClient::readOnlyForContractId($hex, Network::testnet(), self::TEST_RPC_URL)->getContractId());
    }

    public function testContractIdFactoriesRejectMuxedContractId(): void
    {
        $message = 'The contract to invoke must be a contract (C...) address, got a muxed contract (W...) address: '
            . self::MUXED_CONTRACT . '; use its base contract (C...) address';
        $attempts = [
            'forContractId' => fn () => SACClient::forContractId(self::MUXED_CONTRACT, KeyPair::random(),
                Network::testnet(), self::TEST_RPC_URL),
            'readOnlyForContractId' => fn () => SACClient::readOnlyForContractId(self::MUXED_CONTRACT,
                Network::testnet(), self::TEST_RPC_URL),
        ];
        foreach ($attempts as $label => $attempt) {
            try {
                $attempt();
                $this->fail("$label accepted a muxed contract id");
            } catch (InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage(), $label);
            }
        }
    }

    public function testReadOnlyFactoriesUseThePlaceholderSourceWithoutKey(): void
    {
        $this->assertSame('GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF', SACClient::READ_ONLY_SOURCE_ACCOUNT_ID);
        $this->assertSame(str_repeat("\x00", 32), StrKey::decodeAccountId(SACClient::READ_ONLY_SOURCE_ACCOUNT_ID));
        $clients = [
            SACClient::readOnlyForAsset(Asset::native(), Network::testnet(), self::TEST_RPC_URL),
            SACClient::readOnlyForContractId(self::NATIVE_TESTNET_ID, Network::testnet(), self::TEST_RPC_URL),
        ];
        foreach ($clients as $client) {
            $keyPair = $client->getSorobanClient()->getOptions()->sourceAccountKeyPair;
            $this->assertSame(SACClient::READ_ONLY_SOURCE_ACCOUNT_ID, $keyPair->getAccountId());
            $this->assertNull($keyPair->getPrivateKey());
        }
    }

    public function testKeyedFactoriesKeepTheGivenKeyPairAndOptions(): void
    {
        $keyPair = KeyPair::random();
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL);
        $options = $client->getSorobanClient()->getOptions();
        $this->assertSame($keyPair, $options->sourceAccountKeyPair);
        $this->assertSame(self::TEST_RPC_URL, $options->rpcUrl);
        $this->assertSame(Network::testnet()->getNetworkPassphrase(), $options->network->getNetworkPassphrase());
    }

    // ---------------------------------------------------------------------
    // balance
    // ---------------------------------------------------------------------

    public function testReadOnlyBalanceOfAccountHolderSendsOnlySimulate(): void
    {
        $holder = KeyPair::random()->getAccountId();
        $mock = new MockHandler([$this->simulateResponse(XdrSCVal::forI128BigInt(gmp_pow(2, 100)))]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        $balance = $client->balance(Address::fromAccountId($holder));

        $this->assertTrue(gmp_cmp($balance, gmp_pow(2, 100)) === 0);
        $this->assertSame(['simulateTransaction'], $this->requestMethods($history));
        $this->assertSame(0, $mock->count());
        $invocation = $this->simulatedInvocation($history);
        $this->assertSame(self::NATIVE_TESTNET_ID, $invocation->contractId);
        $this->assertSame('balance', $invocation->functionName);
        $this->assertCount(1, $invocation->arguments);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $invocation->arguments[0]->address->type->value);
        $this->assertSame($holder, $invocation->arguments[0]->address->toStrKey());
    }

    public function testKeyedBalanceOfContractHolderLoadsAccountFirst(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forI128BigInt(0)),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $balance = $client->balance(Address::fromContractId(self::MUXED_CONTRACT_BASE));

        $this->assertTrue(gmp_cmp($balance, 0) === 0);
        $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->requestMethods($history));
        $invocation = $this->simulatedInvocation($history);
        $this->assertSame(self::NATIVE_TESTNET_ID, $invocation->contractId);
        $this->assertSame('balance', $invocation->functionName);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $invocation->arguments[0]->address->type->value);
        $this->assertSame(self::MUXED_CONTRACT_BASE, $invocation->arguments[0]->address->toStrKey());
    }

    public function testBalanceRejectsMuxedAccountHolderBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        try {
            $client->balance(Address::fromMuxedAccountId(self::MUXED_ACCOUNT));
            $this->fail('Accepted a muxed account holder');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('The balance holder must be an account (G...) or contract (C...) address, '
                . 'got a muxed account (M...) address: ' . self::MUXED_ACCOUNT
                . '; query its base account ' . self::MUXED_ACCOUNT_BASE, $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testBalanceRejectsMuxedContractHolderBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        try {
            $client->balance(Address::fromMuxedContractId(self::MUXED_CONTRACT));
            $this->fail('Accepted a muxed contract holder');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('The balance holder must be an account (G...) or contract (C...) address, '
                . 'got a muxed contract (W...) address: ' . self::MUXED_CONTRACT
                . '; query its base contract ' . self::MUXED_CONTRACT_BASE, $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testBalanceRejectsClaimableBalanceHolderBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);
        $holder = Address::fromClaimableBalanceId('00' . str_repeat('ab', 32));

        try {
            $client->balance($holder);
            $this->fail('Accepted a claimable balance holder');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Address takes an account (G...) or contract (C...) address, got '
                . $holder->toStrKey()
                . ': a claimable balance address is produced by the host and is not a contract input',
                $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testBalanceSimulationErrorPropagatesInsteadOfZero(): void
    {
        $mock = new MockHandler([$this->simulateErrorResponse('HostError: Error(Contract, #6)')]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        try {
            $client->balance(Address::fromAccountId(KeyPair::random()->getAccountId()));
            $this->fail('Returned a balance for a missing account');
        } catch (Exception $e) {
            $this->assertSame(Exception::class, get_class($e));
            $this->assertSame('Transaction simulation failed: HostError: Error(Contract, #6)', $e->getMessage());
        }
        $this->assertSame(['simulateTransaction'], $this->requestMethods($history));
    }

    public function testReadWithRestorePreambleThrowsTheRestoreMessage(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forI128BigInt(5), false, true),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        try {
            $client->balance(Address::fromAccountId(KeyPair::random()->getAccountId()));
            $this->fail('Read past a restore preamble');
        } catch (RuntimeException $e) {
            $this->assertSame('The Stellar Asset Contract ' . self::NATIVE_TESTNET_ID . ' cannot run balance: '
                . "ledger entries it needs are archived; restore them from a client opened with a funded account's keypair",
                $e->getMessage());
        }
        $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->requestMethods($history));
        $this->assertSame(0, $mock->count());
    }

    public function testReadOnlyReadWithRestorePreambleThrowsTheRestoreMessage(): void
    {
        $mock = new MockHandler([$this->simulateResponse(XdrSCVal::forU32(7), false, true)]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'The Stellar Asset Contract ' . self::NATIVE_TESTNET_ID . ' cannot run decimals: '
            . "ledger entries it needs are archived; restore them from a client opened with a funded account's keypair", '/') . '\z/');
        $client->decimals();
    }

    public function testReadWithRestorePreambleAndErrorThrowsTheSimulationError(): void
    {
        $mock = new MockHandler([$this->simulateErrorResponse('HostError: Error(Contract, #13)', true)]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'Transaction simulation failed: HostError: Error(Contract, #13)', '/') . '\z/');
        $client->balance(Address::fromAccountId(KeyPair::random()->getAccountId()));
    }

    // ---------------------------------------------------------------------
    // decimals, name, symbol
    // ---------------------------------------------------------------------

    public function testDecimalsNameAndSymbol(): void
    {
        $mock = new MockHandler([
            $this->simulateResponse(XdrSCVal::forU32(7)),
            $this->simulateResponse(XdrSCVal::forString('TESTASSET:' . self::TESTASSET_ISSUER)),
            $this->simulateResponse(XdrSCVal::forString('TESTASSET')),
        ]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        $this->assertSame(7, $client->decimals());
        $this->assertSame('TESTASSET:' . self::TESTASSET_ISSUER, $client->name());
        $this->assertSame('TESTASSET', $client->symbol());
        $this->assertSame(['simulateTransaction', 'simulateTransaction', 'simulateTransaction'],
            $this->requestMethods($history));
        foreach (['decimals', 'name', 'symbol'] as $index => $function) {
            $invocation = $this->simulatedInvocation($history, $index);
            $this->assertSame(self::NATIVE_TESTNET_ID, $invocation->contractId, $function);
            $this->assertSame($function, $invocation->functionName);
            $this->assertSame([], $invocation->arguments, $function);
        }
    }

    public function testWrongResultArmThrows(): void
    {
        $prefix = 'The Stellar Asset Contract ' . self::NATIVE_TESTNET_ID . ' returned ';
        $cases = [
            'balance' => [XdrSCVal::forU32(1), $prefix . 'SCV_U32 from balance, expected SCV_I128'],
            'decimals' => [XdrSCVal::forString('7'), $prefix . 'SCV_STRING from decimals, expected SCV_U32'],
            'name' => [XdrSCVal::forU32(1), $prefix . 'SCV_U32 from name, expected SCV_STRING'],
            'symbol' => [XdrSCVal::forVoid(), $prefix . 'SCV_VOID from symbol, expected SCV_STRING'],
        ];
        foreach ($cases as $function => [$value, $message]) {
            $mock = new MockHandler([$this->simulateResponse($value)]);
            $history = [];
            $client = $this->readOnlyClient($mock, $history);
            try {
                if ($function === 'balance') {
                    $client->balance(Address::fromAccountId(KeyPair::random()->getAccountId()));
                } else {
                    $client->$function();
                }
                $this->fail("$function accepted a wrong result arm");
            } catch (RuntimeException $e) {
                $this->assertSame($message, $e->getMessage(), $function);
            }
        }
    }

    // ---------------------------------------------------------------------
    // transfer
    // ---------------------------------------------------------------------

    public function testTransferToEveryDestinationKind(): void
    {
        $keyPair = KeyPair::random();
        $from = Address::fromAccountId($keyPair->getAccountId());
        $destinations = [
            XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT => Address::fromMuxedAccountId(self::MUXED_ACCOUNT),
            XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT => Address::fromMuxedContractId(self::MUXED_CONTRACT),
            XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT => Address::fromContractId(self::MUXED_CONTRACT_BASE),
            XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT => Address::fromAccountId(self::MUXED_ACCOUNT_BASE),
        ];
        foreach ($destinations as $type => $to) {
            $mock = new MockHandler([
                $this->accountResponse($keyPair->getAccountId()),
                $this->simulateResponse(XdrSCVal::forVoid(), true),
            ]);
            $history = [];
            $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
                $this->mockedServer($mock, $history));

            $tx = $client->transfer($from, $to, 250000000);

            $this->assertNull($tx->signed);
            $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->requestMethods($history));
            $invocation = $this->simulatedInvocation($history);
            $this->assertSame(self::NATIVE_TESTNET_ID, $invocation->contractId);
            $this->assertSame('transfer', $invocation->functionName);
            $this->assertCount(3, $invocation->arguments);
            $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT,
                $invocation->arguments[0]->address->type->value);
            $this->assertSame($keyPair->getAccountId(), $invocation->arguments[0]->address->toStrKey());
            $this->assertSame($type, $invocation->arguments[1]->address->type->value);
            $this->assertSame($to->toStrKey(), $invocation->arguments[1]->address->toStrKey());
            $this->assertSame(XdrSCValType::SCV_I128, $invocation->arguments[2]->type->value);
            $this->assertTrue(gmp_cmp($invocation->arguments[2]->toBigInt(), 250000000) === 0);
        }
    }

    public function testTransferAmountFormsGiveTheSameInvocation(): void
    {
        $keyPair = KeyPair::random();
        $from = Address::fromAccountId($keyPair->getAccountId());
        $to = Address::fromContractId(self::MUXED_CONTRACT_BASE);
        $encoded = [];
        foreach ([123456789, '123456789', gmp_init(123456789)] as $amount) {
            $mock = new MockHandler([
                $this->accountResponse($keyPair->getAccountId()),
                $this->simulateResponse(XdrSCVal::forVoid(), true),
            ]);
            $history = [];
            $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
                $this->mockedServer($mock, $history));
            $tx = $client->transfer($from, $to, $amount);
            $this->assertNull($tx->signed);
            $this->assertNotContains('sendTransaction', $this->requestMethods($history));
            $encoded[] = $this->simulatedInvocation($history)->toXdr()->encode();
        }
        $this->assertSame($encoded[0], $encoded[1]);
        $this->assertSame($encoded[0], $encoded[2]);
    }

    public function testTransferAcceptsTheAmountBounds(): void
    {
        $keyPair = KeyPair::random();
        foreach (['0' => 0, self::MAX_I128 => self::MAX_I128] as $expected => $amount) {
            $mock = new MockHandler([
                $this->accountResponse($keyPair->getAccountId()),
                $this->simulateResponse(XdrSCVal::forVoid(), true),
            ]);
            $history = [];
            $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
                $this->mockedServer($mock, $history));
            $client->transfer(Address::fromAccountId($keyPair->getAccountId()),
                Address::fromAccountId(self::MUXED_ACCOUNT_BASE), $amount);
            $value = $this->simulatedInvocation($history)->arguments[2]->toBigInt();
            $this->assertTrue(gmp_cmp($value, gmp_init((string)$expected, 10)) === 0);
        }
    }

    public function testTransferRejectsMuxedFromBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), KeyPair::random(), Network::testnet(), self::TEST_RPC_URL,
            null, $this->mockedServer($mock, $history));

        try {
            $client->transfer(Address::fromMuxedAccountId(self::MUXED_ACCOUNT),
                Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);
            $this->fail('Accepted a muxed from');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Address takes an account (G...) or contract (C...) address, got '
                . self::MUXED_ACCOUNT . ': a muxed address needs a MuxedAddress parameter', $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testTransferFromContractAddress(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $tx = $client->transfer(Address::fromContractId(self::MUXED_CONTRACT_BASE),
            Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 5);

        $this->assertNull($tx->signed);
        $invocation = $this->simulatedInvocation($history);
        $this->assertSame(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $invocation->arguments[0]->address->type->value);
        $this->assertSame(self::MUXED_CONTRACT_BASE, $invocation->arguments[0]->address->toStrKey());
        $this->assertSame(['getLedgerEntries', 'simulateTransaction'], $this->requestMethods($history));
    }

    public function testTransferRejectsMuxedContractFromBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), KeyPair::random(), Network::testnet(), self::TEST_RPC_URL,
            null, $this->mockedServer($mock, $history));

        try {
            $client->transfer(Address::fromMuxedContractId(self::MUXED_CONTRACT),
                Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);
            $this->fail('Accepted a muxed contract from');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Address takes an account (G...) or contract (C...) address, got '
                . self::MUXED_CONTRACT . ': a muxed address needs a MuxedAddress parameter', $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testTransferRejectsClaimableBalanceDestinationBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $keyPair = KeyPair::random();
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL,
            null, $this->mockedServer($mock, $history));
        $to = Address::fromClaimableBalanceId('00' . str_repeat('ab', 32));

        try {
            $client->transfer(Address::fromAccountId($keyPair->getAccountId()), $to, 1);
            $this->fail('Accepted a claimable balance destination');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('MuxedAddress takes an account (G...), muxed account (M...), contract (C...) or '
                . 'muxed contract (W...) address, got ' . $to->toStrKey()
                . ': a claimable balance address is produced by the host and is not a contract input',
                $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testTransferPassesTheMethodOptions(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([$this->accountResponse($keyPair->getAccountId())]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $tx = $client->transfer(Address::fromAccountId($keyPair->getAccountId()),
            Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1, new MethodOptions(simulate: false));

        $this->assertNull($tx->simulationResponse);
        $this->assertNull($tx->tx);
        $this->assertSame(['getLedgerEntries'], $this->requestMethods($history));
        $this->assertSame(0, $mock->count());
    }

    public function testTransferAndSendPassesTheMethodOptions(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(GetTransactionResponse::STATUS_SUCCESS),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $client->transferAndSend(Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1,
            new MethodOptions(fee: 12345));

        $this->assertSame(['getLedgerEntries', 'simulateTransaction', 'sendTransaction', 'getTransaction'],
            $this->requestMethods($history));
        // The operation fee from the options plus the simulated resource fee of 100.
        $this->assertSame(12445, $this->sentTransaction($history, 2)->getFee());
    }

    public function testTransferRejectsInvalidAmountsBeforeAnyRequest(): void
    {
        $rangePrefix = 'The transfer amount must be from 0 to ' . self::MAX_I128 . ' base units, got ';
        $wholeMessage = fn (string $preview) => "The transfer amount must be a whole number of base units, got '"
            . $preview . "'; convert a decimal amount with SACClient::toBaseUnits()";
        $cases = [
            [-1, $rangePrefix . '-1'],
            [gmp_init(-1), $rangePrefix . '-1'],
            [gmp_pow(2, 127), $rangePrefix . '170141183460469231731687303715884105728'],
            ['170141183460469231731687303715884105728', $rangePrefix . '170141183460469231731687303715884105728'],
            ['1.5', $wholeMessage('1.5')],
            ['-5', $wholeMessage('-5')],
            ['', $wholeMessage('')],
            [' 1', $wholeMessage(' 1')],
            ["1\n", $wholeMessage('1\x0A')],
            ['1e7', $wholeMessage('1e7')],
        ];
        $keyPair = KeyPair::random();
        foreach ($cases as [$amount, $message]) {
            $mock = new MockHandler([]);
            $history = [];
            $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
                $this->mockedServer($mock, $history));
            try {
                $client->transfer(Address::fromAccountId($keyPair->getAccountId()),
                    Address::fromAccountId(self::MUXED_ACCOUNT_BASE), $amount);
                $this->fail('Accepted ' . $message);
            } catch (InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
            $this->assertSame([], $history);
        }
    }

    public function testTransferNamesASecretSeedAmountByKindOnly(): void
    {
        $seed = KeyPair::random()->getSecretSeed();
        $this->assertNotNull($seed);
        $mock = new MockHandler([]);
        $history = [];
        $keyPair = KeyPair::random();
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        try {
            $client->transfer(Address::fromAccountId($keyPair->getAccountId()),
                Address::fromAccountId(self::MUXED_ACCOUNT_BASE), $seed);
            $this->fail('Accepted a secret seed as amount');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('The transfer amount must be a whole number of base units, '
                . 'got a secret seed (S...) instead of an amount', $e->getMessage());
            $this->assertStringNotContainsString($seed, $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    public function testReadOnlyTransferWithRestorePreambleThrowsTheRefusal(): void
    {
        $mock = new MockHandler([$this->simulateResponse(XdrSCVal::forVoid(), true, true)]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        try {
            $client->transfer(Address::fromAccountId(KeyPair::random()->getAccountId()),
                Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);
            $this->fail('Restored state from a read-only client');
        } catch (Exception $e) {
            $this->assertSame($this->readOnlyRefusal(), $e->getMessage());
        }
        $this->assertSame(['simulateTransaction'], $this->requestMethods($history));
    }

    public function testReadOnlyTransferSignAndSendThrowsTheRefusal(): void
    {
        $mock = new MockHandler([$this->simulateResponse(XdrSCVal::forVoid(), true)]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);
        $tx = $client->transfer(Address::fromAccountId(KeyPair::random()->getAccountId()),
            Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);

        try {
            $tx->signAndSend();
            $this->fail('Sent a transaction from a read-only client');
        } catch (Exception $e) {
            $this->assertSame($this->readOnlyRefusal(), $e->getMessage());
        }
        $this->assertNull($tx->signed);
        $this->assertSame(['simulateTransaction'], $this->requestMethods($history));
    }

    // ---------------------------------------------------------------------
    // transferAndSend
    // ---------------------------------------------------------------------

    public function testTransferAndSendUsesTheSourceAsFrom(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(GetTransactionResponse::STATUS_SUCCESS),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $response = $client->transferAndSend(Address::fromMuxedAccountId(self::MUXED_ACCOUNT), '1000000000');

        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $response->getStatus());
        $this->assertSame(['getLedgerEntries', 'simulateTransaction', 'sendTransaction', 'getTransaction'],
            $this->requestMethods($history));
        $this->assertSame(0, $mock->count());
        $sent = $this->sentTransaction($history, 2);
        $this->assertSame($keyPair->getAccountId(), $sent->getSourceAccount()->getAccountId());
        $this->assertCount(1, $sent->getSignatures());
        $operation = $sent->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $operation);
        $invocation = $operation->getFunction();
        $this->assertInstanceOf(InvokeContractHostFunction::class, $invocation);
        $this->assertSame('transfer', $invocation->functionName);
        $this->assertSame($keyPair->getAccountId(), $invocation->arguments[0]->address->toStrKey());
        $this->assertSame(self::MUXED_ACCOUNT, $invocation->arguments[1]->address->toStrKey());
        $this->assertTrue(gmp_cmp($invocation->arguments[2]->toBigInt(), 1000000000) === 0);
    }

    public function testTransferAndSendThrowsOnFailedStatus(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
            $this->sendTransactionResponse(),
            $this->getTransactionRpcResponse(GetTransactionResponse::STATUS_FAILED, 'AAAAAAAAAGT/////AAAAAA=='),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'invoke transfer failed with result: AAAAAAAAAGT/////AAAAAA==', '/') . '\z/');
        $client->transferAndSend(Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);
    }

    public function testTransferAndSendThrowsWhenPollingReportsAnRpcError(): void
    {
        $keyPair = KeyPair::random();
        $mock = new MockHandler([
            $this->accountResponse($keyPair->getAccountId()),
            $this->simulateResponse(XdrSCVal::forVoid(), true),
            $this->sendTransactionResponse(),
            new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1,
                'error' => ['code' => -32600, 'message' => 'invalid request']])),
        ]);
        $history = [];
        $client = SACClient::forAsset(Asset::native(), $keyPair, Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));

        try {
            $client->transferAndSend(Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1,
                new MethodOptions(timeoutInSeconds: 0));
            $this->fail('Returned although polling reported an RPC error');
        } catch (Exception $e) {
            $this->assertSame('Interrupted after waiting 0 seconds (options->methodOptions->timeoutInSeconds) '
                . 'for the transaction ' . self::TX_HASH . ' to complete. '
                . 'Last status request error: RPC error code: -32600, message: invalid request', $e->getMessage());
        }
        $this->assertSame(['getLedgerEntries', 'simulateTransaction', 'sendTransaction', 'getTransaction'],
            $this->requestMethods($history));
        $this->assertSame(0, $mock->count());
    }

    public function testReadOnlyTransferAndSendThrowsBeforeAnyRequest(): void
    {
        $mock = new MockHandler([]);
        $history = [];
        $client = $this->readOnlyClient($mock, $history);

        try {
            $client->transferAndSend(Address::fromAccountId(self::MUXED_ACCOUNT_BASE), 1);
            $this->fail('Sent from a read-only client');
        } catch (Exception $e) {
            $this->assertSame("transferAndSend needs the sending account's keypair, and this SACClient is read-only; "
                . 'open the client with SACClient::forAsset() or SACClient::forContractId()', $e->getMessage());
        }
        $this->assertSame([], $history);
    }

    // ---------------------------------------------------------------------
    // toBaseUnits
    // ---------------------------------------------------------------------

    public function testToBaseUnitsVectors(): void
    {
        $vectors = [
            '0' => '0',
            '1' => '10000000',
            '12.5' => '125000000',
            '0.0000001' => '1',
            '1.2345678' => '12345678',
            '007' => '70000000',
            '922337203685.4775807' => '9223372036854775807',
            '17014118346046923173168730371588.4105727' => self::MAX_I128,
        ];
        foreach ($vectors as $input => $expected) {
            $actual = SACClient::toBaseUnits((string)$input);
            $this->assertTrue(gmp_cmp($actual, gmp_init($expected, 10)) === 0,
                "$input gave " . gmp_strval($actual));
        }
        $this->assertTrue(gmp_cmp(SACClient::toBaseUnits('17014118346046923173168730371588.4105727'),
            gmp_sub(gmp_pow(2, 127), 1)) === 0);
    }

    public function testToBaseUnitsRejectsAmountAboveTheMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote(
            'Amount 17014118346046923173168730371588.4105728 exceeds the largest i128 amount, '
            . '17014118346046923173168730371588.4105727', '/') . '\z/');
        SACClient::toBaseUnits('17014118346046923173168730371588.4105728');
    }

    public function testToBaseUnitsRejectsMalformedAmounts(): void
    {
        $cases = [
            '1.12345678' => '1.12345678',
            '-1' => '-1',
            '+1' => '+1',
            ' 1' => ' 1',
            '1 ' => '1 ',
            "1\n" => '1\x0A',
            '' => '',
            '1.' => '1.',
            '.5' => '.5',
            '1e7' => '1e7',
            '1,5' => '1,5',
        ];
        foreach ($cases as $input => $preview) {
            try {
                SACClient::toBaseUnits((string)$input);
                $this->fail("Accepted '$preview'");
            } catch (InvalidArgumentException $e) {
                $this->assertSame("Invalid amount '$preview': expected decimal digits with at most 7 fractional "
                    . 'digits, such as 12.5', $e->getMessage());
            }
        }
    }

    public function testToBaseUnitsNamesASecretSeedByKindOnly(): void
    {
        $seed = KeyPair::random()->getSecretSeed();
        $this->assertNotNull($seed);

        try {
            SACClient::toBaseUnits($seed);
            $this->fail('Accepted a secret seed as amount');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid amount, got a secret seed (S...) instead of decimal digits with at most '
                . '7 fractional digits, such as 12.5', $e->getMessage());
            $this->assertStringNotContainsString($seed, $e->getMessage());
        }
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    private function readOnlyRefusal(): string
    {
        return 'This transaction was built by a read-only client for source account '
            . SACClient::READ_ONLY_SOURCE_ACCOUNT_ID . ', which has no keypair to sign with; '
            . "build it from a client opened with the submitting account's keypair";
    }

    /**
     * Read-only client of the testnet native asset contract over a mocked server.
     */
    private function readOnlyClient(MockHandler $mock, array &$history): SACClient
    {
        return SACClient::readOnlyForAsset(Asset::native(), Network::testnet(), self::TEST_RPC_URL, null,
            $this->mockedServer($mock, $history));
    }

    /**
     * SorobanServer whose requests are served by the mock handler and recorded into $history.
     */
    private function mockedServer(MockHandler $mock, array &$history): SorobanServer
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        return new SorobanServer(self::TEST_RPC_URL, new Client(['handler' => $stack]));
    }

    /**
     * @return array<string> the JSON-RPC method names of the recorded requests, in order
     */
    private function requestMethods(array $history): array
    {
        return array_map(
            fn (array $entry) => json_decode((string)$entry['request']->getBody(), true)['method'],
            $history,
        );
    }

    /**
     * The transaction a recorded simulateTransaction or sendTransaction request carries.
     */
    private function sentTransaction(array $history, int $index): Transaction
    {
        $body = json_decode((string)$history[$index]['request']->getBody(), true);
        $transaction = Transaction::fromEnvelopeBase64XdrString($body['params']['transaction']);
        $this->assertInstanceOf(Transaction::class, $transaction);
        return $transaction;
    }

    /**
     * The contract invocation of the recorded simulateTransaction request; by default the last request.
     */
    private function simulatedInvocation(array $history, ?int $index = null): InvokeContractHostFunction
    {
        $index = $index ?? count($history) - 1;
        $body = json_decode((string)$history[$index]['request']->getBody(), true);
        $this->assertSame('simulateTransaction', $body['method']);
        $operation = $this->sentTransaction($history, $index)->getOperations()[0];
        $this->assertInstanceOf(InvokeHostFunctionOperation::class, $operation);
        $function = $operation->getFunction();
        $this->assertInstanceOf(InvokeContractHostFunction::class, $function);
        return $function;
    }

    private function simulateResponse(XdrSCVal $returnedValue, bool $writeCall = false,
                                      bool $restorePreamble = false): Response
    {
        $readWrite = [];
        if ($writeCall) {
            $ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
            $ledgerKey->account = new XdrLedgerKeyAccount(new XdrAccountID(self::MUXED_ACCOUNT_BASE));
            $readWrite[] = $ledgerKey;
        }
        $result = [
            'minResourceFee' => '100',
            'latestLedger' => 1000,
            'transactionData' => $this->transactionData($readWrite)->toBase64Xdr(),
            'results' => [['auth' => [], 'xdr' => $returnedValue->toBase64Xdr()]],
        ];
        if ($restorePreamble) {
            $result['restorePreamble'] = $this->restorePreambleJson();
        }
        return $this->jsonRpcResponse($result);
    }

    private function simulateErrorResponse(string $error, bool $restorePreamble = false): Response
    {
        $result = ['error' => $error, 'latestLedger' => 1000];
        if ($restorePreamble) {
            $result['restorePreamble'] = $this->restorePreambleJson();
        }
        return $this->jsonRpcResponse($result);
    }

    /**
     * @return array<string, string>
     */
    private function restorePreambleJson(): array
    {
        return ['transactionData' => $this->transactionData([])->toBase64Xdr(), 'minResourceFee' => '50'];
    }

    /**
     * @param array<XdrLedgerKey> $readWrite
     */
    private function transactionData(array $readWrite): XdrSorobanTransactionData
    {
        return new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], $readWrite), 0, 0, 0), 0);
    }

    private function accountResponse(string $accountId): Response
    {
        $entryData = new XdrLedgerEntryData(XdrLedgerEntryType::ACCOUNT());
        $entryData->account = new XdrAccountEntry(
            accountID: new XdrAccountID($accountId),
            balance: new BigInteger(100000000),
            seqNum: new XdrSequenceNumber(new BigInteger(123456789)),
            numSubEntries: 0,
            flags: 0,
            homeDomain: '',
            thresholds: "\x01\x00\x00\x00",
            signers: [],
            ext: new XdrAccountEntryExt(0),
        );
        return $this->jsonRpcResponse([
            'entries' => [['key' => 'AAAAAA==', 'xdr' => $entryData->toBase64Xdr(), 'lastModifiedLedgerSeq' => 1000]],
            'latestLedger' => 1000,
        ]);
    }

    private function sendTransactionResponse(): Response
    {
        return $this->jsonRpcResponse([
            'status' => 'PENDING',
            'hash' => self::TX_HASH,
            'latestLedger' => 1000,
            'latestLedgerCloseTime' => '1700000000',
        ]);
    }

    private function getTransactionRpcResponse(string $status, ?string $resultXdr = null): Response
    {
        $result = [
            'status' => $status,
            'latestLedger' => 1001,
            'latestLedgerCloseTime' => '1700000001',
            'oldestLedger' => 990,
            'oldestLedgerCloseTime' => '1699999000',
        ];
        if ($resultXdr !== null) {
            $result['resultXdr'] = $resultXdr;
        }
        return $this->jsonRpcResponse($result);
    }

    private function jsonRpcResponse(array $result): Response
    {
        return new Response(200, [], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'result' => $result]));
    }
}
