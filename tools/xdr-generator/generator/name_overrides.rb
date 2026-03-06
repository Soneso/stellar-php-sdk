# frozen_string_literal: true

# Maps XDR canonical (CamelCase) type names to PHP SDK class names.
#
# The generator's default convention is to prefix the CamelCase XDR name with
# "Xdr", producing names like "XdrTransaction". Only include entries here where
# that default produces the wrong name for the existing PHP SDK class.
#
# Phase 2 note: This skeleton covers the obvious mismatches. A full audit of
# all 309 types will be performed in Phase 2 to catch any remaining gaps.

NAME_OVERRIDES = {
  # AlphaNum4/12 -> XdrAssetAlphaNum4/12 (prefixed with "Asset")
  "AlphaNum4" => "XdrAssetAlphaNum4",
  "AlphaNum12" => "XdrAssetAlphaNum12",

  # TrustLineAsset -> XdrTrustlineAsset (lowercase 'l')
  "TrustLineAsset" => "XdrTrustlineAsset",

  # AccountEntryExtensionV* -> XdrAccountEntryV* (shortened form used in PHP SDK)
  "AccountEntryExtensionV1" => "XdrAccountEntryV1",
  "AccountEntryExtensionV2" => "XdrAccountEntryV2",
  "AccountEntryExtensionV3" => "XdrAccountEntryV3",

  # Nested extension types follow the same shortened pattern
  "AccountEntryExtensionV1Ext" => "XdrAccountEntryV1Ext",
  "AccountEntryExtensionV2Ext" => "XdrAccountEntryV2Ext",

  # ClaimableBalanceEntryExtensionV1 -> XdrClaimableBalanceEntryExtV1
  "ClaimableBalanceEntryExtensionV1" => "XdrClaimableBalanceEntryExtV1",

  # LedgerEntryExtensionV1 -> XdrLedgerEntryV1
  "LedgerEntryExtensionV1" => "XdrLedgerEntryV1",

  # TrustLineEntryExtensionV2 retains the full name in the PHP SDK
  "TrustLineEntryExtensionV2" => "XdrTrustLineEntryExtensionV2",

  # MuxedEd25519Account -> XdrMuxedAccountMed25519
  "MuxedEd25519Account" => "XdrMuxedAccountMed25519",

  # LiquidityPoolEntryBody -> XdrLiquidityPoolBody
  "LiquidityPoolEntryBody" => "XdrLiquidityPoolBody",

  # LiquidityPoolEntryConstantProduct -> XdrConstantProduct
  "LiquidityPoolEntryConstantProduct" => "XdrConstantProduct",

  # LedgerKeyTtl -> XdrLedgerKeyTTL (uppercase TTL)
  "LedgerKeyTtl" => "XdrLedgerKeyTTL",

  # TrustLineEntryExtV1 -> XdrTrustLineEntryV1
  "TrustLineEntryExtV1" => "XdrTrustLineEntryV1",

  # ManageSellOfferResult/Code -> XdrManageOfferResult/Code (shared with buy-offer)
  "ManageSellOfferResult" => "XdrManageOfferResult",
  "ManageSellOfferResultCode" => "XdrManageOfferResultCode",
  "ManageBuyOfferResult" => "XdrManageOfferResult",
  "ManageBuyOfferResultCode" => "XdrManageOfferResultCode",

  # PathPayment success inner types share a single PHP class
  "PathPaymentStrictReceiveResultSuccess" => "XdrPathPaymentResultSuccess",
  "PathPaymentStrictSendResultSuccess" => "XdrPathPaymentResultSuccess",

  # Operations: PHP SDK expands "Op" suffix to "Operation"
  "CreateAccountOp" => "XdrCreateAccountOperation",
  "PaymentOp" => "XdrPaymentOperation",
  "PathPaymentStrictReceiveOp" => "XdrPathPaymentStrictReceiveOperation",
  "PathPaymentStrictSendOp" => "XdrPathPaymentStrictSendOperation",
  "ManageSellOfferOp" => "XdrManageSellOfferOperation",
  "ManageBuyOfferOp" => "XdrManageBuyOfferOperation",
  "CreatePassiveSellOfferOp" => "XdrCreatePassiveSellOfferOperation",
  "SetOptionsOp" => "XdrSetOptionsOperation",
  "ChangeTrustOp" => "XdrChangeTrustOperation",
  "AllowTrustOp" => "XdrAllowTrustOperation",
  "AccountMergeOp" => "XdrAccountMergeOperation",
  "ManageDataOp" => "XdrManageDataOperation",
  "BumpSequenceOp" => "XdrBumpSequenceOperation",
  "CreateClaimableBalanceOp" => "XdrCreateClaimableBalanceOperation",
  "ClaimClaimableBalanceOp" => "XdrClaimClaimableBalanceOperation",
  "BeginSponsoringFutureReservesOp" => "XdrBeginSponsoringFutureReservesOperation",
  "RevokeSponsorshipOp" => "XdrRevokeSponsorshipOperation",
  "ClawbackOp" => "XdrClawbackOperation",
  "ClawbackClaimableBalanceOp" => "XdrClawbackClaimableBalanceOperation",
  "SetTrustLineFlagsOp" => "XdrSetTrustLineFlagsOperation",
  "LiquidityPoolDepositOp" => "XdrLiquidityPoolDepositOperation",
  "LiquidityPoolWithdrawOp" => "XdrLiquidityPoolWithdrawOperation",

  # Soroban operations keep the "Op" suffix (no expansion in PHP SDK)
  "InvokeHostFunctionOp" => "XdrInvokeHostFunctionOp",
  "ExtendFootprintTTLOp" => "XdrExtendFootprintTTLOp",
  "RestoreFootprintOp" => "XdrRestoreFootprintOp",

  # PathPayment result types retain their full names
  "PathPaymentStrictReceiveResult" => "XdrPathPaymentStrictReceiveResult",
  "PathPaymentStrictSendResult" => "XdrPathPaymentStrictSendResult",
  "PathPaymentStrictReceiveResultCode" => "XdrPathPaymentStrictReceiveResultCode",
  "PathPaymentStrictSendResultCode" => "XdrPathPaymentStrictSendResultCode",

  # AssetCode union -> XdrAllowTrustOperationAsset
  "AssetCode" => "XdrAllowTrustOperationAsset",

  # RevokeSponsorshipOpSigner -> XdrRevokeSponsorshipSigner
  "RevokeSponsorshipOpSigner" => "XdrRevokeSponsorshipSigner",

  # SignerKey nested payload type
  "SignerKeyEd25519SignedPayload" => "XdrSignedPayload",

  # SCP inline types (not yet in PHP SDK — these are for future new type generation)
  "SCPStatementPledgesPrepare" => "XdrSCPStatementPrepare",
  "SCPStatementPledgesConfirm" => "XdrSCPStatementConfirm",
  "SCPStatementPledgesExternalize" => "XdrSCPStatementExternalize",

  # ContractEvent inner struct naming
  "ContractEventV0" => "XdrContractEventBodyV0",

  # InnerTransactionResult shares inner union types with TransactionResult
  "InnerTransactionResultResult" => "XdrTransactionResultResult",
  "InnerTransactionResultExt" => "XdrTransactionResultExt",

  # Miscellaneous inline/nested types
  "LedgerEntryExtensionV1Ext" => "XdrLedgerEntryV1Ext",
  "TrustLineEntryExtV1Ext" => "XdrTrustLineEntryV1Ext",
  "OfferEntryExt" => "XdrOfferEntryExt",
  "ManageOfferSuccessResultOffer" => "XdrManageOfferSuccessResultOffer",
}.freeze
