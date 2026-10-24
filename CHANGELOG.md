# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `ContractCreditedEffectResponse` exposes the muxed contract destination through `getContractMuxed()` and `getContractMuxedId()` when Horizon reports a transfer or mint to a muxed contract address.
- `SorobanServer` and `URIScheme` accept an optional Guzzle `Client` as their last constructor parameter. `SorobanServer` sends its RPC requests through it, `URIScheme` its stellar.toml fetches and callback submissions, so applications can set timeouts, proxies and middleware.

### Changed
- The `InvokeContractHostFunction` constructor, `InvokeContractHostFunction::setContractId` and `AssembledTransaction::buildWithOp` accept only a `C...` strkey or 64 hexadecimal characters as the contract to invoke and reject any other id with an `InvalidArgumentException` such as "The contract to invoke must be a contract (C...) address, got a muxed contract (W...) address: W...; use its base contract (C...) address"; `AssembledTransaction::build` and `buildWithOp` reject it before any request, while `InvokeContractHostFunction::fromXdr` and `toXdr` decode and re-encode every address kind unchanged.
- `SorobanServer::loadContractCodeForContractId`, `SorobanServer::loadContractInfoForContractId` and `SorobanClient::forClientOptions` reject a contract id that is not a contract address before any request, with "The contract to load must be a contract (C...) address, got <kind> address: <id>".
- `SorobanServer::getContractData` rejects an owner that is not a contract address with "The contract data owner must be a contract (C...) address, got <kind> address: <id>", and a key that is or holds a muxed account or muxed contract address, inside vectors and maps at any depth, with "Contract data keys cannot contain muxed account (M...) or muxed contract (W...) addresses", both before any request.

### Deprecated
- `EventInfo::$inSuccessfulContractCall`: Stellar RPC 30 and later omit the field; the SDK still reads it from older servers.

### Fixed
- `InvokeContractHostFunction::toXdr` rejects an id that names no address with an `InvalidArgumentException` that names the id instead of raising a PHP Error.
- `TransferServerService::transaction` rejects a SEP-06 response without a `transaction` object with an `InvalidArgumentException` that names the missing member instead of raising a PHP warning and a `TypeError`.
- `TxRepHelper::unescapeString` passes a `\x` escape without two hex digits through literally without raising a PHP deprecation.
- `PaymentsRequestBuilder::stream` delivers `invoke_host_function` records (Stellar Asset Contract transfers) as `InvokeHostFunctionOperationResponse` instead of ending the stream with an `UnhandledMatchError`.
- `EventInfo` reads the operation and transaction indexes of each event returned by `SorobanServer::getEvents` from the `operationIndex` and `transactionIndex` fields stellar-rpc sends, so `getOpIndex()` and `getTxIndex()` return them instead of null.
- `StellarToml`, `fromDomain` and `currencyFromUrl` reject a field of the wrong type with an `Exception` that names the field and the type found instead of raising a `TypeError`.
- `currencyFromUrl` rejects currency TOML files with no fields, and `StellarToml` rejects non-empty `DOCUMENTATION` lists, with an `Exception` naming the URL or field and what was found.
- `LedgerEntry` reads the entry extension from the `extXdr` key that `getLedgerEntries` returns.
- `ContractSpec::nativeToXdrSCVal` converts u64, timepoint and duration values from non-negative integers or decimal strings across the full unsigned 64-bit range.

## [1.16.0] - 2026-10-14

Migration guide: [docs/migration/1.16.0.md](docs/migration/1.16.0.md)

### Added
- Muxed contract addresses (`W...`, CAP-0084).
  `StrKey::encodeMuxedContractId`, `decodeMuxedContractId`, and `isValidMuxedContractId` encode,
  decode, and validate the SEP-0023 strkey, whose payload is the 32-byte contract id followed by the
  8-byte big-endian id. `Address` gains `TYPE_MUXED_CONTRACT`, `getMuxedContractId`,
  `setMuxedContractId`, `fromMuxedContractId`, `fromMuxedContract`, and `fromStrKey`; `fromAnyId`
  accepts `W...`. `MuxedContract` takes a contract id as a `C...` strkey or 64 hexadecimal
  characters and an optional integer id. `getAddress` returns `W...` with an id and `C...` without;
  `fromAddress`, `fromXdrSCAddress`, `toXdrSCAddress`, and `toAddress` convert between
  representations. Ids above `PHP_INT_MAX` use negative integers in two's complement form.
  `XdrSCAddress::forMuxedContractId` builds the address, and `XdrSCVal::toNative` returns an
  `Address` for the new arm. TxRep writes a muxed contract as `.muxedContract.id` and
  `.muxedContract.contractId`. `VersionByte::MUXED_CONTRACT_ID` is `22 << 3`,
  `CryptoConstants::STRKEY_MUXED_CONTRACT_ID_LENGTH` is 69, and
  `StellarConstants::MUXED_CONTRACT_DECODED_LENGTH` is 40.
