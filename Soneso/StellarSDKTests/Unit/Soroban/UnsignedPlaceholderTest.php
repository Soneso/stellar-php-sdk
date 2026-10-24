<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SimulateHostFunctionResult;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentials;
use Soneso\StellarSDK\Soroban\SorobanAddressCredentialsWithDelegates;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedFunction;
use Soneso\StellarSDK\Soroban\SorobanAuthorizedInvocation;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\SorobanDelegateSignature;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;

/**
 * Unsigned placeholders in Soroban authorization entries.
 *
 * js-stellar-sdk builds unsigned entries with an empty signature vector and expiration ledger 0.
 * A void value and an empty or absent vector are unsigned; every other value is a signature.
 * The tests cover the expiration check of SorobanAuthorizationEntry, the signer lists of
 * AssembledTransaction, and signing such entries through both classes.
 */
class UnsignedPlaceholderTest extends TestCase
{
    private const CONTRACT_ID = 'CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA';
    private const AUX_CONTRACT_ID = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
    private const RPC_URL = 'http://localhost:1';
    private const BLOCKED = 'Transaction requires signatures from multiple signers. See `needsNonInvokerSigningBy` for details.';

    private Network $network;
    private KeyPair $invoker;
    private KeyPair $top;
    private KeyPair $delegate;

    public function setUp(): void
    {
        $this->network = Network::testnet();
        $this->invoker = KeyPair::fromPrivateKey(str_repeat("\x01", 32));
        $this->top = KeyPair::fromPrivateKey(str_repeat("\x11", 32));
        $this->delegate = KeyPair::fromPrivateKey(str_repeat("\x22", 32));
    }

    /** @dataProvider signatureValueProvider */
    public function testCarriesSignatureTreatsVoidAndEmptyVectorsAsUnsigned(XdrSCVal $value, bool $expected): void
    {
        $this->assertSame($expected, SorobanAuthorizationEntry::carriesSignature($value));
    }

    /** @return array<string, array{XdrSCVal, bool}> */
    public static function signatureValueProvider(): array
    {
        return [
            'void' => [XdrSCVal::forVoid(), false],
            'empty vector' => [XdrSCVal::forVec([]), false],
            'empty vector decoded' => [XdrSCVal::fromBase64Xdr(base64_encode(pack('N3', 16, 1, 0))), false],
            'absent vector decoded' => [XdrSCVal::fromBase64Xdr(base64_encode(pack('N2', 16, 0))), false],
            'one-element vector' => [XdrSCVal::forVec([XdrSCVal::forVoid()]), true],
            'bytes' => [XdrSCVal::forBytes(str_repeat("\x07", 64)), true],
        ];
    }

    /** @dataProvider armProvider */
    public function testSignWithExplicitExpirationStampsAPlaceholderEntry(string $arm): void
    {
        $entry = $this->addressEntry($arm, XdrSCVal::forVec([]), 0);

        $entry->sign($this->top, $this->network, 500);

        $credentials = $entry->credentials->getAddressCredentials();
        $this->assertSame(500, $credentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->signature->vec);
        $this->assertSignatureVerifies($this->top, $credentials->signature->vec[0], $entry);
    }

    /** @dataProvider armProvider */
    public function testSignWithoutExpirationKeepsTheStoredZeroOnAPlaceholderEntry(string $arm): void
    {
        $entry = $this->addressEntry($arm, XdrSCVal::forVec([]), 0);

        $entry->sign($this->top, $this->network);

        $credentials = $entry->credentials->getAddressCredentials();
        $this->assertSame(0, $credentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->signature->vec);
        $this->assertSignatureVerifies($this->top, $credentials->signature->vec[0], $entry);
    }

    public function testExpirationCheckIgnoresAnEmptyVectorDelegate(): void
    {
        $entry = $this->delegatesEntry(XdrSCVal::forVoid(), XdrSCVal::forVec([]), 900);

        $this->assertNull($entry->resolveSignatureExpirationLedger(null));
        $this->assertSame(901, $entry->resolveSignatureExpirationLedger(901));

        $entry->sign($this->delegate, $this->network, 901, $this->delegate->getAccountId());

        $this->assertSame(901, $entry->credentials->getAddressCredentials()->signatureExpirationLedger);
        $node = $entry->credentials->addressWithDelegates->delegates[0];
        $this->assertCount(1, $node->signature->vec);
        $this->assertSignatureVerifies($this->delegate, $node->signature->vec[0], $entry);
    }

