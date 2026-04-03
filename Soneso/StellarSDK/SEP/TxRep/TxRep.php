<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TxRep;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\TxRepHelper;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransaction;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrPreconditionType;
use Soneso\StellarSDK\Xdr\XdrPreconditions;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrTimeBounds;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;

/**
 * SEP-0011 TxRep: human-readable representation of Stellar transactions.
 *
 * Thin facade over the generated XDR toTxRep/fromTxRep methods. Handles the
 * envelope-level structure (type header, fee-bump wrapper, inner-tx type line,
 * signatures, and the sorobanData path) which cannot be expressed by purely
 * generic generated code. All inner transaction fields are delegated to the
 * generated XdrTransaction::toTxRep / fromTxRep methods.
 *
 * Public API is backwards-compatible with the previous monolithic implementation.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md
 */
class TxRep
{
    // ---------------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------------

    /**
     * Convert a base64-encoded transaction envelope XDR to TxRep text.
     *
     * @param string $transactionEnvelopeXdrBase64 Base64-encoded XDR.
     * @return string TxRep text (key: value lines joined by PHP_EOL).
     * @throws InvalidArgumentException If the XDR is invalid.
     */
    public static function fromTransactionEnvelopeXdrBase64(string $transactionEnvelopeXdrBase64): string
    {
        $xdr = base64_decode($transactionEnvelopeXdrBase64, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded transaction envelope XDR');
        }
        $envelope = XdrTransactionEnvelope::decode(new XdrBuffer($xdr));

        $lines = [];

        switch ($envelope->getType()->getValue()) {
            case XdrEnvelopeType::ENVELOPE_TYPE_TX_V0:
                self::v0EnvelopeToLines($envelope->getV0(), $lines);
                break;

            case XdrEnvelopeType::ENVELOPE_TYPE_TX:
                $v1 = $envelope->getV1();
                $lines['type'] = 'ENVELOPE_TYPE_TX';
                self::transactionToLines($v1->getTx(), 'tx', $lines);
                self::signaturesToLines('', $v1->getSignatures(), $lines);
                break;

            case XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP:
                self::feeBumpEnvelopeToLines($envelope->getFeeBump(), $lines);
                break;

            default:
                throw new InvalidArgumentException(
                    'Unsupported envelope type: ' . $envelope->getType()->getValue()
                );
        }

        return self::formatLines($lines);
    }

    /**
     * Convert TxRep text to a base64-encoded transaction envelope XDR.
     *
     * @param string $txRep TxRep text.
     * @return string Base64-encoded XDR.
     * @throws InvalidArgumentException If required TxRep fields are missing or invalid.
     */
    public static function transactionEnvelopeXdrBase64FromTxRep(string $txRep): string
    {
        $map = TxRepHelper::parse($txRep);

        $typeStr = TxRepHelper::getValue($map, 'type');
        $isFeeBump = ($typeStr === 'ENVELOPE_TYPE_TX_FEE_BUMP');

        if ($isFeeBump) {
            return self::buildFeeBumpEnvelope($map)->toBase64Xdr();
        }

        return self::buildV1Envelope($map, 'tx')->toBase64Xdr();
    }

    // ---------------------------------------------------------------------------
    // Encoding helpers
    // ---------------------------------------------------------------------------