- The [SEP-23 compatibility matrix](compatibility/sep/SEP-0023_COMPATIBILITY_MATRIX.md) documents
  strkey format support and specification test vectors.

### Changed
- Breaking: `ContractSpec::nativeToXdrSCVal` and `funcArgsToXdrSCValues` accept `G...` or `C...`
  strkeys or account or contract `Address` objects for an `Address` parameter. A `MuxedAddress`
  parameter also accepts `M...` and `W...` strkeys or the corresponding `Address` objects. Callers
  passing a muxed address to an `Address` parameter must use its underlying account or contract
  address, or a contract function with a `MuxedAddress` parameter. Claimable balance and liquidity
  pool addresses, malformed strings, objects that cannot encode, and other value types throw
  `InvalidArgumentException` naming the parameter type and the value or its type. Prebuilt
  `XdrSCVal` values pass through for `Address` and `MuxedAddress` parameters; `null` is accepted
  only for void or optional types. Before this change, an `Address` parameter also accepted an
  `M...` strkey and passed any `Address` object through unchecked, and a `MuxedAddress` parameter
  threw for every string.
- Breaking: Soroban authorization rejects muxed account (`M...`) and muxed contract (`W...`)
  addresses with `InvalidArgumentException`. Use the underlying `G...` or `C...` address for
  credentials, signing targets, and delegates. `SorobanAuthorizationEntry::sign` checks the
  credential address and `forAddress`; `withDelegates` and the `SorobanCredentials` address
  factories check credentials and delegates. `AssembledTransaction::signAuthEntries` and
  `WebAuthForContracts::signAuthorizationEntries` check the credentials they sign before writing an
  expiration or invoking a signing callback. Before this change, an `M...` `forAddress` target or
  delegate was rejected with other messages, and a muxed credential address was signed without a
  check.
- Breaking: the SDK requires PHP 8.1 or higher; `composer.json` declares `php >=8.1`. Projects on
  PHP 8.0 stay on 1.15.0 until they move to 8.1. The floor was 8.0, a version without security
  support since 2023-11 and outside the CI matrix.
- XDR definitions support CAP-0084 muxed contract addresses and CAP-0087 ML-DSA cost types.
  `XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT` has value 5, and `XdrSCAddress` gains the
  `muxedContract` arm with an `XdrMuxedContract` whose constructor takes `id`, then `contractId`.
  `XdrContractCostType` gains nine members from `MlDsa44DecodeVerifyingKey` (86) through
  `VerifyMlDsa87Sig` (94). XDR-JSON renders a muxed contract as its `W...` strkey and adds the enum
  names `muxed_contract` and `ml_dsa44_decode_verifying_key` through `verify_ml_dsa87_sig`. Code
  that dispatches on these values must handle the new cases when receiving them. Before this change,
  decoding `SC_ADDRESS_TYPE_MUXED_CONTRACT` or a cost type from 86 to 94 threw
  `InvalidArgumentException` for an unknown enum value.
- XDR definitions support millisecond ledger close times (CAP-0088). `XdrStellarValueType` gains
  `STELLAR_VALUE_SIGNED_MS` (3) and `STELLAR_VALUE_EMPTY_TX_SET_MS` (4). `XdrStellarValueExt` gains
  `signedMsValue` (`XdrStellarValueSignedMsValue`) and `proposedMsValue`
  (`XdrStellarValueProposedMsValue`), carrying `closeTimeMs` as a PHP integer. XDR-JSON uses
  `signed_ms`, `empty_tx_set_ms`, and `close_time_ms`. Code that dispatches on these values must
  handle the new members; existing values and JSON keys retain their meaning. Before this change,
  decoding `STELLAR_VALUE_SIGNED_MS` or `STELLAR_VALUE_EMPTY_TX_SET_MS` threw
  `InvalidArgumentException` for an unknown enum value.