    /** @dataProvider armProvider */
    public function testAssembledTransactionListsAndSignsAPlaceholderEntry(string $arm): void
    {
        $mock = new MockHandler([$this->latestLedgerResponse(1000)]);
        $tx = $this->assembledTransaction([$this->addressEntry($arm, XdrSCVal::forVec([]), 0)], $mock);
        $address = $this->top->getAccountId();

        $this->assertSame([$address], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$address], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->top);

        $this->assertCount(0, $mock);
        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->getAddressCredentials();
        $this->assertSame(1100, $credentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->signature->vec);
        $this->assertSignatureVerifies($this->top, $credentials->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
        $this->assertNotNull($tx->signed);
    }

    /** @dataProvider armProvider */
    public function testSignAuthEntriesWithExplicitExpirationStampsAPlaceholderEntry(string $arm): void
    {
        $tx = $this->assembledTransaction([$this->addressEntry($arm, XdrSCVal::forVec([]), 0)], new MockHandler([]));

        $tx->signAuthEntries($this->top, null, 500);

        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->getAddressCredentials();
        $this->assertSame(500, $credentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->signature->vec);
        $this->assertSignatureVerifies($this->top, $credentials->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
    }

    public function testAssembledTransactionListsAndSignsAPlaceholderTopLevelWithDelegates(): void
    {
        $mock = new MockHandler([$this->latestLedgerResponse(1000)]);
        $tx = $this->assembledTransaction([$this->delegatesEntry(XdrSCVal::forVec([]), XdrSCVal::forVoid(), 0)], $mock);
        $top = $this->top->getAccountId();
        $delegate = $this->delegate->getAccountId();

        $this->assertSame([$top, $delegate], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$top, $delegate], $this->blockingSigners($tx));

        $tx->signAuthEntries($this->top);

        $this->assertSame([$delegate], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$delegate], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->delegate);

        $this->assertCount(0, $mock);
        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->addressWithDelegates;
        $this->assertSame(1100, $credentials->addressCredentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->addressCredentials->signature->vec);
        $this->assertSignatureVerifies($this->top, $credentials->addressCredentials->signature->vec[0], $entry);
        $this->assertCount(1, $credentials->delegates[0]->signature->vec);
        $this->assertSignatureVerifies($this->delegate, $credentials->delegates[0]->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
    }

    public function testAssembledTransactionTreatsAnEmptyVectorDelegateAsUnsigned(): void
    {
        $mock = new MockHandler([$this->latestLedgerResponse(1000)]);
        $tx = $this->assembledTransaction([$this->delegatesEntry(XdrSCVal::forVoid(), XdrSCVal::forVec([]), 0)], $mock);
        $top = $this->top->getAccountId();
        $delegate = $this->delegate->getAccountId();

        $this->assertSame([$top, $delegate], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$top, $delegate], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->delegate);

