<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TxRep;

use DateTime;
use Exception;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\AccountMergeOperation;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AllowTrustOperation;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperation;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\BumpSequenceOperation;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClaimClaimableBalanceOperation;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\ClawbackClaimableBalanceOperation;
use Soneso\StellarSDK\ClawbackClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\ClawbackOperation;
use Soneso\StellarSDK\ClawbackOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperation;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\CreatePassiveSellOfferOperation;
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperation;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\ExtendFootprintTTLOperation;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\LiquidityPoolDepositOperation;
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;
use Soneso\StellarSDK\LiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\LiquidityPoolWithdrawOperationBuilder;
use Soneso\StellarSDK\ManageBuyOfferOperation;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\ManageDataOperation;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperation;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\RestoreFootprintOperation;
use Soneso\StellarSDK\RevokeSponsorshipOperation;
use Soneso\StellarSDK\RevokeSponsorshipOperationBuilder;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\SetTrustLineFlagsOperation;
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Util\StellarAmount;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use Soneso\StellarSDK\Xdr\XdrConfigSettingID;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimage;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrInvokeContractArgs;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunction;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanResourcesExtV0;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

class TxRep
{
    public static function fromTransactionEnvelopeXdrBase64(string $transactionEnvelopeXdrBase64): string
    {

        $tx = null;
        $feeBump = null;
        $feeBumpSignatures = null;

        $xdr = base64_decode($transactionEnvelopeXdrBase64);
        $xdrBuffer = new XdrBuffer($xdr);
        $envelopeXdr = XdrTransactionEnvelope::decode($xdrBuffer);

        switch ($envelopeXdr->getType()->getValue()) {
            case XdrEnvelopeType::ENVELOPE_TYPE_TX_V0:
                $tx = Transaction::fromV0EnvelopeXdr($envelopeXdr->getV0());
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_TX:
                $tx = Transaction::fromV1EnvelopeXdr($envelopeXdr->getV1());
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP:
                $feeBump = FeeBumpTransaction::fromFeeBumpTransactionEnvelope($envelopeXdr->getFeeBump());
                $tx = $feeBump->getInnerTx();
                $feeBumpSignatures = $envelopeXdr->getFeeBump()->getSignatures();
                break;
        }
        $isFeeBump = $feeBump != null;
        $lines = array();
        $type = $isFeeBump ? 'ENVELOPE_TYPE_TX_FEE_BUMP' : 'ENVELOPE_TYPE_TX';
        $prefix = $isFeeBump ? 'feeBump.tx.innerTx.tx.' : 'tx.';
        $lines += ['type' => $type];

        if ($isFeeBump) {
            $lines += ['feeBump.tx.feeSource' => $feeBump->getFeeAccount()->getAccountId()];
            $lines += ['feeBump.tx.fee' => strval($feeBump->getFee())];
            $lines += ['feeBump.tx.innerTx.type' => 'ENVELOPE_TYPE_TX'];
        }

        $lines += [$prefix . 'sourceAccount' => $tx->getSourceAccount()->getAccountId()];
        $lines += [$prefix . 'fee' => strval($tx->getFee())];
        $lines += [$prefix . 'seqNum' => $tx->getSequenceNumber()->toString()];

        $lines = array_merge($lines, self::getPreconditions($tx->getPreconditions(), $prefix));

        $memo = $tx->getMemo();
        if ($memo->getType() == Memo::MEMO_TYPE_NONE) {
            $lines += [$prefix . 'memo.type' => 'MEMO_NONE'];
        } else if ($memo->getType() == Memo::MEMO_TYPE_TEXT) {
            $lines += [$prefix . 'memo.type' => 'MEMO_TEXT'];
            $lines += [$prefix . 'memo.text' => json_encode($memo->getValue())];
        } else if ($memo->getType() == Memo::MEMO_TYPE_ID) {
            $lines += [$prefix . 'memo.type' => 'MEMO_ID'];
            $lines += [$prefix . 'memo.id' => strval($memo->getValue())];
        } else if ($memo->getType() == Memo::MEMO_TYPE_HASH) {
            $lines += [$prefix . 'memo.type' => 'MEMO_HASH'];
            $lines += [$prefix . 'memo.hash' => $memo->getValue()];
        } else if ($memo->getType() == Memo::MEMO_TYPE_RETURN) {
            $lines += [$prefix . 'memo.type' => 'MEMO_RETURN'];
            $lines += [$prefix . 'memo.retHash' => $memo->getValue()];
        }

        $operations = $tx->getOperations();
        $lines += [$prefix . 'operations.len' => count($operations)];

        $index = 0;
        foreach ($operations as $operation) {
            $operationLines = self::getOperationTx($operation, $index, $prefix);
            $lines = array_merge($lines, $operationLines);
            $index++;
        }

        $txXdr = $tx->toXdr();
        $lines += [$prefix . 'ext.v' => strval($txXdr->ext->discriminant)];
        if ($txXdr->ext->sorobanTransactionData != null) {
            $lines = array_merge(
                $lines,
                self::getSorobanTransactionDataTx(
                    $prefix . 'sorobanData.',
                    $txXdr->ext->sorobanTransactionData,
                ),
            );
        }

        $p = $isFeeBump ? 'feeBump.tx.innerTx.' : '';
        $lines = array_merge($lines, self::getSignatures($p, $tx->getSignatures()));
        if ($isFeeBump) {
            $lines += ['feeBump.tx.ext.v' => '0'];
            $lines = array_merge($lines, self::getSignatures('feeBump.', $feeBumpSignatures));
        }

        $result = "";
        $keys = array_keys($lines);
        $countKeys = count($keys);
        foreach ($keys as $key) {
            $result = $result . $key . ': ' . $lines[$key];
            if ($keys[$countKeys - 1] != $key) {
                $result = $result . PHP_EOL;
            }
        }
        return $result;
    }