    /**
     * Encode a V0 envelope by converting it to V1 format first.
     *
     * @param \Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope $v0
     * @param array<string,string> $lines
     */
    private static function v0EnvelopeToLines(
        \Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope $v0,
        array &$lines,
    ): void {
        // Convert V0 source account (raw Ed25519) to a muxed account wrapper.
        $sourceAccount = new XdrMuxedAccount($v0->getTx()->getSourceAccountEd25519());
        $v0tx = $v0->getTx();

        // Build a synthetic V1 XdrTransaction so we can re-use transactionToLines.
        $cond = ($v0tx->getTimeBounds() !== null)
            ? new XdrPreconditions(XdrPreconditionType::TIME())
            : new XdrPreconditions(XdrPreconditionType::NONE());
        if ($v0tx->getTimeBounds() !== null) {
            $cond->setTimeBounds($v0tx->getTimeBounds());
        }

        $synthetic = new XdrTransaction(
            $sourceAccount,
            $v0tx->getSeqNum(),
            $v0tx->getOperations(),
            $v0tx->getFee(),
            $v0tx->getMemo(),
            $cond,
            new XdrTransactionExt(0),
        );

        $lines['type'] = 'ENVELOPE_TYPE_TX';
        self::transactionToLines($synthetic, 'tx', $lines);
        self::signaturesToLines('', $v0->getSignatures(), $lines);
    }

    /**
     * Emit all transaction body fields into $lines under $prefix.
     *
     * Handles sourceAccount, fee, seqNum, preconditions, memo, operations, and
     * ext (including the sorobanData path at $prefix.sorobanData rather than
     * $prefix.ext.sorobanData to match the SEP-0011 format).
     *
     * @param XdrTransaction       $tx
     * @param string               $prefix  e.g. 'tx' or 'feeBump.tx.innerTx.tx'
     * @param array<string,string> $lines
     */
    private static function transactionToLines(XdrTransaction $tx, string $prefix, array &$lines): void
    {
        $lines[$prefix . '.sourceAccount'] = TxRepHelper::formatMuxedAccount($tx->getSourceAccount());
        $lines[$prefix . '.fee']           = (string)$tx->getFee();
        $tx->getSequenceNumber()->toTxRep($prefix . '.seqNum', $lines);
        $tx->getPreconditions()->toTxRep($prefix . '.cond', $lines);
        $tx->getMemo()->toTxRep($prefix . '.memo', $lines);

        $operations = $tx->getOperations();
        $lines[$prefix . '.operations.len'] = (string)count($operations);
        for ($i = 0; $i < count($operations); $i++) {
            $operations[$i]->toTxRep($prefix . '.operations[' . $i . ']', $lines);
        }

        // Emit ext.v manually, then place sorobanData at the transaction level
        // (i.e. $prefix.sorobanData, not $prefix.ext.sorobanData) to match SEP-0011.
        $ext = $tx->getExt();
        $lines[$prefix . '.ext.v'] = (string)$ext->getDiscriminant();
        if ($ext->getDiscriminant() === 1 && $ext->getSorobanTransactionData() !== null) {
            $ext->getSorobanTransactionData()->toTxRep($prefix . '.sorobanData', $lines);
        }
    }

    /**
     * Emit signature lines.
     *
     * @param string                         $prefix    Empty string for regular tx (''),
     *                                                  or prefix ending with '.' for fee bump contexts.
     * @param array<XdrDecoratedSignature>   $signatures
     * @param array<string,string>           $lines
     */
    private static function signaturesToLines(string $prefix, array $signatures, array &$lines): void
    {
        $lines[$prefix . 'signatures.len'] = (string)count($signatures);
        for ($i = 0; $i < count($signatures); $i++) {
            $lines[$prefix . 'signatures[' . $i . '].hint']      = TxRepHelper::bytesToHex($signatures[$i]->getHint());
            $lines[$prefix . 'signatures[' . $i . '].signature'] = TxRepHelper::bytesToHex($signatures[$i]->getSignature());
        }
    }