        $this->assertCount(0, $mock);
        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->addressWithDelegates;
        $this->assertSame(1100, $credentials->addressCredentials->signatureExpirationLedger);
        $this->assertCount(1, $credentials->delegates[0]->signature->vec);
        $this->assertSignatureVerifies($this->delegate, $credentials->delegates[0]->signature->vec[0], $entry);
        $this->assertSame([$top], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$top], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->top);

        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->addressWithDelegates;
        $this->assertSame(1100, $credentials->addressCredentials->signatureExpirationLedger);
        $this->assertSignatureVerifies($this->top, $credentials->addressCredentials->signature->vec[0], $entry);
        $this->assertSignatureVerifies($this->delegate, $credentials->delegates[0]->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
    }

    public function testAssembledTransactionBlocksAnUnsignedTopLevelWithoutDelegates(): void
    {
        $mock = new MockHandler([$this->latestLedgerResponse(1000)]);
        $tx = $this->assembledTransaction([$this->delegatesEntry(XdrSCVal::forVoid(), null, 0)], $mock);
        $top = $this->top->getAccountId();

        $this->assertSame([$top], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$top], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->top);

        $this->assertCount(0, $mock);
        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->addressWithDelegates;
        $this->assertSame([], $credentials->delegates);
        $this->assertSame(1100, $credentials->addressCredentials->signatureExpirationLedger);
        $this->assertSignatureVerifies($this->top, $credentials->addressCredentials->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
        $this->assertNotNull($tx->signed);
    }

    public function testAssembledTransactionBlocksAPlaceholderTopLevelWithSignedDelegates(): void
    {
        $signed = XdrSCVal::forVec([XdrSCVal::forBytes(str_repeat("\x07", 64))]);
        $tx = $this->assembledTransaction([$this->delegatesEntry(XdrSCVal::forVec([]), $signed, 900)], new MockHandler([]));
        $top = $this->top->getAccountId();

        $this->assertSame([$top], $tx->needsNonInvokerSigningBy());
        $this->assertSame([$top], $this->blockingSigners($tx));
        $this->assertSame(self::BLOCKED, $this->signError($tx));

        $tx->signAuthEntries($this->top);

        $entry = $tx->tx->getOperations()[0]->auth[0];
        $credentials = $entry->credentials->addressWithDelegates->addressCredentials;
        $this->assertSame(900, $credentials->signatureExpirationLedger);
        $this->assertSignatureVerifies($this->top, $credentials->signature->vec[0], $entry);
        $this->assertSame([], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
    }

    public function testAssembledTransactionDoesNotBlockOnAnUnsignedContractTopLevel(): void
    {
        $signed = XdrSCVal::forVec([XdrSCVal::forBytes(str_repeat("\x07", 64))]);
        $contract = Address::fromContractId(self::AUX_CONTRACT_ID);
        $tx = $this->assembledTransaction(
            [$this->delegatesEntry(XdrSCVal::forVoid(), $signed, 900, $contract)],
            new MockHandler([]),
        );

        $this->assertSame([StrKey::decodeContractIdHex(self::AUX_CONTRACT_ID)], $tx->needsNonInvokerSigningBy());
        $this->assertSame([], $this->blockingSigners($tx));
        $this->assertNull($this->signError($tx));
        $this->assertNotNull($tx->signed);
    }

    /** @dataProvider signedValueProvider */
    public function testSignedValuesKeepTheStoredExpiration(string $node, XdrSCVal $signature): void
    {
        $entry = $node === 'top'
            ? $this->addressEntry('legacy', $signature, 900)
            : $this->delegatesEntry(XdrSCVal::forVoid(), $signature, 900);
        $signer = $node === 'top' ? $this->top : $this->delegate;
        $before = $entry->toBase64Xdr();

        $this->assertSame(900, $entry->resolveSignatureExpirationLedger(null));
        try {
            $entry->sign($signer, $this->network, 901, $signer->getAccountId());
            $this->fail('Expected conflicting expiration');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Signature expiration ledger 901 conflicts with stored ledger 900', $e->getMessage());
        }
        $this->assertSame($before, $entry->toBase64Xdr());
    }

    /** @dataProvider signedValueProvider */
    public function testSignedValuesAreNotListedAsUnsigned(string $node, XdrSCVal $signature): void
    {
        $entry = $node === 'top'
            ? $this->addressEntry('legacy', $signature, 900)
            : $this->delegatesEntry(XdrSCVal::forVoid(), $signature, 900);
        $tx = $this->assembledTransaction([$entry], new MockHandler([]));

        $expectedUnsigned = $node === 'top' ? [] : [$this->top->getAccountId()];
        $this->assertSame($expectedUnsigned, $tx->needsNonInvokerSigningBy());
        $this->assertSame($expectedUnsigned, $this->blockingSigners($tx));
        $this->assertSame($node === 'top' ? null : self::BLOCKED, $this->signError($tx));
    }

    /** @return array<string, array{string}> */
    public static function armProvider(): array
    {
        return ['legacy' => ['legacy'], 'v2' => ['v2']];
    }

    /** @return array<string, array{string, XdrSCVal}> */
    public static function signedValueProvider(): array
    {
        return [
            'top-level one-element vector' => ['top', XdrSCVal::forVec([XdrSCVal::forBytes(str_repeat("\x07", 64))])],
            'top-level bytes' => ['top', XdrSCVal::forBytes(str_repeat("\x07", 64))],
            'delegate one-element vector' => ['delegate', XdrSCVal::forVec([XdrSCVal::forBytes(str_repeat("\x07", 64))])],
            'delegate bytes' => ['delegate', XdrSCVal::forBytes(str_repeat("\x07", 64))],
        ];
    }

    private function addressEntry(string $arm, XdrSCVal $signature, int $expiration): SorobanAuthorizationEntry
    {
        $credentials = new SorobanAddressCredentials(Address::fromAccountId($this->top->getAccountId()), 7, $expiration, $signature);
        return new SorobanAuthorizationEntry(
            $arm === 'v2'
                ? SorobanCredentials::forAddressCredentialsV2($credentials)
                : SorobanCredentials::forAddressCredentialsLegacy($credentials),
            $this->invocation(),
        );
    }

    /**
     * Builds a WITH_DELEGATES entry for the top-level account, or for $topAddress when given,
     * with one delegate node for the delegate account, or none when $delegateSignature is null.
     */
    private function delegatesEntry(
        XdrSCVal $topSignature,
        ?XdrSCVal $delegateSignature,
        int $expiration,
        ?Address $topAddress = null,
    ): SorobanAuthorizationEntry {
        $address = $topAddress ?? Address::fromAccountId($this->top->getAccountId());
        $credentials = new SorobanAddressCredentials($address, 7, $expiration, $topSignature);
        $nodes = $delegateSignature === null
            ? []
            : [new SorobanDelegateSignature(XdrSCAddress::forAccountId($this->delegate->getAccountId()), $delegateSignature)];
        return new SorobanAuthorizationEntry(
            SorobanCredentials::forAddressWithDelegates(new SorobanAddressCredentialsWithDelegates($credentials, $nodes)),
            $this->invocation(),
        );
    }

    private function invocation(): SorobanAuthorizedInvocation
    {
        $contract = Address::fromContractId(StrKey::decodeContractIdHex(self::AUX_CONTRACT_ID));
        return new SorobanAuthorizedInvocation(SorobanAuthorizedFunction::forContractFunction($contract, 'test', []));
    }

    private function assertSignatureVerifies(KeyPair $signer, XdrSCVal $element, SorobanAuthorizationEntry $entry): void
    {
        $signature = null;
        foreach ($element->map ?? [] as $mapEntry) {
            if ($mapEntry->key->sym === 'signature') {
                $signature = $mapEntry->val->bytes?->getValue();
            }
        }
        $this->assertNotNull($signature, 'The signature element must carry a signature field');
        $hash = Hash::generate($entry->buildPreimage($this->network)->encode());
        $this->assertTrue($signer->verifySignature($signature, $hash));
    }

    /**
     * Builds an AssembledTransaction that holds the given auth entries without simulating.
     *
     * The auth entries in the simulation result make the call a write call, so sign() runs its
     * signer check. RPC requests go to the given mock handler.
     *
     * @param array<SorobanAuthorizationEntry> $entries
     */
    private function assembledTransaction(array $entries, MockHandler $mock): AssembledTransaction
    {
        $server = new SorobanServer(self::RPC_URL, new Client(['handler' => HandlerStack::create($mock)]));

        $operation = (new InvokeHostFunctionOperationBuilder(
            new InvokeContractHostFunction(self::CONTRACT_ID, 'test', []),
        ))->build();
        $transaction = (new TransactionBuilder(new Account($this->invoker->getAccountId(), new BigInteger(1))))
            ->addOperation($operation)
            ->build();
        $transaction->setSorobanAuth($entries);
        $data = new XdrSorobanTransactionData(
            new XdrSorobanTransactionDataExt(0),
            new XdrSorobanResources(new XdrLedgerFootprint([], []), 100, 100, 100),
            100,
        );
        $transaction->setSorobanTransactionData($data);

        $class = new ReflectionClass(AssembledTransaction::class);
        $tx = $class->newInstanceWithoutConstructor();
        $tx->options = new AssembledTransactionOptions(
            clientOptions: new ClientOptions(
                sourceAccountKeyPair: $this->invoker,
                contractId: self::CONTRACT_ID,
                network: $this->network,
                rpcUrl: self::RPC_URL,
            ),
            methodOptions: new MethodOptions(simulate: false, restore: false),
            method: 'test',
            arguments: [],
        );
        $class->getProperty('server')->setValue($tx, $server);
        $class->getProperty('simulationResult')
            ->setValue($tx, new SimulateHostFunctionResult($data, XdrSCVal::forVoid(), $entries));
        $tx->tx = $transaction;
        return $tx;
    }

    /** @return array<string> */
    private function blockingSigners(AssembledTransaction $tx): array
    {
        return (new ReflectionMethod(AssembledTransaction::class, 'getBlockingNonInvokerSigners'))->invoke($tx);
    }

    private function signError(AssembledTransaction $tx): ?string
    {
        try {
            $tx->sign(force: true);
            return null;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function latestLedgerResponse(int $sequence): Response
    {
        return new Response(200, [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => str_repeat('a', 64),
                'sequence' => $sequence,
                'protocolVersion' => 27,
            ],
        ]));
    }
}
