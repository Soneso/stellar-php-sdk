<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Util\CustomFriendBot;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDKTests\Unit\Soroban\SecretSeedRedactionTest;
use Soneso\StellarSDKTests\Unit\Soroban\SecretSeedVariants;

/**
 * The request methods that put a caller's account id, issuer, pool id, claimable balance id or
 * transaction hash into a Horizon or FriendBot URL refuse a value that starts with "S" or "s"
 * before any request is sent.
 */
class RequestBuilderSecretSeedTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $history the requests the transport receives
     */
    private static function mockedClient(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        return new Client(['handler' => $stack]);
    }

    private static function sdk(Client $client): StellarSDK
    {
        $sdk = new StellarSDK('https://horizon.example.com');
        $sdk->setHttpClient($client);
        return $sdk;
    }

    private static function credit(string $issuer): AssetTypeCreditAlphanum4
    {
        return new AssetTypeCreditAlphanum4('USD', $issuer);
    }

    /**
     * Every method that checks its value: the call and the parameter the message names.
     *
     * @return array<string, array{Closure(StellarSDK, string, Client): mixed, string}>
     */
    public static function guardedMethods(): array
    {
        $noop = static function (): void {
        };
        return [
            'accounts account' => [fn (StellarSDK $s, string $v) => $s->accounts()->account($v), 'accountId'],
            'accounts accountData' => [fn (StellarSDK $s, string $v) => $s->accounts()->accountData($v, 'key'),
                'accountId'],
            'accounts streamAccount' => [fn (StellarSDK $s, string $v) => $s->accounts()->streamAccount($v, $noop),
                'accountId'],
            'accounts streamAccountData' => [
                fn (StellarSDK $s, string $v) => $s->accounts()->streamAccountData($v, 'key', $noop), 'accountId'],
            'accounts forSigner' => [fn (StellarSDK $s, string $v) => $s->accounts()->forSigner($v), 'signer'],
            'accounts forAsset' => [fn (StellarSDK $s, string $v) => $s->accounts()->forAsset(self::credit($v)),
                'asset issuer'],
            'accounts forLiquidityPool' => [fn (StellarSDK $s, string $v) => $s->accounts()->forLiquidityPool($v),
                'liquidityPoolId'],
            'accounts forSponsor' => [fn (StellarSDK $s, string $v) => $s->accounts()->forSponsor($v), 'sponsor'],
            'assets forAssetIssuer' => [fn (StellarSDK $s, string $v) => $s->assets()->forAssetIssuer($v),
                'assetIssuer'],
            'claimable balances forSponsor' => [
                fn (StellarSDK $s, string $v) => $s->claimableBalances()->forSponsor($v), 'sponsor'],
            'claimable balances forClaimant' => [
                fn (StellarSDK $s, string $v) => $s->claimableBalances()->forClaimant($v), 'claimant'],
            'claimable balances forAsset' => [
                fn (StellarSDK $s, string $v) => $s->claimableBalances()->forAsset(self::credit($v)), 'asset issuer'],
            'effects forAccount' => [fn (StellarSDK $s, string $v) => $s->effects()->forAccount($v), 'accountId'],
            'effects forTransaction' => [fn (StellarSDK $s, string $v) => $s->effects()->forTransaction($v),
                'transactionId'],
            'effects forLiquidityPool' => [fn (StellarSDK $s, string $v) => $s->effects()->forLiquidityPool($v),
                'liquidityPoolId'],
            'find paths forDestinationAccount' => [
                fn (StellarSDK $s, string $v) => $s->findPaths()->forDestinationAccount($v), 'account'],
            'find paths forSourceAccount' => [
                fn (StellarSDK $s, string $v) => $s->findPaths()->forSourceAccount($v), 'account'],
            'find paths forDestinationAsset' => [
                fn (StellarSDK $s, string $v) => $s->findPaths()->forDestinationAsset(self::credit($v)),
                'asset issuer'],
            'liquidity pools forPoolId' => [fn (StellarSDK $s, string $v) => $s->liquidityPools()->forPoolId($v),
                'liquidityPoolID'],
            'liquidity pools forAccount' => [fn (StellarSDK $s, string $v) => $s->liquidityPools()->forAccount($v),
                'accountId'],
            'offers forAccount' => [fn (StellarSDK $s, string $v) => $s->offers()->forAccount($v), 'accountId'],
            'offers forSponsor' => [fn (StellarSDK $s, string $v) => $s->offers()->forSponsor($v), 'sponsor'],
            'offers forSeller' => [fn (StellarSDK $s, string $v) => $s->offers()->forSeller($v), 'seller'],
            'offers forSellingAsset' => [
                fn (StellarSDK $s, string $v) => $s->offers()->forSellingAsset(self::credit($v)), 'asset issuer'],
            'offers forBuyingAsset' => [
                fn (StellarSDK $s, string $v) => $s->offers()->forBuyingAsset(self::credit($v)), 'asset issuer'],
            'operations forAccount' => [fn (StellarSDK $s, string $v) => $s->operations()->forAccount($v),
                'accountId'],
            'operations forTransaction' => [fn (StellarSDK $s, string $v) => $s->operations()->forTransaction($v),
                'transactionId'],
            'operations forLiquidityPool' => [
                fn (StellarSDK $s, string $v) => $s->operations()->forLiquidityPool($v), 'liquidityPoolId'],
            'order book forBuyingAsset' => [
                fn (StellarSDK $s, string $v) => $s->orderBook()->forBuyingAsset(self::credit($v)),
                'buyingAsset issuer'],
            'order book forSellingAsset' => [
                fn (StellarSDK $s, string $v) => $s->orderBook()->forSellingAsset(self::credit($v)),
                'sellingAsset issuer'],
            'payments forAccount' => [fn (StellarSDK $s, string $v) => $s->payments()->forAccount($v), 'accountId'],
            'payments forTransaction' => [fn (StellarSDK $s, string $v) => $s->payments()->forTransaction($v),
                'transactionId'],
            'strict receive forDestinationAccount' => [
                fn (StellarSDK $s, string $v) => $s->findStrictReceivePaths()->forDestinationAccount($v), 'account'],
            'strict receive forSourceAccount' => [
                fn (StellarSDK $s, string $v) => $s->findStrictReceivePaths()->forSourceAccount($v), 'account'],
            'strict receive forSourceAssets' => [
                fn (StellarSDK $s, string $v) => $s->findStrictReceivePaths()
                    ->forSourceAssets([Asset::native(), self::credit($v)]), 'assets[1] issuer'],
            'strict receive forSourceAssets keyed by the input' => [
                fn (StellarSDK $s, string $v) => $s->findStrictReceivePaths()
                    ->forSourceAssets([$v => Asset::native(), $v . 'A' => self::credit($v)]), 'assets[1] issuer'],
            'strict receive forDestinationAsset' => [
                fn (StellarSDK $s, string $v) => $s->findStrictReceivePaths()->forDestinationAsset(self::credit($v)),
                'asset issuer'],
            'strict send forDestinationAccount' => [
                fn (StellarSDK $s, string $v) => $s->findStrictSendPaths()->forDestinationAccount($v), 'account'],
            'strict send forSourceAccount' => [
                fn (StellarSDK $s, string $v) => $s->findStrictSendPaths()->forSourceAccount($v), 'account'],
            'strict send forDestinationAssets' => [
                fn (StellarSDK $s, string $v) => $s->findStrictSendPaths()
                    ->forDestinationAssets([Asset::native(), self::credit($v)]), 'assets[1] issuer'],
            'strict send forDestinationAssets keyed by the input' => [
                fn (StellarSDK $s, string $v) => $s->findStrictSendPaths()
                    ->forDestinationAssets([$v => Asset::native(), $v . 'A' => self::credit($v)]), 'assets[1] issuer'],
            'strict send forSourceAsset' => [
                fn (StellarSDK $s, string $v) => $s->findStrictSendPaths()->forSourceAsset(self::credit($v)),
                'asset issuer'],
            'trade aggregations forBaseAsset' => [
                fn (StellarSDK $s, string $v) => $s->tradeAggregations()->forBaseAsset(self::credit($v)),
                'baseAsset issuer'],
            'trade aggregations forCounterAsset' => [
                fn (StellarSDK $s, string $v) => $s->tradeAggregations()->forCounterAsset(self::credit($v)),
                'counterAsset issuer'],
            'trades forBaseAsset' => [
                fn (StellarSDK $s, string $v) => $s->trades()->forBaseAsset(self::credit($v)), 'baseAsset issuer'],
            'trades forCounterAsset' => [
                fn (StellarSDK $s, string $v) => $s->trades()->forCounterAsset(self::credit($v)),
                'counterAsset issuer'],
            'trades forLiquidityPool' => [fn (StellarSDK $s, string $v) => $s->trades()->forLiquidityPool($v),
                'liquidityPoolId'],
            'trades forAccount' => [fn (StellarSDK $s, string $v) => $s->trades()->forAccount($v), 'accountId'],
            'transactions transaction' => [fn (StellarSDK $s, string $v) => $s->transactions()->transaction($v),
                'transactionId'],
            'transactions forAccount' => [fn (StellarSDK $s, string $v) => $s->transactions()->forAccount($v),
                'accountId'],
            'transactions forLiquidityPool' => [
                fn (StellarSDK $s, string $v) => $s->transactions()->forLiquidityPool($v), 'liquidityPoolId'],
            'StellarSDK requestAccount' => [fn (StellarSDK $s, string $v) => $s->requestAccount($v), 'accountId'],
            'StellarSDK accountExists' => [fn (StellarSDK $s, string $v) => $s->accountExists($v), 'accountId'],
            'StellarSDK requestTransaction' => [fn (StellarSDK $s, string $v) => $s->requestTransaction($v),
                'transactionId'],
            'StellarSDK requestLiquidityPool' => [fn (StellarSDK $s, string $v) => $s->requestLiquidityPool($v),
                'liquidityPoolID'],
            'FriendBot fundTestAccount' => [
                fn (StellarSDK $s, string $v, Client $c) => FriendBot::fundTestAccount($v, $c), 'accountId'],
            'FuturenetFriendBot fundTestAccount' => [
                fn (StellarSDK $s, string $v, Client $c) => FuturenetFriendBot::fundTestAccount($v, $c), 'accountId'],
            'CustomFriendBot fundAccount' => [
                fn (StellarSDK $s, string $v, Client $c) => (new CustomFriendBot('https://friendbot.example.com'))
                    ->fundAccount($v, $c), 'accountId'],
        ];
    }

    #[DataProvider('guardedMethods')]
    public function testRefusesAValueStartingWithSBeforeAnyRequest(Closure $call, string $parameter): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $history = [];
            $client = self::mockedClient([], $history);
            try {
                $call(self::sdk($client), $variant['input'], $client);
                $this->fail('Accepted the ' . $name);
            } catch (InvalidArgumentException $e) {
                $this->assertSame($parameter . ' is ' . $variant['kind'] . '; the request is not sent',
                    $e->getMessage(), $name);
                $variants->assertNotShown($e, $name);
            }
            $this->assertSame([], $history, $name);
        }
    }

    /**
     * The methods that take a claimable balance id, whose decoding refuses the value.
     *
     * @return array<string, array{Closure(StellarSDK, string): mixed}>
     */
    public static function claimableBalanceMethods(): array
    {
        return [
            'claimable balances claimableBalance' => [
                fn (StellarSDK $s, string $v) => $s->claimableBalances()->claimableBalance($v)],
            'operations forClaimableBalance' => [
                fn (StellarSDK $s, string $v) => $s->operations()->forClaimableBalance($v)],
            'transactions forClaimableBalance' => [
                fn (StellarSDK $s, string $v) => $s->transactions()->forClaimableBalance($v)],
            'StellarSDK requestClaimableBalance' => [
                fn (StellarSDK $s, string $v) => $s->requestClaimableBalance($v)],
        ];
    }

    #[DataProvider('claimableBalanceMethods')]
    public function testClaimableBalanceIdStartingWithSIsRefusedBeforeAnyRequest(Closure $call): void
    {
        $variants = SecretSeedVariants::create();
        foreach ($variants->variants as $name => $variant) {
            $history = [];
            try {
                $call(self::sdk(self::mockedClient([], $history)), $variant['input']);
                $this->fail('Accepted the ' . $name);
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Claimable balance id must be a "B..." strkey or a hexadecimal string, '
                    . $variant['kind'] . ' given', $e->getMessage(), $name);
                $variants->assertNotShown($e, $name);
            }
            $this->assertSame([], $history, $name);
        }
    }

    public function testValidValuesReachTheUrlUnchanged(): void
    {
        $values = SecretSeedRedactionTest::validKeyValues();
        $g = $values['account (G...)'];
        $m = $values['muxed account (M...)'];
        $l = $values['liquidity pool (L...)'];
        $b = $values['claimable balance (B...)'];
        $hex = $values['hex id'];
        $history = [];
        $sdk = self::sdk(self::mockedClient([], $history));

        $this->assertSame('accounts/' . $g . '/effects?', $sdk->effects()->forAccount($g)->buildUrl());
        $this->assertSame('accounts/' . $m . '/payments?', $sdk->payments()->forAccount($m)->buildUrl());
        $this->assertSame('liquidity_pools/' . StrKey::decodeLiquidityPoolIdHex($l) . '/effects?',
            $sdk->effects()->forLiquidityPool($l)->buildUrl());
        $this->assertSame('liquidity_pools/' . $hex . '/trades?', $sdk->trades()->forLiquidityPool($hex)->buildUrl());
        $this->assertSame('transactions/' . $hex . '/operations?', $sdk->operations()->forTransaction($hex)->buildUrl());
        $this->assertSame('claimable_balances/' . XdrClaimableBalanceID::paddedBalanceIdHexFor($b) . '/transactions?',
            $sdk->transactions()->forClaimableBalance($b)->buildUrl());
        $this->assertSame('assets?asset_code=SEED&asset_issuer=' . $g,
            $sdk->assets()->forAssetCode('SEED')->forAssetIssuer($g)->buildUrl());
        $this->assertSame('offers?selling_asset_type=credit_alphanum4&selling_asset_code=SEED&selling_asset_issuer='
            . $g, $sdk->offers()->forSellingAsset(new AssetTypeCreditAlphanum4('SEED', $g))->buildUrl());
        $this->assertSame('accounts?signer=' . $g, $sdk->accounts()->forSigner($g)->buildUrl());
        $assets = [Asset::native(), new AssetTypeCreditAlphanum4('SEED', $g)];
        $keyedAssets = ['first' => $assets[0], 'second' => $assets[1]];
        $this->assertSame($sdk->findStrictSendPaths()->forDestinationAssets($assets)->buildUrl(),
            $sdk->findStrictSendPaths()->forDestinationAssets($keyedAssets)->buildUrl());
        $this->assertSame($sdk->findStrictReceivePaths()->forSourceAssets($assets)->buildUrl(),
            $sdk->findStrictReceivePaths()->forSourceAssets($keyedAssets)->buildUrl());
        $this->assertStringContainsString('destination_assets=native%2CSEED%3A' . $g,
            $sdk->findStrictSendPaths()->forDestinationAssets($keyedAssets)->buildUrl());
        $this->assertSame([], $history);
    }

    public function testValidValuesAreSent(): void
    {
        $values = SecretSeedRedactionTest::validKeyValues();
        $g = $values['account (G...)'];
        $hex = $values['hex id'];
        $notFound = fn () => new Response(404, [], json_encode(['status' => 404, 'title' => 'Resource Missing']));
        $history = [];
        $client = self::mockedClient([
            new Response(200, [], json_encode(['value' => base64_encode('note')])),
            $notFound(),
            $notFound(),
            new Response(200, [], '{}'),
        ], $history);
        $sdk = self::sdk($client);

        $this->assertSame('note', $sdk->accounts()->accountData($g, 'Secret note')->getDecodedValue());
        $this->assertFalse($sdk->accountExists($g));
        try {
            $sdk->requestTransaction($hex);
            $this->fail('A missing transaction was returned');
        } catch (HorizonRequestException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        $this->assertTrue(FriendBot::fundTestAccount($g, $client));

        $this->assertCount(4, $history);
        $this->assertSame('accounts/' . $g . '/data/Secret%20note', (string)$history[0]['request']->getUri());
        $this->assertSame('accounts/' . $g, (string)$history[1]['request']->getUri());
        $this->assertSame('transactions/' . $hex, (string)$history[2]['request']->getUri());
        $this->assertSame('https://friendbot.stellar.org/?addr=' . $g, (string)$history[3]['request']->getUri());
    }
}
