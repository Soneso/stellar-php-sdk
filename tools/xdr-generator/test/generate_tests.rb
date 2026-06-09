# Auto-generates PHPUnit round-trip tests for all XDR types.
#
# Modeled after the Flutter SDK's generate_tests.rb.
# Produces one test file per .x source file in:
#   Soneso/StellarSDKTests/Unit/Xdr/Generated/
#
# Usage:
#   cd tools/xdr-generator && bundle exec ruby test/generate_tests.rb

require 'set'
require 'xdrgen'
require_relative '../generator/name_overrides'
require_relative '../generator/member_overrides'
require_relative '../generator/field_overrides'
require_relative '../generator/type_overrides'
require_relative '../generator/txrep_types'

AST = Xdrgen::AST

# Maximum recursion depth for constructing nested test values.
MAX_DEPTH = 3

# Test account ID used for XdrAccountID fields.
TEST_ACCOUNT_ID = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H"

# PHP reserved words (same as generator).
PHP_RESERVED_WORDS = %w[
  abstract array as break callable case catch class clone const continue
  declare default do echo else elseif empty enum eval exit extends final
  finally fn for foreach function global goto if implements include
  instanceof insteadof interface isset list match namespace new null or
  print private protected public readonly require return static switch
  throw trait try unset use var while xor yield true false int float
  string bool void never mixed
].freeze

