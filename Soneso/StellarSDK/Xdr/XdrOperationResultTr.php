<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationResultTr
{
    private XdrOperationType $type;
    private ?XdrCreateAccountResult $createAccountResult = null;
    private ?XdrPaymentResult $paymentResult = null;
    private ?XdrChangeTrustResult $changeTrustResult = null;
    private ?XdrBumpSequenceResult $bumpSequenceResult = null;
    private ?XdrAccountMergeResult $accountMergeResult = null;
    private ?XdrManageOfferResult $manageOfferResult = null;
    private ?XdrManageOfferResult $createPassiveSellOfferResult = null;
    private ?XdrPathPaymentStrictReceiveResult $pathPaymentStrictReceiveResult = null;
    private ?XdrPathPaymentStrictSendResult $pathPaymentStrictSendResult = null;
    private ?XdrSetOptionsResult $setOptionsResult = null;
    private ?XdrAllowTrustResult $allowTrustResult = null;
    private ?XdrInflationResult $inflationResult = null;
    private ?XdrManageDataResult $manageDataResult = null;
    private ?XdrCreateClaimableBalanceResult $createClaimableBalanceResult = null;
    private ?XdrClaimClaimableBalanceResult $claimClaimableBalanceResult = null;
    private ?XdrBeginSponsoringFutureReservesResult $beginSponsoringFutureReservesResult = null;
    private ?XdrEndSponsoringFutureReservesResult $endSponsoringFutureReservesResult = null;
    private ?XdrRevokeSponsorshipResult $revokeSponsorshipResult = null;
    private ?XdrClawbackResult $clawbackResult = null;
    private ?XdrClawbackClaimableBalanceResult $clawbackClaimableBalanceResult = null;
    private ?XdrSetTrustLineFlagsResult $setTrustLineFlagsResult = null;
    private ?XdrLiquidityPoolDepositResult $liquidityPoolDepositResult= null;
    private ?XdrLiquidityPoolWithdrawResult $liquidityPoolWithdrawResult = null;
    private ?XdrInvokeHostFunctionResult $invokeHostFunctionResult = null;
    private ?XdrBumpFootprintExpirationResult $bumpFootprintExpirationResult= null;
    private ?XdrRestoreFootprintResult $restoreFootprintResult = null;
    /**
     * @return XdrOperationType
     */
    public function getType(): XdrOperationType
    {
        return $this->type;
    }

    /**
     * @return XdrCreateAccountResult|null
     */
    public function getCreateAccountResult(): ?XdrCreateAccountResult
    {
        return $this->createAccountResult;
    }

    /**
     * @return XdrPaymentResult|null
     */
    public function getPaymentResult(): ?XdrPaymentResult
    {
        return $this->paymentResult;
    }

    /**
     * @return XdrChangeTrustResult|null
     */
    public function getChangeTrustResult(): ?XdrChangeTrustResult
    {
        return $this->changeTrustResult;
    }

    /**
     * @return XdrBumpSequenceResult|null
     */
    public function getBumpSequenceResult(): ?XdrBumpSequenceResult
    {
        return $this->bumpSequenceResult;
    }

    /**
     * @return XdrAccountMergeResult|null
     */
    public function getAccountMergeResult(): ?XdrAccountMergeResult
    {
        return $this->accountMergeResult;
    }

    /**
     * @return XdrManageOfferResult|null
     */
    public function getManageOfferResult(): ?XdrManageOfferResult
    {
        return $this->manageOfferResult;
    }

    /**
     * @return XdrPathPaymentStrictReceiveResult|null
     */
    public function getPathPaymentStrictReceiveResult(): ?XdrPathPaymentStrictReceiveResult
    {
        return $this->pathPaymentStrictReceiveResult;
    }

    /**
     * @return XdrPathPaymentStrictSendResult|null
     */
    public function getPathPaymentStrictSendResult(): ?XdrPathPaymentStrictSendResult
    {
        return $this->pathPaymentStrictSendResult;
    }

    /**
     * @return XdrManageOfferResult|null
     */
    public function getCreatePassiveSellOfferResult(): ?XdrManageOfferResult
    {
        return $this->createPassiveSellOfferResult;
    }

    /**
     * @return XdrSetOptionsResult|null
     */
    public function getSetOptionsResult(): ?XdrSetOptionsResult
    {
        return $this->setOptionsResult;
    }

    /**
     * @return XdrAllowTrustResult|null
     */
    public function getAllowTrustResult(): ?XdrAllowTrustResult
    {
        return $this->allowTrustResult;
    }

    /**
     * @return XdrInflationResult|null
     */
    public function getInflationResult(): ?XdrInflationResult
    {
        return $this->inflationResult;
    }

    /**
     * @return XdrManageDataResult|null
     */
    public function getManageDataResult(): ?XdrManageDataResult
    {
        return $this->manageDataResult;
    }

    /**
     * @return XdrCreateClaimableBalanceResult|null
     */
    public function getCreateClaimableBalanceResult(): ?XdrCreateClaimableBalanceResult
    {
        return $this->createClaimableBalanceResult;
    }

    /**
     * @return XdrClaimClaimableBalanceResult|null
     */
    public function getClaimClaimableBalanceResult(): ?XdrClaimClaimableBalanceResult
    {
        return $this->claimClaimableBalanceResult;
    }

    /**
     * @return XdrBeginSponsoringFutureReservesResult|null
     */
    public function getBeginSponsoringFutureReservesResult(): ?XdrBeginSponsoringFutureReservesResult
    {
        return $this->beginSponsoringFutureReservesResult;
    }

    /**
     * @return XdrEndSponsoringFutureReservesResult|null
     */
    public function getEndSponsoringFutureReservesResult(): ?XdrEndSponsoringFutureReservesResult
    {
        return $this->endSponsoringFutureReservesResult;
    }

    /**
     * @return XdrRevokeSponsorshipResult|null
     */
    public function getRevokeSponsorshipResult(): ?XdrRevokeSponsorshipResult
    {
        return $this->revokeSponsorshipResult;
    }

    /**
     * @return XdrClawbackResult|null
     */
    public function getClawbackResult(): ?XdrClawbackResult
    {
        return $this->clawbackResult;
    }

    /**
     * @return XdrClawbackClaimableBalanceResult|null
     */
    public function getClawbackClaimableBalanceResult(): ?XdrClawbackClaimableBalanceResult
    {
        return $this->clawbackClaimableBalanceResult;
    }

    /**
     * @return XdrSetTrustLineFlagsResult|null
     */
    public function getSetTrustLineFlagsResult(): ?XdrSetTrustLineFlagsResult
    {
        return $this->setTrustLineFlagsResult;
    }

    /**
     * @return XdrLiquidityPoolDepositResult|null
     */
    public function getLiquidityPoolDepositResult(): ?XdrLiquidityPoolDepositResult
    {
        return $this->liquidityPoolDepositResult;
    }

    /**
     * @return XdrLiquidityPoolWithdrawResult|null
     */
    public function getLiquidityPoolWithdrawResult(): ?XdrLiquidityPoolWithdrawResult
    {
        return $this->liquidityPoolWithdrawResult;
    }

    /**
     * @return XdrInvokeHostFunctionResult|null
     */
    public function getInvokeHostFunctionResult(): ?XdrInvokeHostFunctionResult
    {
        return $this->invokeHostFunctionResult;
    }

    /**
     * @param XdrInvokeHostFunctionResult|null $invokeHostFunctionResult
     */
    public function setInvokeHostFunctionResult(?XdrInvokeHostFunctionResult $invokeHostFunctionResult): void
    {
        $this->invokeHostFunctionResult = $invokeHostFunctionResult;
    }

    /**
     * @return XdrBumpFootprintExpirationResult|null
     */
    public function getBumpFootprintExpirationResult(): ?XdrBumpFootprintExpirationResult
    {
        return $this->bumpFootprintExpirationResult;
    }

    /**
     * @param XdrBumpFootprintExpirationResult|null $bumpFootprintExpirationResult
     */
    public function setBumpFootprintExpirationResult(?XdrBumpFootprintExpirationResult $bumpFootprintExpirationResult): void
    {
        $this->bumpFootprintExpirationResult = $bumpFootprintExpirationResult;
    }

    /**
     * @return XdrRestoreFootprintResult|null
     */
    public function getRestoreFootprintResult(): ?XdrRestoreFootprintResult
    {
        return $this->restoreFootprintResult;
    }

    /**
     * @param XdrRestoreFootprintResult|null $restoreFootprintResult
     */
    public function setRestoreFootprintResult(?XdrRestoreFootprintResult $restoreFootprintResult): void
    {
        $this->restoreFootprintResult = $restoreFootprintResult;
    }


    public function encode(): string {
        $bytes = $this->type->encode();
        if ($this->createAccountResult != null) {
            $bytes .= $this->createAccountResult->encode();
        } else if ($this->paymentResult != null) {
            $bytes .= $this->paymentResult->encode();
        } else if ($this->pathPaymentStrictReceiveResult != null) {
            $bytes .= $this->pathPaymentStrictReceiveResult->encode();
        } else if ($this->pathPaymentStrictSendResult != null) {
            $bytes .= $this->pathPaymentStrictSendResult->encode();
        } else if ($this->createPassiveSellOfferResult != null) {
            $bytes .= $this->createPassiveSellOfferResult->encode();
        } else if ($this->setOptionsResult != null) {
            $bytes .= $this->setOptionsResult->encode();
        } else if ($this->changeTrustResult != null) {
            $bytes .= $this->changeTrustResult->encode();
        } else if ($this->allowTrustResult != null) {
            $bytes .= $this->allowTrustResult->encode();
        } else if ($this->bumpSequenceResult != null) {
            $bytes .= $this->bumpSequenceResult->encode();
        } else if ($this->accountMergeResult != null) {
            $bytes .= $this->accountMergeResult->encode();
        } else if ($this->inflationResult != null) {
            $bytes .= $this->inflationResult->encode();
        } else if ($this->manageDataResult != null) {
            $bytes .= $this->manageDataResult->encode();
        } else if ($this->manageOfferResult != null) {
            $bytes .= $this->manageOfferResult->encode();
        } else if ($this->createClaimableBalanceResult != null) {
            $bytes .= $this->createClaimableBalanceResult->encode();
        } else if ($this->claimClaimableBalanceResult != null) {
            $bytes .= $this->claimClaimableBalanceResult->encode();
        } else if ($this->beginSponsoringFutureReservesResult != null) {
            $bytes .= $this->beginSponsoringFutureReservesResult->encode();
        } else if ($this->endSponsoringFutureReservesResult != null) {
            $bytes .= $this->endSponsoringFutureReservesResult->encode();
        } else if ($this->revokeSponsorshipResult != null) {
            $bytes .= $this->revokeSponsorshipResult->encode();
        } else if ($this->clawbackResult != null) {
            $bytes .= $this->clawbackResult->encode();
        } else if ($this->clawbackClaimableBalanceResult != null) {
            $bytes .= $this->clawbackClaimableBalanceResult->encode();
        } else if ($this->setTrustLineFlagsResult != null) {
            $bytes .= $this->setTrustLineFlagsResult->encode();
        } else if ($this->liquidityPoolDepositResult != null) {
            $bytes .= $this->liquidityPoolDepositResult->encode();
        } else if ($this->liquidityPoolWithdrawResult != null) {
            $bytes .= $this->liquidityPoolWithdrawResult->encode();
        } else if ($this->invokeHostFunctionResult != null) {
            $bytes .= $this->invokeHostFunctionResult->encode();
        } else if ($this->bumpFootprintExpirationResult != null) {
            $bytes .= $this->bumpFootprintExpirationResult->encode();
        } else if ($this->restoreFootprintResult != null) {
            $bytes .= $this->restoreFootprintResult->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrOperationResultTr {
        $type = XdrOperationType::decode($xdr);
        $result = new XdrOperationResultTr();
        $result->type = $type;
        switch ($type->getValue()) {
            case XdrOperationType::CREATE_ACCOUNT:
                $result->createAccountResult = XdrCreateAccountResult::decode($xdr);
                break;
            case XdrOperationType::PAYMENT:
                $result->paymentResult = XdrPaymentResult::decode($xdr);
                break;
            case XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE:
                $result->pathPaymentStrictReceiveResult = XdrPathPaymentStrictReceiveResult::decode($xdr);
                break;
            case XdrOperationType::PATH_PAYMENT_STRICT_SEND:
                $result->pathPaymentStrictSendResult = XdrPathPaymentStrictSendResult::decode($xdr);
                break;
            case XdrOperationType::CREATE_PASSIVE_SELL_OFFER:
                $result->createPassiveSellOfferResult = XdrManageOfferResult::decode($xdr);
                break;
            case XdrOperationType::SET_OPTIONS:
                $result->setOptionsResult = XdrSetOptionsResult::decode($xdr);
                break;
            case XdrOperationType::CHANGE_TRUST:
                $result->changeTrustResult = XdrChangeTrustResult::decode($xdr);
                break;
            case XdrOperationType::ALLOW_TRUST:
                $result->allowTrustResult = XdrAllowTrustResult::decode($xdr);
                break;
            case XdrOperationType::BUMP_SEQUENCE:
                $result->bumpSequenceResult = XdrBumpSequenceResult::decode($xdr);
                break;
            case XdrOperationType::ACCOUNT_MERGE:
                $result->accountMergeResult = XdrAccountMergeResult::decode($xdr);
                break;
            case XdrOperationType::INFLATION:
                $result->inflationResult = XdrInflationResult::decode($xdr);
                break;
            case XdrOperationType::MANAGE_DATA:
                $result->manageDataResult = XdrManageDataResult::decode($xdr);
                break;
            case XdrOperationType::MANAGE_BUY_OFFER:
            case XdrOperationType::MANAGE_SELL_OFFER:
                $result->manageOfferResult = XdrManageOfferResult::decode($xdr);
                break;
            case XdrOperationType::CREATE_CLAIMABLE_BALANCE:
                $result->createClaimableBalanceResult = XdrCreateClaimableBalanceResult::decode($xdr);
                break;
            case XdrOperationType::CLAIM_CLAIMABLE_BALANCE:
                $result->claimClaimableBalanceResult = XdrClaimClaimableBalanceResult::decode($xdr);
                break;
            case XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES:
                $result->beginSponsoringFutureReservesResult = XdrBeginSponsoringFutureReservesResult::decode($xdr);
                break;
            case XdrOperationType::END_SPONSORING_FUTURE_RESERVES:
                $result->endSponsoringFutureReservesResult = XdrEndSponsoringFutureReservesResult::decode($xdr);
                break;
            case XdrOperationType::REVOKE_SPONSORSHIP:
                $result->revokeSponsorshipResult = XdrRevokeSponsorshipResult::decode($xdr);
                break;
            case XdrOperationType::CLAWBACK:
                $result->clawbackResult = XdrClawbackResult::decode($xdr);
                break;
            case XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE:
                $result->clawbackClaimableBalanceResult = XdrClawbackClaimableBalanceResult::decode($xdr);
                break;
            case XdrOperationType::SET_TRUST_LINE_FLAGS:
                $result->setTrustLineFlagsResult = XdrSetTrustLineFlagsResult::decode($xdr);
                break;
            case XdrOperationType::LIQUIDITY_POOL_DEPOSIT:
                $result->liquidityPoolDepositResult = XdrLiquidityPoolDepositResult::decode($xdr);
                break;
            case XdrOperationType::LIQUIDITY_POOL_WITHDRAW:
                $result->liquidityPoolWithdrawResult = XdrLiquidityPoolWithdrawResult::decode($xdr);
                break;
            case XdrOperationType::INVOKE_HOST_FUNCTION:
                $result->invokeHostFunctionResult = XdrInvokeHostFunctionResult::decode($xdr);
                break;
            case XdrOperationType::BUMP_FOOTPRINT_EXPIRATION:
                $result->bumpFootprintExpirationResult = XdrBumpFootprintExpirationResult::decode($xdr);
                break;
            case XdrOperationType::RESTORE_FOOTPRINT:
                $result->restoreFootprintResult = XdrRestoreFootprintResult::decode($xdr);
                break;
        }
        return $result;
    }
}