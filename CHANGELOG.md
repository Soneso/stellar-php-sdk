# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `XdrBuffer::assertFullyConsumed()` checks that a buffer holds no unread bytes.
- `DeploySACWithAssetHostFunction` takes optional constructor arguments; with an argument list, including an empty one, it writes `HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2`.
- `XdrJsonHelper::encodeText()` writes SEP-51 JSON text, and `XdrJsonHelper::JSON_DEPTH_LIMIT` holds its nesting limit of 1100 levels.
- `XdrBuffer::getPosition()` returns the number of bytes consumed from the buffer.

### Removed
- `XdrConfigUpgradeSetKeyBase`, `XdrInnerTransactionResultPairBase`, `XdrLiquidityPoolDepositOperationBase`, `XdrLiquidityPoolWithdrawOperationBase` and `XdrOperationResultTrBase`. The classes of the same name without `Base` are generated in full and keep their API.

### Fixed
- `KeyPair::fromBip39SeedHex()` throws an `InvalidArgumentException` without echoing the input when `$bip39SeedHex` is empty, has odd length, or contains non-hexadecimal characters. Before this change, odd-length or non-hexadecimal input raised a PHP warning and a `TypeError`, and the empty string was accepted.
- `SorobanServer::loadContractCodeForWasmId()` and `SorobanServer::loadContractInfoForWasmId()` throw an `InvalidArgumentException` without echoing the input before building any request when `$wasmId` is not 64 hexadecimal digits. Before this change, odd-length or non-hexadecimal input raised a PHP warning and a `TypeError`, and other wrong-length hexadecimal values failed later during XDR encoding.
- `BIP39::reverse()` throws an `Exception` without echoing the input before processing words when `$words` does not have a supported word count (12, 15, 18, 21, or 24) or does not match the instance's configured word count, also when checksum verification is disabled. Before this change, a wrong word count could produce odd-length hexadecimal entropy and raise a PHP warning and a `TypeError`, fail with a checksum error, or return a malformed mnemonic when checksum verification was off.
- `XdrJsonHelper::ksortRecursive()` throws an `InvalidArgumentException` when a value nests more arrays or objects than `$depth`, counting an empty one. Before this change, an empty array or object passed one level beyond `$depth`.
- `Price::fromString()` returns `false` for a value that is zero, negative, not a decimal number or not finite, and for a value whose continued fraction reaches a numerator or denominator above 2147483647. `ManageSellOfferOperationBuilder` and `ManageBuyOfferOperationBuilder` throw an `InvalidArgumentException` for such a price. Before this change, zero and text that is not a number raised a `DivisionByZeroError`, `1e-10` and `2147483648` gave a fraction outside the 32-bit range, and `1abc` gave 1/1.
- `TxRep::transactionEnvelopeXdrBase64FromTxRep()` throws an `InvalidArgumentException` that names the key for a `MEMO_HASH` or `MEMO_RETURN` value that is not 64 hexadecimal digits. Before this change, such a value raised a PHP warning or failed with a length error that did not name the key.
- A transaction read from a V0 envelope with `AbstractTransaction::fromEnvelopeXdr()`, `fromEnvelopeBase64XdrString()` or `Transaction::fromV0EnvelopeXdr()` writes a V0 envelope from `toEnvelopeXdr()` and `toEnvelopeXdrBase64()`. An unchanged transaction gives the bytes of the input. It writes a V1 envelope while it holds Soroban data or a precondition other than time bounds. The hash, `toXdr()` and the inner transaction of a fee bump keep the V1 form. Before this change, such a transaction was written as a V1 envelope.
- `TransactionResponse::fromJson()` reads a text memo from Horizon's `memo_bytes` field and returns its exact bytes; an invalid `memo_bytes` value throws an `InvalidArgumentException`. Before this change, a text memo with invalid UTF-8 or NUL bytes came back altered, and `fromJson()` threw when the altered text exceeded 28 bytes.
- `TransactionResponse::fromJson()` returns an id memo above `PHP_INT_MAX` as an unsigned decimal string, as `Memo::fromXdr()` does, and throws an `InvalidArgumentException` for a `memo` that is not an unsigned 64-bit integer. Before this change, every id from 2^63 to 2^64-1 became `PHP_INT_MAX`, and an invalid value such as `abc` or `1.5` became an integer.
- `fromBase64Xdr()` and the other readers of a complete XDR value throw when bytes follow the value: an `InvalidArgumentException`, or the `ContractChallengeValidationError` of `WebAuthForContracts::decodeAuthorizationEntries()`. Before this change, the trailing bytes were ignored.
- `XdrTransaction::fromTxRep()` and `XdrTransactionV0::fromTxRep()` build the transaction from valid TxRep. Before this change, they threw a `TypeError`.
- `XdrTimeBounds::fromTxRep()` returns its dates in UTC, as `decode()` does.
- `fromJson()` works on `XdrTransactionBase`, `XdrTransactionV0Base`, `XdrManageDataOperationBase` and `XdrTimeBoundsBase`.
- SEP-51 JSON of `XdrConfigUpgradeSetKey` and `XdrInnerTransactionResultPair` writes `content_hash` and `transaction_hash` as 64 hexadecimal characters. Before this change, it wrote 128, and the classes could not read the standard form.
- `XdrLiquidityPoolDepositOperation`, `XdrLiquidityPoolWithdrawOperation`, `XdrConfigUpgradeSetKey` and `XdrInnerTransactionResultPair` reject a malformed id or hash with an `InvalidArgumentException` in every writer. Before this change, a liquidity pool id or inner transaction hash that was too long was truncated, and one with non-hexadecimal characters was encoded as other bytes.
- A liquidity pool id given as an L address converts to SEP-51 JSON and is written to TxRep as hexadecimal, and `XdrConfigUpgradeSetKey` takes a C address as contract id. Before this change, `toJson()` and `encode()` threw for them, and TxRep wrote the L address.
- `TxRepHelper::hexToBytes()` and the TxRep readers built on it replace a secret seed inside an invalid hexadecimal value with `a secret seed (S...)` in the error message. Before this change, a value that held a quoted or prefixed secret seed appeared in full in the error.
- SEP-45 `WebAuthForContracts::jwtToken()` throws `ContractChallengeRequestErrorResponse` for a challenge response whose `authorization_entries` or `network_passphrase` is not a string, and `SubmitContractChallengeErrorResponseException` for a token response that is not a JSON object or array; a `token` or `error` value that is not a string counts as absent. Before this change, such responses threw a `TypeError`.
- SEP-10 `WebAuth::jwtToken()` throws `ChallengeRequestErrorResponse` for a challenge response without a string `transaction`, and `SubmitCompletedChallengeErrorResponseException` for a token response that is not a JSON object or array; a `token` or `error` value that is not a string counts as absent. `ChallengeResponse::getTransaction()` returns null for a response without a transaction. Before this change, such responses threw a PHP `Error` or `TypeError`.
- A response body that is valid JSON but neither an object nor an array, such as a string, a number or `null`, throws the same exception as an invalid JSON body. Before this change, it threw a `TypeError`.
- SEP-10 `WebAuth::jwtToken()` and `ChallengeRequestBuilder::forMemo()` take a memo id from 2^63 up as an unsigned decimal string, as `Memo::id()` does, and reject a negative id. Before this change, the memo was an int, so such an id could not be requested, and a negative int was sent unchanged.
- `XdrSponsorshipDescriptor` encodes and decodes its optional account id with the presence flag and takes a nullable key, and `XdrTxAdvertVector` and `XdrTxDemandVector` encode and decode their elements as 32-byte hashes. Before this change, the three classes did not read or write the XDR form of their types.
- `XdrDataValue::encode()` writes an empty value and `"0"` as present. It previously wrote both as absent; manage data operations were not affected.
- A transaction decoded from a V1 or fee bump envelope keeps `PRECOND_V2` preconditions, so its hash and its re-encoded envelope equal the original. Before this change, `PRECOND_V2` preconditions whose values were all defaults were rebuilt as `PRECOND_TIME` or `PRECOND_NONE`, a minimum sequence age from 2^63 up was dropped, and the hash differed from the network hash.
- Muxed ids from 2^63 up are readable as unsigned values: `MuxedAccount::getIdAsString()` and `MuxedContract::getIdAsString()` return the id as a decimal string. Before this change, `getId()` was the only accessor; it returns such ids as negative integers in two's complement form.
- SEP-51 JSON emission writes unsigned 64-bit values from 2^63 up as decimal strings. Before this change, `toJson()` threw for a memo id, a time bound or a duration in that range.
- `TimeBounds::getMinTimeAsString()`, `TimeBounds::getMaxTimeAsString()` and `TransactionPreconditions::getMinSeqAgeAsString()` return values from 2^63 up as unsigned decimal strings. Before this change, the only getters returned such a time bound as a date before 1970 and such an age as a negative integer.
- SEP-10 challenge validation rejects time bounds from 2^63 up. Before this change, a challenge with such a minimum time passed the time bounds check.
- Negative 64-bit XDR values held as `BigInteger` read as negative. Before this change, a decoded -1 read as 18446744073709551615, and TxRep and SEP-51 JSON wrote that number.
- On PHP 8.1, `XdrTimeBounds` keeps a time point of exactly 2^63 seconds when it decodes XDR or reads SEP-51 JSON. Before this change, it read the value as 2^63+1, so the re-encoded transaction differed.
- `GetTransactionResponse::getXdrTransactionMeta()` decodes `resultMetaXdr`. Before this change, it decoded `resultXdr` and threw or returned a wrong object.
- XDR decoding, encoding, TxRep, and SEP-51 JSON input reject arrays, strings, and opaque values longer than the maximum their XDR definition declares, and `TransactionBuilder::build()` rejects more than 100 operations. Before this change, the SDK accepted such values, for example a transaction with 101 operations or a 29-byte memo text.
- `SetOptionsOperation` writes a clear flags or set flags value of 0 and a home domain of "" or "0". Before this change, it dropped them, so clearing a home domain with "" sent nothing and an imported transaction with such a value got another hash.
- `AbstractTransaction::fromEnvelopeXdr()`, `fromEnvelopeBase64XdrString()` and `AbstractOperation::fromXdr()` read an inflation operation as the new `InflationOperation`, which encodes it unchanged. Before this change, they threw "Unknown operation type: 9", so a transaction with inflation could not be read or hashed.
- XDR decoding rejects a string or variable-length opaque value whose padding bytes are not zero. Before this change, such a value decoded and re-encoded with zero padding, so two different byte strings decoded to the same value.
- `XdrDecoder::opaqueVariable()` and `XdrDecoder::string()` throw `InvalidArgumentException` for a length prefix, value, or padding that is cut off. Before this change, a cut-off length prefix raised a PHP warning and a `TypeError`, and a cut-off value or padding returned the available bytes.
- `DeploySACWithAssetHostFunction` keeps a `HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2` host function with its constructor arguments when it reads one. Before this change, it wrote it back as `HOST_FUNCTION_TYPE_CREATE_CONTRACT`, so an imported transaction that deploys a Stellar Asset Contract this way got another hash.
- `toJson()` and `fromJson()` write and read SEP-51 JSON for every value the binary decoder accepts, nested up to 1100 levels, and throw a `JsonException` for deeper JSON. Before this change, JSON nested deeper than 512 levels could be neither written nor read.
- `XdrLedgerKey` XDR-JSON writes and reads the claimable balance and config setting keys with their `balance_id` and `config_setting_id` members, also inside transaction and ledger metadata. Before this change, it wrote both keys without the member and refused the form with it.
- `XdrLedgerKey` TxRep writes and reads a config setting key under `configSetting.configSettingID`. Before this change, it wrote and read the key without its member name.
- `ManageSellOfferOperation`, `ManageBuyOfferOperation`, and their builders accept any 64-bit signed offer id, so `AbstractOperation::fromXdr()` and `AbstractTransaction::fromEnvelopeBase64XdrString()` read, hash, and re-encode operations with a negative offer id. Before this change, a negative offer id threw `InvalidArgumentException`.
- `XdrLedgerKey` writes SEP-51 JSON and TxRep for a pool id given through `forLiquidityPoolId()` or `setLiquidityPoolID()`, and `fromJson()` and `fromTxRep()` set `getLiquidityPoolID()`, as `decode()` does. Before this change, `toJson()` and `toTxRep()` threw an `Error` for such a key, and `getLiquidityPoolID()` returned null after the two readers.
- `XdrLedgerKey` rejects a pool id given through `forLiquidityPoolId()` or `setLiquidityPoolID()` that is neither an L address nor 64 hexadecimal characters with an `InvalidArgumentException` in every writer. Before this change, `encode()` threw a `TypeError` for a pool id with non-hexadecimal characters.
- `XdrContractIDPreimage` writes SEP-51 JSON and TxRep for a preimage built with `forAddress()` or `forAsset()`, and `fromJson()` and `fromTxRep()` set `getAddress()`, `getSalt()` and `getAsset()`, as `decode()` does. Before this change, `toJson()` and `toTxRep()` threw an `Error` or a `TypeError` for such a preimage, and the three getters returned null after the two readers.
- An `InvokeHostFunctionOperation` that creates a contract or deploys a Stellar Asset Contract writes SEP-51 JSON and TxRep, and one read from them converts with `AbstractOperation::fromXdr()`. Before this change, the writers threw an `Error` or a `TypeError`, and `fromXdr()` threw an invalid argument `Exception`.
- `XdrOperationResultTr` writes SEP-51 JSON for a manage buy offer result set through `setManageOfferResult()`, and `fromJson()` sets `getManageOfferResult()` for a manage buy offer result, as `decode()` does. Before this change, `toJson()` threw an `Error`, and `getManageOfferResult()` returned null after `fromJson()`.
- The SEP-51 JSON and TxRep writers refuse a fixed-length opaque value whose length differs from its XDR definition, with an `InvalidArgumentException`. This includes an asset code longer than 4 or 12 bytes. The readers refuse such a value, except the TxRep readers that keep the hexadecimal text, such as for a liquidity pool id, which `encode()` checks. Before this change, the writers and the hexadecimal readers accepted such values.
- `URIScheme::signAndSubmitTransaction()` posts the signed transaction envelope to a `url:` callback in the form field `xdr`, URL-encoded once, as SEP-7 requires. Before this change, the field was URL-encoded twice, so a callback server read percent-encoded text instead of the base64 envelope.
- `URIScheme::signAndSubmitTransaction()` submits a transaction without a callback to the Horizon of the signing network: public, testnet, or futurenet. For any other network passphrase it throws `InvalidArgumentException`. Before this change, a transaction signed for futurenet or a custom network went to the public Horizon.
- `URIScheme::signAndSubmitTransaction()` throws `InvalidArgumentException` for a `callback` that does not start with `url:`, including an empty one, and sends nothing. Before this change, it submitted the transaction to Horizon.
- `URIScheme::getParameterValue()` returns null for a URI without a query, a URI that cannot be parsed, or a parameter given as an array, so `signAndSubmitTransaction()` throws its documented `InvalidArgumentException`. Before this change, these URIs raised a `TypeError`.
- `StellarSDK`, `SorobanServer`, the SEP clients, and the SEP-7 callback refuse a service URL that cannot be parsed or an `https` URL without a host with `InvalidArgumentException`. Before this change, a URL that could not be parsed raised a `TypeError`, and the check accepted an `https` URL without a host.
- `SorobanContractParser::parseContractByteCode()` reads contract byte code by its wasm sections and throws `SorobanContractParserException` with the byte offset for an invalid wasm header, section or entry. Before this change, it returned a shortened spec without an error when a spec entry did not decode, and threw for an empty spec section.
- `SorobanContractInfo::$metaEntries` combines the entries of every `contractmetav0` section in module order, with the values of repeated `sep` entries joined by a comma as SEP-47 defines, and `$supportedSeps` lists the SEPs among them. Before this change, the entries of later `contractmetav0` sections were lost, and only the last `sep` value of the first section counted.
- `SorobanClient::deploy()` and `SorobanClient::deployFromExternalRef()` throw `SorobanContractParserException` before they build the deployment transaction when they cannot parse the code entry from the RPC. Before this change, they ignored the parser error and went on with the deployment.