# ---------------------------------------------------------------------------
# Fallback test values for complex types that can't be auto-constructed.
# Each entry maps a PHP class name to a PHP expression string.
# ---------------------------------------------------------------------------
FALLBACK_VALUES = {
  "XdrAccountID" => "XdrAccountID::fromAccountId('#{TEST_ACCOUNT_ID}')",
  "XdrMuxedAccount" => "new XdrMuxedAccount(str_repeat(\"\\xAB\", 32))",
  "XdrPublicKey" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return $pk; })()",
  "XdrSequenceNumber" => "new XdrSequenceNumber(new BigInteger('123456789'))",
  "XdrTimeBounds" => "new XdrTimeBounds(new \\DateTime('@1000'), new \\DateTime('@2000'))",
  "XdrClaimableBalanceID" => "new XdrClaimableBalanceID(new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0), str_repeat('ab', 32))",
  "XdrSponsorshipDescriptor" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrSponsorshipDescriptor($pk); })()",
  "XdrManageDataOperation" => "new XdrManageDataOperation('test_key', new XdrDataValue(\"\\x01\\x02\\x03\\x04\"))",
  "XdrSignerKey" => "(function() { $sk = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519)); $sk->ed25519 = str_repeat(\"\\xAB\", 32); return $sk; })()",
  "XdrSCAddress" => "XdrSCAddress::forAccountId('#{TEST_ACCOUNT_ID}')",
  "XdrConfigUpgradeSetKey" => "new XdrConfigUpgradeSetKey(str_repeat('ab', 32), str_repeat('cd', 32))",
  "XdrPeerAddressIp" => "(function() { $ip = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv4)); $ip->ipv4 = str_repeat(\"\\xAB\", 4); return $ip; })()",
  "XdrContractExecutable" => "new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET())",
  "XdrAllowTrustOperationAsset" => "XdrAllowTrustOperationAsset::fromAlphaNumAssetCode('USD')",
  "XdrLiquidityPoolDepositOperation" => "new XdrLiquidityPoolDepositOperation(str_repeat('ab', 32), new BigInteger('1000'), new BigInteger('1000'), new XdrPrice(1, 1), new XdrPrice(2, 1))",
  "XdrLiquidityPoolWithdrawOperation" => "new XdrLiquidityPoolWithdrawOperation(str_repeat('ab', 32), new BigInteger('1000'), new BigInteger('1000'), new BigInteger('1000'))",
  "XdrSCVal" => "new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_VOID))",
  "XdrContractEventBody" => "(function() { $b = new XdrContractEventBody(0); $b->v0 = new XdrContractEventBodyV0([], new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_VOID))); return $b; })()",
  "XdrExtensionPoint" => "new XdrExtensionPoint(0)",
  "XdrDiagnosticEvent" => "(function() { $b = new XdrContractEventBody(0); $b->v0 = new XdrContractEventBodyV0([], new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_VOID))); $d = new XdrDiagnosticEvent(true, new XdrContractEvent(new XdrExtensionPoint(0), new XdrContractEventType(XdrContractEventType::CONTRACT_EVENT_TYPE_SYSTEM), $b)); return $d; })()",
  "XdrTransactionEvent" => "(function() { $b = new XdrContractEventBody(0); $b->v0 = new XdrContractEventBodyV0([], new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_VOID))); return new XdrTransactionEvent(new XdrTransactionEventStage(XdrTransactionEventStage::TRANSACTION_EVENT_STAGE_BEFORE_ALL_TXS), new XdrContractEvent(new XdrExtensionPoint(0), new XdrContractEventType(XdrContractEventType::CONTRACT_EVENT_TYPE_SYSTEM), $b)); })()",

  # --- Group 1a: Primitive/utility dependency types ---
  "XdrStellarValueExt" => "new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))",
  "XdrStellarValue" => "new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC)))",
  "XdrLedgerHeaderExt" => "new XdrLedgerHeaderExt(0)",
  "XdrLedgerCloseMetaExt" => "new XdrLedgerCloseMetaExt(0)",
  "XdrTransactionSet" => "new XdrTransactionSet(str_repeat(\"\\0\", 32), [])",
  "XdrTransactionSetV1" => "new XdrTransactionSetV1(str_repeat(\"\\0\", 32), [])",
  "XdrGeneralizedTransactionSet" => "(function() { $u = new XdrGeneralizedTransactionSet(1); $u->v1TxSet = new XdrTransactionSetV1(str_repeat(\"\\0\", 32), []); return $u; })()",
  "XdrLedgerFootprint" => "new XdrLedgerFootprint([], [])",
  "XdrSorobanTransactionDataExt" => "new XdrSorobanTransactionDataExt(0)",
  "XdrSorobanResources" => "new XdrSorobanResources(new XdrLedgerFootprint([], []), 42, 42, 42)",
  "XdrSorobanTransactionData" => "new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0), new XdrSorobanResources(new XdrLedgerFootprint([], []), 42, 42, 42), 42)",
  "XdrDecoratedSignature" => "new XdrDecoratedSignature(str_repeat(\"\\xAB\", 4), str_repeat(\"\\xAB\", 64))",
  "XdrLedgerKey" => "XdrLedgerKey::forAccountId('#{TEST_ACCOUNT_ID}')",
  "XdrNodeID" => "new XdrNodeID((function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return $pk; })())",
  "XdrValue" => "new XdrValue(str_repeat(\"\\xAB\", 32))",
  "XdrBucketMetadata" => "new XdrBucketMetadata(42, new XdrBucketMetadataExt(0))",

  # --- Group 1b: Ledger header chain ---
  "XdrLedgerHeader" => "new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0))",
  "XdrLedgerHeaderHistoryEntryExt" => "new XdrLedgerHeaderHistoryEntryExt(0)",
  "XdrLedgerHeaderHistoryEntry" => "(function() { $h = new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0)); return new XdrLedgerHeaderHistoryEntry(str_repeat(\"\\0\", 32), $h, new XdrLedgerHeaderHistoryEntryExt(0)); })()",

  # --- Group 1c: Ledger close meta ---
  "XdrLedgerCloseMetaV0" => "(function() { $h = new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0)); $lhhe = new XdrLedgerHeaderHistoryEntry(str_repeat(\"\\0\", 32), $h, new XdrLedgerHeaderHistoryEntryExt(0)); return new XdrLedgerCloseMetaV0($lhhe, new XdrTransactionSet(str_repeat(\"\\0\", 32), []), [], [], []); })()",
  "XdrLedgerCloseMetaV1" => "(function() { $h = new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0)); $lhhe = new XdrLedgerHeaderHistoryEntry(str_repeat(\"\\0\", 32), $h, new XdrLedgerHeaderHistoryEntryExt(0)); $gts = new XdrGeneralizedTransactionSet(1); $gts->v1TxSet = new XdrTransactionSetV1(str_repeat(\"\\0\", 32), []); return new XdrLedgerCloseMetaV1(new XdrLedgerCloseMetaExt(0), $lhhe, $gts, [], [], [], 42, [], []); })()",
  "XdrLedgerCloseMetaV2" => "(function() { $h = new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0)); $lhhe = new XdrLedgerHeaderHistoryEntry(str_repeat(\"\\0\", 32), $h, new XdrLedgerHeaderHistoryEntryExt(0)); $gts = new XdrGeneralizedTransactionSet(1); $gts->v1TxSet = new XdrTransactionSetV1(str_repeat(\"\\0\", 32), []); return new XdrLedgerCloseMetaV2(new XdrLedgerCloseMetaExt(0), $lhhe, $gts, [], [], [], 42, []); })()",
  "XdrLedgerCloseMeta" => "(function() { $h = new XdrLedgerHeader(42, str_repeat(\"\\0\", 32), new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC))), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), 42, 42, 42, 42, 42, 42, 42, 42, [str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32), str_repeat(\"\\0\", 32)], new XdrLedgerHeaderExt(0)); $lhhe = new XdrLedgerHeaderHistoryEntry(str_repeat(\"\\0\", 32), $h, new XdrLedgerHeaderHistoryEntryExt(0)); $u = new XdrLedgerCloseMeta(0); $u->v0 = new XdrLedgerCloseMetaV0($lhhe, new XdrTransactionSet(str_repeat(\"\\0\", 32), []), [], [], []); return $u; })()",
  "XdrLedgerCloseMetaBatch" => "new XdrLedgerCloseMetaBatch(42, 42, [])",
  "XdrLedgerCloseMetaExtV1" => "new XdrLedgerCloseMetaExtV1(new XdrExtensionPoint(0), 42)",
  "XdrUpgradeEntryMeta" => "(function() { $u = new XdrLedgerUpgrade(new XdrLedgerUpgradeType(XdrLedgerUpgradeType::LEDGER_UPGRADE_VERSION)); $u->newLedgerVersion = 42; return new XdrUpgradeEntryMeta($u, []); })()",

  # --- Group 1d: Bucket types ---
  "XdrBucketEntry" => "(function() { $u = new XdrBucketEntry(new XdrBucketEntryType(XdrBucketEntryType::METAENTRY)); $u->metaEntry = new XdrBucketMetadata(42, new XdrBucketMetadataExt(0)); return $u; })()",
  "XdrHotArchiveBucketEntry" => "(function() { $u = new XdrHotArchiveBucketEntry(new XdrHotArchiveBucketEntryType(XdrHotArchiveBucketEntryType::HOT_ARCHIVE_METAENTRY)); $u->metaEntry = new XdrBucketMetadata(42, new XdrBucketMetadataExt(0)); return $u; })()",

  # --- Group 1e: SCP types ---
  "XdrSCPBallot" => "new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32)))",
  "XdrSCPNomination" => "new XdrSCPNomination(str_repeat(\"\\0\", 32), [], [])",
  "XdrSCPStatementPrepare" => "new XdrSCPStatementPrepare(str_repeat(\"\\0\", 32), new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0)",
  "XdrSCPStatementConfirm" => "new XdrSCPStatementConfirm(new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0, 0, str_repeat(\"\\0\", 32))",
  "XdrSCPStatementExternalize" => "new XdrSCPStatementExternalize(new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, str_repeat(\"\\0\", 32))",
  "XdrSCPStatementPledges" => "(function() { $u = new XdrSCPStatementPledges(new XdrSCPStatementType(XdrSCPStatementType::SCP_ST_PREPARE)); $u->prepare = new XdrSCPStatementPrepare(str_repeat(\"\\0\", 32), new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0); return $u; })()",
  "XdrSCPStatement" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $pledges = new XdrSCPStatementPledges(new XdrSCPStatementType(XdrSCPStatementType::SCP_ST_PREPARE)); $pledges->prepare = new XdrSCPStatementPrepare(str_repeat(\"\\0\", 32), new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0); return new XdrSCPStatement(new XdrNodeID($pk), 42, $pledges); })()",
  "XdrSCPEnvelope" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $pledges = new XdrSCPStatementPledges(new XdrSCPStatementType(XdrSCPStatementType::SCP_ST_PREPARE)); $pledges->prepare = new XdrSCPStatementPrepare(str_repeat(\"\\0\", 32), new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0); $stmt = new XdrSCPStatement(new XdrNodeID($pk), 42, $pledges); return new XdrSCPEnvelope($stmt, str_repeat(\"\\xAB\", 64)); })()",
  "XdrSCPQuorumSet" => "new XdrSCPQuorumSet(42, [], [])",
  "XdrSCPHistoryEntryV0" => "new XdrSCPHistoryEntryV0([], new XdrLedgerSCPMessages(42, []))",
  "XdrSCPHistoryEntry" => "(function() { $u = new XdrSCPHistoryEntry(0); $u->v0 = new XdrSCPHistoryEntryV0([], new XdrLedgerSCPMessages(42, [])); return $u; })()",
  "XdrLedgerSCPMessages" => "new XdrLedgerSCPMessages(42, [])",
  "XdrPersistedSCPStateV0" => "new XdrPersistedSCPStateV0([], [], [])",
  "XdrPersistedSCPStateV1" => "new XdrPersistedSCPStateV1([], [])",
  "XdrPersistedSCPState" => "(function() { $u = new XdrPersistedSCPState(0); $u->v0 = new XdrPersistedSCPStateV0([], [], []); return $u; })()",

  # --- Group 1f: Transaction set / storage types ---
  "XdrStoredTransactionSet" => "(function() { $u = new XdrStoredTransactionSet(0); $u->txSet = new XdrTransactionSet(str_repeat(\"\\0\", 32), []); return $u; })()",
  "XdrStoredDebugTransactionSet" => "(function() { $sts = new XdrStoredTransactionSet(0); $sts->txSet = new XdrTransactionSet(str_repeat(\"\\0\", 32), []); return new XdrStoredDebugTransactionSet($sts, 42, new XdrStellarValue(str_repeat(\"\\0\", 32), 42, [], new XdrStellarValueExt(new XdrStellarValueType(XdrStellarValueType::STELLAR_VALUE_BASIC)))); })()",
  "XdrTxSetComponentTxsMaybeDiscountedFee" => "new XdrTxSetComponentTxsMaybeDiscountedFee([])",
  "XdrTxSetComponent" => "(function() { $u = new XdrTxSetComponent(new XdrTxSetComponentType(XdrTxSetComponentType::TXSET_COMP_TXS_MAYBE_DISCOUNTED_FEE)); $u->txsMaybeDiscountedFee = new XdrTxSetComponentTxsMaybeDiscountedFee([]); return $u; })()",
  "XdrTransactionPhase" => "(function() { $u = new XdrTransactionPhase(0); $u->v0Components = []; return $u; })()",
  "XdrParallelTxsComponent" => "new XdrParallelTxsComponent([])",
  "XdrTxAdvertVector" => "new XdrTxAdvertVector([])",
  "XdrTxDemandVector" => "new XdrTxDemandVector([])",
  "XdrDependentTxCluster" => "new XdrDependentTxCluster([])",
  "XdrParallelTxExecutionStage" => "new XdrParallelTxExecutionStage([])",
  "XdrTimeSlicedPeerDataList" => "new XdrTimeSlicedPeerDataList([])",

  # --- Group 1g: Overlay / network types ---
  "XdrCurve25519Public" => "new XdrCurve25519Public(str_repeat(\"\\xAB\", 32))",
  "XdrCurve25519Secret" => "new XdrCurve25519Secret(str_repeat(\"\\xAB\", 32))",
  "XdrHmacSha256Key" => "new XdrHmacSha256Key(str_repeat(\"\\xAB\", 32))",
  "XdrHmacSha256Mac" => "new XdrHmacSha256Mac(str_repeat(\"\\xAB\", 32))",
  "XdrShortHashSeed" => "new XdrShortHashSeed(str_repeat(\"\\xAB\", 16))",
  "XdrEncryptedBody" => "new XdrEncryptedBody(str_repeat(\"\\xAB\", 64))",
  "XdrAuth" => "new XdrAuth(42)",
  "XdrSendMore" => "new XdrSendMore(42)",
  "XdrSendMoreExtended" => "new XdrSendMoreExtended(42, 42)",
  "XdrAuthCert" => "new XdrAuthCert(new XdrCurve25519Public(str_repeat(\"\\xAB\", 32)), 42, str_repeat(\"\\xAB\", 64))",
  "XdrHello" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrHello(42, 42, 42, str_repeat(\"\\0\", 32), 'test', 42, new XdrNodeID($pk), new XdrAuthCert(new XdrCurve25519Public(str_repeat(\"\\xAB\", 32)), 42, str_repeat(\"\\xAB\", 64)), str_repeat(\"\\xAB\", 32)); })()",
  "XdrDontHave" => "new XdrDontHave(new XdrMessageType(XdrMessageType::ERROR_MSG), str_repeat(\"\\0\", 32))",
  "XdrError" => "new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), 'test_error')",
  "XdrPeerAddress" => "(function() { $ip = new XdrPeerAddressIp(new XdrIPAddrType(XdrIPAddrType::IPv4)); $ip->ipv4 = str_repeat(\"\\xAB\", 4); return new XdrPeerAddress($ip, 42, 0); })()",
  "XdrFloodAdvert" => "new XdrFloodAdvert(new XdrTxAdvertVector([]))",
  "XdrFloodDemand" => "new XdrFloodDemand(new XdrTxDemandVector([]))",
  "XdrStellarMessage" => "(function() { $u = new XdrStellarMessage(new XdrMessageType(XdrMessageType::ERROR_MSG)); $u->error = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), 'test_error'); return $u; })()",
  "XdrAuthenticatedMessageV0" => "(function() { $msg = new XdrStellarMessage(new XdrMessageType(XdrMessageType::ERROR_MSG)); $msg->error = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), 'test_error'); return new XdrAuthenticatedMessageV0(42, $msg, new XdrHmacSha256Mac(str_repeat(\"\\xAB\", 32))); })()",
  "XdrAuthenticatedMessage" => "(function() { $msg = new XdrStellarMessage(new XdrMessageType(XdrMessageType::ERROR_MSG)); $msg->error = new XdrError(new XdrErrorCode(XdrErrorCode::ERR_MISC), 'test_error'); $u = new XdrAuthenticatedMessage(0); $u->v0 = new XdrAuthenticatedMessageV0(42, $msg, new XdrHmacSha256Mac(str_repeat(\"\\xAB\", 32))); return $u; })()",

  # --- Group 1h: Survey types ---
  "XdrTimeSlicedNodeData" => "new XdrTimeSlicedNodeData(0, 0, 0, 0, 0, 0, 0, false, 0, 0)",
  "XdrSurveyResponseBody" => "(function() { $u = new XdrSurveyResponseBody(new XdrSurveyMessageResponseType(XdrSurveyMessageResponseType::SURVEY_TOPOLOGY_RESPONSE_V2)); $u->topologyResponseBodyV2 = new XdrTopologyResponseBodyV2(new XdrTimeSlicedPeerDataList([]), new XdrTimeSlicedPeerDataList([]), new XdrTimeSlicedNodeData(0, 0, 0, 0, 0, 0, 0, false, 0, 0)); return $u; })()",
  "XdrTopologyResponseBodyV2" => "new XdrTopologyResponseBodyV2(new XdrTimeSlicedPeerDataList([]), new XdrTimeSlicedPeerDataList([]), new XdrTimeSlicedNodeData(0, 0, 0, 0, 0, 0, 0, false, 0, 0))",
  "XdrSurveyRequestMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrSurveyRequestMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrCurve25519Public(str_repeat(\"\\xAB\", 32)), new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY)); })()",
  "XdrSurveyResponseMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrSurveyResponseMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY), new XdrEncryptedBody(str_repeat(\"\\xAB\", 64))); })()",
  "XdrTimeSlicedSurveyRequestMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $req = new XdrSurveyRequestMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrCurve25519Public(str_repeat(\"\\xAB\", 32)), new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY)); return new XdrTimeSlicedSurveyRequestMessage($req, 42, 0, 0); })()",
  "XdrTimeSlicedSurveyResponseMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $resp = new XdrSurveyResponseMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY), new XdrEncryptedBody(str_repeat(\"\\xAB\", 64))); return new XdrTimeSlicedSurveyResponseMessage($resp, 42); })()",
  "XdrTimeSlicedSurveyStartCollectingMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrTimeSlicedSurveyStartCollectingMessage(new XdrNodeID($pk), 42, 42); })()",
  "XdrTimeSlicedSurveyStopCollectingMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); return new XdrTimeSlicedSurveyStopCollectingMessage(new XdrNodeID($pk), 42, 42); })()",
  "XdrSignedTimeSlicedSurveyRequestMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $req = new XdrSurveyRequestMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrCurve25519Public(str_repeat(\"\\xAB\", 32)), new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY)); $tsReq = new XdrTimeSlicedSurveyRequestMessage($req, 42, 0, 0); return new XdrSignedTimeSlicedSurveyRequestMessage(str_repeat(\"\\xAB\", 64), $tsReq); })()",
  "XdrSignedTimeSlicedSurveyResponseMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $resp = new XdrSurveyResponseMessage(new XdrNodeID($pk), new XdrNodeID($pk), 42, new XdrSurveyMessageCommandType(XdrSurveyMessageCommandType::TIME_SLICED_SURVEY_TOPOLOGY), new XdrEncryptedBody(str_repeat(\"\\xAB\", 64))); $tsResp = new XdrTimeSlicedSurveyResponseMessage($resp, 42); return new XdrSignedTimeSlicedSurveyResponseMessage(str_repeat(\"\\xAB\", 64), $tsResp); })()",
  "XdrSignedTimeSlicedSurveyStartCollectingMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $start = new XdrTimeSlicedSurveyStartCollectingMessage(new XdrNodeID($pk), 42, 42); return new XdrSignedTimeSlicedSurveyStartCollectingMessage(str_repeat(\"\\xAB\", 64), $start); })()",
  "XdrSignedTimeSlicedSurveyStopCollectingMessage" => "(function() { $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519)); $pk->ed25519 = str_repeat(\"\\xAB\", 32); $stop = new XdrTimeSlicedSurveyStopCollectingMessage(new XdrNodeID($pk), 42, 42); return new XdrSignedTimeSlicedSurveyStopCollectingMessage(str_repeat(\"\\xAB\", 64), $stop); })()",

  # --- Group 1i0: Operation result leaf types ---
  # XdrManageOfferResult shares its result-code enum across the sell/buy offer
  # operations; its member-name overrides confuse generic arm synthesis, so pin
  # a simple valid (void error-code) instance.
  "XdrManageOfferResult" => "new XdrManageOfferResult(new XdrManageOfferResultCode(XdrManageOfferResultCode::MALFORMED))",

  # --- Group 1i: Transaction result chain ---
  "XdrTransactionResultResult" => "new XdrTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY))",
  "XdrTransactionResultExt" => "new XdrTransactionResultExt(0)",
  "XdrTransactionResult" => "new XdrTransactionResult(new BigInteger('0'), new XdrTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0))",
  "XdrTransactionResultPair" => "new XdrTransactionResultPair(str_repeat(\"\\0\", 32), new XdrTransactionResult(new BigInteger('0'), new XdrTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0)))",
  "XdrTransactionResultSet" => "new XdrTransactionResultSet([])",
  "XdrInnerTransactionResultResult" => "new XdrInnerTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY))",
  "XdrInnerTransactionResult" => "new XdrInnerTransactionResult(new BigInteger('0'), new XdrInnerTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0))",
  "XdrInnerTransactionResultPair" => "new XdrInnerTransactionResultPair(str_repeat('00', 32), new XdrInnerTransactionResult(new BigInteger('0'), new XdrInnerTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0)))",

  # --- Group 1j: Transaction meta chain ---
  "XdrTransactionMeta" => "(function() { $u = new XdrTransactionMeta(0); $u->operations = []; return $u; })()",
  "XdrTransactionResultMeta" => "(function() { $trp = new XdrTransactionResultPair(str_repeat(\"\\0\", 32), new XdrTransactionResult(new BigInteger('0'), new XdrTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0))); $tm = new XdrTransactionMeta(0); $tm->operations = []; return new XdrTransactionResultMeta($trp, [], $tm); })()",
  "XdrTransactionResultMetaV1" => "(function() { $trp = new XdrTransactionResultPair(str_repeat(\"\\0\", 32), new XdrTransactionResult(new BigInteger('0'), new XdrTransactionResultResult(new XdrTransactionResultCode(XdrTransactionResultCode::TOO_EARLY)), new XdrTransactionResultExt(0))); $tm = new XdrTransactionMeta(0); $tm->operations = []; return new XdrTransactionResultMetaV1(new XdrExtensionPoint(0), $trp, [], $tm, []); })()",

  # --- Group 1k: Transaction history ---
  "XdrTransactionHistoryEntryExt" => "new XdrTransactionHistoryEntryExt(0)",
  "XdrTransactionHistoryEntry" => "new XdrTransactionHistoryEntry(42, new XdrTransactionSet(str_repeat(\"\\0\", 32), []), new XdrTransactionHistoryEntryExt(0))",
  "XdrTransactionHistoryResultEntryExt" => "new XdrTransactionHistoryResultEntryExt(0)",
  "XdrTransactionHistoryResultEntry" => "new XdrTransactionHistoryResultEntry(42, new XdrTransactionResultSet([]), new XdrTransactionHistoryResultEntryExt(0))",

  # --- Group 1m: Liquidity pool / path payment leaf types ---
  "XdrLiquidityPoolConstantProductParameters" => "new XdrLiquidityPoolConstantProductParameters(new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), 30)",
  "XdrLiquidityPoolParameters" => "(function() { $p = new XdrLiquidityPoolParameters(new XdrLiquidityPoolType(XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT)); $p->constantProduct = new XdrLiquidityPoolConstantProductParameters(new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), 30); return $p; })()",
  "XdrConstantProduct" => "new XdrConstantProduct(new XdrLiquidityPoolConstantProductParameters(new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), 30), new BigInteger('1000'), new BigInteger('1000'), new BigInteger('1000'), 1)",
  "XdrSimplePaymentResult" => "new XdrSimplePaymentResult(XdrAccountID::fromAccountId('#{TEST_ACCOUNT_ID}'), new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new BigInteger('1000'))",
  "XdrPathPaymentResultSuccess" => "new XdrPathPaymentResultSuccess([], new XdrSimplePaymentResult(XdrAccountID::fromAccountId('#{TEST_ACCOUNT_ID}'), new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE)), new BigInteger('1000')))",

  # --- Group 1n: Contract code / preimage leaf types ---
  "XdrContractIDPreimage" => "(function() { $p = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS()); $p->fromAddress = new XdrContractIDPreimageFromAddress(XdrSCAddress::forAccountId('#{TEST_ACCOUNT_ID}'), str_repeat(\"\\xAB\", 32)); return $p; })()",
  "XdrCreateContractArgs" => "(function() { $p = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS()); $p->fromAddress = new XdrContractIDPreimageFromAddress(XdrSCAddress::forAccountId('#{TEST_ACCOUNT_ID}'), str_repeat(\"\\xAB\", 32)); return new XdrCreateContractArgs($p, new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET())); })()",
  "XdrCreateContractArgsV2" => "(function() { $p = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS()); $p->fromAddress = new XdrContractIDPreimageFromAddress(XdrSCAddress::forAccountId('#{TEST_ACCOUNT_ID}'), str_repeat(\"\\xAB\", 32)); return new XdrCreateContractArgsV2($p, new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET()), []); })()",
  "XdrContractCodeCostInputs" => "new XdrContractCodeCostInputs(new XdrExtensionPoint(0), 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)",
  "XdrContractCodeEntryV1" => "new XdrContractCodeEntryV1(new XdrExtensionPoint(0), new XdrContractCodeCostInputs(new XdrExtensionPoint(0), 1, 1, 1, 1, 1, 1, 1, 1, 1, 1))",

  # --- Group 1o: Trust line v1 leaf types ---
  "XdrTrustLineEntryV1Ext" => "new XdrTrustLineEntryV1Ext(0)",
  "XdrTrustLineEntryV1" => "new XdrTrustLineEntryV1(new XdrLiabilities(new BigInteger('0'), new BigInteger('0')), new XdrTrustLineEntryV1Ext(0))",

  # --- Group 1l: Transaction types ---
  "XdrTransactionV0" => "new XdrTransactionV0(str_repeat(\"\\xAB\", 32), new XdrSequenceNumber(new BigInteger('123456789')), [])",
  "XdrTransactionV0Envelope" => "new XdrTransactionV0Envelope(new XdrTransactionV0(str_repeat(\"\\xAB\", 32), new XdrSequenceNumber(new BigInteger('123456789')), []), [])",
  "XdrFeeBumpTransactionExt" => "new XdrFeeBumpTransactionExt(0)",

  # --- Group 1p: Transaction / envelope / signature payload chain ---
  "XdrTransaction" => "new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0))",
  "XdrTransactionV1Envelope" => "new XdrTransactionV1Envelope(new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)), [])",
  "XdrFeeBumpTransactionInnerTx" => "(function() { $tx = new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)); $i = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)); $i->v1 = new XdrTransactionV1Envelope($tx, []); return $i; })()",
  "XdrFeeBumpTransaction" => "(function() { $tx = new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)); $i = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)); $i->v1 = new XdrTransactionV1Envelope($tx, []); return new XdrFeeBumpTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), 200, $i, new XdrFeeBumpTransactionExt(0)); })()",
  "XdrFeeBumpTransactionEnvelope" => "(function() { $tx = new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)); $i = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)); $i->v1 = new XdrTransactionV1Envelope($tx, []); $fb = new XdrFeeBumpTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), 200, $i, new XdrFeeBumpTransactionExt(0)); return new XdrFeeBumpTransactionEnvelope($fb, []); })()",
  "XdrTransactionSignaturePayloadTaggedTransaction" => "(function() { $tx = new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)); $t = new XdrTransactionSignaturePayloadTaggedTransaction(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)); $t->tx = $tx; return $t; })()",
  "XdrTransactionSignaturePayload" => "(function() { $tx = new XdrTransaction(new XdrMuxedAccount(str_repeat(\"\\xAB\", 32)), new XdrSequenceNumber(new BigInteger('123456789')), [], 100, new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE)), new XdrPreconditions(XdrPreconditionType::NONE()), new XdrTransactionExt(0)); $t = new XdrTransactionSignaturePayloadTaggedTransaction(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)); $t->tx = $tx; return new XdrTransactionSignaturePayload(str_repeat(\"\\0\", 32), $t); })()",
}.freeze

# Curated instances with optional fields POPULATED for FALLBACK structs whose
# generic construction is unreliable. Used by the optionals-present JSON test to
# exercise the optional-present branches of toJsonValue / fromJsonValue.
OPTIONALS_PRESENT_VALUES = {
  "XdrSCPStatementPrepare" => "new XdrSCPStatementPrepare(str_repeat(\"\\0\", 32), new XdrSCPBallot(42, new XdrValue(str_repeat(\"\\xAB\", 32))), 0, 0, new XdrSCPBallot(1, new XdrValue(str_repeat(\"\\xCD\", 16))), new XdrSCPBallot(2, new XdrValue(str_repeat(\"\\xEF\", 16))))",
}.freeze

# Overrides for specific union arm values that need special treatment
# (e.g., wrapper types that expect hex strings instead of raw binary).
# Keys are [union_php_name, arm_field_name] => PHP expression.
ARM_VALUE_OVERRIDES = {
  ["XdrSCAddress", "contractId"] => "str_repeat('ab', 32)",
  ["XdrSCAddress", "liquidityPoolId"] => "str_repeat('ab', 32)",
  ["XdrContractExecutable", "wasmIdHex"] => "str_repeat('ab', 32)",
  ["XdrContractExecutableBase", "wasmIdHex"] => "str_repeat('ab', 32)",
}.freeze

# Union arms whose JSON representation differs from the auto-synthesised XDR
# representation (e.g. opaque-fixed bytes that JSON renders as a hex string).
# For these the curated FALLBACK_VALUES instance is used instead of synthesis,
# so we do not attempt to build the arm from raw test values for JSON tests.
# Keys are [union_php_name_or_base, arm_field_name].
ARM_JSON_NO_SYNTH = Set.new([
  ["XdrClaimableBalanceID", "hash"],
  ["XdrClaimableBalanceIDBase", "hash"],
]).freeze

# Types to completely skip test generation for (circular references, massive
# dependency trees, or wrapper constructors incompatible with auto-construction).
SKIP_TEST_TYPES = %w[
  XdrLedgerEntry
  XdrLedgerEntryData
  XdrLedgerEntryChange
  XdrOperation
  XdrOperationBody
  XdrOperationResult
  XdrOperationResultTr
  XdrTransactionEnvelope
  XdrTransaction
  XdrSorobanAuthorizationEntry
  XdrFeeBumpTransactionInnerTx
  XdrFeeBumpTransaction
  XdrFeeBumpTransactionEnvelope
  XdrTransactionV1Envelope
  XdrTransactionSignaturePayloadTaggedTransaction
  XdrTransactionSignaturePayload
].freeze

# ---------------------------------------------------------------------------
# Name resolution helpers (must match the generator exactly)
# ---------------------------------------------------------------------------

def raw_xdr_qualified_name(named)
  xdr_name = named.name.camelize
  if named.is_a?(AST::Concerns::NestedDefinition)
    parent_raw = raw_xdr_qualified_name(named.parent_defn)
    "#{parent_raw}#{xdr_name}"
  else
    xdr_name
  end
end

def name(named)
  raw_xdr_name = raw_xdr_qualified_name(named)
  return NAME_OVERRIDES[raw_xdr_name] if NAME_OVERRIDES.key?(raw_xdr_name)

  xdr_name = named.name.camelize
  return NAME_OVERRIDES[xdr_name] if NAME_OVERRIDES.key?(xdr_name)

  if named.is_a?(AST::Concerns::NestedDefinition)
    parent = name(named.parent_defn)
    "#{parent}#{xdr_name}"
  else
    "Xdr#{xdr_name}"
  end
end

def resolve_field_name(type_name, xdr_field_name)
  field = xdr_field_name.to_s
  if FIELD_OVERRIDES.key?(type_name) && FIELD_OVERRIDES[type_name].key?(field)
    FIELD_OVERRIDES[type_name][field]
  else
    safe_field_name(field)
  end
end

def resolve_member_name(type_name, xdr_member_name)
  if MEMBER_OVERRIDES.key?(type_name) && MEMBER_OVERRIDES[type_name].key?(xdr_member_name)
    MEMBER_OVERRIDES[type_name][xdr_member_name]
  elsif defined?(MEMBER_PREFIX_STRIP) && MEMBER_PREFIX_STRIP.key?(type_name)
    prefix = MEMBER_PREFIX_STRIP[type_name]
    xdr_member_name.delete_prefix(prefix)
  else
    xdr_member_name
  end
end

def safe_field_name(n)
  PHP_RESERVED_WORDS.include?(n) ? "#{n}_" : n
end

def extension_point_field?(type_name, field_name)
  return false unless defined?(EXTENSION_POINT_FIELDS)
  return false unless EXTENSION_POINT_FIELDS.key?(type_name)
  EXTENSION_POINT_FIELDS[type_name].include?(field_name)
end

def typedef_is_optional?(typespec)
  return false unless typespec.is_a?(AST::Typespecs::Simple)
  resolved = typespec.resolved_type
  return false unless resolved.is_a?(AST::Definitions::Typedef)
  resolved.declaration.type.sub_type == :optional
end

def find_php_class_file(class_name)
  base = File.join("Soneso", "StellarSDK", "Xdr", "#{class_name}Base.php")
  return base if File.exist?(base)
  main = File.join("Soneso", "StellarSDK", "Xdr", "#{class_name}.php")
  File.exist?(main) ? main : nil
end

# ---------------------------------------------------------------------------
# PHP type resolution (simplified version of generator's php_type_for_typespec)
# ---------------------------------------------------------------------------

def php_type_for_typespec(type)
  case type
  when AST::Typespecs::Bool then "bool"
  when AST::Typespecs::Int, AST::Typespecs::UnsignedInt,
       AST::Typespecs::Hyper, AST::Typespecs::UnsignedHyper
    "int"
  when AST::Typespecs::String, AST::Typespecs::Opaque
    "string"
  when AST::Typespecs::Simple
    resolved = type.resolved_type
    resolved_name = name(resolved)
    return TYPE_OVERRIDES[resolved_name] if TYPE_OVERRIDES.key?(resolved_name)
    if resolved.is_a?(AST::Definitions::Typedef)
      underlying = resolved.declaration.type
      return php_type_for_typespec(underlying) if underlying.sub_type == :optional
    end
    resolved_name
  when AST::Definitions::Base, AST::Concerns::NestedDefinition
    name(type)
  else
    "mixed"
  end
end

def php_type_string(decl)
  case decl
  when AST::Declarations::Array then "array"
  when AST::Declarations::Opaque then "string"
  when AST::Declarations::String then "string"
  else php_type_for_typespec(decl.type)
  end
end

# ---------------------------------------------------------------------------
# Type registry — maps PHP class name -> AST definition
# ---------------------------------------------------------------------------

$type_registry = {}

def build_type_registry(node)
  node.definitions.each do |defn|
    next if defn.is_a?(AST::Definitions::Const)
    php_name = name(defn)
    $type_registry[php_name] = defn
    if defn.respond_to?(:nested_definitions)
      defn.nested_definitions.each do |nested|
        nested_name = name(nested)
        $type_registry[nested_name] = nested
      end
    end
  end
  node.namespaces.each { |ns| build_type_registry(ns) }
end

# ---------------------------------------------------------------------------
# Test value generation
#
# Returns a PHP expression string that constructs a valid instance of the type,
# or nil if the type cannot be constructed at this depth.
# ---------------------------------------------------------------------------

def test_value_for_primitive_typespec(typespec)
  case typespec
  when AST::Typespecs::Bool then "true"
  when AST::Typespecs::Int then "42"
  when AST::Typespecs::UnsignedInt then "42"
  when AST::Typespecs::Hyper then "123456789"
  when AST::Typespecs::UnsignedHyper then "123456789"
  when AST::Typespecs::String then "'test_string'"
  else nil
  end
end

def test_value_for_opaque(decl)
  if decl.is_a?(AST::Declarations::Opaque)
    if decl.fixed?
      "str_repeat(\"\\xAB\", #{decl.size})"
    else
      "\"\\x01\\x02\\x03\\x04\""
    end
  else
    nil
  end
end

def test_value_for_type(php_type, typespec, decl, depth)
  return nil if depth > MAX_DEPTH

  # Check fallbacks first
  return FALLBACK_VALUES[php_type] if FALLBACK_VALUES.key?(php_type)

  # Primitives resolved from TYPE_OVERRIDES
  case php_type
  when "int" then return "42"
  when "bool" then return "true"
  when "string"
    # Check if this is an opaque with a fixed size
    if decl.is_a?(AST::Declarations::Opaque)
      return test_value_for_opaque(decl)
    end
    # Check if the underlying typedef is fixed opaque
    if typespec.is_a?(AST::Typespecs::Simple)
      resolved = typespec.resolved_type
      if resolved.is_a?(AST::Definitions::Typedef)
        inner_decl = resolved.declaration
        if inner_decl.is_a?(AST::Declarations::Opaque) && inner_decl.fixed?
          return "str_repeat(\"\\xAB\", #{inner_decl.size})"
        end
        # Follow typedef chain
        return resolve_typedef_test_value(resolved, depth)
      end
    end
    return "'test_string'"
  when "BigInteger" then return "new BigInteger('123456789')"
  when "XdrDataValueMandatory" then return "new XdrDataValueMandatory(\"\\x01\\x02\\x03\\x04\")"
  when "array" then return "[]"  # empty array is valid for variable-length
  end

  # Look up in type registry
  defn = $type_registry[php_type]
  return nil unless defn

  generate_value_for_defn(php_type, defn, depth)
end

def resolve_typedef_test_value(typedef_defn, depth)
  inner_decl = typedef_defn.declaration
  case inner_decl
  when AST::Declarations::Opaque
    inner_decl.fixed? ? "str_repeat(\"\\xAB\", #{inner_decl.size})" : "\"\\x01\\x02\\x03\\x04\""
  when AST::Declarations::String
    "'test_string'"
  else
    if inner_decl.type.is_a?(AST::Typespecs::Simple)
      resolved = inner_decl.type.resolved_type
      if resolved.is_a?(AST::Definitions::Typedef)
        return resolve_typedef_test_value(resolved, depth)
      end
    end
    "'test_string'"
  end
end

def generate_value_for_defn(php_name, defn, depth)
  case defn
  when AST::Definitions::Enum
    generate_enum_value(php_name, defn)
  when AST::Definitions::Struct
    generate_struct_value(php_name, defn, depth + 1)
  when AST::Definitions::Union
    generate_union_value(php_name, defn, depth + 1)
  when AST::Definitions::Typedef
    generate_typedef_value(php_name, defn, depth + 1)
  else
    nil
  end
end

def generate_enum_value(php_name, enum_defn)
  # Use the wrapper class if it's a BASE_WRAPPER_TYPE
  class_name = php_name
  first_member = enum_defn.members.first
  member_name = resolve_member_name(php_name, first_member.name.to_s)
  # Use the base class for constant reference
  const_class = BASE_WRAPPER_TYPES.include?(php_name) ? "#{php_name}Base" : php_name
  "new #{class_name}(#{const_class}::#{member_name})"
end

# ---------------------------------------------------------------------------
# Shared struct field analysis (used by struct value generation and test patterns)
# ---------------------------------------------------------------------------

def analyze_struct_fields(php_name, struct_defn)
  struct_defn.members.map do |m|
    xdr_field_name = m.name.to_s
    field_name = resolve_field_name(php_name, xdr_field_name)
    decl = m.declaration
    is_ext = extension_point_field?(php_name, xdr_field_name)
    is_optional = !is_ext && (m.type.sub_type == :optional || typedef_is_optional?(decl.type))

    php_type = nil
    unless is_ext
      if FIELD_TYPE_OVERRIDES.key?(php_name) && FIELD_TYPE_OVERRIDES[php_name].key?(xdr_field_name)
        php_type = FIELD_TYPE_OVERRIDES[php_name][xdr_field_name]
      end
      php_type ||= php_type_string(decl)
    end

    is_array = !is_ext && decl.is_a?(AST::Declarations::Array)
    if !is_ext && !is_array && php_type == "array" &&
       decl.respond_to?(:type) && decl.type.is_a?(AST::Typespecs::Simple)
      resolved = decl.type.resolved_type
      if resolved.is_a?(AST::Definitions::Typedef) &&
         resolved.declaration.is_a?(AST::Declarations::Array)
        decl = resolved.declaration
        is_array = true
      end
    end

    elements_optional = false
    if is_array && is_optional && m.type.sub_type != :optional
      is_optional = false
      elements_optional = true
    end

    { name: field_name, xdr_name: xdr_field_name, php_type: php_type,
      decl: decl, member: m, is_optional: is_optional, is_array: is_array,
      is_ext_point: is_ext, elements_optional: elements_optional }
  end
end

def generate_struct_value(php_name, struct_defn, depth)
  return nil if depth > MAX_DEPTH
  return FALLBACK_VALUES[php_name] if FALLBACK_VALUES.key?(php_name)

  # Use wrapper class name for BASE_WRAPPER_TYPES
  class_name = php_name

  # Collect fields using shared helper
  fields = analyze_struct_fields(php_name, struct_defn)

  # Build constructor args (same order as generator: required first, optional last)
  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  args = ordered_fields.map do |f|
    if f[:is_optional]
      "null"
    elsif f[:is_array]
      "[]"
    else
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], depth)
      return nil if val.nil?  # Can't construct this struct
      val
    end
  end

  "new #{class_name}(#{args.join(', ')})"
end

def generate_union_value(php_name, union_defn, depth)
  return nil if depth > MAX_DEPTH
  return FALLBACK_VALUES[php_name] if FALLBACK_VALUES.key?(php_name)

  class_name = php_name
  disc_info = resolve_discriminant_info_test(union_defn, php_name)

  # Find the simplest arm to construct (prefer void arms)
  void_arm = union_defn.normal_arms.find { |a| a.void? }
  if void_arm
    disc_expr = arm_discriminant_expr(void_arm.cases.first.value, disc_info)
    return "new #{class_name}(#{disc_expr})"
  end

  # Try each non-void arm until we find one we can fully construct
  union_defn.normal_arms.each do |arm|
    disc_expr = arm_discriminant_expr(arm.cases.first.value, disc_info)
    field_name = resolve_field_name(php_name, arm.name)
    decl = arm.declaration

    # Determine arm PHP type
    arm_php_type = nil
    if FIELD_TYPE_OVERRIDES.key?(php_name) && FIELD_TYPE_OVERRIDES[php_name].key?(field_name)
      arm_php_type = FIELD_TYPE_OVERRIDES[php_name][field_name]
    else
      arm_php_type = php_type_string(decl)
    end

    # Check for arm-specific value overrides
    override_key = [php_name, field_name]
    arm_value = if ARM_VALUE_OVERRIDES.key?(override_key)
                  ARM_VALUE_OVERRIDES[override_key]
                else
                  typespec = decl.respond_to?(:type) ? decl.type : nil
                  test_value_for_type(arm_php_type, typespec, decl, depth)
                end
    next unless arm_value

    return "(function() { $u = new #{class_name}(#{disc_expr}); $u->#{field_name} = #{arm_value}; return $u; })()"
  end

  nil
end

def generate_typedef_value(php_name, typedef_defn, depth)
  return nil if depth > MAX_DEPTH
  return FALLBACK_VALUES[php_name] if FALLBACK_VALUES.key?(php_name)
  decl = typedef_defn.declaration
  case decl
  when AST::Declarations::Opaque
    inner = decl.fixed? ? "str_repeat(\"\\xAB\", #{decl.size})" : "\"\\x01\\x02\\x03\\x04\""
    "new #{php_name}(#{inner})"
  when AST::Declarations::String
    "new #{php_name}('test_string')"
  when AST::Declarations::Array
    "new #{php_name}([])"
  else
    typespec = decl.type
    val = test_value_for_primitive_typespec(typespec)
    if val
      "new #{php_name}(#{val})"
    else
      inner_type = php_type_for_typespec(typespec)
      inner_val = test_value_for_type(inner_type, typespec, decl, depth)
      inner_val ? "new #{php_name}(#{inner_val})" : nil
    end
  end
end

# ---------------------------------------------------------------------------
# Union discriminant helpers
# ---------------------------------------------------------------------------

def resolve_discriminant_info_test(union, union_name)
  dtype = union.discriminant.type
  disc_field_name = resolve_field_name(union_name, union.discriminant.name.to_s)

  if dtype.respond_to?(:resolved_type)
    resolved = dtype.resolved_type
    if resolved.is_a?(AST::Definitions::Enum)
      php_name = name(resolved)
      return { kind: :enum, php_name: php_name, enum_defn: resolved, field_name: disc_field_name }
    end
  end

  { kind: :int, php_name: nil, enum_defn: nil, field_name: disc_field_name }
end

def arm_discriminant_expr(case_value, disc_info)
  if case_value.is_a?(AST::Identifier)
    if disc_info[:kind] == :enum
      member = resolve_member_name(disc_info[:php_name], case_value.name.to_s)
      const_class = BASE_WRAPPER_TYPES.include?(disc_info[:php_name]) ? "#{disc_info[:php_name]}Base" : disc_info[:php_name]
      "new #{disc_info[:php_name]}(#{const_class}::#{member})"
    else
      case_value.name.to_s
    end
  else
    case_value.value.to_s
  end
end

def arm_case_label(case_value, disc_info)
  if case_value.is_a?(AST::Identifier)
    if disc_info[:kind] == :enum
      member = resolve_member_name(disc_info[:php_name], case_value.name.to_s)
      const_class = BASE_WRAPPER_TYPES.include?(disc_info[:php_name]) ? "#{disc_info[:php_name]}Base" : disc_info[:php_name]
      "#{const_class}::#{member}"
    else
      case_value.name.to_s
    end
  else
    case_value.value.to_s
  end
end

# ---------------------------------------------------------------------------
# Test file generation
# ---------------------------------------------------------------------------

def source_file_for(defn)
  # Walk up to top-level definition
  d = defn
  d = d.parent_defn while d.respond_to?(:parent_defn) && d.parent_defn &&
                          !d.parent_defn.respond_to?(:definitions)
  # Get source file from the namespace or top
  if d.respond_to?(:namespace) && d.namespace
    return d.namespace
  end
  nil
end

def group_definitions_by_source(top, source_files)
  groups = {}

  # Each namespace in top.namespaces corresponds to one .x file (same order as sorted glob)
  top.namespaces.each_with_index do |ns, i|
    source = i < source_files.size ? File.basename(source_files[i], ".x") : "unknown-#{i}"

    collect_namespace_definitions(ns, groups, source)
  end

  groups
end

# Names that are excluded from full test generation (SKIP_TEST_TYPES) but still
# get JSON-only coverage (round-trip + negative) because they expose SEP-51 JSON
# methods. Their binary auto-construction is circular/oversized, but their JSON
# per-arm reconstruction is reachable through the Base class.
$json_only_names = Set.new

def collect_namespace_definitions(ns, groups, source)
  ns.definitions.each do |defn|
    next if defn.is_a?(AST::Definitions::Const)
    php_name = name(defn)

    is_skipped = SKIP_TYPES.include?(php_name) || TYPE_OVERRIDES.key?(php_name) || SKIP_TEST_TYPES.include?(php_name)

    unless is_skipped
      groups[source] ||= []
      groups[source] << defn
    end

    # SKIP_TEST_TYPES that have JSON methods still get JSON-only tests.
    if SKIP_TEST_TYPES.include?(php_name) && !TYPE_OVERRIDES.key?(php_name) &&
       !SKIP_TYPES.include?(php_name) && json_class_for(php_name)
      $json_only_names.add(php_name)
      groups[source] ||= []
      groups[source] << defn
    end

    # Always collect nested definitions (even from skipped parents) so TxRep
    # tests can be generated for types like XdrTransactionExt that are nested
    # under skipped types like XdrTransaction. Recurses so grandchild nested
    # types (e.g. XdrConstantProduct under a nested union) are also collected.
    collect_nested_definitions(defn, groups, source)
  end
end

def collect_nested_definitions(defn, groups, source)
  return unless defn.respond_to?(:nested_definitions)
  defn.nested_definitions.each do |nested|
    nested_name = name(nested)
    next if SKIP_TYPES.include?(nested_name)
    next if TYPE_OVERRIDES.key?(nested_name)
    if SKIP_TEST_TYPES.include?(nested_name)
      # Nested SKIP_TEST_TYPES with JSON methods still get JSON-only tests.
      if json_class_for(nested_name)
        $json_only_names.add(nested_name)
        groups[source] ||= []
        groups[source] << nested
      end
      # Still recurse: a SKIP parent may contain testable grandchildren.
      collect_nested_definitions(nested, groups, source)
      next
    end
    groups[source] ||= []
    groups[source] << nested
    collect_nested_definitions(nested, groups, source)
  end
end

def source_to_class_name(source)
  # "Stellar-types" -> "XdrTypesGenTest"
  # "Stellar-transaction" -> "XdrTransactionGenTest"
  parts = source.gsub("Stellar-", "").split("-").map(&:capitalize)
  "Xdr#{parts.join}GenTest"
end

def generate_test_file(source, definitions, output_dir)
  class_name = source_to_class_name(source)
  file_path = File.join(output_dir, "#{class_name}.php")

  tests = []
  seen = Set.new

  definitions.each do |defn|
    php_name = name(defn)
    next if seen.include?(php_name)
    seen.add(php_name)

    # JSON-only types (SKIP_TEST_TYPES with JSON methods): emit just the SEP-51
    # JSON round-trip and negative tests, never the binary/TxRep/getter tests.
    if $json_only_names.include?(php_name)
      json_only = generate_json_only_tests(php_name, defn)
      tests.concat(json_only) if json_only
      next
    end

    case defn
    when AST::Definitions::Enum
      test = generate_enum_test(php_name, defn)
      tests << test if test
      # Step 1: Invalid enum decode
      invalid_test = generate_enum_invalid_decode_test(php_name, defn)
      tests << invalid_test if invalid_test
      # Base class roundtrip (for wrapper types)
      base_enum_test = generate_base_enum_test(php_name, defn)
      tests << base_enum_test if base_enum_test
      # Enum factory methods
      efactory_test = generate_enum_factory_tests(php_name, defn)
      tests << efactory_test if efactory_test
      # JSON (SEP-51) round-trip
      enum_json_test = generate_enum_json_test(php_name, defn)
      tests << enum_json_test if enum_json_test
      # JSON (SEP-51) negative
      enum_neg_json = generate_enum_negative_json_test(php_name, defn)
      tests << enum_neg_json if enum_neg_json
    when AST::Definitions::Struct
      test = generate_struct_test(php_name, defn)
      tests << test if test
      # JSON (SEP-51) round-trip
      struct_json_test = generate_struct_json_test(php_name, defn)
      tests << struct_json_test if struct_json_test
      # JSON (SEP-51) round-trip with optionals present
      struct_json_opt = generate_struct_json_optionals_present_test(php_name, defn)
      tests << struct_json_opt if struct_json_opt
      # JSON (SEP-51) negative
      struct_neg_json = generate_struct_negative_json_test(php_name, defn)
      tests << struct_neg_json if struct_neg_json
      # Step 2: Optionals present
      opt_test = generate_struct_optionals_present_test(php_name, defn)
      tests << opt_test if opt_test
      # Step 3: Non-empty arrays
      arr_test = generate_struct_with_arrays_test(php_name, defn)
      tests << arr_test if arr_test
      # Step 5: Edge cases
      edge_tests = generate_struct_edge_case_tests(php_name, defn)
      tests.concat(edge_tests) if edge_tests
      # Step 6: Getter/setter
      gs_test = generate_getter_setter_tests(php_name, defn)
      tests << gs_test if gs_test
      # Base class getter/setter + roundtrip (for wrapper types)
      base_gs_test = generate_base_class_getter_setter_tests(php_name, defn)
      tests << base_gs_test if base_gs_test
    when AST::Definitions::Union
      union_tests = generate_union_tests(php_name, defn)
      tests.concat(union_tests) if union_tests
      # JSON (SEP-51) per-arm round-trip
      union_json_test = generate_union_json_test(php_name, defn)
      tests << union_json_test if union_json_test
      # JSON (SEP-51) negative
      union_neg_json = generate_union_negative_json_test(php_name, defn)
      tests << union_neg_json if union_neg_json
      # Step 6: Union getter/setter
      ugs_test = generate_union_getter_setter_tests(php_name, defn)
      tests << ugs_test if ugs_test
      # Base class roundtrip (for wrapper types)
      base_union_test = generate_base_class_union_test(php_name, defn)
      tests << base_union_test if base_union_test
    when AST::Definitions::Typedef
      test = generate_typedef_test(php_name, defn)
      tests << test if test
      # JSON (SEP-51) round-trip
      typedef_json_test = generate_typedef_json_test(php_name, defn)
      tests << typedef_json_test if typedef_json_test
      # JSON (SEP-51) negative
      typedef_neg_json = generate_typedef_negative_json_test(php_name, defn)
      tests << typedef_neg_json if typedef_neg_json
    end

    # Step 7: Factory method tests (wrapper types only)
    if BASE_WRAPPER_TYPES.include?(php_name)
      factory_tests = generate_factory_method_tests(php_name)
      tests.concat(factory_tests) if factory_tests
    end
  end

  # TxRep roundtrip tests — second pass over all definitions
  seen_txrep = Set.new
  definitions.each do |defn|
    php_name = name(defn)
    next if seen_txrep.include?(php_name)
    seen_txrep.add(php_name)
    next if $json_only_names.include?(php_name)

    txrep_tests = generate_txrep_tests(php_name, defn)
    tests.concat(txrep_tests) if txrep_tests

    # Also generate TxRep tests for nested definitions
    if defn.respond_to?(:nested_definitions)
      defn.nested_definitions.each do |nested|
        nested_name = name(nested)
        next if seen_txrep.include?(nested_name)
        seen_txrep.add(nested_name)

        nested_txrep = generate_txrep_tests(nested_name, nested)
        tests.concat(nested_txrep) if nested_txrep
      end
    end
  end

  return if tests.empty?

  # Collect imports
  imports = Set.new(["XdrBuffer", "XdrEncoder"])
  tests.each { |t| imports.merge(t[:imports]) }

  File.open(file_path, "w") do |f|
    f.puts "<?php declare(strict_types=1);"
    f.puts ""
    f.puts "// Auto-generated XDR round-trip tests. DO NOT EDIT."
    f.puts "// Generated by: tools/xdr-generator/test/generate_tests.rb"
    f.puts ""
    f.puts "namespace Soneso\\StellarSDKTests\\Unit\\Xdr\\Generated;"
    f.puts ""
    f.puts "use PHPUnit\\Framework\\TestCase;"
    imports.sort.each do |imp|
      if imp == "BigInteger"
        f.puts "use phpseclib3\\Math\\BigInteger;"
      else
        f.puts "use Soneso\\StellarSDK\\Xdr\\#{imp};"
      end
    end
    f.puts ""
    f.puts "class #{class_name} extends TestCase"
    f.puts "{"
    tests.each_with_index do |test, i|
      f.puts "" if i > 0
      test[:lines].each { |line| f.puts line }
    end
    f.puts "}"
    f.puts ""
  end

  puts "  #{class_name}.php: #{tests.size} tests"
end

# ---------------------------------------------------------------------------
# JSON (SEP-51) round-trip test generation
#
# Oracle: JSON self-consistency. A value must survive
# toJsonValue -> fromJsonValue -> toJsonValue unchanged, and its toJson string
# must be stable under fromJson. This exercises all four JSON methods without
# crossing the JSON and XDR field representations (some types store a field as
# raw bytes for encode() but as a hex string for toJsonValue()), so it is
# robust to representation differences between the two serialisation paths.
# ---------------------------------------------------------------------------

# Xdr class basenames that expose JSON methods (computed once at load).
# Resolved relative to this script's location so it is independent of the
# working directory in effect when the constant is evaluated (the main entry
# point chdir's to the repo root only later).
JSON_METHOD_CLASSES = Dir.glob(
  File.join(File.expand_path("../../..", __dir__), "Soneso", "StellarSDK", "Xdr", "*.php")
).select { |f| File.read(f).include?("function toJsonValue") }
  .map { |f| File.basename(f, ".php") }
  .to_set

# Returns the class to invoke JSON static methods on for php_name, or nil if the
# type has no JSON methods. Always prefers php_name (the public type) over its
# *Base: wrapper types inherit fromJsonValue/fromJson from the Base, and that
# Base code uses `new static(...)` against the *wrapper's* constructor, so the
# call must dispatch on the wrapper for `static` to resolve correctly.
def json_class_for(php_name)
  return php_name if JSON_METHOD_CLASSES.include?(php_name)
  return php_name if JSON_METHOD_CLASSES.include?("#{php_name}Base")
  nil
end

# Self-consistency assertions for an already-constructed instance variable.
def json_roundtrip_assertions(json_class, label, instance_var = "$original")
  [
    "        $j1 = #{instance_var}->toJsonValue();",
    "        $back = #{json_class}::fromJsonValue($j1);",
    "        $this->assertEquals($j1, $back->toJsonValue(), 'JSON value not stable for #{label}');",
    "        $this->assertSame(#{instance_var}->toJson(), $back->toJson(), 'JSON string not stable for #{label}');",
    "        $back2 = #{json_class}::fromJson(#{instance_var}->toJson());",
    "        $this->assertSame(#{instance_var}->toJson(), $back2->toJson(), 'fromJson round-trip failed for #{label}');",
  ]
end

def generate_enum_json_test(php_name, enum_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  const_class = BASE_WRAPPER_TYPES.include?(php_name) ? "#{php_name}Base" : php_name
  imports = Set.new([const_class, php_name, json_class])

  values = enum_defn.members.map do |m|
    member_name = resolve_member_name(php_name, m.name.to_s)
    "#{const_class}::#{member_name}"
  end

  lines = []
  lines << "    public function test#{php_name}EnumJsonRoundTrip(): void"
  lines << "    {"
  lines << "        $values = [#{values.join(', ')}];"
  lines << "        foreach ($values as $v) {"
  lines << "            $original = new #{php_name}($v);"
  lines << "            $j1 = $original->toJsonValue();"
  lines << "            $back = #{json_class}::fromJsonValue($j1);"
  lines << "            $this->assertEquals($j1, $back->toJsonValue(), 'JSON value not stable for #{php_name} value ' . $v);"
  lines << "            $this->assertSame($original->toJson(), $back->toJson(), 'JSON string not stable for #{php_name} value ' . $v);"
  lines << "            $back2 = #{json_class}::fromJson($original->toJson());"
  lines << "            $this->assertSame($original->toJson(), $back2->toJson(), 'fromJson round-trip failed for #{php_name} value ' . $v);"
  lines << "        }"
  lines << "    }"

  { lines: lines, imports: imports }
end

def generate_struct_json_test(php_name, struct_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  value_expr = generate_struct_value(php_name, struct_defn, 0)
  return nil unless value_expr

  imports = collect_imports_from_expr(value_expr)
  imports.add(php_name)
  imports.add(json_class)

  lines = []
  lines << "    public function test#{php_name}StructJsonRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines.concat(json_roundtrip_assertions(json_class, php_name))
  lines << "    }"

  { lines: lines, imports: imports }
end

# JSON round-trip with all populatable optional fields present, exercising the
# optional-present branches of toJsonValue / fromJsonValue (which the default
# struct JSON test leaves null). Only emitted when the struct has at least one
# optional field we can construct a value for.
def generate_struct_json_optionals_present_test(php_name, struct_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  # Curated optionals-present instances for FALLBACK structs whose generic
  # construction is unreliable (typedef-typed constructor args), so we still
  # exercise the optional-present JSON branches for them.
  if OPTIONALS_PRESENT_VALUES.key?(php_name)
    value_expr = OPTIONALS_PRESENT_VALUES[php_name]
    imports = collect_imports_from_expr(value_expr)
    imports.add(php_name)
    imports.add(json_class)
    lines = []
    lines << "    public function test#{php_name}StructJsonOptionalsPresentRoundTrip(): void"
    lines << "    {"
    lines << "        $original = #{value_expr};"
    lines.concat(json_roundtrip_assertions(json_class, "#{php_name} optionals-present"))
    lines << "    }"
    return { lines: lines, imports: imports }
  end

  # Skip FALLBACK structs without a curated optionals-present value: their
  # generic construction is special and cannot be safely populated here.
  return nil if FALLBACK_VALUES.key?(php_name)

  fields = analyze_struct_fields(php_name, struct_defn)
  return nil unless fields.any? { |f| f[:is_optional] }

  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  can_populate_any = false
  args = ordered_fields.map do |f|
    if f[:is_array]
      "[]"
    elsif f[:is_optional]
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      if val
        can_populate_any = true
        val
      else
        "null"
      end
    else
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      return nil if val.nil?
      val
    end
  end
  return nil unless can_populate_any

  value_expr = "new #{php_name}(#{args.join(', ')})"
  imports = collect_imports_from_expr(value_expr)
  imports.add(php_name)
  imports.add(json_class)

  lines = []
  lines << "    public function test#{php_name}StructJsonOptionalsPresentRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines.concat(json_roundtrip_assertions(json_class, "#{php_name} optionals-present"))
  lines << "    }"

  { lines: lines, imports: imports }
end

def generate_typedef_json_test(php_name, typedef_defn)
  return nil if TYPE_OVERRIDES.key?(php_name)
  json_class = json_class_for(php_name)
  return nil unless json_class

  value_expr = generate_typedef_value(php_name, typedef_defn, 0)
  return nil unless value_expr

  imports = collect_imports_from_expr(value_expr)
  imports.add(php_name)
  imports.add(json_class)

  lines = []
  lines << "    public function test#{php_name}TypedefJsonRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines.concat(json_roundtrip_assertions(json_class, php_name))
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Enum test generation
# ---------------------------------------------------------------------------

def generate_enum_test(php_name, enum_defn)
  class_name = php_name
  const_class = BASE_WRAPPER_TYPES.include?(php_name) ? "#{php_name}Base" : php_name

  imports = Set.new([const_class])
  imports.add(php_name) if BASE_WRAPPER_TYPES.include?(php_name)

  values = enum_defn.members.map do |m|
    member_name = resolve_member_name(php_name, m.name.to_s)
    "#{const_class}::#{member_name}"
  end

  lines = []
  lines << "    public function test#{php_name}EnumRoundTrip(): void"
  lines << "    {"
  lines << "        $values = [#{values.join(', ')}];"
  lines << "        foreach ($values as $v) {"
  lines << "            $original = new #{class_name}($v);"
  lines << "            $encoded = $original->encode();"
  lines << "            $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
  lines << "            $this->assertEquals($v, $decoded->getValue(), 'Binary roundtrip failed for value ' . $v);"
  lines << "            $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
  lines << "            $this->assertEquals($v, $b64Decoded->getValue(), 'Base64 roundtrip failed for value ' . $v);"
  lines << "        }"
  lines << "    }"

  { lines: lines, imports: imports }
end

# Test enum static factory methods (e.g., XdrContractCostType::WASM_INSTRUCTIONS())
# These are defined on generated enum classes and return new instances.
def generate_enum_factory_tests(php_name, enum_defn)
  class_name = php_name

  # Check PHP file for static factory methods matching enum member names
  target_file = find_php_class_file(class_name)
  return nil unless target_file

  file_content = File.read(target_file)
  # Find all static factory methods (pattern: public static function NAME(): static or NAME(): ClassName)
  factory_names = file_content.scan(/public\s+static\s+function\s+(\w+)\(\)\s*:\s*(?:static|Xdr\w+)/).flatten
  factory_names.reject! { |m| %w[decode fromBase64Xdr].include?(m) }
  return nil if factory_names.empty?

  # Only target the class that has the methods
  target_class = target_file.include?("Base.php") ? "#{php_name}Base" : php_name

  imports = Set.new([target_class])
  imports.add(php_name) if BASE_WRAPPER_TYPES.include?(php_name)

  lines = []
  lines << "    public function test#{target_class}EnumFactoryMethods(): void"
  lines << "    {"

  factory_names.each do |fn|
    lines << "        $this->assertNotNull(#{target_class}::#{fn}());"
  end

  lines << "    }"

  { lines: lines, imports: imports }
end

# For wrapper enum types, generate a round-trip test that uses the Base class directly.
def generate_base_enum_test(php_name, enum_defn)
  return nil unless BASE_WRAPPER_TYPES.include?(php_name)

  base_name = "#{php_name}Base"
  base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{base_name}.php")
  return nil unless File.exist?(base_file)

  const_class = base_name

  imports = Set.new([base_name])

  first_member = enum_defn.members.first
  member_name = resolve_member_name(php_name, first_member.name.to_s)

  lines = []
  lines << "    public function test#{base_name}RoundTrip(): void"
  lines << "    {"
  lines << "        $original = new #{base_name}(#{const_class}::#{member_name});"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{base_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($original->getValue(), $decoded->getValue());"
  lines << "        $b64 = $original->toBase64Xdr();"
  lines << "        $fromB64 = #{base_name}::fromBase64Xdr($b64);"
  lines << "        $this->assertEquals($original->getValue(), $fromB64->getValue());"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Struct test generation
# ---------------------------------------------------------------------------

def generate_struct_test(php_name, struct_defn)
  # Try to construct a test value
  value_expr = generate_struct_value(php_name, struct_defn, 0)
  return nil unless value_expr

  class_name = php_name
  decode_class = class_name

  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  lines = []
  lines << "    public function test#{php_name}StructRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{decode_class}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Binary roundtrip failed for #{php_name}');"
  lines << "        $b64Decoded = #{decode_class}::fromBase64Xdr($original->toBase64Xdr());"
  lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 roundtrip failed for #{php_name}');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Union test generation — one test per non-default, non-void arm
# ---------------------------------------------------------------------------

def generate_union_tests(php_name, union_defn)
  # If a FALLBACK_VALUES entry exists, generate a single roundtrip test using it
  # instead of per-arm tests (wrapper types may have incompatible constructors).
  if FALLBACK_VALUES.key?(php_name)
    fallback = FALLBACK_VALUES[php_name]

    class_name = php_name
    imports = collect_imports_from_expr(fallback)
    imports.add(class_name)

    lines = []
    lines << "    public function test#{php_name}UnionRoundTrip(): void"
    lines << "    {"
    lines << "        $original = #{fallback};"
    lines << "        $encoded = $original->encode();"
    lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
    lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Binary roundtrip failed for #{php_name}');"
    lines << "        $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
    lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 roundtrip failed for #{php_name}');"
    lines << "    }"

    return [{ lines: lines, imports: imports }]
  end

  class_name = php_name
  disc_info = resolve_discriminant_info_test(union_defn, php_name)

  tests = []

  union_defn.normal_arms.each do |arm|
    next if arm.void?

    # Get discriminant expression from first case
    case_value = arm.cases.first.value
    disc_expr = arm_discriminant_expr(case_value, disc_info)
    case_label = arm_case_label(case_value, disc_info)

    field_name = resolve_field_name(php_name, arm.name)
    decl = arm.declaration

    # Determine arm PHP type
    arm_php_type = nil
    if FIELD_TYPE_OVERRIDES.key?(php_name) && FIELD_TYPE_OVERRIDES[php_name].key?(field_name)
      arm_php_type = FIELD_TYPE_OVERRIDES[php_name][field_name]
    else
      arm_php_type = php_type_string(decl)
    end

    # Generate test value for the arm (check overrides first)
    override_key = [php_name, field_name]
    arm_value = if ARM_VALUE_OVERRIDES.key?(override_key)
                  ARM_VALUE_OVERRIDES[override_key]
                else
                  typespec = decl.respond_to?(:type) ? decl.type : nil
                  test_value_for_type(arm_php_type, typespec, decl, 1)
                end
    next unless arm_value

    # Build safe test method name from case label
    safe_label = case_label.gsub("::", "_").gsub(/[^a-zA-Z0-9_]/, "")

    imports = Set.new([class_name])
    imports.merge(collect_imports_from_expr(disc_expr))
    imports.merge(collect_imports_from_expr(arm_value))

    lines = []
    lines << "    public function test#{php_name}_#{safe_label}_ArmRoundTrip(): void"
    lines << "    {"
    lines << "        $original = new #{class_name}(#{disc_expr});"
    lines << "        $original->#{field_name} = #{arm_value};"
    lines << "        $encoded = $original->encode();"
    lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"

    if disc_info[:kind] == :enum
      lines << "        $this->assertEquals($original->#{disc_info[:field_name]}->getValue(), $decoded->#{disc_info[:field_name]}->getValue());"
    else
      lines << "        $this->assertEquals($original->#{disc_info[:field_name]}, $decoded->#{disc_info[:field_name]});"
    end

    lines << "        $this->assertNotNull($decoded->#{field_name});"

    # Step 4: Assert unused arms are null
    union_defn.normal_arms.each do |other_arm|
      next if other_arm.void?
      next if other_arm == arm
      other_field = resolve_field_name(php_name, other_arm.name)
      next if other_field == field_name  # same field name (multiple cases)
      lines << "        $this->assertNull($decoded->#{other_field});"
    end

    lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Binary roundtrip failed');"
    lines << "        $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
    lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 roundtrip failed');"
    lines << "    }"

    tests << { lines: lines, imports: imports }
  end

  # Also generate void arm tests
  union_defn.normal_arms.each do |arm|
    next unless arm.void?

    case_value = arm.cases.first.value
    disc_expr = arm_discriminant_expr(case_value, disc_info)
    case_label = arm_case_label(case_value, disc_info)

    safe_label = case_label.gsub("::", "_").gsub(/[^a-zA-Z0-9_]/, "")

    imports = Set.new([class_name])
    imports.merge(collect_imports_from_expr(disc_expr))

    lines = []
    lines << "    public function test#{php_name}_#{safe_label}_VoidArmRoundTrip(): void"
    lines << "    {"
    lines << "        $original = new #{class_name}(#{disc_expr});"
    lines << "        $encoded = $original->encode();"
    lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"

    if disc_info[:kind] == :enum
      lines << "        $this->assertEquals($original->#{disc_info[:field_name]}->getValue(), $decoded->#{disc_info[:field_name]}->getValue());"
    else
      lines << "        $this->assertEquals($original->#{disc_info[:field_name]}, $decoded->#{disc_info[:field_name]});"
    end

    lines << "        $this->assertEquals($encoded, $decoded->encode());"
    lines << "    }"

    tests << { lines: lines, imports: imports }
  end

  tests.empty? ? nil : tests
end

# ---------------------------------------------------------------------------
# Slice 2: Union per-arm JSON round-trips
#
# Exercises every reconstructible match-arm of fromJsonValue. For each arm we
# build a valid instance (discriminant + arm field assignment, against the Base
# class so the constructor signature is always discriminant-only), call
# toJsonValue() and run the JSON self-consistency oracle (which dispatches
# fromJsonValue on the public wrapper). Void arms are covered too. Arms whose
# value cannot be synthesised are skipped with a printed SKIP line so the gap is
# visible. This covers both the per-arm reconstruction logic and the union's
# top-level JSON entry points for fallback unions whose binary tests use a
# single fallback instance and therefore only touch one arm.
# ---------------------------------------------------------------------------

# Returns the class used to *construct* per-arm union instances. The Base class
# (when present) always has a discriminant-only constructor, so per-arm field
# assignment works uniformly even for wrapper types with custom constructors.
def union_construct_class(php_name)
  if BASE_WRAPPER_TYPES.include?(php_name)
    base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php")
    return "#{php_name}Base" if File.exist?(base_file)
  end
  php_name
end

# Build [label, value_expr] pairs for each reconstructible arm of a union,
# constructing instances against the given construct_class. Prints SKIP lines
# for arms whose value cannot be synthesised.
def union_arm_json_instances(php_name, union_defn, construct_class)
  disc_info = resolve_discriminant_info_test(union_defn, php_name)
  # For fallback unions, per-arm field synthesis is unreliable because the JSON
  # representation of a field may differ from its XDR/raw representation (e.g.
  # XdrClaimableBalanceID stores its hash as a hex string for JSON but as raw
  # bytes for the binary path). Seed with the curated fallback instance, which
  # is known-good through the public wrapper, and only add per-arm instances
  # that have an explicit override.
  is_fallback = FALLBACK_VALUES.key?(php_name)
  instances = []
  if is_fallback
    instances << ["fallback", FALLBACK_VALUES[php_name]]
  end

  union_defn.normal_arms.each do |arm|
    # Build one instance per case value: arms that group several discriminant
    # values (case A: case B: T field;) reconstruct identically but render a
    # distinct toJsonValue key per value, so each value needs its own instance.
    field_name = arm.void? ? nil : resolve_field_name(php_name, arm.name)
    decl = arm.declaration

    arm_value = nil
    unless arm.void?
      arm_php_type = nil
      if FIELD_TYPE_OVERRIDES.key?(php_name) && FIELD_TYPE_OVERRIDES[php_name].key?(field_name)
        arm_php_type = FIELD_TYPE_OVERRIDES[php_name][field_name]
      else
        arm_php_type = php_type_string(decl)
      end

      [[php_name, field_name], [construct_class, field_name]].each do |k|
        if ARM_VALUE_OVERRIDES.key?(k)
          arm_value = ARM_VALUE_OVERRIDES[k]
          break
        end
      end
      if arm_value.nil? && !ARM_JSON_NO_SYNTH.include?([php_name, field_name])
        typespec = decl.respond_to?(:type) ? decl.type : nil
        arm_value = test_value_for_type(arm_php_type, typespec, decl, 1)
      end
    end

    arm.cases.each do |c|
      case_value = c.value
      disc_expr = arm_discriminant_expr(case_value, disc_info)
      case_label = arm_case_label(case_value, disc_info)
      safe_label = case_label.gsub("::", "_").gsub(/[^a-zA-Z0-9_]/, "")

      if arm.void?
        instances << [safe_label, "new #{construct_class}(#{disc_expr})"]
        next
      end

      if arm_value.nil?
        # Fallback unions already contribute one curated arm; only report a SKIP
        # for non-fallback unions where a synthesisable arm was expected.
        unless is_fallback
          puts "  SKIP #{php_name} JSON arm #{safe_label}: cannot synthesise value for field #{field_name} (#{php_type_string(decl)})"
        end
        next
      end

      expr = "(function() { $u = new #{construct_class}(#{disc_expr}); $u->#{field_name} = #{arm_value}; return $u; })()"
      instances << [safe_label, expr]
    end
  end

  instances
end

# Emits one JSON round-trip test per union that has JSON methods, asserting the
# self-consistency oracle for every reconstructible arm.
def generate_union_json_test(php_name, union_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  construct_class = union_construct_class(php_name)
  instances = union_arm_json_instances(php_name, union_defn, construct_class)
  return nil if instances.empty?

  imports = Set.new([php_name, json_class, construct_class])

  lines = []
  lines << "    public function test#{php_name}UnionJsonRoundTrip(): void"
  lines << "    {"
  instances.each_with_index do |(label, expr), i|
    var = "$arm#{i}"
    imports.merge(collect_imports_from_expr(expr))
    lines << "        #{var} = #{expr};"
    lines.concat(json_roundtrip_assertions(json_class, "#{php_name} arm #{label}", var))
  end
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Slice 3: Negative tests for fromJsonValue validation branches
#
# For each type that has fromJsonValue, build the valid JSON value (from a
# constructed instance) then feed malformed variants that must trigger the
# per-field / per-arm validation throws. One test method per type loops over
# the corruption cases. Assertions are loose: we only require that an
# InvalidArgumentException is raised (messages are brittle). The genuinely
# unreachable `default` arms are wrapped in @codeCoverageIgnore in the
# generated source and are not targeted here.
# ---------------------------------------------------------------------------

# Negative test for an enum's fromJsonValue: wrong scalar type and unknown
# string value both reject.
def generate_enum_negative_json_test(php_name, enum_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  imports = Set.new([php_name, json_class])

  lines = []
  lines << "    public function test#{php_name}EnumJsonRejectsInvalid(): void"
  lines << "    {"
  lines << "        $cases = [42, true, [], '__definitely_not_a_member__'];"
  lines << "        foreach ($cases as $bad) {"
  lines << "            $threw = false;"
  lines << "            try { #{json_class}::fromJsonValue($bad); }"
  lines << "            catch (\\InvalidArgumentException $e) { $threw = true; }"
  lines << "            $this->assertTrue($threw, 'Expected rejection for #{php_name} JSON: ' . var_export($bad, true));"
  lines << "        }"
  lines << "    }"

  { lines: lines, imports: imports }
end

# Returns the set of JSON keys of a type whose fromJsonValue performs NO
# wrong-type validation (the value is coerced silently). These are the
# BigInteger-backed int64/uint64 fields emitted as
#   new BigInteger(is_string($value['key']) ? ... : (string)(int) $value['key'])
# which accept any non-string without throwing, so a wrong-type corruption on
# them is not a reachable reject branch. Read directly from the generated
# source so it stays correct as the emitter evolves.
def unvalidated_json_keys(php_name)
  keys = Set.new
  [
    File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php"),
    File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php"),
  ].each do |path|
    next unless File.exist?(path)
    src = File.read(path)
    src.scan(/new BigInteger\(is_string\(\$value\['([^']+)'\]\)/).flatten.each { |k| keys.add(k) }
  end
  keys
end

# Negative test for a struct's fromJsonValue. Corruptions, all data-driven off
# the valid JSON value at runtime:
#   - non-array top-level value (hits the Expected object throw)
#   - each present key removed in turn (hits the per-field missing-key throws,
#     which exist for required AND optional fields since the key must be present)
#   - each non-null scalar/array value replaced with a wrong-typed value (hits
#     the inline is_string / is_int / is_bool / is_array field validators);
#     keys with no wrong-type validator are excluded from this case.
def generate_struct_negative_json_test(php_name, struct_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  value_expr = generate_struct_value(php_name, struct_defn, 0)
  return nil unless value_expr

  imports = collect_imports_from_expr(value_expr)
  imports.add(php_name)
  imports.add(json_class)

  skip_keys = unvalidated_json_keys(php_name)
  skip_php = "[" + skip_keys.map { |k| "'#{k}' => true" }.join(", ") + "]"

  lines = []
  lines << "    public function test#{php_name}StructJsonRejectsInvalid(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $valid = $original->toJsonValue();"
  lines << "        $noWrongTypeCheck = #{skip_php};"
  lines.concat(struct_negative_assertion_loop_lines(json_class))
  lines << "    }"

  { lines: lines, imports: imports }
end

# Shared runtime loop body for struct negative JSON corruption cases.
def struct_negative_assertion_loop_lines(json_class)
  [
    "        $assertRejects = function ($bad, string $desc) {",
    "            $threw = false;",
    "            try { #{json_class}::fromJsonValue($bad); }",
    "            catch (\\InvalidArgumentException $e) { $threw = true; }",
    "            $this->assertTrue($threw, 'Expected rejection: ' . $desc);",
    "        };",
    "        if (!is_array($valid)) {",
    "            // Some structs render as a single scalar (e.g. 128-bit integer",
    "            // parts as one string); their fromJsonValue rejects the wrong",
    "            // scalar type and malformed scalar payloads.",
    "            if (is_string($valid)) {",
    "                $assertRejects(42, 'non-string scalar struct value');",
    "                $assertRejects([], 'array for scalar struct value');",
    "                $assertRejects('@@@malformed@@@', 'malformed scalar struct value');",
    "            } else {",
    "                $assertRejects('not-the-right-scalar', 'wrong scalar struct value');",
    "            }",
    "            return;",
    "        }",
    "        $assertRejects('not-an-object', 'non-array top-level');",
    "        foreach (array_keys($valid) as $k) {",
    "            if ($k === '$schema') { continue; }",
    "            $missing = $valid; unset($missing[$k]);",
    "            $assertRejects($missing, 'missing field ' . $k);",
    "            $v = $valid[$k];",
    "            if ($v === null) { continue; }",
    "            if (isset($noWrongTypeCheck[$k])) { continue; }",
    "            $wrong = $valid;",
    "            if (is_bool($v)) { $wrong[$k] = 'not-a-bool'; }",
    "            elseif (is_array($v)) { $wrong[$k] = 'not-an-array'; }",
    "            else { $wrong[$k] = []; }",
    "            $assertRejects($wrong, 'wrong type for field ' . $k);",
    "        }",
  ]
end

# Negative test for a union's fromJsonValue. Two shapes exist:
#   - StrKey/string-encoded unions (toJsonValue returns a string): reject
#     non-string and empty / unknown-prefix strings.
#   - generic single-key-object unions: reject non-array, wrong key count,
#     non-string key, and an unknown arm key.
# We detect the shape at runtime from the valid JSON value, so a single
# generated method covers either form.
def generate_union_negative_json_test(php_name, union_defn)
  json_class = json_class_for(php_name)
  return nil unless json_class

  construct_class = union_construct_class(php_name)
  instances = union_arm_json_instances(php_name, union_defn, construct_class)
  return nil if instances.empty?

  imports = Set.new([php_name, json_class, construct_class])
  instances.each { |(_l, e)| imports.merge(collect_imports_from_expr(e)) }

  # Emit every reconstructible arm's valid JSON value so the runtime can choose
  # an object-form value (for the single-key-object guards) and/or a string-form
  # value (for void/strkey unions) regardless of arm ordering.
  lines = []
  lines << "    public function test#{php_name}UnionJsonRejectsInvalid(): void"
  lines << "    {"
  lines << "        $samples = [];"
  instances.each do |(_label, expr)|
    lines << "        $samples[] = (#{expr})->toJsonValue();"
  end
  lines << "        $valid = $samples[0];"
  lines << "        foreach ($samples as $s) { if (!is_string($s)) { $valid = $s; break; } }"
  lines << "        $assertRejects = function ($bad, string $desc) {"
  lines << "            $threw = false;"
  lines << "            try { #{json_class}::fromJsonValue($bad); }"
  lines << "            catch (\\InvalidArgumentException $e) { $threw = true; }"
  lines << "            $this->assertTrue($threw, 'Expected rejection for #{php_name}: ' . $desc);"
  lines << "        };"
  lines << "        $hasStringForm = false; foreach ($samples as $s) { if (is_string($s)) { $hasStringForm = true; break; } }"
  lines << "        if (is_string($valid)) {"
  lines << "            $assertRejects(['not' => 'a string'], 'non-string union value');"
  lines << "            $assertRejects('', 'empty string union value');"
  lines << "            $assertRejects('@@@invalid-prefix@@@', 'unknown prefix union value');"
  lines << "        } else {"
  lines << "            if ($hasStringForm) {"
  lines << "                // Extension-point hybrid: an unknown bare string is rejected."
  lines << "                $assertRejects('__unknown_void_arm_string__', 'unknown void-arm string');"
  lines << "            }"
  lines << "            $assertRejects('not-an-object', 'non-array union value');"
  lines << "            $assertRejects(['__unknown_arm_key__' => 1], 'unknown arm key');"
  lines << "            // Integer-keyed single-entry array hits the non-string arm key guard."
  lines << "            $assertRejects([5 => 1], 'non-string arm key');"
  lines << "            // Some extension-point unions also accept bare void-arm strings;"
  lines << "            // an unrecognised bare string is rejected by those, and by the"
  lines << "            // object-only unions via the non-array guard above (already tested)."
  lines << "            $assertRejects('__not_a_void_arm__', 'unknown bare string arm');"
  lines << "            if (is_array($valid) && count($valid) === 1) {"
  lines << "                $two = $valid; $two['__extra__'] = 1;"
  lines << "                $assertRejects($two, 'too many arm keys');"
  lines << "                $assertRejects([], 'zero arm keys');"
  lines << "                // Extension-point unions reject a non-void arm name supplied"
  lines << "                // as a bare string instead of a single-key object."
  lines << "                $armKey = array_key_first($valid);"
  lines << "                if (is_string($armKey)) {"
  lines << "                    $threwArm = false;"
  lines << "                    try { #{json_class}::fromJsonValue($armKey); } catch (\\InvalidArgumentException $e) { $threwArm = true; }"
  lines << "                    $this->assertTrue($threwArm, 'Expected rejection for #{php_name}: non-void arm name as bare string');"
  lines << "                }"
  lines << "            }"
  lines << "        }"
  lines << "    }"

  { lines: lines, imports: imports }
end

# Emits JSON-only tests (round-trip + negative) for a SKIP_TEST_TYPES type that
# nonetheless exposes SEP-51 JSON methods. No binary/TxRep/getter tests are
# produced for these types.
def generate_json_only_tests(php_name, defn)
  out = []
  case defn
  when AST::Definitions::Enum
    t = generate_enum_json_test(php_name, defn);          out << t if t
    t = generate_enum_negative_json_test(php_name, defn); out << t if t
  when AST::Definitions::Struct
    t = generate_struct_json_test(php_name, defn);                    out << t if t
    t = generate_struct_json_optionals_present_test(php_name, defn);  out << t if t
    t = generate_struct_negative_json_test(php_name, defn);           out << t if t
  when AST::Definitions::Union
    t = generate_union_json_test(php_name, defn);          out << t if t
    t = generate_union_negative_json_test(php_name, defn); out << t if t
  when AST::Definitions::Typedef
    t = generate_typedef_json_test(php_name, defn);          out << t if t
    t = generate_typedef_negative_json_test(php_name, defn); out << t if t
  end
  out.empty? ? nil : out
end

# Negative test for a typedef's fromJsonValue (wrong scalar type only — the
# inner validation is delegated to the underlying type's fromJsonValue).
def generate_typedef_negative_json_test(php_name, typedef_defn)
  return nil if TYPE_OVERRIDES.key?(php_name)
  json_class = json_class_for(php_name)
  return nil unless json_class

  value_expr = generate_typedef_value(php_name, typedef_defn, 0)
  return nil unless value_expr

  decl = typedef_defn.declaration
  # Only opaque/string/array typedefs have a directly-checkable scalar shape we
  # can reliably corrupt; for those the inner fromJsonValue rejects wrong types.
  bad_case =
    case decl
    when AST::Declarations::Opaque, AST::Declarations::String then "42"
    when AST::Declarations::Array then "'not-an-array'"
    else nil
    end
  return nil if bad_case.nil?

  imports = Set.new([php_name, json_class])

  lines = []
  lines << "    public function test#{php_name}TypedefJsonRejectsInvalid(): void"
  lines << "    {"
  lines << "        $threw = false;"
  lines << "        try { #{json_class}::fromJsonValue(#{bad_case}); }"
  lines << "        catch (\\InvalidArgumentException $e) { $threw = true; }"
  lines << "        $this->assertTrue($threw, 'Expected rejection for #{php_name} typedef JSON');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Typedef test generation
# ---------------------------------------------------------------------------

def generate_typedef_test(php_name, typedef_defn)
  # Skip typedefs that resolve to primitives (TYPE_OVERRIDES handles them)
  return nil if TYPE_OVERRIDES.key?(php_name)

  # Check if the PHP file exists
  php_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php")
  base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php")
  return nil unless File.exist?(php_file) || File.exist?(base_file)

  value_expr = generate_typedef_value(php_name, typedef_defn, 0)
  return nil unless value_expr

  class_name = php_name
  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  lines = []
  lines << "    public function test#{php_name}TypedefRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Binary roundtrip failed for #{php_name}');"
  lines << "        $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
  lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 roundtrip failed for #{php_name}');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Step 1: Invalid enum decode test — covers default: branch in decode()
# ---------------------------------------------------------------------------

def generate_enum_invalid_decode_test(php_name, enum_defn)
  class_name = php_name

  imports = Set.new([class_name])
  imports.add("#{php_name}Base") if BASE_WRAPPER_TYPES.include?(php_name)

  lines = []
  lines << "    public function test#{php_name}DecodeInvalidValueThrows(): void"
  lines << "    {"
  lines << "        $this->expectException(\\InvalidArgumentException::class);"
  lines << "        $invalidXdr = pack('N', 99999);"
  lines << "        #{class_name}::decode(new XdrBuffer($invalidXdr));"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Step 2: Struct with optional fields present — tests integer32(1) + value path
# ---------------------------------------------------------------------------

def generate_struct_optionals_present_test(php_name, struct_defn)
  # Skip for types with FALLBACK_VALUES — their construction is special
  return nil if FALLBACK_VALUES.key?(php_name)

  fields = analyze_struct_fields(php_name, struct_defn)
  has_optionals = fields.any? { |f| f[:is_optional] }
  return nil unless has_optionals

  class_name = php_name

  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  # Check if we can construct values for ALL optional fields
  can_populate_any = false
  args = ordered_fields.map do |f|
    if f[:is_array]
      "[]"
    elsif f[:is_optional]
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      if val
        can_populate_any = true
        val
      else
        "null"  # Fall back to null if we can't construct this optional
      end
    else
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      return nil if val.nil?
      val
    end
  end

  return nil unless can_populate_any

  value_expr = "new #{class_name}(#{args.join(', ')})"
  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  lines = []
  lines << "    public function test#{php_name}StructOptionalsPresentRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Optionals-present roundtrip failed for #{php_name}');"
  lines << "        $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
  lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 optionals-present roundtrip failed for #{php_name}');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Step 3: Struct with non-empty arrays — tests array encoding/decoding loop
# ---------------------------------------------------------------------------

def array_element_php_type(field_info)
  decl = field_info[:decl]
  return nil unless decl.is_a?(AST::Declarations::Array)
  php_type_for_typespec(decl.type)
end

def array_element_typespec(field_info)
  decl = field_info[:decl]
  return nil unless decl.is_a?(AST::Declarations::Array)
  decl.type
end

def generate_struct_with_arrays_test(php_name, struct_defn)
  # Skip for types with FALLBACK_VALUES — auto-construction may not match wrapper signature
  return nil if FALLBACK_VALUES.key?(php_name)

  fields = analyze_struct_fields(php_name, struct_defn)
  has_arrays = fields.any? { |f| f[:is_array] && !f[:is_ext_point] }
  return nil unless has_arrays

  class_name = php_name

  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  can_populate_any = false
  args = ordered_fields.map do |f|
    if f[:is_optional]
      "null"
    elsif f[:is_array]
      elem_type = array_element_php_type(f)
      elem_typespec = array_element_typespec(f)
      if elem_type
        elem_val = test_value_for_type(elem_type, elem_typespec, f[:decl], 1)
        if elem_val
          can_populate_any = true
          "[#{elem_val}]"
        else
          "[]"
        end
      else
        "[]"
      end
    else
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      return nil if val.nil?
      val
    end
  end

  return nil unless can_populate_any

  value_expr = "new #{class_name}(#{args.join(', ')})"
  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  lines = []
  lines << "    public function test#{php_name}StructWithArraysRoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Arrays roundtrip failed for #{php_name}');"
  lines << "        $b64Decoded = #{class_name}::fromBase64Xdr($original->toBase64Xdr());"
  lines << "        $this->assertEquals($encoded, $b64Decoded->encode(), 'Base64 arrays roundtrip failed for #{php_name}');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Step 5: Edge case tests — boundary values for int/string/BigInteger fields
# ---------------------------------------------------------------------------

def generate_struct_edge_case_tests(php_name, struct_defn)
  # Skip types with FALLBACK_VALUES — their construction is special
  return nil if FALLBACK_VALUES.key?(php_name)

  fields = analyze_struct_fields(php_name, struct_defn)
  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }

  # Find first required non-array field with an edge-case-testable type
  edge_field = required_fields.find do |f|
    next false if f[:is_array]
    next false if f[:php_type] != "int" && f[:php_type] != "string"
    # Skip fixed opaque fields — they require exact length, not empty string
    next false if f[:decl].is_a?(AST::Declarations::Opaque) && f[:decl].fixed?
    # Also skip string fields that resolve to fixed opaque via typedef
    if f[:decl].respond_to?(:type) && f[:decl].type.is_a?(AST::Typespecs::Simple)
      resolved = f[:decl].type.resolved_type
      if resolved.is_a?(AST::Definitions::Typedef)
        inner = resolved.declaration
        next false if inner.is_a?(AST::Declarations::Opaque) && inner.fixed?
      end
    end
    true
  end
  return nil unless edge_field

  # Determine edge values based on the underlying AST type
  typespec = edge_field[:decl].respond_to?(:type) ? edge_field[:decl].type : nil
  edge_values = case typespec
                when AST::Typespecs::Int
                  { "Zero" => "0", "MaxInt32" => "2147483647" }
                when AST::Typespecs::UnsignedInt
                  { "Zero" => "0", "MaxUInt32" => "4294967295" }
                when AST::Typespecs::Hyper
                  { "Zero" => "0" }
                when AST::Typespecs::UnsignedHyper
                  { "Zero" => "0" }
                when AST::Typespecs::String
                  { "EmptyString" => "''" }
                else
                  # Only emit EmptyString for actual XDR string declarations
                  if edge_field[:decl].is_a?(AST::Declarations::String)
                    { "EmptyString" => "''" }
                  elsif edge_field[:php_type] == "int"
                    { "Zero" => "0" }
                  else
                    nil
                  end
                end
  return nil unless edge_values

  class_name = php_name
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  tests = []
  edge_values.each do |label, edge_val|
    args = ordered_fields.map do |f|
      if f[:is_optional]
        "null"
      elsif f[:is_array]
        "[]"
      elsif f[:name] == edge_field[:name]
        edge_val
      else
        typespec_f = f[:decl].respond_to?(:type) ? f[:decl].type : nil
        val = test_value_for_type(f[:php_type], typespec_f, f[:decl], 1)
        return nil if val.nil?
        val
      end
    end

    value_expr = "new #{class_name}(#{args.join(', ')})"
    imports = collect_imports_from_expr(value_expr)
    imports.add(class_name)

    lines = []
    lines << "    public function test#{php_name}EdgeCase#{label}RoundTrip(): void"
    lines << "    {"
    lines << "        $original = #{value_expr};"
    lines << "        $encoded = $original->encode();"
    lines << "        $decoded = #{class_name}::decode(new XdrBuffer($encoded));"
    lines << "        $this->assertEquals($encoded, $decoded->encode(), 'Edge case #{label} failed for #{php_name}');"
    lines << "    }"

    tests << { lines: lines, imports: imports }
  end

  tests.empty? ? nil : tests
end

# ---------------------------------------------------------------------------
# Step 6: Getter/setter tests — tests get*/set* methods on generated classes
# ---------------------------------------------------------------------------

def generate_getter_setter_tests(php_name, struct_defn)
  fields = analyze_struct_fields(php_name, struct_defn)

  # We need the initial construction to succeed
  value_expr = generate_struct_value(php_name, struct_defn, 0)
  return nil unless value_expr

  class_name = php_name

  # Check if the PHP file has getters
  target_file = find_php_class_file(class_name)
  return nil unless target_file

  file_content = File.read(target_file)
  return nil unless file_content.include?("public function get")

  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  # Check we have at least one testable getter
  has_any = constructor_fields.any? do |f|
    getter = "get#{f[:name][0].upcase}#{f[:name][1..]}"
    file_content.include?("function #{getter}(")
  end
  return nil unless has_any

  lines = []
  lines << "    public function test#{php_name}GettersSetters(): void"
  lines << "    {"
  lines << "        $obj = #{value_expr};"

  constructor_fields.each do |f|
    getter = "get#{f[:name][0].upcase}#{f[:name][1..]}"
    setter = "set#{f[:name][0].upcase}#{f[:name][1..]}"

    next unless file_content.include?("function #{getter}(")

    if f[:is_optional] && !FALLBACK_VALUES.key?(php_name)
      lines << "        $this->assertNull($obj->#{getter}());"
    elsif f[:is_array]
      lines << "        $this->assertIsArray($obj->#{getter}());"
    elsif FALLBACK_VALUES.key?(php_name)
      # FALLBACK construction may differ from Base — just exercise the getter
      lines << "        $obj->#{getter}();"
    else
      lines << "        $this->assertNotNull($obj->#{getter}());"
    end

    # Test set + get roundtrip for non-optional, non-array fields with setter
    if !f[:is_optional] && !f[:is_array] && file_content.include?("function #{setter}(")
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      new_val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      if new_val
        imports.merge(collect_imports_from_expr(new_val))
        lines << "        $newVal = #{new_val};"
        lines << "        $obj->#{setter}($newVal);"
        lines << "        $this->assertSame($newVal, $obj->#{getter}());"
      end
    end
  end

  lines << "    }"

  { lines: lines, imports: imports }
end

# Generate getter/setter tests specifically targeting the Base class methods.
# For wrapper types, the Base class has getters/setters that may not be
# directly tested by wrapper tests.
def generate_base_class_getter_setter_tests(php_name, struct_defn)
  return nil unless BASE_WRAPPER_TYPES.include?(php_name)

  base_name = "#{php_name}Base"
  base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{base_name}.php")
  return nil unless File.exist?(base_file)

  file_content = File.read(base_file)
  return nil unless file_content.include?("public function get")

  fields = analyze_struct_fields(php_name, struct_defn)

  # Build constructor args for the Base class
  constructor_fields = fields.reject { |f| f[:is_ext_point] }
  required_fields = constructor_fields.reject { |f| f[:is_optional] }
  optional_fields = constructor_fields.select { |f| f[:is_optional] }
  ordered_fields = required_fields + optional_fields

  args = ordered_fields.map do |f|
    if f[:is_optional]
      "null"
    elsif f[:is_array]
      "[]"
    else
      typespec = f[:decl].respond_to?(:type) ? f[:decl].type : nil
      val = test_value_for_type(f[:php_type], typespec, f[:decl], 1)
      return nil if val.nil?
      val
    end
  end

  value_expr = "new #{base_name}(#{args.join(', ')})"
  imports = collect_imports_from_expr(value_expr)
  imports.add(base_name)

  lines = []
  lines << "    public function test#{base_name}GettersSetters(): void"
  lines << "    {"
  lines << "        $obj = #{value_expr};"

  constructor_fields.each do |f|
    getter = "get#{f[:name][0].upcase}#{f[:name][1..]}"
    next unless file_content.include?("function #{getter}(")

    if f[:is_optional]
      lines << "        $this->assertNull($obj->#{getter}());"
    elsif f[:is_array]
      lines << "        $this->assertIsArray($obj->#{getter}());"
    else
      lines << "        $this->assertNotNull($obj->#{getter}());"
    end
  end

  # Also test encode/decode on the Base class directly
  lines << "        $encoded = $obj->encode();"
  lines << "        $decoded = #{base_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode());"
  lines << "        $b64 = $obj->toBase64Xdr();"
  lines << "        $fromB64 = #{base_name}::fromBase64Xdr($b64);"
  lines << "        $this->assertEquals($encoded, $fromB64->encode());"

  lines << "    }"

  { lines: lines, imports: imports }
end

def generate_base_class_union_test(php_name, union_defn)
  return nil unless BASE_WRAPPER_TYPES.include?(php_name)

  base_name = "#{php_name}Base"
  base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{base_name}.php")
  return nil unless File.exist?(base_file)

  # Build a value expression using the Base class directly
  disc_info = resolve_discriminant_info_test(union_defn, php_name)

  # Find simplest arm (prefer void)
  void_arm = union_defn.normal_arms.find { |a| a.void? }
  if void_arm
    disc_expr = arm_discriminant_expr(void_arm.cases.first.value, disc_info)
    value_expr = "new #{base_name}(#{disc_expr})"
  else
    # Try non-void arms
    value_expr = nil
    union_defn.normal_arms.each do |arm|
      disc_expr = arm_discriminant_expr(arm.cases.first.value, disc_info)
      field_name = resolve_field_name(php_name, arm.name)
      decl = arm.declaration
      arm_php_type = php_type_string(decl)

      override_key = [base_name, field_name]
      base_override_key = [php_name, field_name]
      arm_value = if ARM_VALUE_OVERRIDES.key?(override_key)
                    ARM_VALUE_OVERRIDES[override_key]
                  elsif ARM_VALUE_OVERRIDES.key?(base_override_key)
                    ARM_VALUE_OVERRIDES[base_override_key]
                  else
                    typespec = decl.respond_to?(:type) ? decl.type : nil
                    test_value_for_type(arm_php_type, typespec, decl, 1)
                  end
      next unless arm_value

      value_expr = "(function() { $u = new #{base_name}(#{disc_expr}); $u->#{field_name} = #{arm_value}; return $u; })()"
      break
    end
    return nil unless value_expr
  end

  imports = collect_imports_from_expr(value_expr)
  imports.add(base_name)

  lines = []
  lines << "    public function test#{base_name}RoundTrip(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $encoded = $original->encode();"
  lines << "        $decoded = #{base_name}::decode(new XdrBuffer($encoded));"
  lines << "        $this->assertEquals($encoded, $decoded->encode());"
  lines << "        $b64 = $original->toBase64Xdr();"
  lines << "        $fromB64 = #{base_name}::fromBase64Xdr($b64);"
  lines << "        $this->assertEquals($encoded, $fromB64->encode());"
  lines << "    }"

  { lines: lines, imports: imports }
end

def generate_union_getter_setter_tests(php_name, union_defn)
  value_expr = generate_union_value(php_name, union_defn, 0)
  return nil unless value_expr

  class_name = php_name

  # Check if the PHP file (or Base file) has getters
  target_file = find_php_class_file(class_name)
  return nil unless target_file

  file_content = File.read(target_file)
  return nil unless file_content.include?("public function get")

  disc_info = resolve_discriminant_info_test(union_defn, php_name)

  imports = collect_imports_from_expr(value_expr)
  imports.add(class_name)

  lines = []
  lines << "    public function test#{php_name}GettersSetters(): void"
  lines << "    {"
  lines << "        $obj = #{value_expr};"

  # Test discriminant getter
  disc_getter = "get#{disc_info[:field_name][0].upcase}#{disc_info[:field_name][1..]}"
  if file_content.include?("function #{disc_getter}(")
    lines << "        $this->assertNotNull($obj->#{disc_getter}());"
  end

  # Test arm field getters — for each arm, check if getter exists
  union_defn.normal_arms.each do |arm|
    next if arm.void?
    field_name = resolve_field_name(php_name, arm.name)
    getter = "get#{field_name[0].upcase}#{field_name[1..]}"
    next unless file_content.include?("function #{getter}(")
    # Just check getter is callable (it returns null for non-active arms, non-null for active)
    lines << "        $obj->#{getter}();"  # just exercise the method
  end

  lines << "    }"

  { lines: lines, imports: imports }
end

# ---------------------------------------------------------------------------
# Step 7: Factory method tests — tests static factory methods on wrapper classes
# ---------------------------------------------------------------------------

# Explicit factory method test definitions: { ClassName => { method_name => [arg_exprs] } }
# Only methods listed here will get factory tests (safe, known-working calls).
FACTORY_METHOD_TESTS = {
  "XdrSCAddress" => {
    "forAccountId" => ["'#{TEST_ACCOUNT_ID}'"],
  },
  "XdrContractExecutable" => {
    "forToken" => [],
    "forWasmId" => ["str_repeat('ab', 32)"],
  },
  "XdrAccountID" => {
    "fromAccountId" => ["'#{TEST_ACCOUNT_ID}'"],
  },
  "XdrAllowTrustOperationAsset" => {
    "fromAlphaNumAssetCode" => ["'USD'"],
  },
  "XdrHostFunction" => {
    "forUploadContractWasm" => ["str_repeat(\"\\xAB\", 64)"],
    "forDeploySACWithAsset" => ["new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE))"],
  },
  "XdrLedgerKey" => {
    "forAccountId" => ["'#{TEST_ACCOUNT_ID}'"],
  },
}.freeze

def generate_factory_method_tests(php_name)
  return nil unless FACTORY_METHOD_TESTS.key?(php_name)

  tests = []

  FACTORY_METHOD_TESTS[php_name].each do |method_name, arg_values|
    imports = Set.new([php_name])
    arg_values.each { |v| imports.merge(collect_imports_from_expr(v)) }

    lines = []
    lines << "    public function test#{php_name}_#{method_name}_Factory(): void"
    lines << "    {"
    lines << "        $result = #{php_name}::#{method_name}(#{arg_values.join(', ')});"
    lines << "        $this->assertInstanceOf(#{php_name}::class, $result);"
    lines << "        $encoded = $result->encode();"
    lines << "        $this->assertNotEmpty($encoded);"
    lines << "        $decoded = #{php_name}::decode(new XdrBuffer($encoded));"
    lines << "        $this->assertEquals($encoded, $decoded->encode());"
    lines << "    }"

    tests << { lines: lines, imports: imports }
  end

  tests.empty? ? nil : tests
end

# ---------------------------------------------------------------------------
# Import collection from PHP expressions
# ---------------------------------------------------------------------------

def collect_imports_from_expr(expr)
  imports = Set.new
  # Match class references: "new XdrFoo(" or "XdrFoo::" or "XdrFoo::fromAccountId"
  expr.scan(/\b(Xdr[A-Z][a-zA-Z0-9]*)\b/).flatten.uniq.each do |cls|
    imports.add(cls)
  end
  # BigInteger
  imports.add("BigInteger") if expr.include?("BigInteger")
  # XdrDataValueMandatory
  imports.add("XdrDataValueMandatory") if expr.include?("XdrDataValueMandatory")
  imports
end

# ---------------------------------------------------------------------------
# TxRep roundtrip test generation
#
# Generates tests that exercise toTxRep/fromTxRep on every TXREP_TYPES type.
# For enums, tests every member's enumName/fromTxRepName.
# For unions, tests every arm.
# ---------------------------------------------------------------------------

# Types to skip TxRep test generation for (same as SKIP_TEST_TYPES but
# additionally skipping types whose TxRep construction is too complex).
SKIP_TXREP_TEST_TYPES = (SKIP_TEST_TYPES + %w[
  XdrTransactionV0Envelope
]).freeze

# Check if a PHP file (or its Base file) contains a fromTxRep method.
def has_from_txrep?(php_name)
  is_base = BASE_WRAPPER_TYPES.include?(php_name)
  if is_base
    # Check wrapper file first
    wrapper_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php")
    if File.exist?(wrapper_file)
      return true if File.read(wrapper_file).include?("function fromTxRep(")
    end
    # Check base file
    base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php")
    if File.exist?(base_file)
      return true if File.read(base_file).include?("function fromTxRep(")
    end
  else
    file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php")
    if File.exist?(file)
      return true if File.read(file).include?("function fromTxRep(")
    end
  end
  false
end

# Determine which class to use for fromTxRep calls.
# For BASE_WRAPPER_TYPES, the wrapper may override fromTxRep.
def from_txrep_class(php_name)
  if BASE_WRAPPER_TYPES.include?(php_name)
    wrapper_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php")
    if File.exist?(wrapper_file) && File.read(wrapper_file).include?("function fromTxRep(")
      return php_name
    end
    base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php")
    if File.exist?(base_file) && File.read(base_file).include?("function fromTxRep(")
      return "#{php_name}Base"
    end
  end
  php_name
end

# Determine which class to use for toTxRep calls (construction class).
# For BASE_WRAPPER_TYPES, we use the wrapper for construction since it may
# have custom toTxRep.
def to_txrep_class(php_name)
  php_name
end

# Check if a PHP class has enumName/fromTxRepName methods (enum-specific TxRep).
def has_enum_txrep_names?(php_name)
  is_base = BASE_WRAPPER_TYPES.include?(php_name)
  target_class = is_base ? "#{php_name}Base" : php_name
  file = File.join("Soneso", "StellarSDK", "Xdr", "#{target_class}.php")
  return false unless File.exist?(file)
  content = File.read(file)
  content.include?("function enumName(") && content.include?("function fromTxRepName(")
end

# Generate TxRep roundtrip tests for a given type.
# Returns an array of test hashes or nil.
def generate_txrep_tests(php_name, defn)
  return nil unless TXREP_TYPES.include?(php_name)
  return nil if SKIP_TXREP_TEST_TYPES.include?(php_name)
  return nil unless has_from_txrep?(php_name)

  case defn
  when AST::Definitions::Enum
    generate_enum_txrep_tests(php_name, defn)
  when AST::Definitions::Struct
    generate_struct_txrep_test(php_name, defn)
  when AST::Definitions::Union
    generate_union_txrep_tests(php_name, defn)
  when AST::Definitions::Typedef
    generate_typedef_txrep_test(php_name, defn)
  else
    nil
  end
rescue => e
  $stderr.puts "WARNING: Failed to generate TxRep test for #{php_name}: #{e.message}"
  $stderr.puts e.backtrace.first(3).join("\n")
  nil
end

# Generate a single TxRep roundtrip test.
# value_expr: PHP expression that constructs the test value.
# construct_class: class used for toTxRep (the value's class).
# decode_class: class used for fromTxRep.
def txrep_roundtrip_test_php(php_name, value_expr, suffix = "")
  construct_class = to_txrep_class(php_name)
  decode_class = from_txrep_class(php_name)

  imports = collect_imports_from_expr(value_expr)
  imports.add(construct_class)
  imports.add(decode_class) if decode_class != construct_class
  imports.add("TxRepHelper")

  safe_suffix = suffix.gsub(/[^a-zA-Z0-9_]/, "")
  method_name = "test#{php_name}TxRepRoundTrip#{safe_suffix}"

  lines = []
  lines << "    public function #{method_name}(): void"
  lines << "    {"
  lines << "        $original = #{value_expr};"
  lines << "        $lines = [];"
  lines << "        $original->toTxRep('test', $lines);"
  lines << "        $reconstructed = #{decode_class}::fromTxRep($lines, 'test');"
  lines << "        $this->assertEquals($original->toBase64Xdr(), $reconstructed->toBase64Xdr(), 'TxRep roundtrip failed for #{php_name}#{suffix}');"
  lines << "    }"

  { lines: lines, imports: imports }
end

# Generate TxRep tests for all enum members.
# Tests enumName/fromTxRepName for each member, and toTxRep/fromTxRep roundtrip.
def generate_enum_txrep_tests(php_name, enum_defn)
  tests = []

  # Test enumName/fromTxRepName for every member
  if has_enum_txrep_names?(php_name)
    is_base = BASE_WRAPPER_TYPES.include?(php_name)
    const_class = is_base ? "#{php_name}Base" : php_name
    class_name = php_name

    imports = Set.new([class_name, "TxRepHelper"])
    imports.add(const_class) if const_class != class_name

    lines = []
    lines << "    public function test#{php_name}TxRepEnumNames(): void"
    lines << "    {"

    enum_defn.members.each do |m|
      member_name = resolve_member_name(php_name, m.name.to_s)
      xdr_member_name = m.name.to_s  # Original XDR name used by enumName()
      lines << "        $val = new #{class_name}(#{const_class}::#{member_name});"
      lines << "        $name = $val->enumName();"
      lines << "        $this->assertEquals('#{xdr_member_name}', $name);"
      lines << "        $back = #{class_name}::fromTxRepName($name);"
      lines << "        $this->assertEquals($val->getValue(), $back->getValue());"
    end

    lines << "    }"
    tests << { lines: lines, imports: imports }
  end

  # TxRep roundtrip for each enum member
  is_base = BASE_WRAPPER_TYPES.include?(php_name)
  const_class = is_base ? "#{php_name}Base" : php_name

  enum_defn.members.each do |m|
    member_name = resolve_member_name(php_name, m.name.to_s)
    value_expr = "new #{php_name}(#{const_class}::#{member_name})"
    test = txrep_roundtrip_test_php(php_name, value_expr, "_#{member_name}")
    tests << test if test
  end

  tests.empty? ? nil : tests
end

# Generate TxRep roundtrip test for a struct type.
def generate_struct_txrep_test(php_name, struct_defn)
  value_expr = generate_struct_value(php_name, struct_defn, 0)
  return nil unless value_expr

  test = txrep_roundtrip_test_php(php_name, value_expr)
  test ? [test] : nil
end

# Generate TxRep roundtrip tests for a union type — one test per arm.
def generate_union_txrep_tests(php_name, union_defn)
  # If a FALLBACK_VALUES entry exists, generate a single roundtrip test
  if FALLBACK_VALUES.key?(php_name)
    test = txrep_roundtrip_test_php(php_name, FALLBACK_VALUES[php_name])
    return test ? [test] : nil
  end

  class_name = php_name
  disc_info = resolve_discriminant_info_test(union_defn, php_name)

  tests = []

  # Non-void arms
  union_defn.normal_arms.each do |arm|
    next if arm.void?

    case_value = arm.cases.first.value
    disc_expr = arm_discriminant_expr(case_value, disc_info)
    case_label = arm_case_label(case_value, disc_info)

    field_name = resolve_field_name(php_name, arm.name)
    decl = arm.declaration

    arm_php_type = nil
    if FIELD_TYPE_OVERRIDES.key?(php_name) && FIELD_TYPE_OVERRIDES[php_name].key?(field_name)
      arm_php_type = FIELD_TYPE_OVERRIDES[php_name][field_name]
    else
      arm_php_type = php_type_string(decl)
    end

    override_key = [php_name, field_name]
    arm_value = if ARM_VALUE_OVERRIDES.key?(override_key)
                  ARM_VALUE_OVERRIDES[override_key]
                else
                  typespec = decl.respond_to?(:type) ? decl.type : nil
                  test_value_for_type(arm_php_type, typespec, decl, 1)
                end
    next unless arm_value

    safe_label = case_label.gsub("::", "_").gsub(/[^a-zA-Z0-9_]/, "")
    value_expr = "(function() { $u = new #{class_name}(#{disc_expr}); $u->#{field_name} = #{arm_value}; return $u; })()"
    test = txrep_roundtrip_test_php(php_name, value_expr, "_#{safe_label}")
    tests << test if test
  end

  # Void arms
  union_defn.normal_arms.each do |arm|
    next unless arm.void?

    case_value = arm.cases.first.value
    disc_expr = arm_discriminant_expr(case_value, disc_info)
    case_label = arm_case_label(case_value, disc_info)

    safe_label = case_label.gsub("::", "_").gsub(/[^a-zA-Z0-9_]/, "")
    value_expr = "new #{class_name}(#{disc_expr})"
    test = txrep_roundtrip_test_php(php_name, value_expr, "_#{safe_label}")
    tests << test if test
  end

  # If no arm tests generated, try single fallback
  if tests.empty?
    value_expr = generate_union_value(php_name, union_defn, 0)
    return nil unless value_expr
    test = txrep_roundtrip_test_php(php_name, value_expr)
    return test ? [test] : nil
  end

  tests
end

# Generate TxRep roundtrip test for a typedef type.
def generate_typedef_txrep_test(php_name, typedef_defn)
  return nil if TYPE_OVERRIDES.key?(php_name)

  php_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}.php")
  base_file = File.join("Soneso", "StellarSDK", "Xdr", "#{php_name}Base.php")
  return nil unless File.exist?(php_file) || File.exist?(base_file)

  value_expr = generate_typedef_value(php_name, typedef_defn, 0)
  return nil unless value_expr

  test = txrep_roundtrip_test_php(php_name, value_expr)
  test ? [test] : nil
end

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

# Capture-only generator — stores the parsed AST top node for test generation.
$captured_top = nil
class CaptureGenerator < Xdrgen::Generators::Base
  def generate
    $captured_top = @top
  end
end

puts "Generating PHP XDR round-trip tests..."

Dir.chdir("../..")

source_files = Dir.glob("xdr/*.x").sort

Xdrgen::Compilation.new(
  source_files,
  output_dir: "Soneso/StellarSDK/Xdr/",
  generator: CaptureGenerator,
  namespace: "stellar",
).compile

# Build type registry from the parsed AST
build_type_registry($captured_top)

# Group definitions by .x source file
groups = group_definitions_by_source($captured_top, source_files)

# Ensure output directory exists
output_dir = "Soneso/StellarSDKTests/Unit/Xdr/Generated"
FileUtils.mkdir_p(output_dir)

groups.each do |source, definitions|
  next if definitions.empty?
  generate_test_file(source, definitions, output_dir)
end

# Count total tests across all generated files
test_count = 0
Dir.glob(File.join(output_dir, "*.php")).each do |f|
  test_count += File.read(f).scan(/public function test/).size
end

puts "Done! Generated #{test_count} tests in #{Dir.glob(File.join(output_dir, '*.php')).size} files."
