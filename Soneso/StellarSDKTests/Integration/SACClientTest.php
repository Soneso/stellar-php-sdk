<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use GMP;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransaction;
use Soneso\StellarSDK\Soroban\Contract\AssembledTransactionOptions;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SACClient;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrContractEventBodyV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDKTests\TestUtils;

/**
 * Stellar Asset Contract end-to-end integration test on testnet.
 *
 * Issues a fresh asset, deploys its Stellar Asset Contract, and checks the contract id
 * derivation, the detection in SorobanClient::forClientOptions, the read-only reads, and the
 * transfers of SACClient to an account, a muxed account, and a muxed contract, the last one
 * on protocol 30 or later.
 */
class SACClientTest extends TestCase
{
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const TESTNET_NATIVE_SAC_ID = "CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC";
    const ASSET_CODE = "TEST";

    private Network $network;
    private string $rpcUrl;
    private SorobanServer $server;
    private StellarSDK $sdk;
    private ?int $protocolVersion;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->network = Network::testnet();
        $this->rpcUrl = self::TESTNET_SERVER_URL;
        $this->server = new SorobanServer($this->rpcUrl);
        $this->sdk = StellarSDK::getTestNetInstance();
        $this->protocolVersion = $this->server->getNetwork()->getProtocolVersion();
    }

    /**
     * @return array{asset: Asset, issuer: KeyPair, a: KeyPair, b: KeyPair, contractId: string}
     */
    public function testSacClient(): array
    {
        $issuer = KeyPair::random();
        $a = KeyPair::random();
        $b = KeyPair::random();
        $issuerId = $issuer->getAccountId();
        $aId = $a->getAccountId();
        $bId = $b->getAccountId();
        foreach ([$issuerId, $aId, $bId] as $accountId) {
            TestUtils::fundTestAccountAndAwaitVisibility($accountId, rpc: $this->server, horizon: $this->sdk);
        }
        $aAddress = Address::fromAccountId($aId);
        $bAddress = Address::fromAccountId($bId);
        $asset = new AssetTypeCreditAlphanum4(self::ASSET_CODE, $issuerId);
        $sacId = $asset->getContractId($this->network);

        // 1. Trustlines of A and B in one classic transaction from the issuer.
        $issuerAccount = $this->server->getAccount($issuerId);
        $this->assertNotNull($issuerAccount);
        $trustTx = (new TransactionBuilder($issuerAccount))
            ->addOperation((new ChangeTrustOperationBuilder($asset))->setSourceAccount($aId)->build())
            ->addOperation((new ChangeTrustOperationBuilder($asset))->setSourceAccount($bId)->build())
            ->build();
        $trustTx->sign($issuer, $this->network);
        $trustTx->sign($a, $this->network);
        $trustTx->sign($b, $this->network);
        $trustResponse = $this->sdk->submitTransaction($trustTx);
        $this->assertTrue($trustResponse->isSuccessful());
        $this->awaitRpcLedger($trustResponse->getLedger());

        // 2. Deploy the asset's contract with the issuer as source.
        $deployOp = (new InvokeHostFunctionOperationBuilder(new DeploySACWithAssetHostFunction($asset)))->build();
        $deployOptions = new AssembledTransactionOptions(
            clientOptions: new ClientOptions(
                sourceAccountKeyPair: $issuer,
                contractId: $sacId,
                network: $this->network,
                rpcUrl: $this->rpcUrl,
            ),
            methodOptions: new MethodOptions(),
            method: '__constructor',
        );
        $deployResponse = AssembledTransaction::buildWithOp($deployOp, $deployOptions)->signAndSend();
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $deployResponse->getStatus());
        $createdContractId = $deployResponse->getCreatedContractId();
        $this->assertNotNull($createdContractId);
        $this->assertSame($asset->getContractId(Network::testnet()), StrKey::encodeContractIdHex($createdContractId));
        $this->awaitRpcLedger($this->ledgerOf($deployResponse));

        // 3. Classic payment of 1000 TEST from the issuer to A.
        $issuerAccount = $this->server->getAccount($issuerId);
        $this->assertNotNull($issuerAccount);
        $paymentTx = (new TransactionBuilder($issuerAccount))
            ->addOperation((new PaymentOperationBuilder($aId, $asset, "1000"))->build())
            ->build();
        $paymentTx->sign($issuer, $this->network);
        $paymentResponse = $this->sdk->submitTransaction($paymentTx);
        $this->assertTrue($paymentResponse->isSuccessful());
        $this->awaitRpcLedger($paymentResponse->getLedger());

        // 4. The generic client detects the SAC and opens it with the embedded spec.
        $detected = SorobanClient::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $a,
            contractId: $sacId,
            network: $this->network,
            rpcUrl: $this->rpcUrl,
        ));
        $methodNames = $detected->getMethodNames();
        sort($methodNames);
        $this->assertSame([
            'admin', 'allowance', 'approve', 'authorized', 'balance', 'burn', 'burn_from', 'clawback',
            'decimals', 'mint', 'name', 'set_admin', 'set_authorized', 'symbol', 'transfer',
            'transfer_from', 'trust',
        ], $methodNames);

        // 5. Read-only reads.
        $reader = SACClient::readOnlyForAsset($asset, $this->network, $this->rpcUrl);
        $this->assertSame($sacId, $reader->getContractId());
        $this->assertSame('10000000000', gmp_strval($reader->balance($aAddress)));
        $this->assertSame(7, $reader->decimals());
        $this->assertSame(self::ASSET_CODE . ':' . $issuerId, $reader->name());
        $this->assertSame(self::ASSET_CODE, $reader->symbol());

        // 6. A to B with A as source.
        $aClient = SACClient::forAsset($asset, $a, $this->network, $this->rpcUrl);
        $sendResponse = $aClient->transferAndSend($bAddress, SACClient::toBaseUnits("100"));
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $sendResponse->getStatus());
        $this->awaitRpcLedger($this->ledgerOf($sendResponse));
        $this->assertSame('1000000000', gmp_strval($reader->balance($bAddress)));
        $this->assertSame('9000000000', gmp_strval($reader->balance($aAddress)));

        // 7. A to a muxed account of B: B is credited, and the event carries the muxed id.
        $muxedB = Address::fromMuxedAccountId((new MuxedAccount($bId, 4242))->getAccountId());
        $muxedResponse = $aClient->transfer($aAddress, $muxedB, 250000000)->signAndSend();
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $muxedResponse->getStatus());
        $event = $this->transferEvent($muxedResponse);
        $this->assertSame(XdrSCValType::SCV_ADDRESS, $event->topics[2]->type->value);
        $this->assertSame($bId, $event->topics[2]->address?->toStrKey());
        $this->assertSame('250000000', gmp_strval($this->i128Entry($event->data, 'amount')));
        $this->assertSame(4242, $this->u64Entry($event->data, 'to_muxed_id'));
        $this->awaitRpcLedger($this->ledgerOf($muxedResponse));
        $this->assertSame('1250000000', gmp_strval($reader->balance($bAddress)));

        // 8. The issuer submits a transfer from A, which A authorizes.
        $issuerClient = SACClient::forAsset($asset, $issuer, $this->network, $this->rpcUrl);
        $authTx = $issuerClient->transfer($aAddress, $bAddress, SACClient::toBaseUnits("10.5"));
        $this->assertSame([$aId], $authTx->needsNonInvokerSigningBy());
        $authTx->signAuthEntries($a);
        $authResponse = $authTx->signAndSend();
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $authResponse->getStatus());
        $this->awaitRpcLedger($this->ledgerOf($authResponse));
        $this->assertSame('1355000000', gmp_strval($reader->balance($bAddress)));

        // 9. The native asset's contract.
        $nativeReader = SACClient::readOnlyForAsset(Asset::native(), $this->network, $this->rpcUrl);
        $this->assertSame(self::TESTNET_NATIVE_SAC_ID, $nativeReader->getContractId());
        $this->assertGreaterThan(0, gmp_cmp($nativeReader->balance($aAddress), 0));
        $this->assertSame(7, $nativeReader->decimals());
        $this->assertSame('native', $nativeReader->name());
        $this->assertSame('native', $nativeReader->symbol());

        return [
            'asset' => $asset,
            'issuer' => $issuer,
            'a' => $a,
            'b' => $b,
            'contractId' => $sacId,
        ];
    }

    /**
     * @param array{asset: Asset, issuer: KeyPair, a: KeyPair, b: KeyPair, contractId: string} $context
     */
    #[Depends('testSacClient')]
    public function testTransferToMuxedContract(array $context): void
    {
        $this->assertNotNull($this->protocolVersion,
            'The getNetwork response of ' . $this->rpcUrl . ' carries no protocol version');
        if ($this->protocolVersion < 30) {
            $this->markTestSkipped('The network reports protocol ' . $this->protocolVersion
                . '; muxed contract destinations need protocol 30.');
        }
        $asset = $context['asset'];
        $a = $context['a'];
        $sacId = $context['contractId'];
        $aAddress = Address::fromAccountId($a->getAccountId());

        $aClient = SACClient::forAsset($asset, $a, $this->network, $this->rpcUrl);
        $response = $aClient->transfer($aAddress, Address::fromMuxedContract($sacId, 7), 50000000)->signAndSend();
        $this->assertSame(GetTransactionResponse::STATUS_SUCCESS, $response->getStatus());
        $event = $this->transferEvent($response);
        $this->assertSame(7, $this->u64Entry($event->data, 'to_muxed_id'));
        $this->assertSame('50000000', gmp_strval($this->i128Entry($event->data, 'amount')));
        $this->awaitRpcLedger($this->ledgerOf($response));

        $reader = SACClient::readOnlyForAsset($asset, $this->network, $this->rpcUrl);
        $this->assertSame('50000000', gmp_strval($reader->balance(Address::fromContractId($sacId))));
    }

    /**
     * Returns the body of the transfer event the first operation emitted.
     */
    private function transferEvent(GetTransactionResponse $response): XdrContractEventBodyV0
    {
        $operationEvents = $response->getEvents()?->contractEventsXdr[0] ?? null;
        $this->assertNotNull($operationEvents);
        foreach ($operationEvents as $eventXdr) {
            $body = XdrContractEvent::fromBase64Xdr($eventXdr)->body->v0;
            if ($body !== null && count($body->topics) > 0
                && $body->topics[0]->type->value === XdrSCValType::SCV_SYMBOL
                && $body->topics[0]->sym === 'transfer') {
                return $body;
            }
        }
        $this->fail('The first operation emitted no transfer event');
    }

    /**
     * Returns the value of the map entry whose key is the given symbol.
     */
    private function mapEntry(XdrSCVal $map, string $key): XdrSCVal
    {
        $this->assertSame(XdrSCValType::SCV_MAP, $map->type->value);
        $this->assertNotNull($map->map);
        foreach ($map->map as $entry) {
            if ($entry->key->type->value === XdrSCValType::SCV_SYMBOL && $entry->key->sym === $key) {
                return $entry->val;
            }
        }
        $this->fail("The event data has no entry {$key}");
    }

    private function i128Entry(XdrSCVal $map, string $key): GMP
    {
        $value = $this->mapEntry($map, $key);
        $this->assertSame(XdrSCValType::SCV_I128, $value->type->value);
        $bigInt = $value->toBigInt();
        $this->assertNotNull($bigInt);
        return $bigInt;
    }

    private function u64Entry(XdrSCVal $map, string $key): ?int
    {
        $value = $this->mapEntry($map, $key);
        $this->assertSame(XdrSCValType::SCV_U64, $value->type->value);
        return $value->u64;
    }

    private function ledgerOf(GetTransactionResponse $response): int
    {
        $ledger = $response->getLedger();
        $this->assertNotNull($ledger);
        return $ledger;
    }

    /**
     * Waits until the RPC serves the given ledger in consecutive rounds, so that the next read
     * or account lookup sees the state a transaction of that ledger wrote.
     */
    private function awaitRpcLedger(int $ledger, int $timeoutSeconds = 60): void
    {
        $streak = 0;
        $start = microtime(true);
        while (true) {
            $latest = $this->server->getLatestLedger()->sequence;
            $streak = ($latest !== null && $latest >= $ledger) ? $streak + 1 : 0;
            if ($streak >= 3) {
                return;
            }
            if (microtime(true) - $start >= $timeoutSeconds) {
                throw new RuntimeException("The RPC did not serve ledger {$ledger} within {$timeoutSeconds} s");
            }
            sleep(1);
        }
    }
}