## [1.18.0] - 2026-11-14

### Added
- `ReportedContractError` and `SimulateTransactionResponse::reportedContractError()` return an immutable code, provenance and optional emitting contract ID selected from the host header or diagnostic events.
- `SorobanSimulationException` carries an immutable base64 diagnostic-event snapshot, the reported contract error and the original JSON-RPC error when a Soroban client simulation fails.
- `LedgerEntry::getLedgerKeyXdr()` decodes the response key as an `XdrLedgerKey`.
- `SACClient` opens a Stellar Asset Contract from an `Asset` or a contract id without an RPC request. It has typed `balance`, `transfer`, `transferAndSend`, `decimals`, `name`, and `symbol`, `toBaseUnits()` for decimal amounts, and a read-only mode without a keypair.
- `Asset::getContractId()` derives the asset's Stellar Asset Contract id.
- `SACSpec` embeds the Stellar Asset Contract spec at upstream commit 4f08d14.
- `SorobanClient::forSpecEntries()` builds a client from spec entries without an RPC request. With `readOnly: true`, carried in `AssembledTransactionOptions::$readOnly`, its transactions simulate without loading the source account and refuse to sign and to send.

### Changed
- `SorobanClient::forClientOptions()` opens Stellar Asset Contracts with the embedded spec.
- The user documentation and the agent skill describe the Stellar Asset Contract client and reading the reported contract error of a failed simulation from `SorobanSimulationException`. The skill lists that exception in its exception hierarchy and names the messages the TxRep parser throws. After a restore, its example says to rebuild the original transaction from a freshly loaded account.