    public static function transactionEnvelopeXdrBase64FromTxRep(string $txRep): string
    {
        $lines = explode(PHP_EOL, $txRep);
        $map = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == "") {
                continue;
            }
            $parts = explode(':', $line);
            if (count($parts) > 1) {
                $key = $parts[0];
                $value = trim(implode(':', array_slice($parts, 1)));
                $map += [$key => $value];
            }
        }
        $prefix = 'tx.';
        $isFeeBump = self::getClearValue('type', $map) == 'ENVELOPE_TYPE_TX_FEE_BUMP';
        $feeBumpFee = null;
        $feeBumpSource = self::getClearValue('feeBump.tx.feeSource', $map);

        if ($isFeeBump) {
            $prefix = 'feeBump.tx.innerTx.tx.';
            $feeBumpFeeStr = self::getClearValue('feeBump.tx.fee', $map);
            if (!$feeBumpFeeStr) {
                throw new InvalidArgumentException('missing feeBump.tx.fee');
            }
            if (!is_numeric($feeBumpFeeStr)) {
                throw new InvalidArgumentException('invalid feeBump.tx.fee');
            }

            $feeBumpFee = (int)$feeBumpFeeStr;

            if (!$feeBumpSource) {
                throw new InvalidArgumentException('missing feeBump.tx.feeSource');
            }

            try {
                KeyPair::fromAccountId($feeBumpSource);
            } catch (Exception $e) {
                throw new InvalidArgumentException('invalid feeBump.tx.feeSource');
            }
        }

        $sourceAccountId = self::getClearValue($prefix . 'sourceAccount', $map);
        if (!$sourceAccountId) {
            throw new InvalidArgumentException('missing ' . $prefix . 'sourceAccount');
        }

        try {
            KeyPair::fromAccountId($sourceAccountId);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $prefix . 'sourceAccount');
        }

        $feeStr = self::getClearValue($prefix . 'fee', $map);
        if (!$feeStr || !is_numeric($feeStr)) {
            throw new InvalidArgumentException('missing or invalid ' . $prefix . 'fee');
        }
        $fee = (int)$feeStr;
        $sequenceNumberStr = self::getClearValue($prefix . 'seqNum', $map);
        if (!$sequenceNumberStr) {
            throw new InvalidArgumentException('missing ' . $prefix . 'seqNum');
        }
        $sequenceNumber = new BigInteger($sequenceNumberStr);
        if ($sequenceNumber->toString() != $sequenceNumberStr) {
            throw new InvalidArgumentException('invalid ' . $prefix . 'seqNum');
        }

        $sourceAccount = Account::fromAccountId($sourceAccountId, $sequenceNumber->subtract(new BigInteger(1)));
        $txBuilder = new TransactionBuilder($sourceAccount);

        $preconditions = self::parsePreconditions($map, $prefix);
        if ($preconditions != null) {
            $txBuilder->setPreconditions($preconditions);
        }

        $memoType = self::getClearValue($prefix . 'memo.type', $map);
        if (!$memoType) {
            throw new InvalidArgumentException('missing ' . $prefix . 'memo.type');
        }
        if ($memoType == 'MEMO_TEXT' && self::getClearValue($prefix . 'memo.text', $map)) {
            $text = str_replace('"', '', self::getClearValue($prefix . 'memo.text', $map));
            $txBuilder->addMemo(Memo::text($text));
        } else if ($memoType == 'MEMO_ID' && self::getClearValue($prefix . 'memo.id', $map)) {
            $val = self::getClearValue($prefix . 'memo.id', $map);
            if (!is_numeric($val)) {
                throw new InvalidArgumentException($prefix . 'memo.id');
            }
            $id = (int)self::getClearValue($prefix . 'memo.id', $map);
            $txBuilder->addMemo(Memo::id($id));
        } else if ($memoType == 'MEMO_HASH' && self::getClearValue($prefix . 'memo.hash', $map)) {
            $hash = hex2bin(self::getClearValue($prefix . 'memo.hash', $map));
            if (!$hash) {
                throw new InvalidArgumentException($prefix . 'memo.hash');
            }
            $txBuilder->addMemo(Memo::hash($hash));
        } else if ($memoType == 'MEMO_RETURN' && self::getClearValue($prefix . 'memo.return', $map)) {
            $hash = hex2bin(self::getClearValue($prefix . 'memo.return', $map));
            if (!$hash) {
                throw new InvalidArgumentException($prefix . 'memo.return');
            }
            $txBuilder->addMemo(Memo::return($hash));
        } else {
            $txBuilder->addMemo(Memo::none());
        }

        $operationsLen = self::getClearValue($prefix . 'operations.len', $map);
        if (!$operationsLen) {
            throw new InvalidArgumentException('missing ' . $prefix . 'operations.len');
        }
        if (!is_numeric($operationsLen)) {
            throw new InvalidArgumentException('invalid ' . $prefix . 'operations.len');
        }
        $nrOfOperations = (int)$operationsLen;
        if ($nrOfOperations > 100) {
            throw new InvalidArgumentException('invalid ' . $prefix . 'operations.len - greater than 100');
        }

        for ($i = 0; $i < $nrOfOperations; $i++) {
            $operation = self::getOperation($i, $map, $prefix);
            if ($operation) {
                $txBuilder->addOperation($operation);
            }
        }
        $maxOperationFee = intval(round((float)$fee / (float)$nrOfOperations));
        $txBuilder->setMaxOperationFee($maxOperationFee);


        $transaction = $txBuilder->build();
        $sorobanTransactionData = self::getSorobanTransactionData($prefix, $map);
        $transaction->setSorobanTransactionData($sorobanTransactionData);

        // Signatures
        $prefix = $isFeeBump ? 'feeBump.tx.innerTx.' : "";
        $signaturesLen = self::getClearValue($prefix . 'signatures.len', $map);
        if ($signaturesLen) {
            if (!is_numeric($signaturesLen)) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'signatures.len');
            }
            $nrOfSignatures = intval($signaturesLen);
            if ($nrOfSignatures > 20) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'signatures.len - greater than 20');
            }
            $signatures = array();
            for ($i = 0; $i < $nrOfSignatures; $i++) {
                $signature = self::getSignature($i, $map, $prefix);
                if ($signature) {
                    array_push($signatures, $signature);
                }
            }
            $transaction->setSignatures($signatures);
        }
        if ($isFeeBump) {
            $builder = new FeeBumpTransactionBuilder($transaction);
            $baseFee = intval(round((float)$feeBumpFee / (float)($nrOfOperations + 1)));
            $builder->setBaseFee($baseFee);
            $builder->setMuxedFeeAccount(MuxedAccount::fromAccountId($feeBumpSource));
            $feeBumpTransaction = $builder->build();
            $fbSignaturesLen = self::getClearValue('feeBump.signatures.len', $map);
            if ($fbSignaturesLen) {
                if (!is_numeric($fbSignaturesLen)) {
                    throw new InvalidArgumentException('invalid ' . $prefix . 'feeBump.signatures.len');
                }
                $nrOfFbSignatures = intval($fbSignaturesLen);
                if ($nrOfFbSignatures > 20) {
                    throw new InvalidArgumentException('invalid ' . $prefix . 'feeBump.signatures.len - greater than 20');
                }
                $fbSignatures = array();
                for ($i = 0; $i < $nrOfFbSignatures; $i++) {
                    $signature = self::getSignature($i, $map, 'feeBump.');
                    if ($signature) {
                        array_push($fbSignatures, $signature);
                    }
                }
                $feeBumpTransaction->setSignatures($fbSignatures);
            }
            return $feeBumpTransaction->toEnvelopeXdrBase64();
        }
        return $transaction->toEnvelopeXdrBase64();
    }

    private static function getSignature(int $index, array $map, string $txPrefix): ?XdrDecoratedSignature
    {
        $hintStr = self::getClearValue($txPrefix . 'signatures[' . $index . '].hint', $map);
        if (!$hintStr) {
            throw new InvalidArgumentException('missing ' . $txPrefix . 'signatures[' . $index . '].hint');
        }
        $signatureStr = self::getClearValue($txPrefix . 'signatures[' . $index . '].signature', $map);
        if (!$signatureStr) {
            throw new InvalidArgumentException('missing ' . $txPrefix . 'signatures[' . $index . '].signature');
        }
        $hint = hex2bin($hintStr);
        $signature = hex2bin($signatureStr);
        if ($hint && $signature) {
            return new XdrDecoratedSignature($hint, $signature);
        }
        return null;
    }

    private static function parsePreconditions(array $map, string $txPrefix): ?TransactionPreconditions
    {
        $cond = new TransactionPreconditions();
        $preonditionsType = self::getClearValue($txPrefix . 'cond.type', $map);
        if ($preonditionsType == "PRECOND_TIME") {
            $precondPrefix = $txPrefix . 'cond.';
            $minTimeStr = self::getClearValue($precondPrefix . 'timeBounds.minTime', $map);
            $maxTimeStr = self::getClearValue($precondPrefix . 'timeBounds.maxTime', $map);
            if ($minTimeStr != null && $maxTimeStr != null) {
                if (!is_numeric($minTimeStr)) {
                    throw new InvalidArgumentException('invalid ' . $precondPrefix . 'minTime');
                }
                if (!is_numeric($maxTimeStr)) {
                    throw new InvalidArgumentException('invalid ' . $precondPrefix . 'maxTime');
                }
                $minTime = (int)$minTimeStr;
                $maxTime = (int)$maxTimeStr;
                $timeBounds = new TimeBounds((new DateTime)->setTimestamp($minTime), (new DateTime)->setTimestamp($maxTime));
                $cond->setTimeBounds($timeBounds);
                return $cond;
            } else {
                throw new InvalidArgumentException('invalid ' . $precondPrefix . 'timeBounds');
            }
        } else if ($preonditionsType == "PRECOND_V2") {
            $precondPrefix = $txPrefix . 'cond.v2.';
            $cond->setTimeBounds(self::parseTimeBounds($map, $precondPrefix));
            $cond->setLedgerBounds(self::parseLedgerBounds($map, $precondPrefix));
            $minSeqNrStr = self::getClearValue($precondPrefix . 'minSeqNum', $map);
            if (self::getClearValue($precondPrefix . 'minSeqNum._present', $map) == 'true' && $minSeqNrStr != null) {
                $minSeqNr = new BigInteger($minSeqNrStr);
                $cond->setMinSeqNumber($minSeqNr);
            } else {
                throw new InvalidArgumentException('invalid ' . $precondPrefix . 'minSeqNum');
            }
            $minSeqAgeStr = self::getClearValue($precondPrefix . 'minSeqAge', $map);
            if ($minSeqAgeStr != null) {
                if (!is_numeric($minSeqAgeStr)) {
                    throw new InvalidArgumentException('invalid ' . $precondPrefix . 'minSeqAge');
                }
                $minSeqAge = (int)$minSeqAgeStr;
                $cond->setMinSeqAge($minSeqAge);
            } else {
                throw new InvalidArgumentException('missing ' . $precondPrefix . 'minSeqAge');
            }
            $minSeqLedgerGapStr = self::getClearValue($precondPrefix . 'minSeqLedgerGap', $map);
            if ($minSeqLedgerGapStr != null) {
                if (!is_numeric($minSeqLedgerGapStr)) {
                    throw new InvalidArgumentException('invalid ' . $precondPrefix . 'minSeqLedgerGap');
                }
                $minSeqLedgerGap = (int)$minSeqLedgerGapStr;
                $cond->setMinSeqLedgerGap($minSeqLedgerGap);
            } else {
                throw new InvalidArgumentException('missing ' . $precondPrefix . 'minSeqLedgerGap');
            }

            $extraSignersLen = self::getClearValue($precondPrefix . 'extraSigners.len', $map);
            if (!$extraSignersLen) {
                throw new InvalidArgumentException('missing ' . $precondPrefix . 'extraSigners.len');
            }
            if (!is_numeric($extraSignersLen)) {
                throw new InvalidArgumentException('invalid ' . $precondPrefix . 'extraSigners.len');
            }
            $nrOfExtraSigners = (int)$extraSignersLen;
            if ($nrOfExtraSigners > 2) {
                throw new InvalidArgumentException('invalid ' . $precondPrefix . 'extraSigners.len - greater than 2');
            }
            $extraSigners = array();
            for ($i = 0; $i < $nrOfExtraSigners; $i++) {
                $key = self::getClearValue($precondPrefix . 'extraSigners[' . strval($i) . ']', $map);
                if (!$key) {
                    throw new InvalidArgumentException('missing ' . $precondPrefix . 'extraSigners[' . strval($i) . ']');
                }

                if (str_starts_with($key, 'G')) {
                    $signer = new XdrSignerKey();
                    $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
                    $signer->setEd25519(StrKey::decodeAccountId($key));
                    array_push($extraSigners, $signer);
                } else if (str_starts_with($key, 'T')) {
                    $signer = new XdrSignerKey();
                    $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
                    $signer->setPreAuthTx(StrKey::decodePreAuthTx($key));
                    array_push($extraSigners, $signer);
                } else if (str_starts_with($key, 'X')) {
                    $signer = new XdrSignerKey();
                    $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
                    $signer->setHashX(StrKey::decodeSha256Hash($key));
                    array_push($extraSigners, $signer);
                } else if (str_starts_with($key, 'P')) {
                    $signer = new XdrSignerKey();
                    $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519_SIGNED_PAYLOAD));
                    $signer->setSignedPayload(StrKey::decodeXdrSignedPayload($key));
                    array_push($extraSigners, $signer);
                } else {
                    throw new InvalidArgumentException('invalid ' . $precondPrefix . 'extraSigners[' . strval($i) . ']');
                }
            }
            $cond->setExtraSigners($extraSigners);
            return $cond;
        } else if ($preonditionsType == "PRECOND_NONE") {
            return null;
        }
        $cond->setTimeBounds(self::parseTimeBounds($map, $txPrefix));
        return $cond;
    }

    private static function parseTimeBounds(array $map, string $prefix): ?TimeBounds
    {
        $minTimeStr = self::getClearValue($prefix . 'timeBounds.minTime', $map);
        $maxTimeStr = self::getClearValue($prefix . 'timeBounds.maxTime', $map);
        if (self::getClearValue($prefix . 'timeBounds._present', $map) == 'true' && $minTimeStr != null && $maxTimeStr != null) {
            if (!is_numeric($minTimeStr)) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'minTime');
            }
            if (!is_numeric($maxTimeStr)) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'maxTime');
            }
            $minTime = (int)$minTimeStr;
            $maxTime = (int)$maxTimeStr;
            return new TimeBounds((new DateTime)->setTimestamp($minTime), (new DateTime)->setTimestamp($maxTime));
        } else {
            throw new InvalidArgumentException('invalid ' . $prefix . 'timeBounds');
        }
    }

    private static function parseLedgerBounds(array $map, string $prefix): ?LedgerBounds
    {
        $minLedgerStr = self::getClearValue($prefix . 'ledgerBounds.minLedger', $map);
        $maxLedgerStr = self::getClearValue($prefix . 'ledgerBounds.maxLedger', $map);
        if (self::getClearValue($prefix . 'ledgerBounds._present', $map) == 'true' && $minLedgerStr != null && $maxLedgerStr != null) {
            if (!is_numeric($minLedgerStr)) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'minLedger');
            }
            if (!is_numeric($maxLedgerStr)) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'maxLedger');
            }
            $minLedger = (int)$minLedgerStr;
            $maxLedger = (int)$maxLedgerStr;
            return new LedgerBounds($minLedger, $maxLedger);
        } else {
            throw new InvalidArgumentException('invalid ' . $prefix . 'ledgerBounds');
        }
    }

    private static function getOperation(int $index, array $map, string $txPrefix): ?AbstractOperation
    {
        $prefix = $txPrefix . 'operations[' . $index . '].body.';
        $sourceAccountId = null;
        if (self::getClearValue($txPrefix . 'operations[' . $index . '].sourceAccount._present', $map) == 'true') {
            $sourceAccountId = self::getClearValue($txPrefix . 'operations[' . $index . '].sourceAccount', $map);
            if (!$sourceAccountId) {
                throw new InvalidArgumentException('missing ' . $txPrefix . 'operations[' . $index . '].sourceAccount');
            }
            try {
                KeyPair::fromAccountId($sourceAccountId);
            } catch (Exception $e) {
                throw new InvalidArgumentException('invalid ' . $txPrefix . 'operations[' . $index . '].sourceAccount');
            }
        }

        $opType = self::getClearValue($prefix . 'type', $map);
        if (!$opType) {
            throw new InvalidArgumentException($prefix . 'type');
        }
        if ($opType == 'CREATE_ACCOUNT') {
            $opPrefix = $prefix . 'createAccountOp.';
            return self::getCreateAccountOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'PAYMENT') {
            $opPrefix = $prefix . 'paymentOp.';
            return self::getPaymentOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'PATH_PAYMENT_STRICT_RECEIVE') {
            $opPrefix = $prefix . 'pathPaymentStrictReceiveOp.';
            return self::getPathPaymentStrictReceiveOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'PATH_PAYMENT_STRICT_SEND') {
            $opPrefix = $prefix . 'pathPaymentStrictSendOp.';
            return self::getPathPaymentStrictSendOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'MANAGE_SELL_OFFER') {
            $opPrefix = $prefix . 'manageSellOfferOp.';
            return self::getManageSellOfferOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CREATE_PASSIVE_SELL_OFFER') {
            $opPrefix = $prefix . 'createPassiveSellOfferOp.';
            return self::getCreatePassiveSellOfferOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'SET_OPTIONS') {
            $opPrefix = $prefix . 'setOptionsOp.';
            return self::getSetOptionsOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CHANGE_TRUST') {
            $opPrefix = $prefix . 'changeTrustOp.';
            return self::getChangeTrustOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'ALLOW_TRUST') {
            $opPrefix = $prefix . 'allowTrustOp.';
            return self::getAllowTrustOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'ACCOUNT_MERGE') {
            // account merge does not include 'accountMergeOp' prefix
            return self::getAccountMergeOperation($index, $map, $txPrefix, $sourceAccountId);
        } else if ($opType == 'MANAGE_DATA') {
            $opPrefix = $prefix . 'manageDataOp.';
            return self::getManageDataOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'BUMP_SEQUENCE') {
            $opPrefix = $prefix . 'bumpSequenceOp.';
            return self::getBumpSequenceOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'MANAGE_BUY_OFFER') {
            $opPrefix = $prefix . 'manageBuyOfferOp.';
            return self::getManageBuyOfferOperation($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CREATE_CLAIMABLE_BALANCE') {
            $opPrefix = $prefix . 'createClaimableBalanceOp.';
            return self::getCreateClaimableBalanceOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CLAIM_CLAIMABLE_BALANCE') {
            $opPrefix = $prefix . 'claimClaimableBalanceOp.';
            return self::getClaimClaimableBalanceOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'BEGIN_SPONSORING_FUTURE_RESERVES') {
            $opPrefix = $prefix . 'beginSponsoringFutureReservesOp.';
            return self::getBeginSponsoringFutureReservesOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'END_SPONSORING_FUTURE_RESERVES') {
            return self::getEndSponsoringFutureReservesOp($sourceAccountId);
        } else if ($opType == 'REVOKE_SPONSORSHIP') {
            $opPrefix = $prefix . 'revokeSponsorshipOp.';
            return self::getRevokeSponsorshipOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CLAWBACK') {
            $opPrefix = $prefix . 'clawbackOp.';
            return self::getClawbackOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'CLAWBACK_CLAIMABLE_BALANCE') {
            $opPrefix = $prefix . 'clawbackClaimableBalanceOp.';
            return self::getClawbackClaimableBalanceOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'SET_TRUST_LINE_FLAGS') {
            $opPrefix = $prefix . 'setTrustLineFlagsOp.';
            return self::getSetTrustlineFlagsOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'LIQUIDITY_POOL_DEPOSIT') {
            $opPrefix = $prefix . 'liquidityPoolDepositOp.';
            return self::getLiquidityPoolDepositOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'LIQUIDITY_POOL_WITHDRAW') {
            $opPrefix = $prefix . 'liquidityPoolWithdrawOp.';
            return self::getLiquidityPoolWithdrawOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'INVOKE_HOST_FUNCTION') {
            $opPrefix = $prefix . 'invokeHostFunctionOp.';
            return self::getInvokeHostFunctionOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'EXTEND_FOOTPRINT_TTL') {
            $opPrefix = $prefix . 'extendFootprintTTLOp.';
            return self::getExtendFootprintTTLOp($opPrefix, $map, $sourceAccountId);
        } else if ($opType == 'RESTORE_FOOTPRINT') {
            $result = new RestoreFootprintOperation();
            if ($sourceAccountId !== null) {
                $result->setSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
            }
            return $result;
        }
        return null;
    }

    private static function getSCAddress($prefix, array $map): XdrSCAddress
    {
        $type = self::getString($prefix . 'type', $map);
        if ("SC_ADDRESS_TYPE_ACCOUNT" == $type) {
            $accId = self::getString($prefix . 'accountId', $map);
            return XdrSCAddress::forAccountId($accId);
        } else if ("SC_ADDRESS_TYPE_CONTRACT" == $type) {
            $contractId = self::getString($prefix . 'contractId', $map);
            return XdrSCAddress::forContractId($contractId);
        } else if ("SC_ADDRESS_TYPE_MUXED_ACCOUNT" == $type) {
            $muxedAccountId = self::getString($prefix . 'muxedAccount', $map);
            return XdrSCAddress::forAccountId($muxedAccountId);
        } else if ("SC_ADDRESS_TYPE_CLAIMABLE_BALANCE" == $type) {
            $claimableBalanceId = self::getString($prefix . 'claimableBalanceId.balanceID.v0', $map);
            return XdrSCAddress::forClaimableBalanceId($claimableBalanceId);
        } else if ("SC_ADDRESS_TYPE_LIQUIDITY_POOL" == $type) {
            $liquidityPoolId = self::getString($prefix . 'liquidityPoolId', $map);
            return XdrSCAddress::forLiquidityPoolId($liquidityPoolId);
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    /**
     * @param $prefix
     * @param array $map
     * @return array<XdrSCVal>
     */
    private static function getSCVec($prefix, array $map): array
    {
        /**
         * @var array<XdrSCVal> $vec
         */
        $vec = array();
        $len = self::getInt($prefix . 'vec.len', $map);
        for ($i = 0; $i < $len; $i++) {
            $val = self::getSCVal($prefix . 'vec[' . strval($i) . '].', $map);
            array_push($vec, $val);
        }
        return $vec;
    }

    /**
     * @param $prefix
     * @param array $map
     * @return array<XdrSCMapEntry>
     */
    private static function getSCMapEntries($prefix, array $map): array
    {
        /**
         * @var array<XdrSCMapEntry> $result
         */
        $result = array();
        $len = self::getInt($prefix . '.len', $map);
        for ($i = 0; $i < $len; $i++) {
            $key = self::getSCVal($prefix . '[' . strval($i) . '].key.', $map);
            $value = self::getSCVal($prefix . '[' . strval($i) . '].val.', $map);
            $entry = new XdrSCMapEntry($key, $value);
            array_push($result, $entry);
        }
        return $result;
    }

    private static function getContractExecutable($prefix, array $map): XdrContractExecutable
    {
        $type = self::getString($prefix . 'type', $map);

        if ("CONTRACT_EXECUTABLE_WASM" === $type) {
            $wasm = self::getString($prefix . 'wasm_hash', $map);
            return XdrContractExecutable::forWasmId($wasm);
        } else if ("CONTRACT_EXECUTABLE_STELLAR_ASSET" === $type) {
            return XdrContractExecutable::forToken();
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    private static function getSCVal($prefix, array $map): XdrSCVal
    {
        $valType = self::getString($prefix . 'type', $map);
        if ('SCV_BOOL' == $valType) {
            $bStr = self::getString($prefix . 'b', $map);
            if ($bStr == 'true') {
                return XdrSCVal::forTrue();
            } else if ($bStr == 'false') {
                return XdrSCVal::forFalse();
            } else {
                throw new InvalidArgumentException('invalid ' . $prefix . 'b');
            }
        } else if ('SCV_VOID' == $valType) {
            return XdrSCVal::forVoid();
        } else if ('SCV_ERROR' == $valType) {
            return XdrSCVal::forError(self::getSCError($prefix . 'error', $map));
        } else if ('SCV_U32' == $valType) {
            $u32 = self::getInt($prefix . 'u32', $map);
            return XdrSCVal::forU32($u32);
        } else if ('SCV_I32' == $valType) {
            $i32 = self::getInt($prefix . 'i32', $map);
            return XdrSCVal::forI32($i32);
        } else if ('SCV_U64' == $valType) {
            $u64 = self::getInt($prefix . 'u64', $map);
            return XdrSCVal::forU64($u64);
        } else if ('SCV_I64' == $valType) {
            $i64 = self::getInt($prefix . 'i64', $map);
            return XdrSCVal::forI64($i64);
        } else if ('SCV_TIMEPOINT' == $valType) {
            $timepoint = self::getInt($prefix . 'timepoint', $map);
            return XdrSCVal::forTimepoint($timepoint);
        } else if ('SCV_DURATION' == $valType) {
            $duration = self::getInt($prefix . 'duration', $map);
            return XdrSCVal::forDuration($duration);
        } else if ('SCV_U128' == $valType) {
            $u128Lo = self::getInt($prefix . 'u128.lo', $map);
            $u128Hi = self::getInt($prefix . 'u128.hi', $map);
            return XdrSCVal::forU128(new XdrUInt128Parts($u128Hi, $u128Lo));
        } else if ('SCV_I128' == $valType) {
            $i128Lo = self::getInt($prefix . 'i128.lo', $map);
            $i128Hi = self::getInt($prefix . 'i128.hi', $map);
            return XdrSCVal::forI128(new XdrInt128Parts($i128Hi, $i128Lo));
        } else if ('SCV_U256' == $valType) {
            $u256LoLo = self::getInt($prefix . 'u256.lo_lo', $map);
            $u256LoHi = self::getInt($prefix . 'u256.lo_hi', $map);
            $u256HiLo = self::getInt($prefix . 'u256.hi_lo', $map);
            $u256HiHi = self::getInt($prefix . 'u256.hi_hi', $map);
            return XdrSCVal::forU256(new XdrUInt256Parts($u256HiHi, $u256HiLo, $u256LoHi, $u256LoLo));
        } else if ('SCV_I256' == $valType) {
            $i256LoLo = self::getInt($prefix . 'i256.lo_lo', $map);
            $i256LoHi = self::getInt($prefix . 'i256.lo_hi', $map);
            $i256HiLo = self::getInt($prefix . 'i256.hi_lo', $map);
            $i256HiHi = self::getInt($prefix . 'i256.hi_hi', $map);
            return XdrSCVal::forI256(new XdrInt256Parts($i256HiHi, $i256HiLo, $i256LoHi, $i256LoLo));
        } else if ('SCV_BYTES' == $valType) {
            $bin = self::getString($prefix . 'bytes', $map);
            return XdrSCVal::forBytes(hex2bin($bin));
        } else if ('SCV_STRING' == $valType) {
            $str = self::getString($prefix . 'str', $map);
            return XdrSCVal::forString($str);
        } else if ('SCV_SYMBOL' == $valType) {
            $sym = self::getString($prefix . 'sym', $map);
            return XdrSCVal::forSymbol($sym);
        } else if ('SCV_VEC' == $valType) {
            $present = self::getString($prefix . 'vec._present', $map);
            if ('true' != $present) {
                return new XdrSCVal(XdrSCValType::VEC());
            }
            return XdrSCVal::forVec(self::getSCVec($prefix, $map));
        } else if ('SCV_MAP' == $valType) {
            $present = self::getString($prefix . 'map._present', $map);
            if ('true' != $present) {
                return new XdrSCVal(XdrSCValType::MAP());
            }
            return XdrSCVal::forMap(self::getSCMapEntries($prefix . 'map', $map));
        } else if ('SCV_ADDRESS' == $valType) {
            $address = self::getSCAddress($prefix . 'address.', $map);
            return XdrSCVal::forAddress($address);
        } else if ('SCV_LEDGER_KEY_CONTRACT_INSTANCE' == $valType) {
            return XdrSCVal::forLedgerKeyContractInstance();
        } else if ('SCV_LEDGER_KEY_NONCE' == $valType) {
            $nonce = self::getInt($prefix . 'nonce_key.nonce', $map);
            return XdrSCVal::forLedgerNonceKey(new XdrSCNonceKey($nonce));
        } else if ('SCV_CONTRACT_INSTANCE' == $valType) {
            $executable = self::getContractExecutable($prefix . 'contractInstance.executable.', $map);
            $present = self::getString($prefix . 'storage._present', $map);
            if ('true' != $present) {
                /**
                 * @var array<XdrSCMapEntry> $storageEntries
                 */
                $storageEntries = self::getSCMapEntries($prefix . 'storage', $map);
                return XdrSCVal::forContractInstance(new XdrSCContractInstance($executable, $storageEntries));
            } else {
                return XdrSCVal::forContractInstance(new XdrSCContractInstance($executable, null));
            }
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $valType);
        }
    }

    private static function getSCError($prefix, array $map): XdrSCError
    {
        $type = self::getString($prefix . 'type', $map);

        if ("SCE_CONTRACT" === $type) {
            $code = self::getInt($prefix . 'contractCode', $map);
            $result = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
            $result->contractCode = $code;
            return $result;
        } else if ("SCE_WASM_VM" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_WASM_VM());
        } else if ("SCE_CONTEXT" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_CONTEXT());
        } else if ("SCE_STORAGE" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_STORAGE());
        } else if ("SCE_OBJECT" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_OBJECT());
        } else if ("SCE_CRYPTO" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_CRYPTO());
        } else if ("SCE_EVENTS" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_EVENTS());
        } else if ("SCE_BUDGET" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_BUDGET());
        } else if ("SCE_VALUE" === $type) {
            return new XdrSCError(XdrSCErrorType::SCE_VALUE());
        } else if ("SCE_AUTH" === $type) {
            $result =  new XdrSCError(XdrSCErrorType::SCE_AUTH());
            $code = self::getString($prefix . 'code', $map);
            if ("SCEC_ARITH_DOMAIN" === $code) {
                $result->code = new XdrSCErrorCode(0);
            } else if ("SCEC_INDEX_BOUNDS" === $code) {
                $result->code = new XdrSCErrorCode(1);
            } else if ("SCEC_INVALID_INPUT" === $code) {
                $result->code = new XdrSCErrorCode(2);
            } else if ("SCEC_MISSING_VALUE" === $code) {
                $result->code = new XdrSCErrorCode(3);
            } else if ("SCEC_EXISTING_VALUE" === $code) {
                $result->code = new XdrSCErrorCode(4);
            } else if ("SCEC_EXCEEDED_LIMIT" === $code) {
                $result->code = new XdrSCErrorCode(5);
            } else if ("SCEC_INVALID_ACTION" === $code) {
                $result->code = new XdrSCErrorCode(6);
            } else if ("SCEC_INTERNAL_ERROR" === $code) {
                $result->code = new XdrSCErrorCode(7);
            } else if ("SCEC_UNEXPECTED_TYPE" === $code) {
                $result->code = new XdrSCErrorCode(8);
            } else if ("SCEC_UNEXPECTED_SIZE" === $code) {
                $result->code = new XdrSCErrorCode(9);
            } else {
                throw new InvalidArgumentException('unknown ' . $prefix . 'code ' . $type);
            }
            return $result;
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }
    private static function getContractIDPreimage($prefix, array $map): XdrContractIDPreimage
    {
        $type = self::getString($prefix . 'type', $map);

        if ("CONTRACT_ID_PREIMAGE_FROM_ADDRESS" === $type) {
            $address = self::getSCAddress($prefix . 'fromAddress.address.', $map);
            $salt = self::getString($prefix . 'fromAddress.salt', $map);
            return XdrContractIDPreimage::forAddress($address, $salt);
        } else if ("CONTRACT_ID_PREIMAGE_FROM_ASSET" === $type) {
            $assetStr = self::getString($prefix . 'fromAsset', $map);
            $asset = Asset::createFromCanonicalForm($assetStr);
            if ($asset === null) {
                throw new InvalidArgumentException('invalid value for ' . $prefix . 'fromAsset');
            }
            return XdrContractIDPreimage::forAsset($asset->toXdr());
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    private static function getCreateContractArgs($prefix, array $map): XdrCreateContractArgs
    {
        $preimage = self::getContractIDPreimage($prefix . 'contractIDPreimage.', $map);
        $executable = self::getContractExecutable($prefix . 'executable.', $map);
        return new XdrCreateContractArgs($preimage, $executable);
    }

    private static function getCreateContractV2Args($prefix, array $map): XdrCreateContractArgsV2
    {
        $preimage = self::getContractIDPreimage($prefix . 'contractIDPreimage.', $map);
        $executable = self::getContractExecutable($prefix . 'executable.', $map);
        $argsLen = self::getInt($prefix . 'constructorArgs.len', $map);
        /**
         * @var array<XdrSCVal> $arguments
         */
        $arguments = array();

        for ($i = 0; $i < $argsLen; $i++) {
            $next = self::getSCVal($prefix . 'constructorArgs[' . $i . '].', $map);
            array_push($arguments, $next);
        }

        return new XdrCreateContractArgsV2($preimage, $executable, $arguments);
    }

    private static function getInvokeContractArgs($prefix, array $map): XdrInvokeContractArgs
    {
        $address = self::getSCAddress($prefix . 'contractAddress.', $map);
        $functionName = self::getString($prefix . 'functionName', $map);
        $argsLen = self::getInt($prefix . 'args.len', $map);
        /**
         * @var array<XdrSCVal> $arguments
         */
        $arguments = array();

        for ($i = 0; $i < $argsLen; $i++) {
            $next = self::getSCVal($prefix . 'args[' . $i . '].', $map);
            array_push($arguments, $next);
        }
        return new XdrInvokeContractArgs($address, $functionName, $arguments);
    }

    private static function getHostFunction($prefix, array $map): XdrHostFunction
    {
        $type = self::getString($prefix . 'type', $map);
        switch ($type) {
            case 'HOST_FUNCTION_TYPE_INVOKE_CONTRACT':
                $args = self::getInvokeContractArgs($prefix . 'invokeContract.', $map);
                return XdrHostFunction::forInvokingContractWithArgs($args);
            case 'HOST_FUNCTION_TYPE_CREATE_CONTRACT':
                $args = self::getCreateContractArgs($prefix . 'createContract.', $map);
                return XdrHostFunction::forCreatingContractWithArgs($args);
            case 'HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2':
                $args = self::getCreateContractV2Args($prefix . 'createContractV2.', $map);
                return XdrHostFunction::forCreatingContractV2WithArgs($args);
            case 'HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM':
                $wasm = self::getString($prefix . 'wasm', $map);
                return XdrHostFunction::forUploadContractWasm(hex2bin($wasm));
            default:
                throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    private static function getSorobanAddressCredentials($prefix, array $map): XdrSorobanAddressCredentials
    {
        $address = self::getSCAddress($prefix . 'address.', $map);
        $nonce = self::getInt($prefix . 'nonce', $map);
        $expLedger = self::getInt($prefix . 'signatureExpirationLedger', $map);
        $signature = self::getSCVal($prefix . 'signature.', $map);
        return new XdrSorobanAddressCredentials($address, $nonce, $expLedger, $signature);
    }

    private static function getSorobanCredentials($prefix, array $map): XdrSorobanCredentials
    {
        $type = self::getString($prefix . 'type', $map);

        if ("SOROBAN_CREDENTIALS_SOURCE_ACCOUNT" === $type) {
            return XdrSorobanCredentials::forSourceAccount();
        } else if ("SOROBAN_CREDENTIALS_ADDRESS" === $type) {
            $addressCredentials = self::getSorobanAddressCredentials($prefix . 'address.', $map);
            return XdrSorobanCredentials::forAddressCredentials($addressCredentials);
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    private static function getSorobanAuthorizedFunction($prefix, array $map): XdrSorobanAuthorizedFunction
    {
        $type = self::getString($prefix . 'type', $map);

        if ("SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN" === $type) {
            $args = self::getInvokeContractArgs($prefix . 'contractFn.', $map);
            return XdrSorobanAuthorizedFunction::forInvokeContractArgs($args);
        } else if ("SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN" === $type) {
            $args = self::getCreateContractArgs($prefix . 'createContractHostFn.', $map);
            return XdrSorobanAuthorizedFunction::forCreateContractArgs($args);
        } else if ("SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN" === $type) {
            $args = self::getCreateContractV2Args($prefix . 'createContractV2HostFn.', $map);
            return XdrSorobanAuthorizedFunction::forCreateContractArgsV2($args);
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }

    private static function getSorobanAuthorizedInvocation($prefix, array $map): XdrSorobanAuthorizedInvocation
    {
        $function = self::getSorobanAuthorizedFunction($prefix . 'function.', $map);
        $subsLen = self::getInt($prefix . 'subInvocations.len', $map);
        /**
         * @var array<XdrSorobanAuthorizedInvocation> $subs
         */
        $subs = array();

        for ($i = 0; $i < $subsLen; $i++) {
            $next = self::getSorobanAuthorizedInvocation($prefix . 'subInvocations[' . $i . '].', $map);
            array_push($subs, $next);
        }
        return new XdrSorobanAuthorizedInvocation($function, $subs);
    }

    private static function getSorobanAuthEntry($prefix, array $map): XdrSorobanAuthorizationEntry
    {
        $credentials = self::getSorobanCredentials($prefix . 'credentials.', $map);
        $rootInvocation = self::getSorobanAuthorizedInvocation($prefix . 'rootInvocation.', $map);
        return new XdrSorobanAuthorizationEntry($credentials, $rootInvocation);
    }

    /**
     * @param $prefix
     * @param array $map
     * @return array<XdrSorobanAuthorizationEntry>
     */
    private static function getSorobanAuthEntries($prefix, array $map): array
    {
        $len = self::getInt($prefix . '.len', $map);
        /**
         * @var array<XdrSorobanAuthorizationEntry> $entries
         */
        $entries = array();

        for ($i = 0; $i < $len; $i++) {
            $next = self::getSorobanAuthEntry($prefix . '[' . $i . '].', $map);
            array_push($entries, $next);
        }
        return $entries;
    }

    private static function getExtendFootprintTTLOp($opPrefix, array $map, ?string $sourceAccountId): ExtendFootprintTTLOperation {
        $extendTo = self::getInt($opPrefix . 'extendTo', $map);
        $result = new ExtendFootprintTTLOperation($extendTo);
        if ($sourceAccountId !== null) {
            $result->setSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $result;
    }

    private static function getInvokeHostFunctionOp($opPrefix, array $map, ?string $sourceAccountId): InvokeHostFunctionOperation
    {
        $hostFunctionXdr = self::getHostFunction($opPrefix . 'hostFunction.', $map);
        $authEntriesXdr = self::getSorobanAuthEntries($opPrefix . 'auth', $map);
        $xdr = new XdrInvokeHostFunctionOp($hostFunctionXdr, $authEntriesXdr);
        try {
            $result = InvokeHostFunctionOperation::fromXdrOperation($xdr);
            if ($sourceAccountId !== null) {
                $result->setSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
            }
            return $result;
        } catch(Exception) {
            throw new InvalidArgumentException('could not decode host function');
        }
    }

    private static function getLedgerKey(String $prefix, array $map): XdrLedgerKey {

        $ledgerKeyType = self::getString($prefix . 'type', $map);
        if ($ledgerKeyType === 'ACCOUNT') {
            $accountId = self::getAccountId($prefix . 'account.accountID', $map);
            return XdrLedgerKey::forAccountId($accountId);
        } else if($ledgerKeyType === 'TRUSTLINE') {
            $accountId = self::getAccountId($prefix . 'trustLine.accountID', $map);
            $assetStr = self::getString($prefix . 'trustLine.asset', $map);
            $asset = Asset::createFromCanonicalForm($assetStr);
            if (!$asset) {
                throw new InvalidArgumentException('invalid ' . $prefix . 'trustLine.asset');
            }
            return XdrLedgerKey::forTrustLine($accountId, $asset->toXdr());
        } else if($ledgerKeyType === 'OFFER') {
            $sellerId = self::getAccountId($prefix . 'offer.sellerID', $map);
            $offerId = self::getInt($prefix . 'offer.offerID', $map);
            return XdrLedgerKey::forOffer($sellerId, $offerId);
        } else if($ledgerKeyType === 'DATA') {
            $accountId = self::getAccountId($prefix . 'data.accountID', $map);
            $dataNameStr = self::getClearValue($prefix . 'data.dataName', $map);
            $dataName = str_replace('"','', $dataNameStr);
            return XdrLedgerKey::forData($accountId, $dataName);
        } else if($ledgerKeyType === 'CLAIMABLE_BALANCE') {
            $claimableBalanceId = self::getString($prefix . 'claimableBalance.balanceID.v0', $map);
            if (!$claimableBalanceId) {
                throw new InvalidArgumentException('missing ' . $prefix . 'claimableBalance.balanceID.v0');
            }
            return XdrLedgerKey::forClaimableBalanceId($claimableBalanceId);
        } else if($ledgerKeyType === 'LIQUIDITY_POOL') {
            $liquidityPoolId = self::getString($prefix . 'liquidityPool.liquidityPoolID', $map);
            return XdrLedgerKey::forLiquidityPoolId($liquidityPoolId);
        } else if($ledgerKeyType === 'CONTRACT_DATA') {
            $address = self::getSCAddress($prefix . 'contractData.contract.', $map);
            $keyVal = self::getSCVal($prefix . 'contractData.key.', $map);
            $durability = self::getString($prefix . 'contractData.durability', $map);
            if ($durability === 'PERSISTENT') {
                return XdrLedgerKey::forContractData($address, $keyVal, XdrContractDataDurability::PERSISTENT());
            } else if ($durability === 'TEMPORARY') {
                return XdrLedgerKey::forContractData($address, $keyVal, XdrContractDataDurability::TEMPORARY());
            } else {
                throw new InvalidArgumentException('unknown ' . $prefix . 'contractData.durability ' . $durability);
            }
        } else if($ledgerKeyType === 'CONTRACT_CODE') {
            $hashStr = self::getString($prefix . 'contractCode.hash', $map);
            return XdrLedgerKey::forContractCode(hex2bin($hashStr));
        } else if($ledgerKeyType === 'CONFIG_SETTING') {
            $id = self::getConfigSettingID($prefix . 'configSetting.configSettingID', $map);
            return XdrLedgerKey::forConfigSettingID($id);
        } else if($ledgerKeyType === 'TTL') {
            $hashStr = self::getString($prefix . 'ttl.keyHash', $map);
            return XdrLedgerKey::forTTL(hex2bin($hashStr));
        } else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $ledgerKeyType);
        }
    }

    private static function getConfigSettingID(string $prefix, array $map): XdrConfigSettingID {

        $type = self::getString($prefix . 'type', $map);

        if ("CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES();
        } else if ("CONFIG_SETTING_CONTRACT_COMPUTE_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0();
        } else if ("CONFIG_SETTING_CONTRACT_LEDGER_COST_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0();
        } else if ("CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0();
        } else if ("CONFIG_SETTING_CONTRACT_EVENTS_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0();
        } else if ("CONFIG_SETTING_CONTRACT_BANDWIDTH_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0();
        } else if ("CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS();
        } else if ("CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES();
        } else if ("CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES();
        } else if ("CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES();
        } else if ("CONFIG_SETTING_STATE_ARCHIVAL" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL();
        } else if ("CONFIG_SETTING_CONTRACT_EXECUTION_LANES" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES();
        } else if ("CONFIG_SETTING_BUCKETLIST_SIZE_WINDOW" === $type ||
            "CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW();
        } else if ("CONFIG_SETTING_EVICTION_ITERATOR" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR();
        } else if ("CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0();
        } else if ("CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0();
        } else if ("CONFIG_SETTING_SCP_TIMING" === $type) {
            return XdrConfigSettingID::CONFIG_SETTING_SCP_TIMING();
        }else {
            throw new InvalidArgumentException('unknown ' . $prefix . 'type ' . $type);
        }
    }
    private static function getFootprint(string $prefix, array $map): XdrLedgerFootprint {
        /**
         * @var array<XdrLedgerKey> $readOnly
         */
        $readOnly = array();
        $len = self::getInt($prefix . 'readOnly.len', $map);
        for ($i = 0; $i < $len; $i++) {
            $next = self::getLedgerKey($prefix . 'readOnly[' . $i . '].', $map);
            array_push($readOnly, $next);
        }

        /**
         * @var array<XdrLedgerKey> $readWrite
         */
        $readWrite = array();
        $len = self::getInt($prefix . 'readWrite.len', $map);
        for ($i = 0; $i < $len; $i++) {
            $next = self::getLedgerKey($prefix . 'readWrite[' . $i . '].', $map);
            array_push($readWrite, $next);
        }
        return new XdrLedgerFootprint($readOnly, $readWrite);
    }
    private static function getSorobanResources(String $prefix, array $map): XdrSorobanResources {
        $footprint = self::getFootprint($prefix . 'footprint.', $map);
        $instructions = self::getInt($prefix . 'instructions', $map);
        if (self::getClearValue($prefix . 'readBytes', $map) !== null) {
            $diskReadBytes = self::getInt($prefix . 'readBytes', $map);
        } else {
            $diskReadBytes = self::getInt($prefix . 'diskReadBytes', $map);
        }

        $writeBytes= self::getInt($prefix . 'writeBytes', $map);
        return new XdrSorobanResources($footprint, $instructions, $diskReadBytes, $writeBytes);
    }

    private static function getSorobanTransactionData(String $prefix, array $map): ?XdrSorobanTransactionData {
        $v = self::getInt($prefix . 'ext.v', $map);
        if ($v !== 1) {
            return null;
        }
        $ext = new XdrSorobanTransactionDataExt(0);
        if (self::getClearValue($prefix . 'sorobanData.ext.v', $map) !== null &&
            self::getInt($prefix . 'sorobanData.ext.v', $map) === 1) {

            /**
             * @var array<int> $archivedEntries
             */
            $archivedEntries = array();
            $len = self::getInt($prefix . 'sorobanData.ext.archivedSorobanEntries.len', $map);
            for ($i = 0; $i < $len; $i++) {
                $next = self::getInt($prefix . 'sorobanData.ext.archivedSorobanEntries[' . $i . '].', $map);
                $archivedEntries[] = $next;
            }
            $ext = new XdrSorobanTransactionDataExt(discriminant: 1,
                resourceExt: new XdrSorobanResourcesExtV0($archivedEntries));
        }
        $resources = self::getSorobanResources($prefix . 'sorobanData.resources.', $map);
        $resourcesFee = self::getInt($prefix . 'sorobanData.resourceFee', $map);
        return new XdrSorobanTransactionData($ext, $resources, $resourcesFee);
    }

    private static function getAccountId(string $key, array $map): String {
        $accountId = self::getString($key, $map);
        try {
            KeyPair::fromAccountId($accountId);
        } catch (Exception) {
            throw new InvalidArgumentException('invalid value for '. $key);
        }
        return $accountId;
    }

    private static function getString(string $key, array $map): String {
        $str = self::getClearValue($key, $map);
        if ($str === null || $str === '') {
            throw new InvalidArgumentException('missing ' . $key);
        }
        return $str;
    }

    private static function getInt(string $key, array $map): int {
        $str = self::getClearValue($key, $map);
        if ($str === null) {
            throw new InvalidArgumentException('missing ' . $key);
        }
        if (!self::isInt($str)) {
            throw new InvalidArgumentException('invalid value for ' . $key);
        }

        return intval($str);
    }

    private static function isInt(string $value): bool {
        return is_numeric($value) && floatval(intval($value)) === floatval($value);
    }

    private static function getLiquidityPoolWithdrawOp($opPrefix, array $map, ?string $sourceAccountId) : LiquidityPoolWithdrawOperation
    {
        $liquidityPoolID = self::getClearValue($opPrefix . 'liquidityPoolID', $map);
        if (!$liquidityPoolID) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'liquidityPoolID');
        }

        $amountStr = self::getClearValue($opPrefix.'amount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amount = self::fromAmount($amountStr);

        $minAmountAStr = self::getClearValue($opPrefix.'minAmountA', $map);
        if ($minAmountAStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'minAmountA');
        }
        if (!is_numeric($minAmountAStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'minAmountA');
        }
        $minAmountA = self::fromAmount($minAmountAStr);

        $minAmountBStr = self::getClearValue($opPrefix.'minAmountB', $map);
        if ($minAmountBStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'minAmountB');
        }
        if (!is_numeric($minAmountBStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'minAmountB');
        }
        $minAmountB = self::fromAmount($minAmountBStr);

        $builder = new LiquidityPoolWithdrawOperationBuilder($liquidityPoolID,$amount, $minAmountA, $minAmountB);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getLiquidityPoolDepositOp($opPrefix, array $map, ?string $sourceAccountId) : LiquidityPoolDepositOperation
    {
        $liquidityPoolID = self::getClearValue($opPrefix . 'liquidityPoolID', $map);
        if (!$liquidityPoolID) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'liquidityPoolID');
        }

        $maxAmountAStr = self::getClearValue($opPrefix.'maxAmountA', $map);
        if ($maxAmountAStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'maxAmountA');
        }
        if (!is_numeric($maxAmountAStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'maxAmountA');
        }
        $maxAmountA = self::fromAmount($maxAmountAStr);

        $maxAmountBStr = self::getClearValue($opPrefix.'maxAmountB', $map);
        if ($maxAmountBStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'maxAmountB');
        }
        if (!is_numeric($maxAmountBStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'maxAmountB');
        }
        $maxAmountB = self::fromAmount($maxAmountBStr);

        $minPriceNStr = self::getClearValue($opPrefix.'minPrice.n', $map);
        if ($minPriceNStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'minPrice.n');
        }
        if (!is_numeric($minPriceNStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'minPrice.n');
        }
        $minPriceN = (int)($minPriceNStr);

        $minPriceDStr = self::getClearValue($opPrefix.'minPrice.d', $map);
        if ($minPriceDStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'minPrice.d');
        }
        if (!is_numeric($minPriceDStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'minPrice.d');
        }
        $minPriceD = (int)($minPriceDStr);

        $maxPriceNStr = self::getClearValue($opPrefix.'maxPrice.n', $map);
        if ($maxPriceNStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'maxPrice.n');
        }
        if (!is_numeric($maxPriceNStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'maxPrice.n');
        }
        $maxPriceN = (int)($maxPriceNStr);

        $maxPriceDStr = self::getClearValue($opPrefix.'maxPrice.d', $map);
        if ($maxPriceDStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'maxPrice.d');
        }
        if (!is_numeric($maxPriceDStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'maxPrice.d');
        }
        $maxPriceD = (int)($maxPriceDStr);

        $builder = new LiquidityPoolDepositOperationBuilder($liquidityPoolID,$maxAmountA, $maxAmountB,
            new Price($minPriceN, $minPriceD), new Price($maxPriceN, $maxPriceD));
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getSetTrustlineFlagsOp($opPrefix, array $map, ?string $sourceAccountId) : SetTrustLineFlagsOperation
    {
        $trustor = self::getClearValue($opPrefix . 'trustor', $map);
        if (!$trustor) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'trustor');
        }
        try {
            KeyPair::fromAccountId($trustor);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'trustor');
        }

        $assetStr = self::getClearValue($opPrefix . 'asset', $map);
        if (!$assetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'asset');
        }
        $asset = Asset::createFromCanonicalForm($assetStr);
        if (!$asset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'asset');
        }

        $clearFlags = self::getClearValue($opPrefix . 'clearFlags', $map);
        if ($clearFlags == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'clearFlags');
        }
        if(!is_numeric($clearFlags)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'clearFlags');
        }

        $setFlags = self::getClearValue($opPrefix . 'setFlags', $map);
        if ($setFlags == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'setFlags');
        }
        if(!is_numeric($setFlags)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'setFlags');
        }

        $builder = new SetTrustLineFlagsOperationBuilder($trustor, $asset, (int)$clearFlags, (int)$setFlags);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getClawbackClaimableBalanceOp($opPrefix, array $map, ?string $sourceAccountId) : ClawbackClaimableBalanceOperation
    {
        $claimableBalanceId = self::getClearValue($opPrefix . 'balanceID.v0', $map);
        if (!$claimableBalanceId) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'balanceID.v0');
        }
        $builder = new ClawbackClaimableBalanceOperationBuilder($claimableBalanceId);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getClawbackOp($opPrefix, array $map, ?string $sourceAccountId) : ClawbackOperation
    {
        $assetStr = self::getClearValue($opPrefix . 'asset', $map);
        if (!$assetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'asset');
        }
        $asset = Asset::createFromCanonicalForm($assetStr);
        if (!$asset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'asset');
        }
        $amountStr = self::getClearValue($opPrefix.'amount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amount = self::fromAmount($amountStr);

        $accountId = self::getClearValue($opPrefix . 'from', $map);
        if (!$accountId) {
            throw new InvalidArgumentException('missing '.$opPrefix.'from');
        }
        try {
            KeyPair::fromAccountId($accountId);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'from');
        }

        $builder = new ClawbackOperationBuilder($asset, MuxedAccount::fromAccountId($accountId), $amount);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getRevokeSponsorshipOp($opPrefix, array $map, ?string $sourceAccountId) : RevokeSponsorshipOperation
    {
        $type = self::getClearValue($opPrefix . 'type', $map);
        if (!$type) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'type');
        }
        $builder = new RevokeSponsorshipOperationBuilder();

        if ($type == "REVOKE_SPONSORSHIP_LEDGER_ENTRY") {
            $ledgerKeyType = self::getClearValue($opPrefix . 'ledgerKey.type', $map);
            if (!$ledgerKeyType) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'ledgerKey.type');
            }
            if ($ledgerKeyType == 'ACCOUNT') {
                $accountId = self::getClearValue($opPrefix . 'ledgerKey.account.accountID', $map);
                if (!$accountId) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'ledgerKey.account.accountID');
                }
                try {
                    KeyPair::fromAccountId($accountId);
                } catch (Exception $e) {
                    throw new InvalidArgumentException('invalid '.$opPrefix.'ledgerKey.account.accountID');
                }
                $builder = $builder->revokeAccountSponsorship($accountId);
            } else if($ledgerKeyType == 'TRUSTLINE') {
                $accountId = self::getClearValue($opPrefix . 'ledgerKey.trustLine.accountID', $map);
                if (!$accountId) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'ledgerKey.trustLine.accountID');
                }
                try {
                    KeyPair::fromAccountId($accountId);
                } catch (Exception $e) {
                    throw new InvalidArgumentException('invalid '.$opPrefix.'ledgerKey.trustLine.accountID');
                }
                $assetStr = self::getClearValue($opPrefix . 'ledgerKey.trustLine.asset', $map);
                if (!$assetStr) {
                    throw new InvalidArgumentException('missing ' . $opPrefix . 'ledgerKey.trustLine.asset');
                }
                $asset = Asset::createFromCanonicalForm($assetStr);
                if (!$asset) {
                    throw new InvalidArgumentException('invalid ' . $opPrefix . 'ledgerKey.trustLine.asset');
                }
                $builder = $builder->revokeTrustlineSponsorship($accountId,$asset);
            } else if($ledgerKeyType == 'OFFER') {
                $sellerId = self::getClearValue($opPrefix . 'ledgerKey.offer.sellerID', $map);
                if (!$sellerId) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'ledgerKey.offer.sellerID');
                }
                try {
                    KeyPair::fromAccountId($sellerId);
                } catch (Exception $e) {
                    throw new InvalidArgumentException('invalid '.$opPrefix.'ledgerKey.offer.sellerID');
                }
                $offerId = self::getClearValue($opPrefix . 'ledgerKey.offer.offerID', $map);
                if ($offerId == null) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'ledgerKey.offer.offerID');
                }
                if(!is_numeric($offerId)) {
                    throw new InvalidArgumentException('invalid '.$opPrefix.'ledgerKey.offer.offerID');
                }
                $builder = $builder->revokeOfferSponsorship($sellerId,(int)$offerId);
            } else if($ledgerKeyType == 'DATA') {
                $accountId = self::getClearValue($opPrefix . 'ledgerKey.data.accountID', $map);
                if (!$accountId) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'ledgerKey.data.accountID');
                }
                try {
                    KeyPair::fromAccountId($accountId);
                } catch (Exception $e) {
                    throw new InvalidArgumentException('invalid '.$opPrefix.'ledgerKey.data.accountID');
                }
                $dataNameStr = self::getClearValue($opPrefix . 'ledgerKey.data.dataName', $map);
                if (!$dataNameStr) {
                    throw new InvalidArgumentException('missing ' . $opPrefix . 'ledgerKey.data.dataName');
                }
                $dataName = str_replace('"','', $dataNameStr);
                $builder = $builder->revokeDataSponsorship($accountId, $dataName);
            } else if($ledgerKeyType == 'CLAIMABLE_BALANCE') {
                $claimableBalanceId = self::getClearValue($opPrefix . 'ledgerKey.claimableBalance.balanceID.v0', $map);
                if (!$claimableBalanceId) {
                    throw new InvalidArgumentException('missing ' . $opPrefix . 'ledgerKey.claimableBalance.balanceID.v0');
                }
                $builder = $builder->revokeClaimableBalanceSponsorship($claimableBalanceId);
            }
        } else if ($type == "REVOKE_SPONSORSHIP_SIGNER") {
            $accountId = self::getClearValue($opPrefix . 'signer.accountID', $map);
            if (!$accountId) {
                throw new InvalidArgumentException('missing '.$opPrefix.'signer.accountID');
            }
            try {
                KeyPair::fromAccountId($accountId);
            } catch (Exception $e) {
                throw new InvalidArgumentException('invalid '.$opPrefix.'signer.accountID');
            }
            $key = self::getClearValue($opPrefix . 'signer.signerKey', $map);
            if (!$key) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'signer.signerKey');
            }

            if (str_starts_with($key, 'G')) {
                $builder->revokeEd25519Signer($accountId, $key);
            } else if (str_starts_with($key, 'T')) {
                $builder->revokePreAuthTxSigner($accountId, $key);
            } else if (str_starts_with($key, 'X')) {
                $builder->revokeSha256HashSigner($accountId, $key);
            } else {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'signer.signerKey');
            }
        }
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getEndSponsoringFutureReservesOp(?string $sourceAccountId) : EndSponsoringFutureReservesOperation
    {
        $builder = new EndSponsoringFutureReservesOperationBuilder();
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getBeginSponsoringFutureReservesOp($opPrefix, array $map, ?string $sourceAccountId) : BeginSponsoringFutureReservesOperation
    {
        $sponsoredID = self::getClearValue($opPrefix . 'sponsoredID', $map);
        if (!$sponsoredID) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'sponsoredID');
        }
        $builder = new BeginSponsoringFutureReservesOperationBuilder($sponsoredID);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getClaimClaimableBalanceOp($opPrefix, array $map, ?string $sourceAccountId) : ClaimClaimableBalanceOperation
    {
        $claimableBalanceId = self::getClearValue($opPrefix . 'balanceID.v0', $map);
        if (!$claimableBalanceId) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'balanceID.v0');
        }
        $builder = new ClaimClaimableBalanceOperationBuilder($claimableBalanceId);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getCreateClaimableBalanceOp($opPrefix, array $map, ?string $sourceAccountId) : CreateClaimableBalanceOperation
    {
        $assetStr = self::getClearValue($opPrefix . 'asset', $map);
        if (!$assetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'asset');
        }
        $asset = Asset::createFromCanonicalForm($assetStr);
        if (!$asset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'asset');
        }
        $amountStr = self::getClearValue($opPrefix.'amount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amount = self::fromAmount($amountStr);

        $claimants = array();
        $claimantsLengthKey = $opPrefix.'claimants.len';
        $claimantsLengthStr = self::getClearValue($claimantsLengthKey, $map);
        if ($claimantsLengthStr) {
            if (!is_numeric($claimantsLengthStr)) {
                throw new InvalidArgumentException('invalid '.$claimantsLengthKey);
            }
            $claimantsLen = (int)$claimantsLengthStr;
            for ($i = 0; $i < $claimantsLen; $i++) {
                $nextClaimant = self::getClaimant($opPrefix, $map, $i);
                array_push($claimants, $nextClaimant);
            }
        }
        $builder = new CreateClaimableBalanceOperationBuilder($claimants,$asset,$amount);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getClaimant($opPrefix, array $map, int $index) : Claimant
    {
        $destination = self::getClearValue($opPrefix.'claimants['.$index.'].v0.destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing ' . $opPrefix.'claimants['.$index.'].v0.destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $opPrefix.'claimants['.$index.'].v0.destination');
        }
        $predicate = self::getClaimPredicate($opPrefix.'claimants['.$index.'].v0.predicate.', $map);
        return new Claimant($destination, $predicate);

    }

    private static function getClaimPredicate($prefix, array $map) : XdrClaimPredicate {
        $type = self::getClearValue($prefix.'type', $map);
        if (!$type) {
            throw new InvalidArgumentException('missing ' . $prefix.'type');
        }
        switch ($type) {
            case 'CLAIM_PREDICATE_UNCONDITIONAL':
               return new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL));
            case 'CLAIM_PREDICATE_AND':
                $lenStr = self::getClearValue($prefix.'andPredicates.len', $map);
                if ($lenStr == null) {
                    throw new InvalidArgumentException('missing ' . $prefix.'andPredicates.len');
                }
                if (!is_numeric($lenStr)) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'andPredicates.len');
                }
                $len = (int)$lenStr;
                if ($len != 2) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'andPredicates.len');
                }
                $andPredicates = array();
                for ($i = 0; $i < $len; $i++) {
                    $next = self::getClaimPredicate($prefix.'andPredicates['.strval($i).'].',$map);
                    array_push($andPredicates,$next);
                }
                $res = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::AND));
                $res->setAndPredicates($andPredicates);
                return $res;
            case 'CLAIM_PREDICATE_OR':
                $lenStr = self::getClearValue($prefix.'orPredicates.len', $map);
                if ($lenStr == null) {
                    throw new InvalidArgumentException('missing ' . $prefix.'orPredicates.len');
                }
                if (!is_numeric($lenStr)) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'orPredicates.len');
                }
                $len = (int)$lenStr;
                if ($len != 2) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'orPredicates.len');
                }
                $orPredicates = array();
                for ($i = 0; $i < $len; $i++) {
                    $next = self::getClaimPredicate($prefix.'orPredicates['.strval($i).'].',$map);
                    array_push($orPredicates,$next);
                }
                $res = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::OR));
                $res->setOrPredicates($orPredicates);
                return $res;
            case 'CLAIM_PREDICATE_NOT':
                $res = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::NOT));
                $res->setNotPredicate(self::getClaimPredicate($prefix.'notPredicate.', $map));
                return $res;
            case 'CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME':
                $res = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME));
                $timeStr = self::getClearValue($prefix.'absBefore', $map);
                if ($timeStr == null) {
                    throw new InvalidArgumentException('missing ' . $prefix.'absBefore');
                }
                if (!is_numeric($timeStr)) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'absBefore');
                }
                $res->setAbsBefore((int)$timeStr);
                return $res;
            case 'CLAIM_PREDICATE_BEFORE_RELATIVE_TIME':
                $res = new XdrClaimPredicate(new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME));
                $timeStr = self::getClearValue($prefix.'relBefore', $map);
                if ($timeStr == null) {
                    throw new InvalidArgumentException('missing ' . $prefix.'relBefore');
                }
                if (!is_numeric($timeStr)) {
                    throw new InvalidArgumentException('invalid ' . $prefix.'relBefore');
                }
                $res->setRelBefore((int)$timeStr);
                return $res;
            default:
                throw new InvalidArgumentException('invalid ' . $prefix.'type');
        }
    }

    private static function getBumpSequenceOperation($opPrefix, array $map, ?string $sourceAccountId) : BumpSequenceOperation
    {
        $bumpToStr = self::getClearValue($opPrefix . 'bumpTo', $map);
        if (!$bumpToStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'bumpTo');
        }

        $sequenceNumber = new BigInteger($bumpToStr);
        if ($sequenceNumber->toString() != $bumpToStr) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'bumpTo');
        }
        $builder = new BumpSequenceOperationBuilder($sequenceNumber);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getManageDataOperation($opPrefix, array $map, ?string $sourceAccountId) : ManageDataOperation
    {
        $dataNameStr = self::getClearValue($opPrefix . 'dataName', $map);
        if (!$dataNameStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'dataName');
        }
        $dataName = str_replace('"','', $dataNameStr);

        $present = self::getClearValue($opPrefix . 'dataValue._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'dataValue._present');
        }

        $dataValue = null;
        if ($present == 'true') {
            $dataValueStr = self::getClearValue($opPrefix . 'dataValue', $map);
            if (!$dataValueStr) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'dataValue');
            }
            $dataValue = hex2bin($dataValueStr);
            if (!$dataValue) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'dataValue');
            }
        }

        $builder = new ManageDataOperationBuilder($dataName, $dataValue);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getAccountMergeOperation(int $index, array $map, string $txPrefix, ?string $sourceAccountId) : AccountMergeOperation
    {
        $destination = self::getClearValue($txPrefix.'operations['.strval($index).'].body.destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing ' . $txPrefix.'operations['.strval($index).'].body.destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $txPrefix.'operations['.strval($index).'].body.destination');
        }
        $builder = new AccountMergeOperationBuilder($destination);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getAllowTrustOperation($opPrefix, array $map, ?string $sourceAccountId) : AllowTrustOperation
    {
        $trustor = self::getClearValue($opPrefix.'trustor', $map);
        if (!$trustor) {
            throw new InvalidArgumentException('missing '.$opPrefix.'trustor');
        }
        $assetCode = self::getClearValue($opPrefix.'asset', $map);
        if (!$assetCode) {
            throw new InvalidArgumentException('missing '.$opPrefix.'asset');
        }
        $authStr = self::getClearValue($opPrefix.'authorize', $map);
        if ($authStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'authorize');
        }
        if (!is_numeric($authStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'authorize');
        }
        $authorize = intval($authStr);
        if($authorize < 0 || $authorize > 2) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'authorize');
        }
        $auth = $authorize == 1;
        $authToMaintainLiabilities = $authorize == 2;
        $builder = new AllowTrustOperationBuilder($trustor, $assetCode, $auth, $authToMaintainLiabilities);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getChangeTrustOperation(string $opPrefix, array $map, ?string $sourceAccountId) : ChangeTrustOperation
    {
        $assetStr = self::getClearValue($opPrefix . 'line', $map);
        if (!$assetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'line');
        }
        $asset = Asset::createFromCanonicalForm($assetStr);
        if (!$asset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'line');
        }

        $limitStr = self::getClearValue($opPrefix.'limit', $map);
        if ($limitStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'limit');
        }
        if (!is_numeric($limitStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'limit');
        }
        $limit = self::fromAmount($limitStr);
        $builder = new ChangeTrustOperationBuilder($asset, $limit);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getSetOptionsOperation(string $opPrefix, array $map, ?string $sourceAccountId) : SetOptionsOperation
    {
        $present = self::getClearValue($opPrefix . 'inflationDest._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'inflationDest._present');
        }
        $inflationDest = null;
        if ($present == 'true') {
            $inflationDest = self::getClearValue($opPrefix . 'inflationDest', $map);
            if (!$inflationDest) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'inflationDest');
            }
        }

        $present = self::getClearValue($opPrefix . 'clearFlags._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'clearFlags._present');
        }
        $clearFlags = null;
        if ($present == 'true') {
            $clearFlagsStr = self::getClearValue($opPrefix . 'clearFlags', $map);
            if ($clearFlagsStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'clearFlags');
            }
            if (!is_numeric($clearFlagsStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'clearFlags');
            }
            $clearFlags = (int)$clearFlagsStr;
        }

        $present = self::getClearValue($opPrefix . 'setFlags._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'setFlags._present');
        }
        $setFlags = null;
        if ($present == 'true') {
            $setFlagsStr = self::getClearValue($opPrefix . 'setFlags', $map);
            if ($setFlagsStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'setFlags');
            }
            if (!is_numeric($setFlagsStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'setFlags');
            }
            $setFlags = (int)$setFlagsStr;
        }

        $present = self::getClearValue($opPrefix . 'masterWeight._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'masterWeight._present');
        }
        $masterWeight = null;
        if ($present == 'true') {
            $masterWeightStr = self::getClearValue($opPrefix . 'masterWeight', $map);
            if ($masterWeightStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'masterWeight');
            }
            if (!is_numeric($masterWeightStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'masterWeight');
            }
            $masterWeight = (int)$masterWeightStr;
        }

        $present = self::getClearValue($opPrefix . 'lowThreshold._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'lowThreshold._present');
        }
        $lowThreshold = null;
        if ($present == 'true') {
            $lowThresholdStr = self::getClearValue($opPrefix . 'lowThreshold', $map);
            if (!$lowThresholdStr) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'lowThreshold');
            }
            if (!is_numeric($lowThresholdStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'lowThreshold');
            }
            $lowThreshold = (int)$lowThresholdStr;
        }

        $present = self::getClearValue($opPrefix . 'medThreshold._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'medThreshold._present');
        }
        $medThreshold = null;
        if ($present == 'true') {
            $medThresholdStr = self::getClearValue($opPrefix . 'medThreshold', $map);
            if ($medThresholdStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'medThreshold');
            }
            if (!is_numeric($medThresholdStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'medThreshold');
            }
            $medThreshold = (int)$medThresholdStr;
        }

        $present = self::getClearValue($opPrefix . 'highThreshold._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'highThreshold._present');
        }
        $highThreshold = null;
        if ($present == 'true') {
            $highThresholdStr = self::getClearValue($opPrefix . 'highThreshold', $map);
            if ($highThresholdStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'highThreshold');
            }
            if (!is_numeric($highThresholdStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'highThreshold');
            }
            $highThreshold = (int)$highThresholdStr;
        }

        $present = self::getClearValue($opPrefix . 'homeDomain._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'homeDomain._present');
        }
        $homeDomain = null;
        if ($present == 'true') {
            $homeDomainStr = self::getClearValue($opPrefix . 'homeDomain', $map);
            if (!$homeDomainStr) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'homeDomain');
            }
            $homeDomain = str_replace('"','', $homeDomainStr);
        }

        $present = self::getClearValue($opPrefix . 'signer._present', $map);
        if (!$present) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'signer._present');
        }
        $signer = null;
        $signerWeight = null;
        if ($present == 'true') {
            $signerWeightStr = self::getClearValue($opPrefix . 'signer.weight', $map);
            if ($signerWeightStr == null) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'signer.weight');
            }
            if (!is_numeric($signerWeightStr)) {
                throw new InvalidArgumentException('invalid ' . $opPrefix . 'signer.weight');
            }
            $signerWeight = (int)$signerWeightStr;

            $key = self::getClearValue($opPrefix . 'signer.key', $map);
            if (!$key) {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'signer.key');
            }

            if (str_starts_with($key, 'G')) {
                $signer = new XdrSignerKey();
                $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
                $signer->setEd25519(StrKey::decodeAccountId($key));
            } else if (str_starts_with($key, 'T')) {
                $signer = new XdrSignerKey();
                $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
                $signer->setPreAuthTx(StrKey::decodePreAuthTx($key));
            } else if (str_starts_with($key, 'X')) {
                $signer = new XdrSignerKey();
                $signer->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
                $signer->setHashX(StrKey::decodeSha256Hash($key));
            } else {
                throw new InvalidArgumentException('missing ' . $opPrefix . 'signer.key');
            }
        }

        $builder = new SetOptionsOperationBuilder();
        if ($inflationDest != null) {
            $builder->setInflationDestination($inflationDest);
        }
        if ($clearFlags != null) {
            $builder->setClearFlags($clearFlags);
        }
        if ($setFlags != null) {
            $builder->setSetFlags($setFlags);
        }
        if ($masterWeight != null) {
            $builder->setMasterKeyWeight($masterWeight);
        }
        if ($lowThreshold != null) {
            $builder->setLowThreshold($lowThreshold);
        }
        if ($medThreshold != null) {
            $builder->setMediumThreshold($medThreshold);
        }
        if ($highThreshold != null) {
            $builder->setHighThreshold($highThreshold);
        }
        if ($homeDomain != null) {
            $builder->setHomeDomain($homeDomain);
        }
        if ($signer != null && $signerWeight != null) {
            $builder->setSigner($signer, $signerWeight);
        }
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }
    private static function getCreatePassiveSellOfferOperation(string $opPrefix, array $map, ?string $sourceAccountId) : CreatePassiveSellOfferOperation
    {
        $sellingStr = self::getClearValue($opPrefix . 'selling', $map);
        if (!$sellingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'selling');
        }
        $selling = Asset::createFromCanonicalForm($sellingStr);
        if (!$selling) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'selling');
        }
        $buyingStr = self::getClearValue($opPrefix . 'buying', $map);
        if (!$buyingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'buying');
        }
        $buying = Asset::createFromCanonicalForm($buyingStr);
        if (!$buying) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'buying');
        }

        $amountStr = self::getClearValue($opPrefix.'amount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amount = self::fromAmount($amountStr);

        $priceNStr = self::getClearValue($opPrefix.'price.n', $map);
        if ($priceNStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.n');
        }
        if (!is_numeric($priceNStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.n');
        }
        $priceN = (int)$priceNStr;

        $priceDStr = self::getClearValue($opPrefix.'price.d', $map);
        if ($priceDStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.d');
        }
        if (!is_numeric($priceDStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.d');
        }
        $priceD = (int)$priceDStr;
        if ($priceD == 0) {
            throw new InvalidArgumentException('price denominator can not be 0 in ' . $opPrefix . 'price.d');
        }
        $price = new Price($priceN, $priceD);

        $builder = new CreatePassiveSellOfferOperationBuilder($selling, $buying,$amount, $price);

        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getManageSellOfferOperation(string $opPrefix, array $map, ?string $sourceAccountId) : ManageSellOfferOperation
    {
        $sellingStr = self::getClearValue($opPrefix . 'selling', $map);
        if (!$sellingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'selling');
        }
        $selling = Asset::createFromCanonicalForm($sellingStr);
        if (!$selling) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'selling');
        }
        $buyingStr = self::getClearValue($opPrefix . 'buying', $map);
        if (!$buyingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'buying');
        }
        $buying = Asset::createFromCanonicalForm($buyingStr);
        if (!$buying) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'buying');
        }

        $amountStr = self::getClearValue($opPrefix.'amount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amount = self::fromAmount($amountStr);

        $priceNStr = self::getClearValue($opPrefix.'price.n', $map);
        if ($priceNStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.n');
        }
        if (!is_numeric($priceNStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.n');
        }
        $priceN = (int)$priceNStr;

        $priceDStr = self::getClearValue($opPrefix.'price.d', $map);
        if ($priceDStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.d');
        }
        if (!is_numeric($priceDStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.d');
        }
        $priceD = (int)$priceDStr;
        if ($priceD == 0) {
            throw new InvalidArgumentException('price denominator can not be 0 in ' . $opPrefix . 'price.d');
        }
        $dec = (float)$priceN / (float)$priceD;
        $offerIdStr = self::getClearValue($opPrefix.'offerID', $map);
        if ($offerIdStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'offerID');
        }
        if (!is_numeric($offerIdStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'offerID');
        }
        $offerID = (int)$offerIdStr;

        $builder = new ManageSellOfferOperationBuilder($selling, $buying,$amount, strval($dec));

        $builder->setOfferId($offerID);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getManageBuyOfferOperation(string $opPrefix, array $map, ?string $sourceAccountId) : ManageBuyOfferOperation
    {
        $sellingStr = self::getClearValue($opPrefix . 'selling', $map);
        if (!$sellingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'selling');
        }
        $selling = Asset::createFromCanonicalForm($sellingStr);
        if (!$selling) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'selling');
        }
        $buyingStr = self::getClearValue($opPrefix . 'buying', $map);
        if (!$buyingStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'buying');
        }
        $buying = Asset::createFromCanonicalForm($buyingStr);
        if (!$buying) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'buying');
        }

        $amountStr = self::getClearValue($opPrefix.'buyAmount', $map);
        if ($amountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'buyAmount');
        }
        if (!is_numeric($amountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'buyAmount');
        }
        $amount = self::fromAmount($amountStr);

        $priceNStr = self::getClearValue($opPrefix.'price.n', $map);
        if ($priceNStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.n');
        }
        if (!is_numeric($priceNStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.n');
        }
        $priceN = (int)$priceNStr;

        $priceDStr = self::getClearValue($opPrefix.'price.d', $map);
        if ($priceDStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'price.d');
        }
        if (!is_numeric($priceDStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'price.d');
        }
        $priceD = (int)$priceDStr;
        if ($priceD == 0) {
            throw new InvalidArgumentException('price denominator can not be 0 in ' . $opPrefix . 'price.d');
        }
        $dec = (float)$priceN / (float)$priceD;
        $offerIdStr = self::getClearValue($opPrefix.'offerID', $map);
        if ($offerIdStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'offerID');
        }
        if (!is_numeric($offerIdStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'offerID');
        }
        $offerID = (int)$offerIdStr;

        $builder = new ManageBuyOfferOperationBuilder($selling, $buying,$amount, strval($dec));

        $builder->setOfferId($offerID);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getPathPaymentStrictSendOperation(string $opPrefix, array $map, ?string $sourceAccountId) : PathPaymentStrictSendOperation
    {
        $sendAssetStr = self::getClearValue($opPrefix . 'sendAsset', $map);
        if (!$sendAssetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'sendAsset');
        }
        $sendAsset = Asset::createFromCanonicalForm($sendAssetStr);
        if (!$sendAsset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'sendAsset');
        }
        $sendAmountStr = self::getClearValue($opPrefix.'sendAmount', $map);
        if ($sendAmountStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'sendAmount');
        }
        if (!is_numeric($sendAmountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'sendAmount');
        }
        $sendAmount = self::fromAmount($sendAmountStr);

        $destination = self::getClearValue($opPrefix . 'destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'destination');
        }

        $destAssetStr = self::getClearValue($opPrefix . 'destAsset', $map);
        if (!$destAssetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'destAsset');
        }
        $destAsset = Asset::createFromCanonicalForm($destAssetStr);
        if (!$destAsset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'destAsset');
        }

        $destMinStr= self::getClearValue($opPrefix.'destMin', $map);
        if ($destMinStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'destMin');
        }
        if (!is_numeric($destMinStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'destMin');
        }
        $destMin = self::fromAmount($destMinStr);

        $path = array();
        $pathLengthKey = $opPrefix.'path.len';
        $pathLengthStr = self::getClearValue($pathLengthKey, $map);
        if ($pathLengthStr != null) {
            if (!is_numeric($pathLengthStr)) {
                throw new InvalidArgumentException('invalid '.$pathLengthKey);
            }
            $pathLen = (int)$pathLengthStr;
            if($pathLen > 5) {
                throw new InvalidArgumentException('path.len can not be greater than 5 in '.$pathLengthKey.' but is '.strval($pathLen));
            }
            for ($i = 0; $i < $pathLen; $i++) {
                $nextAssetStr = self::getClearValue($opPrefix.'path['.strval($i).']', $map);
                if (!$nextAssetStr) {
                    throw new InvalidArgumentException('missing '.$opPrefix.'path['.strval($i).']');
                }
                $nextAsset = Asset::createFromCanonicalForm($nextAssetStr);
                if (!$nextAsset) {
                    throw new InvalidArgumentException('invalid ' . $opPrefix . 'path['.strval($i).']');
                }
                array_push($path, $nextAsset);
            }
        }
        $builder = new PathPaymentStrictSendOperationBuilder($sendAsset,$sendAmount,$destination,$destAsset,$destMin);
        $builder->setPath($path);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getPathPaymentStrictReceiveOperation(string $opPrefix, array $map, ?string $sourceAccountId) : PathPaymentStrictReceiveOperation
    {
        $sendAssetStr = self::getClearValue($opPrefix . 'sendAsset', $map);
        if (!$sendAssetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'sendAsset');
        }
        $sendAsset = Asset::createFromCanonicalForm($sendAssetStr);
        if (!$sendAsset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'sendAsset');
        }
        $sendMaxStr = self::getClearValue($opPrefix.'sendMax', $map);
        if ($sendMaxStr == null) {
            throw new InvalidArgumentException('missing '.$opPrefix.'sendMax');
        }
        if (!is_numeric($sendMaxStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'sendMax');
        }
        $sendMax = self::fromAmount($sendMaxStr);

        $destination = self::getClearValue($opPrefix . 'destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'destination');
        }

        $destAssetStr = self::getClearValue($opPrefix . 'destAsset', $map);
        if (!$destAssetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'destAsset');
        }
        $destAsset = Asset::createFromCanonicalForm($destAssetStr);
        if (!$destAsset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'destAsset');
        }

        $destAmountStr = self::getClearValue($opPrefix.'destAmount', $map);
        if (!$destAmountStr) {
            throw new InvalidArgumentException('missing '.$opPrefix.'destAmount');
        }
        if (!is_numeric($destAmountStr)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'destAmount');
        }
        $destAmount = self::fromAmount($destAmountStr);

        $path = array();
        $pathLengthKey = $opPrefix.'path.len';
        $pathLengthStr = self::getClearValue($pathLengthKey, $map);
        if ($pathLengthStr != null) {
            if (!is_numeric($pathLengthStr)) {
               throw new InvalidArgumentException('invalid '.$pathLengthKey);
           }
           $pathLen = (int)$pathLengthStr;
           if($pathLen > 5) {
               throw new InvalidArgumentException('path.len can not be greater than 5 in '.$pathLengthKey.' but is '.strval($pathLen));
           }
           for ($i = 0; $i < $pathLen; $i++) {
               $nextAssetStr = self::getClearValue($opPrefix.'path['.strval($i).']', $map);
               if ($nextAssetStr == null) {
                   throw new InvalidArgumentException('missing '.$opPrefix.'path['.strval($i).']');
               }
               $nextAsset = Asset::createFromCanonicalForm($nextAssetStr);
               if (!$nextAsset) {
                   throw new InvalidArgumentException('invalid ' . $opPrefix . 'path['.strval($i).']');
               }
               array_push($path, $nextAsset);
           }
        }
        $builder = new PathPaymentStrictReceiveOperationBuilder($sendAsset,$sendMax,$destination,$destAsset,$destAmount);
        $builder->setPath($path);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getPaymentOperation(string $opPrefix, array $map, ?string $sourceAccountId) : PaymentOperation
    {
        $destination = self::getClearValue($opPrefix . 'destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'destination');
        }
        $assetStr = self::getClearValue($opPrefix . 'asset', $map);
        if (!$assetStr) {
            throw new InvalidArgumentException('missing ' . $opPrefix . 'asset');
        }
        $asset = Asset::createFromCanonicalForm($assetStr);
        if (!$asset) {
            throw new InvalidArgumentException('invalid ' . $opPrefix . 'asset');
        }
        $amountValue = self::getClearValue($opPrefix.'amount', $map);
        if (!$amountValue) {
            throw new InvalidArgumentException('missing '.$opPrefix.'amount');
        }
        if (!is_numeric($amountValue)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'amount');
        }
        $amountValue = self::fromAmount($amountValue);

        $builder = new PaymentOperationBuilder($destination,$asset, $amountValue);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getCreateAccountOperation(string $opPrefix, array $map, ?string $sourceAccountId) : CreateAccountOperation {
        $destination = self::getClearValue($opPrefix.'destination', $map);
        if (!$destination) {
            throw new InvalidArgumentException('missing '.$opPrefix.'destination');
        }
        try {
            KeyPair::fromAccountId($destination);
        } catch (Exception $e) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'destination');
        }
        $startingBalanceValue = self::getClearValue($opPrefix.'startingBalance', $map);
        if (!$startingBalanceValue) {
            throw new InvalidArgumentException('missing '.$opPrefix.'startingBalance');
        }
        if (!is_numeric($startingBalanceValue)) {
            throw new InvalidArgumentException('invalid '.$opPrefix.'startingBalance');
        }
        $startingBalanceValue = self::fromAmount($startingBalanceValue);
        $builder = new CreateAccountOperationBuilder($destination, $startingBalanceValue);
        if ($sourceAccountId != null) {
            $builder->setMuxedSourceAccount(MuxedAccount::fromAccountId($sourceAccountId));
        }
        return $builder->build();
    }

    private static function getClearValue(string $key, array $map) : ?string {
        // check if exists
        if (!array_key_exists($key, $map) || $map[$key] == null) {
            return null;
        }
        // remove comment
        $arr = explode('(', $map[$key]);
        return trim($arr[0]);
    }

    private static function getSignatures(string $prefix, ?array $signatures) : array {
        $lines = array();
        if ($signatures) {
            $lines += [$prefix.'signatures.len' => count($signatures)];
            $index = 0;
            foreach($signatures as $signature) {
                if ($signature instanceof XdrDecoratedSignature) {
                    $lines = array_merge($lines, self::getSignatureTx($signature, $index, $prefix));
                }
                $index++;
            }
        } else {
            $lines += [$prefix.'signatures.len' => '0'];
        }
        return $lines;
    }

    private static function getSignatureTx(XdrDecoratedSignature $signature, int $index, string $prefix) : array {
        $lines = array();
        $lines += [$prefix.'signatures['.strval($index).'].hint' => bin2hex($signature->getHint())];
        $lines += [$prefix.'signatures['.strval($index).'].signature' => bin2hex($signature->getSignature())];
        return $lines;
    }

    private static function getPreconditions(?TransactionPreconditions $cond, string $prefix) : array {
        $lines = array();

        if (!$cond || (!$cond->hasV2() && !$cond->getTimeBounds())) {
            $lines += [$prefix.'cond.type' => 'PRECOND_NONE'];
            return $lines;
        }
        if ($cond->hasV2()) {
            $lines += [$prefix.'cond.type' => 'PRECOND_V2'];
            $precondPrefix = $prefix."cond.v2.";
            $lines = array_merge($lines, self::getTimeBounds($cond->getTimeBounds(), $precondPrefix));
            $lines = array_merge($lines, self::getLedgerBounds($cond->getLedgerBounds(), $precondPrefix));
            if ($cond->getMinSeqNumber()) {
                $lines += [$precondPrefix.'minSeqNum._present' => 'true'];
                $lines += [$precondPrefix.'minSeqNum' => $cond->getMinSeqNumber()->toString()];
            } else {
                $lines += [$precondPrefix.'minSeqNum._present' => 'false'];
            }
            $lines += [$precondPrefix.'minSeqAge' => strval($cond->getMinSeqAge())];
            $lines += [$precondPrefix.'minSeqLedgerGap' => strval($cond->getMinSeqLedgerGap())];
            $extraSigners = $cond->getExtraSigners();
            $lines += [$precondPrefix.'extraSigners.len' => count($extraSigners)];
            $index = 0;
            foreach ($extraSigners as $signer) {
                if ($signer instanceof XdrSignerKey) {
                    if ($signer->getType()->getValue() == XdrSignerKeyType::ED25519) {
                        $lines += [$precondPrefix.'extraSigners['.strval($index).']' => StrKey::encodeAccountId($signer->getEd25519())];
                    } else if ($signer->getType()->getValue() == XdrSignerKeyType::PRE_AUTH_TX) {
                        $lines += [$precondPrefix.'extraSigners['.strval($index).']' => StrKey::encodePreAuthTx($signer->getPreAuthTx())];
                    } else if ($signer->getType()->getValue() == XdrSignerKeyType::HASH_X) {
                        $lines += [$precondPrefix.'extraSigners['.strval($index).']' => StrKey::encodeSha256Hash($signer->getHashX())];
                    } else if ($signer->getType()->getValue() == XdrSignerKeyType::ED25519_SIGNED_PAYLOAD) {
                        $lines += [$precondPrefix.'extraSigners['.strval($index).']' => StrKey::encodeXdrSignedPayload($signer->getSignedPayload())];
                    }
                }
                $index++;
            }
        } else if($cond->getTimeBounds()) {
            $lines += [$prefix.'cond.type' => 'PRECOND_TIME'];
            $lines += [$prefix.'cond.timeBounds.minTime' => strval($cond->getTimeBounds()->getMinTime()->getTimestamp())];
            $lines += [$prefix.'cond.timeBounds.maxTime' => strval($cond->getTimeBounds()->getMaxTime()->getTimestamp())];
        }
        return $lines;
    }

    private static function getTimeBounds(?TimeBounds $timeBounds, string $prefix) : array {
        $lines = array();
        if ($timeBounds) {
            $lines += [$prefix.'timeBounds._present' => 'true'];
            $lines += [$prefix.'timeBounds.minTime' => strval($timeBounds->getMinTime()->getTimestamp())];
            $lines += [$prefix.'timeBounds.maxTime' => strval($timeBounds->getMaxTime()->getTimestamp())];
        } else {
            $lines += [$prefix.'timeBounds._present' => 'false'];
        }
        return $lines;
    }

    private static function getLedgerBounds(?LedgerBounds $ledgerBounds, string $prefix) : array {
        $lines = array();
        if ($ledgerBounds) {
            $lines += [$prefix.'ledgerBounds._present' => 'true'];
            $lines += [$prefix.'ledgerBounds.minLedger' => strval($ledgerBounds->getMinLedger())];
            $lines += [$prefix.'ledgerBounds.maxLedger' => strval($ledgerBounds->getMaxLedger())];
        } else {
            $lines += [$prefix.'ledgerBounds._present' => 'false'];
        }
        return $lines;
    }


    private static function getOperationTx(AbstractOperation $operation, int $index, string $txPrefix) : array {
        $lines = array();
        $sourceAccount = $operation->getSourceAccount();
        if ($sourceAccount) {
            $lines += [$txPrefix.'operations['.strval($index).'].sourceAccount._present' => 'true'];
            $lines += [$txPrefix.'operations['.strval($index).'].sourceAccount' => $sourceAccount->getAccountId()];
        } else {
            $lines += [$txPrefix.'operations['.strval($index).'].sourceAccount._present' => 'false'];
        }

        $lines += [$txPrefix.'operations['.strval($index).'].body.type' => self::txRepOpTypeUpperCase($operation)];

        $prefix = $txPrefix.'operations['.strval($index).'].body.'.self::txRepOpType($operation).".";

        if ($operation instanceof CreateAccountOperation) {
            $lines += [$prefix.'destination' => $operation->getDestination()];
            $lines += [$prefix.'startingBalance' => self::toAmount($operation->getStartingBalance())];
        } else if ($operation instanceof PaymentOperation) {
            $lines += [$prefix.'destination' => $operation->getDestination()->getAccountId()];
            $lines += [$prefix.'asset' => self::encodeAsset($operation->getAsset())];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
        } else if ($operation instanceof PathPaymentStrictReceiveOperation) {
            $lines += [$prefix.'sendAsset' => self::encodeAsset($operation->getSendAsset())];
            $lines += [$prefix.'sendMax' => self::toAmount($operation->getSendMax())];
            $lines += [$prefix.'destination' => $operation->getDestination()->getAccountId()];
            $lines += [$prefix.'destAsset' => self::encodeAsset($operation->getDestAsset())];
            $lines += [$prefix.'destAmount' => self::toAmount($operation->getDestAmount())];
            $path = $operation->getPath();
            if ($path) {
                $lines += [$prefix.'path.len' => count($path)];
                $assetIndex = 0;
                foreach ($path as $asset) {
                    $lines += [$prefix.'path['.strval($assetIndex).']' => self::encodeAsset($asset)];
                    $assetIndex++;
                }
            } else {
                $lines += [$prefix.'path.len' => '0'];
            }
        } else if ($operation instanceof PathPaymentStrictSendOperation) {
            $lines += [$prefix.'sendAsset' => self::encodeAsset($operation->getSendAsset())];
            $lines += [$prefix.'sendAmount' => self::toAmount($operation->getSendAmount())];
            $lines += [$prefix.'destination' => $operation->getDestination()->getAccountId()];
            $lines += [$prefix.'destAsset' => self::encodeAsset($operation->getDestAsset())];
            $lines += [$prefix.'destMin' => self::toAmount($operation->getDestMin())];
            $path = $operation->getPath();
            if ($path) {
                $lines += [$prefix.'path.len' => count($path)];
                $assetIndex = 0;
                foreach ($path as $asset) {
                    $lines += [$prefix.'path['.strval($assetIndex).']' => self::encodeAsset($asset)];
                    $assetIndex++;
                }
            } else {
                $lines += [$prefix.'path.len' => '0'];
            }
        }  else if ($operation instanceof ManageSellOfferOperation) {
            $lines += [$prefix.'selling' => self::encodeAsset($operation->getSelling())];
            $lines += [$prefix.'buying' => self::encodeAsset($operation->getBuying())];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
            $price = $operation->getPrice();
            $lines += [$prefix.'price.n' => strval($price->getN())];
            $lines += [$prefix.'price.d' => strval($price->getD())];
            $lines += [$prefix.'offerID' => strval($operation->getOfferId())];
        }  else if ($operation instanceof CreatePassiveSellOfferOperation) {
            $lines += [$prefix.'selling' => self::encodeAsset($operation->getSelling())];
            $lines += [$prefix.'buying' => self::encodeAsset($operation->getBuying())];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
            $price = $operation->getPrice();
            $lines += [$prefix.'price.n' => strval($price->getN())];
            $lines += [$prefix.'price.d' => strval($price->getD())];
        }  else if ($operation instanceof SetOptionsOperation) {

            if ($operation->getInflationDestination()) {
                $lines += [$prefix.'inflationDest._present' => 'true'];
                $lines += [$prefix.'inflationDest' => $operation->getInflationDestination()];
            } else {
                $lines += [$prefix.'inflationDest._present' => 'false'];
            }

            if ($operation->getClearFlags()) {
                $lines += [$prefix.'clearFlags._present' => 'true'];
                $lines += [$prefix.'clearFlags' => strval($operation->getClearFlags())];
            } else {
                $lines += [$prefix.'clearFlags._present' => 'false'];
            }

            if ($operation->getSetFlags()) {
                $lines += [$prefix.'setFlags._present' => 'true'];
                $lines += [$prefix.'setFlags' => strval($operation->getSetFlags())];
            } else {
                $lines += [$prefix.'setFlags._present' => 'false'];
            }

            if ($operation->getMasterKeyWeight()) {
                $lines += [$prefix.'masterWeight._present' => 'true'];
                $lines += [$prefix.'masterWeight' => strval($operation->getMasterKeyWeight())];
            } else {
                $lines += [$prefix.'masterWeight._present' => 'false'];
            }

            if ($operation->getLowThreshold()) {
                $lines += [$prefix.'lowThreshold._present' => 'true'];
                $lines += [$prefix.'lowThreshold' => strval($operation->getLowThreshold())];
            } else {
                $lines += [$prefix.'lowThreshold._present' => 'false'];
            }

            if ($operation->getMediumThreshold()) {
                $lines += [$prefix.'medThreshold._present' => 'true'];
                $lines += [$prefix.'medThreshold' => strval($operation->getMediumThreshold())];
            } else {
                $lines += [$prefix.'medThreshold._present' => 'false'];
            }

            if ($operation->getHighThreshold()) {
                $lines += [$prefix.'highThreshold._present' => 'true'];
                $lines += [$prefix.'highThreshold' => strval($operation->getHighThreshold())];
            } else {
                $lines += [$prefix.'highThreshold._present' => 'false'];
            }

            if ($operation->getHomeDomain()) {
                $lines += [$prefix.'homeDomain._present' => 'true'];
                $lines += [$prefix.'homeDomain' => '"'.$operation->getHomeDomain().'"'];
            } else {
                $lines += [$prefix.'homeDomain._present' => 'false'];
            }
            $signer = $operation->getSignerKey();
            if ($signer) {
                $lines += [$prefix.'signer._present' => 'true'];
                if ($signer->getType()->getValue() == XdrSignerKeyType::ED25519) {
                    $lines += [$prefix.'signer.key' => StrKey::encodeAccountId($signer->getEd25519())];
                } else if ($signer->getType()->getValue() == XdrSignerKeyType::PRE_AUTH_TX) {
                    $lines += [$prefix.'signer.key' => StrKey::encodePreAuthTx($signer->getPreAuthTx())];
                } else if ($signer->getType()->getValue() == XdrSignerKeyType::HASH_X) {
                    $lines += [$prefix.'signer.key' => StrKey::encodeSha256Hash($signer->getHashX())];
                }
                $lines += [$prefix.'signer.weight' => strval($operation->getSignerWeight())];
            } else {
                $lines += [$prefix.'signer._present' => 'false'];
            }
        } else if ($operation instanceof ChangeTrustOperation) {
            $lines += [$prefix.'line' => self::encodeAsset($operation->getAsset())];
            $lines += [$prefix.'limit' => self::toAmount($operation->getLimit())];
        } else if ($operation instanceof AllowTrustOperation) {
            $lines += [$prefix.'trustor' => $operation->getTrustor()];
            $lines += [$prefix.'asset' => $operation->getAssetCode()];
            $auth = $operation->isAuthorize() ? 1 : 0;
            $auth = $operation->isAuthorizeToMaintainLiabilities() ? 2 : $auth;
            $lines += [$prefix.'authorize' => strval($auth)];
        } else if ($operation instanceof AccountMergeOperation) {
            // account merge does not include 'accountMergeOp' prefix
            $lines += [$txPrefix.'operations['.$index.'].body.destination' => $operation->getDestination()->getAccountId()];
        } else if ($operation instanceof ManageDataOperation) {
            $lines += [$prefix.'dataName' => '"'.$operation->getKey().'"'];
            if ($operation->getValue()) {
                $lines += [$prefix.'dataValue._present' => 'true'];
                $lines += [$prefix.'dataValue' => bin2hex($operation->getValue())];
            } else {
                $lines += [$prefix.'dataValue._present' => 'false'];
            }
        } else if ($operation instanceof BumpSequenceOperation) {
            $lines += [$prefix.'bumpTo' => $operation->getBumpTo()->toString()];
        } else if ($operation instanceof ManageBuyOfferOperation) {
            $lines += [$prefix.'selling' => self::encodeAsset($operation->getSelling())];
            $lines += [$prefix.'buying' => self::encodeAsset($operation->getBuying())];
            $lines += [$prefix.'buyAmount' => self::toAmount($operation->getAmount())];
            $price = $operation->getPrice();
            $lines += [$prefix.'price.n' => strval($price->getN())];
            $lines += [$prefix.'price.d' => strval($price->getD())];
            $lines += [$prefix.'offerID' => strval($operation->getOfferId())];
        } else if ($operation instanceof CreateClaimableBalanceOperation) {
            $lines += [$prefix.'asset' => self::encodeAsset($operation->getAsset())];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
            $claimants = $operation->getClaimants();
            $lines += [$prefix.'claimants.len' => strval(count($claimants))];
            $index = 0;
            foreach ($claimants as $claimant) {
                if($claimant instanceof Claimant) {
                    $lines += [$prefix.'claimants['.strval($index.'].type') => 'CLAIMANT_TYPE_V0'];
                    $lines += [$prefix.'claimants['.strval($index.'].v0.destination') => $claimant->getDestination()];
                    $px = $prefix.'claimants[' . strval($index) . '].v0.predicate.';
                    $predicate = $claimant->getPredicate();
                    $lines = array_merge($lines, self::getPredicateTx($px, $predicate));
                    $index++;
                }
            }
        } else if ($operation instanceof ClaimClaimableBalanceOperation) {
            $lines += [$prefix.'balanceID.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0'];
            $lines += [$prefix.'balanceID.v0' => $operation->getBalanceId()];
        } else if ($operation instanceof BeginSponsoringFutureReservesOperation) {
            $lines += [$prefix.'sponsoredID' => $operation->getSponsoredId()];
        } else if ($operation instanceof RevokeSponsorshipOperation) {
            $ledgerKey = $operation->getLedgerKey();
            $signerKey = $operation->getSignerKey();
            $signer = $operation->getSignerAccount();
            if($ledgerKey != null) {
                $lines += [$prefix.'type' => 'REVOKE_SPONSORSHIP_LEDGER_ENTRY'];
                if ($ledgerKey->getType()->getValue() == XdrLedgerEntryType::ACCOUNT) {
                    $lines += [$prefix.'ledgerKey.type' => 'ACCOUNT'];
                    $lines += [$prefix.'ledgerKey.account.accountID' => $ledgerKey->getAccount()->getAccountID()->getAccountId()];
                }
                else if ($ledgerKey->getType()->getValue() == XdrLedgerEntryType::TRUSTLINE) {
                    $lines += [$prefix.'ledgerKey.type' => 'TRUSTLINE'];
                    $lines += [$prefix.'ledgerKey.trustLine.accountID' => $ledgerKey->getTrustline()->getAccountID()->getAccountId()];
                    $lines += [$prefix.'ledgerKey.trustLine.asset' => self::encodeAsset(Asset::fromXdr($ledgerKey->getTrustline()->getAsset()))];
                }
                else if ($ledgerKey->getType()->getValue() == XdrLedgerEntryType::OFFER) {
                    $lines += [$prefix.'ledgerKey.type' => 'OFFER'];
                    $lines += [$prefix.'ledgerKey.offer.sellerID' => $ledgerKey->getOffer()->getSellerID()->getAccountId()];
                    $lines += [$prefix.'ledgerKey.offer.offerID' => $ledgerKey->getOffer()->getOfferID()];
                }
                else if ($ledgerKey->getType()->getValue() == XdrLedgerEntryType::DATA) {
                    $lines += [$prefix.'ledgerKey.type' => 'DATA'];
                    $lines += [$prefix.'ledgerKey.data.accountID' => $ledgerKey->getData()->getAccountID()->getAccountId()];
                    $lines += [$prefix.'ledgerKey.data.dataName' => '"' . $ledgerKey->getData()->getDataName() . '"'];
                }
                else if ($ledgerKey->getType()->getValue() == XdrLedgerEntryType::CLAIMABLE_BALANCE) {
                    $lines += [$prefix.'ledgerKey.type' => 'CLAIMABLE_BALANCE'];
                    $lines += [$prefix.'ledgerKey.claimableBalance.balanceID.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0'];
                    $lines += [$prefix.'ledgerKey.claimableBalance.balanceID.v0' => $ledgerKey->getBalanceID()->getHash()];
                }
            } else if($signerKey != null && $signer != null) {
                $lines += [$prefix.'type' => 'REVOKE_SPONSORSHIP_SIGNER'];
                $lines += [$prefix.'signer.accountID' => $signer];
                if($signerKey->getEd25519() != null) {
                    $lines += [$prefix.'signer.signerKey' => StrKey::encodeAccountId($signerKey->getEd25519())];
                } else if($signerKey->getPreAuthTx() != null) {
                    $lines += [$prefix.'signer.signerKey' => StrKey::encodePreAuthTx($signerKey->getPreAuthTx())];
                } else if($signerKey->getHashX() != null) {
                    $lines += [$prefix.'signer.signerKey' => StrKey::encodeSha256Hash($signerKey->getHashX())];
                }
            }
        } else if ($operation instanceof ClawbackOperation) {
            $lines += [$prefix.'asset' => self::encodeAsset($operation->getAsset())];
            $lines += [$prefix.'from' => $operation->getFrom()->getAccountId()];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
        } else if ($operation instanceof ClawbackClaimableBalanceOperation) {
            $lines += [$prefix.'balanceID.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0'];
            $lines += [$prefix.'balanceID.v0' => $operation->getBalanceId()];
        } else if ($operation instanceof SetTrustLineFlagsOperation) {
            $lines += [$prefix.'trustor' => $operation->getTrustorId()];
            $lines += [$prefix.'asset' => self::encodeAsset($operation->getAsset())];
            $lines += [$prefix.'clearFlags' => $operation->getClearFlags()];
            $lines += [$prefix.'setFlags' => $operation->getSetFlags()];
        } else if ($operation instanceof LiquidityPoolDepositOperation) {
            $lines += [$prefix.'liquidityPoolID' => $operation->getLiqudityPoolId()];
            $lines += [$prefix.'maxAmountA' => self::toAmount($operation->getMaxAmountA())];
            $lines += [$prefix.'maxAmountB' => self::toAmount($operation->getMaxAmountB())];
            $minPrice = $operation->getMinPrice();
            $maxPrice = $operation->getMaxPrice();
            $lines += [$prefix.'minPrice.n' => strval($minPrice->getN())];
            $lines += [$prefix.'minPrice.d' => strval($minPrice->getD())];
            $lines += [$prefix.'maxPrice.n' => strval($maxPrice->getN())];
            $lines += [$prefix.'maxPrice.d' => strval($maxPrice->getD())];
        } else if ($operation instanceof LiquidityPoolWithdrawOperation) {
            $lines += [$prefix.'liquidityPoolID' => $operation->getLiqudityPoolId()];
            $lines += [$prefix.'amount' => self::toAmount($operation->getAmount())];
            $lines += [$prefix.'minAmountA' => self::toAmount($operation->getMinAmountA())];
            $lines += [$prefix.'minAmountB' => self::toAmount($operation->getMinAmountB())];
        }
        else if ($operation instanceof InvokeHostFunctionOperation) {
             $fnPrefix = $prefix . 'hostFunction.';
             $function = $operation->function;
             $hostFunctionXdr = $function->toXdr();
             if ($hostFunctionXdr->type->value == XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT) {
                 $lines += [$fnPrefix.'type' => 'HOST_FUNCTION_TYPE_INVOKE_CONTRACT'];
                 $lines = array_merge($lines, self::getInvokeContractArgsTx($fnPrefix.'invokeContract.', $hostFunctionXdr->invokeContract));
             } else if ($hostFunctionXdr->type->value == XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT) {
                 $lines += [$fnPrefix.'type' => 'HOST_FUNCTION_TYPE_CREATE_CONTRACT'];
                 $lines = array_merge($lines, self::getCreateContractArgsTx($fnPrefix.'createContract.', $hostFunctionXdr->createContract));
             } else if ($hostFunctionXdr->type->value == XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2) {
                 $lines += [$fnPrefix.'type' => 'HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2'];
                 $lines = array_merge($lines, self::getCreateContractV2ArgsTx($fnPrefix.'createContractV2.', $hostFunctionXdr->createContractV2));
             } else if ($hostFunctionXdr->type->value == XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM) {
                 $lines += [$fnPrefix.'type' => 'HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM'];
                 $lines += [$fnPrefix.'wasm' => bin2hex($hostFunctionXdr->wasm->value)];
             }
             $auth = $operation->getAuth();
             $lines += [$prefix . 'auth.len' => strval(count($auth))];
             $index = 0;
             foreach ($auth as $val) {
                $lines = array_merge($lines, self::getSorobanAuthorizationEntryTx($prefix.'auth['.$index.'].', $val->toXdr()));
                $index++;
             }

        }  else if ($operation instanceof ExtendFootprintTTLOperation) {
            $lines += [$prefix.'ext.v' => '0'];
            $lines += [$prefix.'extendTo' => strval($operation->getExtendTo())];
        } else if ($operation instanceof RestoreFootprintOperation) {
            $lines += [$prefix.'ext.v' => '0'];
        }
        return $lines;
    }

    private static function getSCAddressTx(string $prefix, XdrSCAddress $address) : array {
        $type = $address->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $lines += [$prefix . 'type' => 'SC_ADDRESS_TYPE_ACCOUNT'];
                $lines += [$prefix . 'accountId' => $address->getAccountId()->getAccountId()];
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $lines += [$prefix . 'type' => 'SC_ADDRESS_TYPE_CONTRACT'];
                $lines += [$prefix . 'contractId' => StrKey::encodeContractIdHex($address->getContractId())];
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                $lines += [$prefix . 'type' => 'SC_ADDRESS_TYPE_MUXED_ACCOUNT'];
                $lines += [$prefix . 'muxedAccount' => $address->muxedAccount->getAccountId()];
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                $lines += [$prefix . 'type' => 'SC_ADDRESS_TYPE_CLAIMABLE_BALANCE'];
                $lines += [$prefix . 'claimableBalanceId.balanceID.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0'];
                $lines += [$prefix . 'claimableBalanceId.balanceID.v0' => $address->getClaimableBalanceId()];
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $lines += [$prefix . 'type' => 'SC_ADDRESS_TYPE_LIQUIDITY_POOL'];
                $lines += [$prefix . 'liquidityPoolId' => $address->getLiquidityPoolId()];
                break;
        }
        return $lines;
    }

    private static function getContractExecutableTx(string $prefix, XdrContractExecutable $val) : array {
        $type = $val->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM:
                $lines += [$prefix . 'type' => 'CONTRACT_EXECUTABLE_WASM'];
                $lines += [$prefix . 'wasm_hash' => $val->getWasmIdHex()];
                break;
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET:
                $lines += [$prefix . 'type' => 'CONTRACT_EXECUTABLE_STELLAR_ASSET'];
                break;
        }
        return $lines;
    }

    private static function getSCErrorTx(string $prefix, XdrSCError $val) : array {
        $lines = array();
        $type = $val->getType()->getValue();
        switch ($type) {
            case XdrSCErrorType::SCE_CONTRACT:
                $lines += [$prefix . 'type' => 'SCE_CONTRACT'];
                $lines += [$prefix . 'contractCode' => strval($val->getContractCode())];
                break;
            case XdrSCErrorType::SCE_WASM_VM:
                $lines += [$prefix . 'type' => 'SCE_WASM_VM'];
                break;
            case XdrSCErrorType::SCE_CONTEXT:
                $lines += [$prefix . 'type' => 'SCE_CONTEXT'];
                break;
            case XdrSCErrorType::SCE_STORAGE:
                $lines += [$prefix . 'type' => 'SCE_STORAGE'];
                break;
            case XdrSCErrorType::SCE_OBJECT:
                $lines += [$prefix . 'type' => 'SCE_OBJECT'];
                break;
            case XdrSCErrorType::SCE_CRYPTO:
                $lines += [$prefix . 'type' => 'SCE_CRYPTO'];
                break;
            case XdrSCErrorType::SCE_EVENTS:
                $lines += [$prefix . 'type' => 'SCE_EVENTS'];
                break;
            case XdrSCErrorType::SCE_BUDGET:
                $lines += [$prefix . 'type' => 'SCE_BUDGET'];
                break;
            case XdrSCErrorType::SCE_VALUE:
                $lines += [$prefix . 'type' => 'SCE_VALUE'];
                break;
            case XdrSCErrorType::SCE_AUTH:
                $lines += [$prefix . 'type' => 'SCE_AUTH'];
                switch ($val->code->value) {
                    case 0:
                        $lines += [$prefix . 'code' => 'SCEC_ARITH_DOMAIN'];
                        break;
                    case 1:
                        $lines += [$prefix . 'code' => 'SCEC_INDEX_BOUNDS'];
                        break;
                    case 2:
                        $lines += [$prefix . 'code' => 'SCEC_INVALID_INPUT'];
                        break;
                    case 3:
                        $lines += [$prefix . 'code' => 'SCEC_MISSING_VALUE'];
                        break;
                    case 4:
                        $lines += [$prefix . 'code' => 'SCEC_EXISTING_VALUE'];
                        break;
                    case 5:
                        $lines += [$prefix . 'code' => 'SCEC_EXCEEDED_LIMIT'];
                        break;
                    case 6:
                        $lines += [$prefix . 'code' => 'SCEC_INVALID_ACTION'];
                        break;
                    case 7:
                        $lines += [$prefix . 'code' => 'SCEC_INTERNAL_ERROR'];
                        break;
                    case 8:
                        $lines += [$prefix . 'code' => 'SCEC_UNEXPECTED_TYPE'];
                        break;
                    case 9:
                        $lines += [$prefix . 'code' => 'SCEC_UNEXPECTED_SIZE'];
                        break;
                }
                break;
        }
        return $lines;
    }
    private static function getSCValTx(string $prefix, XdrSCVal $val) : array {
        $lines = array();
        $type = $val->getType()->getValue();
        switch ($type) {
            case XdrSCValType::SCV_BOOL:
                $lines += [$prefix.'type' => 'SCV_BOOL'];
                $lines += [$prefix.'b' => $val->b];
                break;
            case XdrSCValType::SCV_VOID:
                $lines += [$prefix.'type' => 'SCV_VOID'];
                break;
            case XdrSCValType::SCV_ERROR:
                $lines += [$prefix.'type' => 'SCV_ERROR'];
                $lines = array_merge($lines, self::getSCErrorTx($prefix.'error.', $val->error));
                break;
            case XdrSCValType::SCV_U32:
                $lines += [$prefix.'type' => 'SCV_U32'];
                $lines += [$prefix.'u32' => $val->u32];
                break;
            case XdrSCValType::SCV_I32:
                $lines += [$prefix.'type' => 'SCV_I32'];
                $lines += [$prefix.'i32' => $val->i32];
                break;
            case XdrSCValType::SCV_U64:
                $lines += [$prefix.'type' => 'SCV_U64'];
                $lines += [$prefix.'u64' => $val->u64];
                break;
            case XdrSCValType::SCV_I64:
                $lines += [$prefix.'type' => 'SCV_I64'];
                $lines += [$prefix.'i64' => $val->i64];
                break;
            case XdrSCValType::SCV_TIMEPOINT:
                $lines += [$prefix.'type' => 'SCV_TIMEPOINT'];
                $lines += [$prefix.'timepoint' => $val->timepoint];
                break;
            case XdrSCValType::SCV_DURATION:
                $lines += [$prefix.'type' => 'SCV_DURATION'];
                $lines += [$prefix.'duration' => $val->duration];
                break;
            case XdrSCValType::SCV_U128:
                $lines += [$prefix.'type' => 'SCV_U128'];
                $lines += [$prefix.'u128.lo' => $val->u128->lo];
                $lines += [$prefix.'u128.hi' => $val->u128->hi];
                break;
            case XdrSCValType::SCV_I128:
                $lines += [$prefix.'type' => 'SCV_I128'];
                $lines += [$prefix.'i128.lo' => $val->i128->lo];
                $lines += [$prefix.'i128.hi' => $val->i128->hi];
                break;
            case XdrSCValType::SCV_U256:
                $lines += [$prefix.'type' => 'SCV_U256'];
                $lines += [$prefix.'u256.hi_hi' => $val->u256->hiHi];
                $lines += [$prefix.'u256.hi_lo' => $val->u256->hiLo];
                $lines += [$prefix.'u256.lo_hi' => $val->u256->loHi];
                $lines += [$prefix.'u256.lo_lo' => $val->u256->loLo];
                break;
            case XdrSCValType::SCV_I256:
                $lines += [$prefix.'type' => 'SCV_I256'];
                $lines += [$prefix.'i256.hi_hi' => $val->i256->hiHi];
                $lines += [$prefix.'i256.hi_lo' => $val->i256->hiLo];
                $lines += [$prefix.'i256.lo_hi' => $val->i256->loHi];
                $lines += [$prefix.'i256.lo_lo' => $val->i256->loLo];
                break;
            case XdrSCValType::SCV_BYTES:
                $lines += [$prefix.'type' => 'SCV_BYTES'];
                $lines += [$prefix.'bytes' => bin2hex($val->bytes->getValue())];
                break;
            case XdrSCValType::SCV_STRING:
                $lines += [$prefix.'type' => 'SCV_STRING'];
                $lines += [$prefix.'str' => $val->str];
                break;
            case XdrSCValType::SCV_SYMBOL:
                $lines += [$prefix.'type' => 'SCV_SYMBOL'];
                $lines += [$prefix.'sym' => $val->sym];
                break;
            case XdrSCValType::SCV_VEC:
                $lines += [$prefix.'type' => 'SCV_VEC'];
                if ($val->vec == null) {
                    $lines += [$prefix . 'vec._present' => 'false'];
                    break;
                }
                $lines += [$prefix . 'vec._present' => 'true'];
                $lines += [$prefix.'vec.len' => strval(count($val->vec))];
                $index = 0;
                foreach ($val->vec as $vecVal) {
                    $lines = array_merge($lines, self::getSCValTx($prefix.'vec['.$index.'].', $vecVal));
                    $index++;
                }
                break;
            case XdrSCValType::SCV_MAP:
                $lines += [$prefix.'type' => 'SCV_MAP'];
                if ($val->map == null) {
                    $lines += [$prefix . 'map._present' => 'false'];
                    break;
                }
                $lines += [$prefix . 'map._present' => 'true'];
                $lines += [$prefix.'map.len' => strval(count($val->map))];
                $index = 0;
                foreach ($val->map as $mapEntry) {
                    $entryKey = $mapEntry->key;
                    $entryVal = $mapEntry->val;
                    $lines = array_merge($lines, self::getSCValTx($prefix.'map['.$index.'].key.', $entryKey));
                    $lines = array_merge($lines, self::getSCValTx($prefix.'map['.$index.'].val.', $entryVal));
                    $index++;
                }
                break;
            case XdrSCValType::SCV_ADDRESS:
                $lines += [$prefix.'type' => 'SCV_ADDRESS'];
                $lines = array_merge($lines, self::getSCAddressTx($prefix.'address.', $val->address));
                break;
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE:
                $lines += [$prefix.'type' => 'SCV_LEDGER_KEY_CONTRACT_INSTANCE'];
                break;
            case XdrSCValType::SCV_LEDGER_KEY_NONCE:
                $lines += [$prefix.'type' => 'SCV_LEDGER_KEY_NONCE'];
                $lines += [$prefix.'nonce_key.nonce' => strval($val->nonceKey->nonce)];
                break;
            case XdrSCValType::SCV_CONTRACT_INSTANCE:
                $lines += [$prefix.'type' => 'SCV_CONTRACT_INSTANCE'];
                $lines = array_merge($lines, self::getContractExecutableTx($prefix.'executable.', $val->instance->executable));
                if ($val->instance->storage !== null) {
                    $lines += [$prefix.'storage._present' => 'true'];
                    $lines += [$prefix.'storage.len' => strval(count($val->instance->storage))];
                    $index = 0;
                    foreach ($val->instance->storage as $entry) {
                        $lines = array_merge($lines, self::getSCValTx($prefix.'storage['.$index.'].key.', $entry->key));
                        $lines = array_merge($lines, self::getSCValTx($prefix.'storage['.$index.'].val.', $entry->val));
                        $index++;
                    }
                } else {
                    $lines += [$prefix.'storage._present' => 'false'];
                }
                break;
        }
        return $lines;
    }

    private static function getContractIDPreimageTx(string $prefix, XdrContractIDPreimage $val) : array {
        $type = $val->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS:
                $lines += [$prefix . 'type' => 'CONTRACT_ID_PREIMAGE_FROM_ADDRESS'];
                $lines = array_merge($lines, self::getSCAddressTx($prefix.'fromAddress.address.', $val->address));
                $lines += [$prefix . 'fromAddress.salt' => bin2hex($val->salt)];
                break;
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET:
                $lines += [$prefix . 'type' => 'CONTRACT_ID_PREIMAGE_FROM_ASSET'];
                $lines += [$prefix . 'fromAsset' => self::encodeAsset(Asset::fromXdr($val->asset))];
                break;
        }
        return $lines;
    }
    private static function getCreateContractArgsTx(string $prefix, XdrCreateContractArgs $args) : array {
        $lines = array();
        $lines = array_merge($lines, self::getContractIDPreimageTx($prefix.'contractIDPreimage.', $args->contractIDPreimage));
        return array_merge($lines, self::getContractExecutableTx($prefix.'executable.', $args->executable));
    }

    private static function getCreateContractV2ArgsTx(string $prefix, XdrCreateContractArgsV2 $args) : array {
        $lines = array();
        $lines = array_merge($lines, self::getContractIDPreimageTx($prefix.'contractIDPreimage.', $args->contractIDPreimage));
        $lines = array_merge($lines, self::getContractExecutableTx($prefix.'executable.', $args->executable));
        $lines += [$prefix . 'constructorArgs.len' => strval(count($args->constructorArgs))];
        $index = 0;
        foreach ($args->constructorArgs as $val) {
            $lines = array_merge($lines, self::getSCValTx($prefix.'constructorArgs['.$index.'].', $val));
            $index++;
        }
        return $lines;
    }

    private static function getInvokeContractArgsTx(string $prefix, XdrInvokeContractArgs $args) : array {
        $lines = array();
        $lines = array_merge($lines, self::getSCAddressTx($prefix.'contractAddress.', $args->contractAddress));
        $lines += [$prefix . 'functionName' => $args->functionName];
        $lines += [$prefix . 'args.len' => strval(count($args->args))];
        $index = 0;
        foreach ($args->args as $val) {
            $lines = array_merge($lines, self::getSCValTx($prefix.'args['.$index.'].', $val));
            $index++;
        }
        return $lines;
    }

    private static function getSorobanAddressCredentialsTx(string $prefix, XdrSorobanAddressCredentials $credentials) : array {
        $lines = array();
        $lines = array_merge($lines, self::getSCAddressTx($prefix.'address.', $credentials->address));
        $lines += [$prefix . 'nonce' => strval($credentials->nonce)];
        $lines += [$prefix . 'signatureExpirationLedger' => strval($credentials->signatureExpirationLedger)];
        return array_merge($lines, self::getSCValTx($prefix.'signature.', $credentials->signature));
    }
    private static function getSorobanCredentialsTx(string $prefix, XdrSorobanCredentials $credentials) : array {
        $type = $credentials->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT:
                $lines += [$prefix . 'type' => 'SOROBAN_CREDENTIALS_SOURCE_ACCOUNT'];
                break;
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                $lines += [$prefix . 'type' => 'SOROBAN_CREDENTIALS_ADDRESS'];
                $lines = array_merge($lines, self::getSorobanAddressCredentialsTx($prefix.'address.', $credentials->address));
                break;
        }
        return $lines;
    }

    private static function getSorobanAuthorizedFunctionTx(string $prefix, XdrSorobanAuthorizedFunction $function) : array {
        $type = $function->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN:
                $lines += [$prefix . 'type' => 'SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN'];
                $lines = array_merge($lines, self::getInvokeContractArgsTx($prefix.'contractFn.', $function->contractFn));
                break;
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN:
                $lines += [$prefix . 'type' => 'SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN'];
                $lines = array_merge($lines, self::getCreateContractArgsTx($prefix.'createContractHostFn.', $function->createContractHostFn));
                break;
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN:
                $lines += [$prefix . 'type' => 'SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN'];
                $lines = array_merge($lines, self::getCreateContractV2ArgsTx($prefix.'createContractV2HostFn.', $function->createContractV2HostFn));
                break;
        }
        return $lines;
    }

    private static function getSorobanAuthorizedInvocationTx(string $prefix, XdrSorobanAuthorizedInvocation $invocation) : array {
        $lines = array();
        $lines = array_merge($lines, self::getSorobanAuthorizedFunctionTx($prefix.'function.', $invocation->function));

        $lines += [$prefix . 'subInvocations.len' => strval(count($invocation->subInvocations))];
        $index = 0;
        foreach ($invocation->subInvocations as $val) {
            $lines = array_merge($lines, self::getSorobanAuthorizedInvocationTx($prefix.'subInvocations['.$index.'].', $val));
            $index++;
        }
        return $lines;

    }
    private static function getSorobanAuthorizationEntryTx(string $prefix, XdrSorobanAuthorizationEntry $entry) : array {
        $lines = array();
        $lines = array_merge($lines, self::getSorobanCredentialsTx($prefix.'credentials.', $entry->credentials));
        return array_merge($lines, self::getSorobanAuthorizedInvocationTx($prefix.'rootInvocation.', $entry->rootInvocation));
    }

    private static function getLedgerKeyTx(string $prefix, XdrLedgerKey $ledgerKey) : array {
        $lines = array();
        $type = $ledgerKey->getType()->getValue();
        if ($type === XdrLedgerEntryType::ACCOUNT) {
            $lines += [$prefix.'type' => 'ACCOUNT'];
            $lines += [$prefix.'account.accountID' => $ledgerKey->getAccount()->getAccountID()->getAccountId()];
        }
        else if ($type === XdrLedgerEntryType::TRUSTLINE) {
            $lines += [$prefix.'type' => 'TRUSTLINE'];
            $lines += [$prefix.'trustLine.accountID' => $ledgerKey->getTrustline()->getAccountID()->getAccountId()];
            $lines += [$prefix.'trustLine.asset' => self::encodeAsset(Asset::fromXdr($ledgerKey->getTrustline()->getAsset()))];
        }
        else if ($type === XdrLedgerEntryType::OFFER) {
            $lines += [$prefix.'type' => 'OFFER'];
            $lines += [$prefix.'offer.sellerID' => $ledgerKey->getOffer()->getSellerID()->getAccountId()];
            $lines += [$prefix.'offer.offerID' => $ledgerKey->getOffer()->getOfferID()];
        }
        else if ($type === XdrLedgerEntryType::DATA) {
            $lines += [$prefix.'type' => 'DATA'];
            $lines += [$prefix.'data.accountID' => $ledgerKey->getData()->getAccountID()->getAccountId()];
            $lines += [$prefix.'data.dataName' => '"' . $ledgerKey->getData()->getDataName() . '"'];
        }
        else if ($type === XdrLedgerEntryType::CLAIMABLE_BALANCE) {
            $lines += [$prefix.'type' => 'CLAIMABLE_BALANCE'];
            $lines += [$prefix.'claimableBalance.balanceID.type' => 'CLAIMABLE_BALANCE_ID_TYPE_V0'];
            $lines += [$prefix.'claimableBalance.balanceID.v0' => $ledgerKey->getBalanceID()->getHash()];
        } else if ($type === XdrLedgerEntryType::LIQUIDITY_POOL) {
            $lines += [$prefix.'type' => 'LIQUIDITY_POOL'];
            $lines += [$prefix.'liquidityPool.liquidityPoolID' => $ledgerKey->getLiquidityPoolID()];
        } else if ($type === XdrLedgerEntryType::CONTRACT_DATA) {
            $lines += [$prefix.'type' => 'CONTRACT_DATA'];
            $lines = array_merge($lines, self::getSCAddressTx($prefix .'contractData.contract.', $ledgerKey->contractData->contract));
            $lines = array_merge($lines, self::getSCValTx($prefix .'contractData.key.', $ledgerKey->contractData->key));
            if ($ledgerKey->contractData->durability->value === XdrContractDataDurability::PERSISTENT) {
                $lines += [$prefix.'contractData.durability' => 'PERSISTENT'];
            } else if ($ledgerKey->contractData->durability->value === XdrContractDataDurability::TEMPORARY) {
                $lines += [$prefix.'contractData.durability' => 'TEMPORARY'];
            }
        } else if ($type === XdrLedgerEntryType::CONTRACT_CODE) {
            $lines += [$prefix.'type' => 'CONTRACT_CODE'];
            $lines += [$prefix.'contractCode.hash' => bin2hex($ledgerKey->contractCode->getHash())];
        } else if ($type === XdrLedgerEntryType::CONFIG_SETTING) {
            $lines += [$prefix.'type' => 'CONFIG_SETTING'];
            $lines += [$prefix.'configSetting.configSettingID' => strval($ledgerKey->configSetting->value)];
        } else if ($type === XdrLedgerEntryType::TTL) {
            $lines += [$prefix.'type' => 'TTL'];
            $lines += [$prefix.'ttl.keyHash' => bin2hex($ledgerKey->ttl->keyHash)];
        }
        return $lines;
    }

    private static function getFootprintTx(string $prefix, XdrLedgerFootprint $footprint) : array {
        $lines = array();
        $lines += [$prefix . 'readOnly.len' => strval(count($footprint->readOnly))];
        $index = 0;
        foreach ($footprint->readOnly as $val) {
            $lines = array_merge($lines, self::getLedgerKeyTx($prefix.'readOnly['.$index.'].', $val));
            $index++;
        }
        $lines += [$prefix . 'readWrite.len' => strval(count($footprint->readWrite))];
        $index = 0;
        foreach ($footprint->readWrite as $val) {
            $lines = array_merge($lines, self::getLedgerKeyTx($prefix.'readWrite['.$index.'].', $val));
            $index++;
        }
        return $lines;
    }
    private static function getSorobanResourcesTx(string $prefix, XdrSorobanResources $resources) : array {
        $lines = array();
        $lines = array_merge($lines, self::getFootprintTx($prefix.'footprint.', $resources->footprint));
        $lines += [$prefix . 'instructions' => strval($resources->instructions)];
        $lines += [$prefix . 'diskReadBytes' => strval($resources->diskReadBytes)];
        $lines += [$prefix . 'writeBytes' => strval($resources->writeBytes)];
        return $lines;
    }
    private static function getSorobanTransactionDataTx(string $prefix, XdrSorobanTransactionData $data) : array {
        $lines = array();
        if ($data->ext->discriminant === 1) {
            $lines += [$prefix . 'ext.v' => '1'];
            $lines += [$prefix . 'ext.archivedSorobanEntries.len' => strval(count($data->ext->resourceExt->archivedSorobanEntries))];
            $index = 0;
            foreach ($data->ext->resourceExt->archivedSorobanEntries as $val) {
                $lines += [$prefix.'ext.archivedSorobanEntries['.$index.'].' => strval($val)];
                $index++;
            }
        } else {
            $lines += [$prefix . 'ext.v' => '0'];
        }

        $lines = array_merge($lines, self::getSorobanResourcesTx($prefix.'resources.', $data->resources));
        $lines += [$prefix . 'resourceFee' => strval($data->resourceFee)];
        return $lines;
    }

    private static function getPredicateTx(string $prefix, XdrClaimPredicate $predicate) : array {
        $type = $predicate->getType()->getValue();
        $lines = array();
        switch ($type) {
            case XdrClaimPredicateType::UNCONDITIONAL:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_UNCONDITIONAL'];
                return $lines;
            case XdrClaimPredicateType::AND:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_AND'];
                $andPredicates = $predicate->getAndPredicates();
                $count = count($andPredicates);
                $lines += [$prefix.'andPredicates.len' => strval($count)];
                for($i = 0; $i < $count; $i++) {
                    $px = $prefix.'andPredicates['.strval($i).'].';
                    $lines += self::getPredicateTx($px, $andPredicates[$i]);
                }
                return $lines;
            case XdrClaimPredicateType::OR:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_OR'];
                $orPredicates = $predicate->getOrPredicates();
                $count = count($orPredicates);
                $lines += [$prefix.'orPredicates.len' => strval($count)];
                for ($i = 0; $i < $count; $i++) {
                    $px = $prefix.'orPredicates['.strval($i).'].';
                    $lines += self::getPredicateTx($px, $orPredicates[$i]);
                }
                return $lines;
            case XdrClaimPredicateType::NOT:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_NOT'];
                $notPredicate = $predicate->getNotPredicate();
                if ($notPredicate != null) {
                    $lines += [$prefix.'notPredicate._present' => 'true'];
                    $px = $prefix . 'notPredicate.';
                    $lines += self::getPredicateTx($px, $predicate->getNotPredicate());
                }
                else {
                    $lines += [$prefix.'notPredicate._present' => 'false'];
                }
                return $lines;
            case XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME'];
                $lines += [$prefix.'absBefore' => strval($predicate->getAbsBefore())];
                return $lines;
            case XdrClaimPredicateType::BEFORE_RELATIVE_TIME:
                $lines += [$prefix.'type' => 'CLAIM_PREDICATE_BEFORE_RELATIVE_TIME'];
                $lines += [$prefix.'relBefore' => strval($predicate->getRelBefore())];
                return $lines;
            default:
                return $lines;
        }
    }

    private static function encodeAsset(Asset $asset) : string {
        if ($asset instanceof AssetTypeNative) {
            return "XLM";
        } else if ($asset instanceof AssetTypeCreditAlphanum) {
            return $asset->getCode() . ":" . $asset->getIssuer();
        }
        return 'UNKNOWN';
    }

    private static function toAmount(string $value): string {
        $amount = StellarAmount::fromString($value);
        return $amount->getStroopsAsString();
    }

    private static function fromAmount(string $value): string {
        $val = new BigInteger($value);
        $amount = new StellarAmount($val);
        return $amount->getDecimalValueAsString();
    }

    private static function txRepOpTypeUpperCase(AbstractOperation $operation) : string {
        $type = $operation->toXdr()->getBody()->getType()->getValue();

        return match ($type) {
            XdrOperationType::CREATE_ACCOUNT => 'CREATE_ACCOUNT',
            XdrOperationType::PAYMENT => 'PAYMENT',
            XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE => 'PATH_PAYMENT_STRICT_RECEIVE',
            XdrOperationType::MANAGE_SELL_OFFER => 'MANAGE_SELL_OFFER',
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER => 'CREATE_PASSIVE_SELL_OFFER',
            XdrOperationType::SET_OPTIONS => 'SET_OPTIONS',
            XdrOperationType::CHANGE_TRUST => 'CHANGE_TRUST',
            XdrOperationType::ALLOW_TRUST => 'ALLOW_TRUST',
            XdrOperationType::ACCOUNT_MERGE => 'ACCOUNT_MERGE',
            XdrOperationType::INFLATION => 'INFLATION',
            XdrOperationType::MANAGE_DATA => 'MANAGE_DATA',
            XdrOperationType::BUMP_SEQUENCE => 'BUMP_SEQUENCE',
            XdrOperationType::MANAGE_BUY_OFFER => 'MANAGE_BUY_OFFER',
            XdrOperationType::PATH_PAYMENT_STRICT_SEND => 'PATH_PAYMENT_STRICT_SEND',
            XdrOperationType::CREATE_CLAIMABLE_BALANCE => 'CREATE_CLAIMABLE_BALANCE',
            XdrOperationType::CLAIM_CLAIMABLE_BALANCE => 'CLAIM_CLAIMABLE_BALANCE',
            XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES => 'BEGIN_SPONSORING_FUTURE_RESERVES',
            XdrOperationType::END_SPONSORING_FUTURE_RESERVES => 'END_SPONSORING_FUTURE_RESERVES',
            XdrOperationType::REVOKE_SPONSORSHIP => 'REVOKE_SPONSORSHIP',
            XdrOperationType::CLAWBACK => 'CLAWBACK',
            XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE => 'CLAWBACK_CLAIMABLE_BALANCE',
            XdrOperationType::SET_TRUST_LINE_FLAGS => 'SET_TRUST_LINE_FLAGS',
            XdrOperationType::LIQUIDITY_POOL_DEPOSIT => 'LIQUIDITY_POOL_DEPOSIT',
            XdrOperationType::LIQUIDITY_POOL_WITHDRAW => 'LIQUIDITY_POOL_WITHDRAW',
            XdrOperationType::INVOKE_HOST_FUNCTION => 'INVOKE_HOST_FUNCTION',
            XdrOperationType::EXTEND_FOOTPRINT_TTL => 'EXTEND_FOOTPRINT_TTL',
            XdrOperationType::RESTORE_FOOTPRINT => 'RESTORE_FOOTPRINT',
            default => strval($type)
        };
    }

    private static function txRepOpType(AbstractOperation $operation) : string {
        $type = $operation->toXdr()->getBody()->getType()->getValue();

        return match ($type) {
            XdrOperationType::CREATE_ACCOUNT => 'createAccountOp',
            XdrOperationType::PAYMENT => 'paymentOp',
            XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE => 'pathPaymentStrictReceiveOp',
            XdrOperationType::MANAGE_SELL_OFFER => 'manageSellOfferOp',
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER => 'createPassiveSellOfferOp',
            XdrOperationType::SET_OPTIONS => 'setOptionsOp',
            XdrOperationType::CHANGE_TRUST => 'changeTrustOp',
            XdrOperationType::ALLOW_TRUST => 'allowTrustOp',
            XdrOperationType::ACCOUNT_MERGE => 'accountMergeOp',
            XdrOperationType::INFLATION => 'inflationOp',
            XdrOperationType::MANAGE_DATA => 'manageDataOp',
            XdrOperationType::BUMP_SEQUENCE => 'bumpSequenceOp',
            XdrOperationType::MANAGE_BUY_OFFER => 'manageBuyOfferOp',
            XdrOperationType::PATH_PAYMENT_STRICT_SEND => 'pathPaymentStrictSendOp',
            XdrOperationType::CREATE_CLAIMABLE_BALANCE => 'createClaimableBalanceOp',
            XdrOperationType::CLAIM_CLAIMABLE_BALANCE => 'claimClaimableBalanceOp',
            XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES => 'beginSponsoringFutureReservesOp',
            XdrOperationType::END_SPONSORING_FUTURE_RESERVES => 'endSponsoringFutureReservesOp',
            XdrOperationType::REVOKE_SPONSORSHIP => 'revokeSponsorshipOp',
            XdrOperationType::CLAWBACK => 'clawbackOp',
            XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE => 'clawbackClaimableBalanceOp',
            XdrOperationType::SET_TRUST_LINE_FLAGS => 'setTrustLineFlagsOp',
            XdrOperationType::LIQUIDITY_POOL_DEPOSIT => 'liquidityPoolDepositOp',
            XdrOperationType::LIQUIDITY_POOL_WITHDRAW => 'liquidityPoolWithdrawOp',
            XdrOperationType::INVOKE_HOST_FUNCTION => 'invokeHostFunctionOp',
            XdrOperationType::EXTEND_FOOTPRINT_TTL => 'extendFootprintTTLOp',
            XdrOperationType::RESTORE_FOOTPRINT => 'restoreFootprintOp',
            default => strval($type)
        };
    }
}