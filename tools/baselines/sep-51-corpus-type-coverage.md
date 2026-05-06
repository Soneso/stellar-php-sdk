# SEP-51 corpus type-coverage report

**Date:** 2026-05-04
**Corpus path:** `tools/sep-51-fixtures/corpus.json`

This report tracks transitive type coverage of the SEP-51 snapshot fixture corpus. Each XDR type that will receive a `toJson` method is listed with a coverage status:

- `[x] covered` — at least one corpus entry directly exercises the type at the top level.
- `[~] indirect` — covered as an inner type of another corpus entry's container (e.g. SCVal arms inside a TransactionEnvelope).
- `[ ] uncovered` — no corpus entry covers this type; covered by per-arm/per-member tests in Phase 5a.
- `[ ] uncovered (intentional)` — explicitly excluded from corpus; rationale recorded.

The report is populated by hand at corpus authoring time and re-checked at every Phase 5b acceptance. Initial Phase 0 commit lists every BASE_WRAPPER_TYPES entry, every Cat-A inline target, and the high-impact union/struct types as `[ ] uncovered (intentional — covered by per-arm tests in Phase 5a)`. As corpus entries land, statuses flip to `[x] covered` or `[~] indirect`.

---

## BASE_WRAPPER_TYPES (Cat B; 34 entries)