### Deprecated
- `LedgerEntry::getKeyXdr()` decodes the ledger key bytes as an `XdrSCVal`, which does not yield the key; use `getLedgerKeyXdr()`.

### Fixed
- Soroban client simulation failures include reported contract errors hidden by constructor or authorization failures in their messages when diagnostic events provide them.
- Soroban client JSON-RPC simulation failures name the RPC code and message and retain the original RPC error.
- TxRep parsing rejects a malformed integer or a value outside the field's XDR width with an `InvalidArgumentException` that names the field key and the text, replaces anything in the text that may be a secret seed with `a secret seed (S...)`, and reads and writes uint64 values at or above 2^63 unsigned.
- Binary XDR decoding of the self-containing types (`XdrSCVal`, `XdrClaimPredicate`, `XdrSCSpecTypeDef`, `XdrSorobanAuthorizedInvocation` and others) stops at 500 nested levels with an `InvalidArgumentException`, and a failed decode leaves the buffer's depth counter unchanged.
- `ContractSpec::nativeToXdrSCVal` rejects a u32 or i32 integer outside its range and a `BytesN` string of another byte length with an `InvalidArgumentException` that names the value or length and the bound.
- `ContractSpec::nativeToXdrSCVal` and `funcArgsToXdrSCValues` convert a struct, union, or enum value when an earlier function, event, or error enum has the same name, instead of throwing an `InvalidArgumentException` "failed to parse udt".
- `AssembledTransaction::send` and `signAndSend` poll every three seconds until `timeoutInSeconds`, retry status requests that fail in transport or with an RPC error, bound each request by the remaining time and the client's own limits, and name the last error when polling expires.
- Errors for an invalid address, id, signer key, wasm hash, or hex string leave out a value that starts with `S` or `s`. They name it as `a secret seed (S...)` or as a value starting with S. Errors that show other caller text replace each run of 40 or more letters and digits that starts with `S` or `s` with `a secret seed (S...)`. This also covers a seed with a changed, missing, or added character. Such text includes amounts, asset codes and types, federation addresses, executable tags, duplicate map keys, pool share assets, SEP-51 JSON numbers and keys, and TxRep values and field keys. It also includes the names of Soroban functions, methods, and enum cases. The `ContractSpec` u64, timepoint, and duration conversion errors use `a secret seed (S...)` in place of `[redacted seed]`.
- `TxRepHelper::parseTrustlineAsset` and `TxRep::transactionEnvelopeXdrBase64FromTxRep` throw only an `InvalidArgumentException` for a trust line asset of 64 characters that is not a hexadecimal pool id, and raise no PHP warning before it.
- `AssembledTransaction::simulate` refreshes the source sequence after automatic restore, preserves operations, memo, preconditions and inclusion fee, and re-simulates the rebuilt transaction so it is ready to sign and send.

