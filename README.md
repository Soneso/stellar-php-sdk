# [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk)

[![Latest Stable Version](https://poser.pugx.org/soneso/stellar-php-sdk/v/stable.svg)](https://packagist.org/packages/soneso/stellar-php-sdk)
[![Total Downloads](https://poser.pugx.org/soneso/stellar-php-sdk/downloads.svg)](https://packagist.org/packages/soneso/stellar-php-sdk)
[![codecov](https://codecov.io/gh/Soneso/stellar-php-sdk/branch/main/graph/badge.svg)](https://codecov.io/gh/Soneso/stellar-php-sdk)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/Soneso/stellar-php-sdk)

Build and sign Stellar transactions, query [Horizon](https://developers.stellar.org/docs/data/apis/horizon), and interact with [Soroban](https://developers.stellar.org/docs/build/smart-contracts/overview) smart contracts via RPC. Communicate with anchors and external services using built-in support for 18 SEPs.

## Installation

```bash
composer require soneso/stellar-php-sdk
```

Requires PHP 8.0+.

## Quick examples

### Send a payment

Transfer XLM between accounts:

```php
$payment = (new PaymentOperationBuilder($receiverId, Asset::native(), '100'))->build();
$tx = (new TransactionBuilder($account))->addOperation($payment)->build();
$tx->sign($senderKeyPair, Network::testnet());
$sdk->submitTransaction($tx);
```

### Trust an asset

Enable your account to receive a token (like USDC):

```php
$asset = Asset::createNonNativeAsset('USDC', $issuerAccountId);
$trustOp = (new ChangeTrustOperationBuilder($asset))->build();
$tx = (new TransactionBuilder($account))->addOperation($trustOp)->build();
$tx->sign($accountKeyPair, Network::testnet());
$sdk->submitTransaction($tx);
```

### Call a smart contract

Invoke a Soroban contract method:

```php
$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: $keyPair,
    contractId: 'CABC...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));
$result = $client->invokeMethod('hello', [XdrSCVal::forSymbol('World')]);
```

For complete walkthroughs, see the [documentation](docs/).

## Documentation

| Guide | Description |
|-------|-------------|
| [Quick start](docs/quick-start.md) | Your first transaction in 15 minutes |
| [Getting started](docs/getting-started.md) | Keys, accounts, and fundamentals |
| [SDK usage](docs/sdk-usage.md) | Transactions, operations, Horizon queries, streaming |
| [Soroban](docs/soroban.md) | Smart contract deployment and interaction |
| [SEPs](docs/sep/) | Anchor integration, authentication, KYC, etc. |

[API reference](https://soneso.github.io/stellar-php-sdk/)

## Compatibility

- [Horizon API compatibility matrix](compatibility/horizon/COMPATIBILITY_MATRIX.md)
- [RPC API compatibility matrix](compatibility/rpc/RPC_COMPATIBILITY_MATRIX.md)
- [SEP support matrices](compatibility/sep/)

## Feedback

If you're using this SDK, feedback helps improve it:

- [Report a bug](https://github.com/Soneso/stellar-php-sdk/issues/new?template=bug_report.yml)
- [Request a feature](https://github.com/Soneso/stellar-php-sdk/issues/new?template=feature_request.yml)
- [Start a discussion](https://github.com/Soneso/stellar-php-sdk/discussions)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

Apache 2.0. See [LICENSE](LICENSE).