- [x] covered `XdrAccountID` (`account_id_g`, `account_id_g_alt`)
- [~] indirect `XdrAllowTrustOperationAsset` (covered via `operation_allow_trust`)
- [~] indirect `XdrAssetAlphaNum12` (covered via Asset alphanum12 fixtures)
- [~] indirect `XdrAssetAlphaNum4` (covered via Asset alphanum4 fixtures)
- [~] indirect `XdrChangeTrustAsset` (covered via `operation_change_trust`)
- [~] indirect `XdrChangeTrustOperation` (covered via `operation_change_trust`)
- [x] covered `XdrClaimableBalanceID` (`claimable_balance_id_v0`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrConfigUpgradeSetKey`
- [~] indirect `XdrContractExecutable` (covered via `scval_contract_instance_wasm` and `scval_contract_instance_stellar_asset`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrContractIDPreimage`
- [x] covered `XdrDecoratedSignature` (`decorated_signature`)
- [~] indirect `XdrHostFunction` (covered via `operation_invoke_host_function_upload`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrInnerTransactionResultPair`
- [x] covered `XdrLedgerKey` (`ledger_key_account`)
- [~] indirect `XdrLedgerKeyAccount` (covered via `ledger_key_account`)
- [~] indirect `XdrLiquidityPoolDepositOperation` (covered via `operation_liquidity_pool_deposit`)
- [~] indirect `XdrLiquidityPoolWithdrawOperation` (covered via `operation_liquidity_pool_withdraw`)
- [~] indirect `XdrManageDataOperation` (covered via `operation_manage_data`)
- [x] covered `XdrMuxedAccount` (`muxed_account_ed25519`, `muxed_account_med25519`)
- [~] indirect `XdrMuxedAccountMed25519` (covered via `muxed_account_med25519`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrOperationResultTr`
- [~] indirect `XdrSCAddress` (covered via SCVal address fixtures)
- [x] covered `XdrSCSpecEntry` (5 entries: function_v0, udt_struct_v0, udt_union_v0, udt_enum_v0, udt_error_enum_v0)
- [~] indirect `XdrSCSpecTypeDef` (covered via SCSpecEntry fixtures)
- [~] indirect `XdrSCSpecUDTUnionCaseV0` (covered via `sc_spec_entry_udt_union_v0`)
- [x] covered `XdrSCVal` (133 entries spanning every arm including SCV_CONTRACT_INSTANCE)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrSignerKeyType`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrSorobanAuthorizedFunction`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrSorobanCredentials`
- [x] covered `XdrTimeBounds` (`time_bounds_zero`, `time_bounds_typical`)
- [~] indirect `XdrTransaction` (covered via `transaction_envelope_canonical`)
- [x] covered `XdrTransactionEnvelope` (`transaction_envelope_canonical`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrTransactionV0`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrTrustlineAsset`

## Cat-A inline targets (15 entries)

- [x] covered `XdrAsset` (10 entries spanning native, alphanum4, alphanum12 arms)
- [x] covered `XdrInt128Parts` (`int128_zero`, `int128_negative_one`)
- [x] covered `XdrInt256Parts` (`int256_zero`)
- [x] covered `XdrLedgerBounds` (`ledger_bounds_zero`, `ledger_bounds_typical`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrLiquidityPoolEntry`
- [x] covered `XdrMemo` (`memo_none`, `memo_text`, `memo_text_non_ascii`, `memo_id`, `memo_hash`, `memo_return`)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrNodeID`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrPreconditions`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrPreconditionsV2`
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrPublicKey`
- [x] covered `XdrSignedPayload` (`signed_payload_standalone`)
- [x] covered `XdrSignerKey` (4 arms: ed25519, pre_auth_tx, hash_x, ed25519_signed_payload)
- [ ] uncovered (intentional — covered by per-arm tests in Phase 5a) `XdrSorobanResources`
- [x] covered `XdrUInt128Parts` (`uint128_zero`, `uint128_one`, `uint128_typical`)
- [x] covered `XdrUInt256Parts` (`uint256_zero`, `uint256_one`)

## High-impact union arms (Phase 5b corpus targets)

These types receive multiple corpus entries — one per arm or member — at corpus authoring time:

- [x] covered Operation type variants — every OperationBody arm now has a corpus fixture: `operation_create_account`, `operation_payment`, `operation_path_payment_strict_receive`, `operation_manage_sell_offer`, `operation_create_passive_sell_offer`, `operation_set_options_minimal`, `operation_change_trust`, `operation_allow_trust`, `operation_account_merge`, `operation_inflation`, `operation_manage_data`, `operation_bump_sequence`, `operation_manage_buy_offer`, `operation_path_payment_strict_send`, `operation_create_claimable_balance`, `operation_claim_claimable_balance`, `operation_begin_sponsoring_future_reserves`, `operation_end_sponsoring_future_reserves`, `operation_revoke_sponsorship_ledger_entry`, `operation_clawback`, `operation_clawback_claimable_balance`, `operation_set_trust_line_flags`, `operation_liquidity_pool_deposit`, `operation_liquidity_pool_withdraw`, `operation_invoke_host_function_upload`, `operation_extend_footprint_ttl`, `operation_restore_footprint` (27 arms total).
- [x] covered SCVal arms — all 22 arms exercised including SCV_CONTRACT_INSTANCE (`scval_contract_instance_wasm`, `scval_contract_instance_stellar_asset`).
- [ ] uncovered LedgerEntry data arms (account, trustline, offer, data, claimableBalance, liquidityPool, contractData, contractCode, configSetting, ttl) — covered indirectly via BucketEntry LIVEENTRY/INITENTRY (`bucket_entry_liveentry_account`, `bucket_entry_initentry_account`); per-arm coverage in Phase 5a.
- [ ] uncovered LedgerKey arms (account, trustline, offer, data, claimableBalance, liquidityPool, contractData, contractCode, configSetting, ttl) — partially covered via `ledger_key_account` and BucketEntry DEADENTRY / HotArchiveBucketEntry HOT_ARCHIVE_LIVE; per-arm coverage in Phase 5a.

## Spec-anchor coverage (SEP-0051 Examples)

- [x] covered SEP-0051 §Examples > TransactionEnvelope canonical example (`transaction_envelope_canonical`)
- [x] covered SEP-0051 §Specification > Integer (multiple `scval_i32_*` entries)
- [x] covered SEP-0051 §Specification > Unsigned Integer (multiple `scval_u32_*` entries)
- [x] covered SEP-0051 §Specification > Hyper Integer (multiple `scval_i64_*` entries)
- [x] covered SEP-0051 §Specification > Unsigned Hyper Integer (multiple `scval_u64_*` entries)
- [x] covered SEP-0051 §Specification > Boolean (`scval_bool_true`, `scval_bool_false`)
- [ ] uncovered SEP-0051 §Specification > Opaque (fixed) — covered indirectly via Memo hash arms
- [x] covered SEP-0051 §Specification > Opaque (variable) (multiple `scval_bytes_*` entries)
- [x] covered SEP-0051 §Specification > String (multiple `scval_string_*` entries)
- [ ] uncovered SEP-0051 §Specification > Array (fixed) — covered indirectly via SignerKey ed25519 arm (32-byte fixed)
- [x] covered SEP-0051 §Specification > Array (variable) (`scval_vec_*` entries)
- [ ] uncovered SEP-0051 §Specification > Enum — covered by per-arm Phase 5a tests
- [x] covered SEP-0051 §Specification > Struct (TransactionEnvelope canonical)
- [x] covered SEP-0051 §Specification > Discriminated Union (void/non-void/multi-void/int-cased) (`memo_none` void; SCVal arms non-void; LedgerCloseMeta v0/v1/v2 int-cased; BucketEntry mixed)
- [ ] uncovered SEP-0051 §Specification > Optional Data — Phase 5a per-struct
- [ ] uncovered SEP-0051 §Specification > `$schema` strip-then-dispatch (positive + negative) — Phase 5b NegativeInputTest

## Boundary fixtures

- [x] covered AssetCode4 with byte 0x80+ (non-ASCII; spec escape ladder vs py crash) (`asset_alphanum4_non_ascii`)
- [x] covered AssetCode12 3-byte-with-padding (right-pad to 5 NULs case) (`asset_alphanum12_3byte_padded`)
- [x] covered AssetCode12 5-byte (no padding case) (`asset_alphanum12_5byte`)
- [x] covered AssetCode12 12-byte (full case) — covered by AssetCode12 6-byte/11-byte/CUSTOM/TWELVECHARS entries
- [x] covered Memo text non-ASCII (`memo_text_non_ascii`)
- [x] covered String non-ASCII (`scval_string_non_ascii`)
- [x] covered LedgerCloseMeta v0 (`ledger_close_meta_v0`)
- [x] covered LedgerCloseMeta v1 (`ledger_close_meta_v1`)
- [x] covered LedgerCloseMeta v2 (`ledger_close_meta_v2`)
- [x] covered BucketEntry variants (`bucket_entry_metaentry`, `bucket_entry_deadentry`, `bucket_entry_liveentry_account`, `bucket_entry_initentry_account`)
- [x] covered HotArchiveBucketEntry variants (`hot_archive_bucket_entry_metaentry`, `hot_archive_bucket_entry_archived`, `hot_archive_bucket_entry_live`)
- [x] covered SCSpecEntry variants (`sc_spec_entry_function_v0`, `sc_spec_entry_udt_struct_v0`, `sc_spec_entry_udt_union_v0`, `sc_spec_entry_udt_enum_v0`, `sc_spec_entry_udt_error_enum_v0`)
- [x] covered ConfigSettingEntry variants (10 entries: `config_setting_entry_max_size_bytes` through `config_setting_entry_state_size_window`)

---

## Refresh protocol

This document is updated at every Phase 5b acceptance: each corpus entry that lands flips its primary type's status from `[ ]` to `[x]`, and contributes `[~]` flips for every inner type it transitively covers. The Phase 5b reviewer (test-automator dimension) verifies the updated coverage matches the corpus state.