- SEP compatibility matrices state the specification version and status that the assessment uses.
- The Horizon compatibility matrix targets Horizon v29.0.0 and the Soroban RPC matrix targets
  stellar-rpc v29.0.1; both retain their reported coverage.
- The [SEP guide index](docs/sep/README.md) and [contract parser
  guide](docs/soroban.md#contract-parser) document SEP-46 metadata, SEP-47 interface discovery, and
  SEP-48 interface specifications. The parser example shows the declared SEP numbers in the
  `supportedSeps` property of `SorobanContractInfo`. The README lists 23 supported SEPs, and the
  agent skill documents the same three SEPs.
- The user documentation and the agent skill describe the behavior of this release: muxed contract
  addresses in every strkey list and section, the `ContractSpec` address rules, the address guards
  of Soroban authorization, the expiration rule of `signAuthEntries`, the host key order of map
  arguments, the pool-share asset order, the PHP 8.1 floor, the count of 31 streaming endpoints,
  the unsigned placeholder in the SEP-45 example, and the top-level signature rule of entries with
  delegates.
- The repository documents its build, test, generation and contribution rules in `AGENTS.md` and
  `CONTRIBUTING.md`; CI checks that every path `AGENTS.md` names exists.

### Fixed
- Breaking: `AssembledTransaction::signAuthEntries` and `SorobanAuthorizationEntry::sign` keep
  `signatureExpirationLedger` when any top-level or delegate node already carries a signature,
  preserving earlier signatures when another signer signs the entry. Callers must omit an explicit
  expiration or pass the stored value; a conflicting `validUntilLedgerSeq` or
  `signatureExpirationLedger` throws `InvalidArgumentException` naming both values.
  `signAuthEntries` fetches the latest ledger at most once, only for unsigned entries that need the
  default of the latest ledger plus 100. It signs copies and replaces the transaction's auth entries
  only after every matching entry signs successfully, so a failure leaves them unchanged.
  `SorobanAuthorizationEntry::resolveSignatureExpirationLedger` exposes the same expiration check.
  Before this change, `signAuthEntries` passed a fresh expiration ledger on every call. A second
  signer whose call computed a later default or passed another explicit value therefore signed a
  different payload than the first, and the host rejected the entry. A signature value of void or an
  empty vector counts as unsigned, which matches the placeholder js-stellar-sdk writes.
  `SorobanAuthorizationEntry::carriesSignature` exposes the check.
- Breaking: `XdrSCVal::forMap` and maps and map-encoded structs built by `ContractSpec` use the
  Soroban host's key order, fixing host rejection of arguments emitted in insertion order. Callers
  must supply distinct keys; keys equal in host order throw `InvalidArgumentException` naming the
  duplicate key. The public `ScValHostOrder::compare` comparator covers every value type, including
  signed integer and nonce ordering. Decoding preserves the wire order.
- Breaking: `AssetTypePoolShare` validates credit assets of the same type and code by the issuers'
  raw 32-byte public keys, matching stellar-core. Strkey text orders some issuer pairs differently.
  Construction and `Asset::fromXdrChangeTrustAsset` accept the correct order and reject the reversed
  pair with `RuntimeException` naming both assets. Before this change, the SDK used that text order,
  so for such a pair it rejected the order stellar-core accepts and accepted the reversed one.
- `TransactionInfo` reads `diagnosticEventsXdr` from each transaction returned by
  `SorobanServer::getTransactions`, exposing the server's diagnostic events. Before this change the
  property stayed null because the SDK read the absent `diagnostic_events` key.
- The [SEP-29 guide](docs/sep/sep-29.md) replaces vanished example accounts with variables whose
  comments describe the required funded accounts and memo settings, so callers can run the examples
  with their own accounts.
- `HorizonRequestException::__construct` and the `stream()` methods of the effects, ledgers,
  operations, payments, and transactions request builders declare their optional parameters as
  nullable types. PHP 8.4 and later reported an implicit-nullable deprecation when these classes
  loaded.
- `AssembledTransaction::sign` stops on an unsigned account at the top level of a
  `SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES` entry whatever its delegate nodes carry, and blocks
  on every unsigned account `needsNonInvokerSigningBy` reports. Before this change, the check
  treated such an account as satisfied when every delegate node carried a signature or the entry
  had no delegate nodes, so `sign` produced an envelope the host rejects when it authenticates the
  entry: an account address authenticates with its own signatures (CAP-71). A custom account
  contract at the top level never blocks.