    /**
     * Encode a fee-bump envelope.
     *
     * @param XdrFeeBumpTransactionEnvelope $feeBumpEnv
     * @param array<string,string>          $lines
     */
    private static function feeBumpEnvelopeToLines(
        XdrFeeBumpTransactionEnvelope $feeBumpEnv,
        array &$lines,
    ): void {
        $fbTx = $feeBumpEnv->getTx();
        $innerV1 = $fbTx->getInnerTx()->getV1();

        $lines['type'] = 'ENVELOPE_TYPE_TX_FEE_BUMP';
        $lines['feeBump.tx.feeSource'] = TxRepHelper::formatMuxedAccount($fbTx->getFeeSource());
        $lines['feeBump.tx.fee']       = (string)$fbTx->getFee();
        $lines['feeBump.tx.innerTx.type'] = 'ENVELOPE_TYPE_TX';

        self::transactionToLines($innerV1->getTx(), 'feeBump.tx.innerTx.tx', $lines);
        self::signaturesToLines('feeBump.tx.innerTx.', $innerV1->getSignatures(), $lines);

        $lines['feeBump.tx.ext.v'] = (string)$fbTx->getExt()->getDiscriminant();

        self::signaturesToLines('feeBump.', $feeBumpEnv->getSignatures(), $lines);
    }

    // ---------------------------------------------------------------------------
    // Decoding helpers
    // ---------------------------------------------------------------------------

    /**
     * Build an XdrTransactionEnvelope (V1) from a parsed TxRep map.
     *
     * @param array<string,string> $map    Parsed TxRep map.
     * @param string               $prefix Transaction prefix (e.g. 'tx').
     * @return XdrTransactionEnvelope
     */
    private static function buildV1Envelope(array $map, string $prefix): XdrTransactionEnvelope
    {
        $tx        = self::decodeTransaction($map, $prefix);
        $sigs      = self::decodeSignatures($map, '');
        $v1        = new XdrTransactionV1Envelope($tx, $sigs);
        $envelope  = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1($v1);
        return $envelope;
    }

    /**
     * Build an XdrTransactionEnvelope (fee bump) from a parsed TxRep map.
     *
     * @param array<string,string> $map
     * @return XdrTransactionEnvelope
     */
    private static function buildFeeBumpEnvelope(array $map): XdrTransactionEnvelope
    {
        $feeSourceStr = TxRepHelper::getValue($map, 'feeBump.tx.feeSource');
        if ($feeSourceStr === null) {
            throw new InvalidArgumentException('missing feeBump.tx.feeSource');
        }
        $feeSource = TxRepHelper::parseMuxedAccount($feeSourceStr);

        $feeStr = TxRepHelper::getValue($map, 'feeBump.tx.fee');
        if ($feeStr === null || !is_numeric($feeStr)) {
            throw new InvalidArgumentException('missing or invalid feeBump.tx.fee');
        }
        $fee = (int)$feeStr;

        // Inner transaction.
        $innerTx   = self::decodeTransaction($map, 'feeBump.tx.innerTx.tx');
        $innerSigs = self::decodeSignatures($map, 'feeBump.tx.innerTx.');
        $innerV1   = new XdrTransactionV1Envelope($innerTx, $innerSigs);

        $innerTxWrapper = new XdrFeeBumpTransactionInnerTx(
            new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX)
        );
        $innerTxWrapper->setV1($innerV1);

        $fbTx = new XdrFeeBumpTransaction(
            $feeSource,
            $fee,
            $innerTxWrapper,
            new XdrFeeBumpTransactionExt(0),
        );