### Security
- The Horizon request builders, the `StellarSDK` methods that use them, and the FriendBot helpers throw an `InvalidArgumentException` before any request for a value that starts with `S` or `s`. This holds for an account id, signer, sponsor, claimant, asset issuer, liquidity pool id, or transaction hash, so a secret seed passed by mistake is not sent.

## [1.17.0] - 2026-10-24

### Added
- `ContractCreditedEffectResponse` exposes the muxed contract destination through `getContractMuxed()` and `getContractMuxedId()` when Horizon reports a transfer or mint to a muxed contract address.
- `SorobanServer` and `URIScheme` accept an optional Guzzle `Client` as their last constructor parameter. `SorobanServer` sends its RPC requests through it, `URIScheme` its stellar.toml fetches and callback submissions, so applications can set timeouts, proxies and middleware.

### Changed
- The `InvokeContractHostFunction` constructor, `InvokeContractHostFunction::setContractId` and `AssembledTransaction::buildWithOp` accept only a `C...` strkey or 64 hexadecimal characters as the contract to invoke and reject any other id with an `InvalidArgumentException` such as "The contract to invoke must be a contract (C...) address, got a muxed contract (W...) address: W...; use its base contract (C...) address"; `AssembledTransaction::build` and `buildWithOp` reject it before any request, while `InvokeContractHostFunction::fromXdr` and `toXdr` decode and re-encode every address kind unchanged.
- `SorobanServer::loadContractCodeForContractId`, `SorobanServer::loadContractInfoForContractId` and `SorobanClient::forClientOptions` reject a contract id that is not a contract address before any request with an `InvalidArgumentException` such as "The contract to load must be a contract (C...) address, got <kind> address: <id>"; a muxed contract id gets the advice to use its base contract (C...) address, and the message names a secret seed by its kind only.
- `SorobanServer::getContractData` rejects an owner that is not a contract address with an `InvalidArgumentException` such as "The contract data owner must be a contract (C...) address, got <kind> address: <id>", and a key that is or holds a muxed account or muxed contract address, inside vectors and maps at any depth, with "Contract data keys cannot contain muxed account (M...) or muxed contract (W...) addresses", both before any request.
- The user documentation and the agent skill cover the contract id forms and key rule of `SorobanServer::getContractData`, the optional Guzzle client of `SorobanServer` and `URIScheme`, and the six response classes of the payments stream. They name the stellar.toml errors for a mistyped field, a non-empty `DOCUMENTATION` list, or an empty currency file, and the malformed-response error of `TransferServerService::transaction`. They list the unsigned 64-bit input forms of `ContractSpec` and attribute the `W...` strkey to SEP-23. The offer and effect examples call `getOfferId()`, `Asset::canonicalForm()`, and `getHumanReadableEffectType()`.

### Deprecated
- `EventInfo::$inSuccessfulContractCall`: Stellar RPC 30 and later omit the field; the SDK still reads it from older servers.

### Fixed
- `InvokeContractHostFunction::toXdr` rejects an id that names no address with an `InvalidArgumentException` that names the id instead of raising a PHP Error.
- `TransferServerService::transaction` rejects a SEP-6 response without a `transaction` object with an `InvalidArgumentException` that names the missing member instead of raising a PHP warning and a `TypeError`.
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