        $outerSigs   = self::decodeSignatures($map, 'feeBump.');
        $feeBumpEnv  = new XdrFeeBumpTransactionEnvelope($fbTx, $outerSigs);
        $envelope    = new XdrTransactionEnvelope(
            new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP)
        );
        $envelope->setFeeBump($feeBumpEnv);
        return $envelope;
    }

    /**
     * Build an XdrTransaction from a parsed TxRep map.
     *
     * Handles all transaction body fields: sourceAccount, fee, seqNum,
     * preconditions (with legacy timeBounds._present fallback), memo,
     * operations, and ext/sorobanData.
     *
     * @param array<string,string> $map
     * @param string               $prefix  e.g. 'tx' or 'feeBump.tx.innerTx.tx'
     * @return XdrTransaction
     */
    private static function decodeTransaction(array $map, string $prefix): XdrTransaction
    {
        // Source account.
        $sourceAccountStr = TxRepHelper::getValue($map, $prefix . '.sourceAccount');
        if ($sourceAccountStr === null) {
            throw new InvalidArgumentException('missing ' . $prefix . '.sourceAccount');
        }
        $sourceAccount = TxRepHelper::parseMuxedAccount($sourceAccountStr);

        // Fee.
        $feeStr = TxRepHelper::getValue($map, $prefix . '.fee');
        if ($feeStr === null || !is_numeric($feeStr)) {
            throw new InvalidArgumentException('missing or invalid ' . $prefix . '.fee');
        }
        $fee = (int)$feeStr;

        // Sequence number.
        $seqNum = XdrSequenceNumber::fromTxRep($map, $prefix . '.seqNum');

        // Preconditions — delegate to generated code; fall back to legacy format.
        $condType = TxRepHelper::getValue($map, $prefix . '.cond.type');
        if ($condType !== null) {
            $cond = XdrPreconditions::fromTxRep($map, $prefix . '.cond');
        } else {
            // Legacy TxRep used timeBounds._present instead of cond.type.
            $tbPresent = TxRepHelper::getValue($map, $prefix . '.timeBounds._present');
            if ($tbPresent === 'true') {
                $cond = new XdrPreconditions(XdrPreconditionType::TIME());
                $cond->setTimeBounds(XdrTimeBounds::fromTxRep($map, $prefix . '.timeBounds'));
            } else {
                $cond = new XdrPreconditions(XdrPreconditionType::NONE());
            }
        }

        // Memo.
        $memo = self::decodeMemo($map, $prefix . '.memo');

        // Operations.
        $opsLenStr = TxRepHelper::getValue($map, $prefix . '.operations.len');
        if ($opsLenStr === null || !is_numeric($opsLenStr)) {
            throw new InvalidArgumentException('missing or invalid ' . $prefix . '.operations.len');
        }
        $opsLen = (int)$opsLenStr;
        $operations = [];
        for ($i = 0; $i < $opsLen; $i++) {
            $operations[] = XdrOperation::fromTxRep($map, $prefix . '.operations[' . $i . ']');
        }

        // Ext/sorobanData — sorobanData is at the transaction level in SEP-0011,
        // not inside ext. Read ext.v to determine discriminant, then read
        // sorobanData from $prefix.sorobanData (not $prefix.ext.sorobanData).
        $extV = TxRepHelper::getValue($map, $prefix . '.ext.v');
        if ($extV !== null && (int)$extV === 1) {
            $ext = new XdrTransactionExt(1);
            $ext->setSorobanTransactionData(
                XdrSorobanTransactionData::fromTxRep($map, $prefix . '.sorobanData')
            );
        } else {
            $ext = new XdrTransactionExt(0);
        }

        return new XdrTransaction($sourceAccount, $seqNum, $operations, $fee, $memo, $cond, $ext);
    }

    /**
     * Decode a memo from the TxRep map.
     *
     * Handles MEMO_NONE, MEMO_TEXT (with both json-encoded and escape-sequence
     * strings for backwards compatibility), MEMO_ID, MEMO_HASH, and MEMO_RETURN.
     *
     * @param array<string,string> $map
     * @param string               $prefix  e.g. 'tx.memo'
     * @return XdrMemo
     */
    private static function decodeMemo(array $map, string $prefix): XdrMemo
    {
        $memoTypeStr = TxRepHelper::getValue($map, $prefix . '.type');
        if ($memoTypeStr === null) {
            throw new InvalidArgumentException('missing ' . $prefix . '.type');
        }

        if ($memoTypeStr === 'MEMO_NONE') {
            return new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_NONE));
        }

        if ($memoTypeStr === 'MEMO_TEXT') {
            $raw = TxRepHelper::getValue($map, $prefix . '.text') ?? '';
            // Accept both json_encode format ("text") and TxRepHelper::escapeString format.
            // json_decode handles both "plain text" and "escaped\ntext" transparently.
            if (str_starts_with($raw, '"') && str_ends_with($raw, '"')) {
                $text = json_decode($raw, false);
                if ($text === null) {
                    // Fall back to TxRepHelper::unescapeString for non-JSON-valid strings.
                    $text = TxRepHelper::unescapeString($raw);
                }
            } else {
                $text = $raw;
            }
            $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
            $memo->setText((string)$text);
            return $memo;
        }

        if ($memoTypeStr === 'MEMO_ID') {
            $idStr = TxRepHelper::getValue($map, $prefix . '.id');
            if ($idStr === null || !is_numeric($idStr)) {
                throw new InvalidArgumentException('missing or invalid ' . $prefix . '.id');
            }
            $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_ID));
            $memo->setId((int)$idStr);
            return $memo;
        }

        if ($memoTypeStr === 'MEMO_HASH') {
            $hashHex = TxRepHelper::getValue($map, $prefix . '.hash');
            if ($hashHex === null) {
                throw new InvalidArgumentException('missing ' . $prefix . '.hash');
            }
            $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_HASH));
            $memo->setHash(hex2bin($hashHex) ?: '');
            return $memo;
        }

        if ($memoTypeStr === 'MEMO_RETURN') {
            $hashHex = TxRepHelper::getValue($map, $prefix . '.retHash');
            if ($hashHex === null) {
                throw new InvalidArgumentException('missing ' . $prefix . '.retHash');
            }
            $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_RETURN));
            $memo->setReturnHash(hex2bin($hashHex) ?: '');
            return $memo;
        }

        throw new InvalidArgumentException('Unknown memo type: ' . $memoTypeStr);
    }

    /**
     * Decode a list of decorated signatures from the TxRep map.
     *
     * @param array<string,string> $map
     * @param string               $prefix  Prefix with trailing dot or empty: '' or 'feeBump.'
     *                                      or 'feeBump.tx.innerTx.'
     * @return array<XdrDecoratedSignature>
     */
    private static function decodeSignatures(array $map, string $prefix): array
    {
        $lenStr = TxRepHelper::getValue($map, $prefix . 'signatures.len');
        if ($lenStr === null || !is_numeric($lenStr)) {
            return [];
        }
        $count = (int)$lenStr;
        $sigs  = [];
        for ($i = 0; $i < $count; $i++) {
            $hintHex = TxRepHelper::getValue($map, $prefix . 'signatures[' . $i . '].hint');
            $sigHex  = TxRepHelper::getValue($map, $prefix . 'signatures[' . $i . '].signature');
            if ($hintHex === null || $sigHex === null) {
                throw new InvalidArgumentException(
                    'missing ' . $prefix . 'signatures[' . $i . '].hint or .signature'
                );
            }
            $sigs[] = new XdrDecoratedSignature(
                TxRepHelper::hexToBytes($hintHex),
                TxRepHelper::hexToBytes($sigHex),
            );
        }
        return $sigs;
    }

    // ---------------------------------------------------------------------------
    // Output formatting
    // ---------------------------------------------------------------------------

    /**
     * Format a key/value map as TxRep text.
     *
     * Each entry is emitted as "key: value". Lines are joined with PHP_EOL.
     * No trailing newline is added to the last line.
     *
     * @param array<string,string> $lines
     * @return string
     */
    private static function formatLines(array $lines): string
    {
        $keys   = array_keys($lines);
        $count  = count($keys);
        $result = '';
        for ($i = 0; $i < $count; $i++) {
            $key    = $keys[$i];
            $result .= $key . ': ' . $lines[$key];
            if ($i < $count - 1) {
                $result .= PHP_EOL;
            }
        }
        return $result;
    }
}
