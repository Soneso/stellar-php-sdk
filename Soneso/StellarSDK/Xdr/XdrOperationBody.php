<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationBody
{
    private XdrOperationType $type;
    private ?XdrCreateAccountOperation $createAccountOp = null;
    private ?XdrPaymentOperation $paymentOp = null;
    private ?XdrChangeTrustOperation $changeTrustOp = null;
    private ?XdrBumpSequenceOperation $bumpSequenceOp = null;
    private ?XdrAccountMergeOperation $accountMergeOp = null;
    private ?XdrManageBuyOfferOperation $manageBuyOfferOp = null;
    private ?XdrManageSellOfferOperation $manageSellOfferOp = null;
    private ?XdrPathPaymentStrictReceiveOperation $pathPaymentStrictReceiveOp = null;
    private ?XdrPathPaymentStrictSendOperation $pathPaymentStrictSendOp = null;
    private ?XdrCreatePassiveSellOfferOperation $createPassiveSellOfferOp = null;
    private ?XdrSetOptionsOperation $setOptionsOp = null;
    private ?XdrAllowTrustOperation $allowTrustOperation = null;
    private ?XdrManageDataOperation $manageDataOperation = null;
    private ?XdrCreateClaimableBalanceOperation $createClaimableBalanceOperation = null;
    private ?XdrClaimClaimableBalanceOperation $claimClaimableBalanceOperation = null;
    private ?XdrBeginSponsoringFutureReservesOperation $beginSponsoringFutureReservesOperation = null;
    private ?XdrRevokeSponsorshipOperation $revokeSponsorshipOperation = null;
    private ?XdrClawbackOperation $clawbackOperation = null;
    private ?XdrClawbackClaimableBalanceOperation $clawbackClaimableBalanceOperation = null;
    private ?XdrSetTrustLineFlagsOperation $setTrustLineFlagsOperation = null;
    private ?XdrLiquidityPoolDepositOperation $liquidityPoolDepositOperation = null;
    private ?XdrLiquidityPoolWithdrawOperation $liquidityPoolWithdrawOperation = null;
    private ?XdrInvokeHostFunctionOp $invokeHostFunctionOperation = null;
    private ?XdrBumpFootprintExpirationOp $bumpFootprintExpirationOp = null;
    private ?XdrRestoreFootprintOp $restoreFootprintOp = null;

    public function __construct(XdrOperationType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrOperationType
     */
    public function getType(): XdrOperationType
    {
        return $this->type;
    }

    /**
     * @return XdrCreateAccountOperation|null
     */
    public function getCreateAccountOp(): ?XdrCreateAccountOperation
    {
        return $this->createAccountOp;
    }

    /**
     * @param XdrCreateAccountOperation|null $createAccountOp
     */
    public function setCreateAccountOp(?XdrCreateAccountOperation $createAccountOp): void
    {
        $this->createAccountOp = $createAccountOp;
    }

    /**
     * @return XdrPaymentOperation|null
     */
    public function getPaymentOp(): ?XdrPaymentOperation
    {
        return $this->paymentOp;
    }

    /**
     * @param XdrPaymentOperation|null $paymentOp
     */
    public function setPaymentOp(?XdrPaymentOperation $paymentOp): void
    {
        $this->paymentOp = $paymentOp;
    }

    /**
     * @return XdrChangeTrustOperation|null
     */
    public function getChangeTrustOp(): ?XdrChangeTrustOperation
    {
        return $this->changeTrustOp;
    }

    /**
     * @param XdrChangeTrustOperation|null $changeTrustOp
     */
    public function setChangeTrustOp(?XdrChangeTrustOperation $changeTrustOp): void
    {
        $this->changeTrustOp = $changeTrustOp;
    }

    /**
     * @return XdrBumpSequenceOperation|null
     */
    public function getBumpSequenceOp(): ?XdrBumpSequenceOperation
    {
        return $this->bumpSequenceOp;
    }

    /**
     * @param XdrBumpSequenceOperation|null $bumpSequenceOp
     */
    public function setBumpSequenceOp(?XdrBumpSequenceOperation $bumpSequenceOp): void
    {
        $this->bumpSequenceOp = $bumpSequenceOp;
    }

    /**
     * @return XdrAccountMergeOperation|null
     */
    public function getAccountMergeOp(): ?XdrAccountMergeOperation
    {
        return $this->accountMergeOp;
    }

    /**
     * @param XdrAccountMergeOperation|null $accountMergeOp
     */
    public function setAccountMergeOp(?XdrAccountMergeOperation $accountMergeOp): void
    {
        $this->accountMergeOp = $accountMergeOp;
    }

    /**
     * @return XdrManageSellOfferOperation|null
     */
    public function getManageSellOfferOp(): ?XdrManageSellOfferOperation
    {
        return $this->manageSellOfferOp;
    }

    /**
     * @param XdrManageSellOfferOperation|null $manageSellOfferOp
     */
    public function setManageSellOfferOp(?XdrManageSellOfferOperation $manageSellOfferOp): void
    {
        $this->manageSellOfferOp = $manageSellOfferOp;
    }

    /**
     * @return XdrManageBuyOfferOperation|null
     */
    public function getManageBuyOfferOp(): ?XdrManageBuyOfferOperation
    {
        return $this->manageBuyOfferOp;
    }

    /**
     * @param XdrManageBuyOfferOperation|null $manageBuyOfferOp
     */
    public function setManageBuyOfferOp(?XdrManageBuyOfferOperation $manageBuyOfferOp): void
    {
        $this->manageBuyOfferOp = $manageBuyOfferOp;
    }

    /**
     * @return XdrPathPaymentStrictReceiveOperation|null
     */
    public function getPathPaymentStrictReceiveOp(): ?XdrPathPaymentStrictReceiveOperation
    {
        return $this->pathPaymentStrictReceiveOp;
    }

    /**
     * @param XdrPathPaymentStrictReceiveOperation|null $pathPaymentStrictReceiveOp
     */
    public function setPathPaymentStrictReceiveOp(?XdrPathPaymentStrictReceiveOperation $pathPaymentStrictReceiveOp): void
    {
        $this->pathPaymentStrictReceiveOp = $pathPaymentStrictReceiveOp;
    }

    /**
     * @return XdrPathPaymentStrictSendOperation|null
     */
    public function getPathPaymentStrictSendOp(): ?XdrPathPaymentStrictSendOperation
    {
        return $this->pathPaymentStrictSendOp;
    }

    /**
     * @param XdrPathPaymentStrictSendOperation|null $pathPaymentStrictSendOp
     */
    public function setPathPaymentStrictSendOp(?XdrPathPaymentStrictSendOperation $pathPaymentStrictSendOp): void
    {
        $this->pathPaymentStrictSendOp = $pathPaymentStrictSendOp;
    }

    /**
     * @return XdrCreatePassiveSellOfferOperation|null
     */
    public function getCreatePassiveSellOfferOp(): ?XdrCreatePassiveSellOfferOperation
    {
        return $this->createPassiveSellOfferOp;
    }

    /**
     * @param XdrCreatePassiveSellOfferOperation|null $createPassiveSellOfferOp
     */
    public function setCreatePassiveSellOfferOp(?XdrCreatePassiveSellOfferOperation $createPassiveSellOfferOp): void
    {
        $this->createPassiveSellOfferOp = $createPassiveSellOfferOp;
    }

    /**
     * @return XdrSetOptionsOperation|null
     */
    public function getSetOptionsOp(): ?XdrSetOptionsOperation
    {
        return $this->setOptionsOp;
    }

    /**
     * @param XdrSetOptionsOperation|null $setOptionsOp
     */
    public function setSetOptionsOp(?XdrSetOptionsOperation $setOptionsOp): void
    {
        $this->setOptionsOp = $setOptionsOp;
    }

    /**
     * @return XdrAllowTrustOperation|null
     */
    public function getAllowTrustOperation(): ?XdrAllowTrustOperation
    {
        return $this->allowTrustOperation;
    }

    /**
     * @param XdrAllowTrustOperation|null $allowTrustOperation
     */
    public function setAllowTrustOperation(?XdrAllowTrustOperation $allowTrustOperation): void
    {
        $this->allowTrustOperation = $allowTrustOperation;
    }

    /**
     * @return XdrManageDataOperation|null
     */
    public function getManageDataOperation(): ?XdrManageDataOperation
    {
        return $this->manageDataOperation;
    }

    /**
     * @param XdrManageDataOperation|null $manageDataOperation
     */
    public function setManageDataOperation(?XdrManageDataOperation $manageDataOperation): void
    {
        $this->manageDataOperation = $manageDataOperation;
    }

    /**
     * @return XdrCreateClaimableBalanceOperation|null
     */
    public function getCreateClaimableBalanceOperation(): ?XdrCreateClaimableBalanceOperation
    {
        return $this->createClaimableBalanceOperation;
    }

    /**
     * @param XdrCreateClaimableBalanceOperation|null $createClaimableBalanceOperation
     */
    public function setCreateClaimableBalanceOperation(?XdrCreateClaimableBalanceOperation $createClaimableBalanceOperation): void
    {
        $this->createClaimableBalanceOperation = $createClaimableBalanceOperation;
    }

    /**
     * @return XdrClaimClaimableBalanceOperation|null
     */
    public function getClaimClaimableBalanceOperation(): ?XdrClaimClaimableBalanceOperation
    {
        return $this->claimClaimableBalanceOperation;
    }

    /**
     * @param XdrClaimClaimableBalanceOperation|null $claimClaimableBalanceOperation
     */
    public function setClaimClaimableBalanceOperation(?XdrClaimClaimableBalanceOperation $claimClaimableBalanceOperation): void
    {
        $this->claimClaimableBalanceOperation = $claimClaimableBalanceOperation;
    }

    /**
     * @return XdrBeginSponsoringFutureReservesOperation|null
     */
    public function getBeginSponsoringFutureReservesOperation(): ?XdrBeginSponsoringFutureReservesOperation
    {
        return $this->beginSponsoringFutureReservesOperation;
    }

    /**
     * @param XdrBeginSponsoringFutureReservesOperation|null $beginSponsoringFutureReservesOperation
     */
    public function setBeginSponsoringFutureReservesOperation(?XdrBeginSponsoringFutureReservesOperation $beginSponsoringFutureReservesOperation): void
    {
        $this->beginSponsoringFutureReservesOperation = $beginSponsoringFutureReservesOperation;
    }

    /**
     * @return XdrRevokeSponsorshipOperation|null
     */
    public function getRevokeSponsorshipOperation(): ?XdrRevokeSponsorshipOperation
    {
        return $this->revokeSponsorshipOperation;
    }

    /**
     * @param XdrRevokeSponsorshipOperation|null $revokeSponsorshipOperation
     */
    public function setRevokeSponsorshipOperation(?XdrRevokeSponsorshipOperation $revokeSponsorshipOperation): void
    {
        $this->revokeSponsorshipOperation = $revokeSponsorshipOperation;
    }

    /**
     * @return XdrClawbackOperation|null
     */
    public function getClawbackOperation(): ?XdrClawbackOperation
    {
        return $this->clawbackOperation;
    }

    /**
     * @param XdrClawbackOperation|null $clawbackOperation
     */
    public function setClawbackOperation(?XdrClawbackOperation $clawbackOperation): void
    {
        $this->clawbackOperation = $clawbackOperation;
    }

    /**
     * @return XdrClawbackClaimableBalanceOperation|null
     */
    public function getClawbackClaimableBalanceOperation(): ?XdrClawbackClaimableBalanceOperation
    {
        return $this->clawbackClaimableBalanceOperation;
    }

    /**
     * @param XdrClawbackClaimableBalanceOperation|null $clawbackClaimableBalanceOperation
     */
    public function setClawbackClaimableBalanceOperation(?XdrClawbackClaimableBalanceOperation $clawbackClaimableBalanceOperation): void
    {
        $this->clawbackClaimableBalanceOperation = $clawbackClaimableBalanceOperation;
    }

    /**
     * @return XdrSetTrustLineFlagsOperation|null
     */
    public function getSetTrustLineFlagsOperation(): ?XdrSetTrustLineFlagsOperation
    {
        return $this->setTrustLineFlagsOperation;
    }

    /**
     * @param XdrSetTrustLineFlagsOperation|null $setTrustLineFlagsOperation
     */
    public function setSetTrustLineFlagsOperation(?XdrSetTrustLineFlagsOperation $setTrustLineFlagsOperation): void
    {
        $this->setTrustLineFlagsOperation = $setTrustLineFlagsOperation;
    }

    /**
     * @return XdrLiquidityPoolDepositOperation|null
     */
    public function getLiquidityPoolDepositOperation(): ?XdrLiquidityPoolDepositOperation
    {
        return $this->liquidityPoolDepositOperation;
    }

    /**
     * @param XdrLiquidityPoolDepositOperation|null $liquidityPoolDepositOperation
     */
    public function setLiquidityPoolDepositOperation(?XdrLiquidityPoolDepositOperation $liquidityPoolDepositOperation): void
    {
        $this->liquidityPoolDepositOperation = $liquidityPoolDepositOperation;
    }

    /**
     * @return XdrLiquidityPoolWithdrawOperation|null
     */
    public function getLiquidityPoolWithdrawOperation(): ?XdrLiquidityPoolWithdrawOperation
    {
        return $this->liquidityPoolWithdrawOperation;
    }

    /**
     * @param XdrLiquidityPoolWithdrawOperation|null $liquidityPoolWithdrawOperation
     */
    public function setLiquidityPoolWithdrawOperation(?XdrLiquidityPoolWithdrawOperation $liquidityPoolWithdrawOperation): void
    {
        $this->liquidityPoolWithdrawOperation = $liquidityPoolWithdrawOperation;
    }

    /**
     * @return XdrInvokeHostFunctionOp|null
     */
    public function getInvokeHostFunctionOperation(): ?XdrInvokeHostFunctionOp
    {
        return $this->invokeHostFunctionOperation;
    }

    /**
     * @param XdrInvokeHostFunctionOp|null $invokeHostFunctionOperation
     */
    public function setInvokeHostFunctionOperation(?XdrInvokeHostFunctionOp $invokeHostFunctionOperation): void
    {
        $this->invokeHostFunctionOperation = $invokeHostFunctionOperation;
    }

    /**
     * @return XdrBumpFootprintExpirationOp|null
     */
    public function getBumpFootprintExpirationOp(): ?XdrBumpFootprintExpirationOp
    {
        return $this->bumpFootprintExpirationOp;
    }

    /**
     * @param XdrBumpFootprintExpirationOp|null $bumpFootprintExpirationOp
     */
    public function setBumpFootprintExpirationOp(?XdrBumpFootprintExpirationOp $bumpFootprintExpirationOp): void
    {
        $this->bumpFootprintExpirationOp = $bumpFootprintExpirationOp;
    }

    /**
     * @return XdrRestoreFootprintOp|null
     */
    public function getRestoreFootprintOp(): ?XdrRestoreFootprintOp
    {
        return $this->restoreFootprintOp;
    }

    /**
     * @param XdrRestoreFootprintOp|null $restoreFootprintOp
     */
    public function setRestoreFootprintOp(?XdrRestoreFootprintOp $restoreFootprintOp): void
    {
        $this->restoreFootprintOp = $restoreFootprintOp;
    }


    public function encode() : string {
        $bytes = $this->type->encode();
        $bytes .= match ($this->type->getValue()) {
            XdrOperationType::CREATE_ACCOUNT => $this->createAccountOp->encode() ?? "",
            XdrOperationType::PAYMENT => $this->paymentOp->encode() ?? "",
            XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE => $this->pathPaymentStrictReceiveOp->encode() ?? "",
            XdrOperationType::PATH_PAYMENT_STRICT_SEND => $this->pathPaymentStrictSendOp->encode() ?? "",
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER => $this->createPassiveSellOfferOp->encode() ?? "",
            XdrOperationType::SET_OPTIONS => $this->setOptionsOp->encode() ?? "",
            XdrOperationType::CHANGE_TRUST => $this->changeTrustOp->encode() ?? "",
            XdrOperationType::BUMP_SEQUENCE => $this->bumpSequenceOp->encode() ?? "",
            XdrOperationType::ACCOUNT_MERGE => $this->accountMergeOp->encode() ?? "",
            XdrOperationType::MANAGE_SELL_OFFER => $this->manageSellOfferOp->encode() ?? "",
            XdrOperationType::MANAGE_BUY_OFFER => $this->manageBuyOfferOp->encode() ?? "",
            XdrOperationType::ALLOW_TRUST => $this->allowTrustOperation->encode() ?? "",
            XdrOperationType::MANAGE_DATA => $this->manageDataOperation->encode() ?? "",
            XdrOperationType::CREATE_CLAIMABLE_BALANCE => $this->createClaimableBalanceOperation->encode() ?? "",
            XdrOperationType::CLAIM_CLAIMABLE_BALANCE => $this->claimClaimableBalanceOperation->encode() ?? "",
            XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES => $this->beginSponsoringFutureReservesOperation->encode() ?? "",
            XdrOperationType::END_SPONSORING_FUTURE_RESERVES => "",
            XdrOperationType::REVOKE_SPONSORSHIP => $this->revokeSponsorshipOperation->encode() ?? "",
            XdrOperationType::CLAWBACK => $this->clawbackOperation->encode() ?? "",
            XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE => $this->clawbackClaimableBalanceOperation->encode() ?? "",
            XdrOperationType::SET_TRUST_LINE_FLAGS => $this->setTrustLineFlagsOperation->encode() ?? "",
            XdrOperationType::LIQUIDITY_POOL_DEPOSIT => $this->liquidityPoolDepositOperation->encode() ?? "",
            XdrOperationType::LIQUIDITY_POOL_WITHDRAW => $this->liquidityPoolWithdrawOperation->encode() ?? "",
            XdrOperationType::INVOKE_HOST_FUNCTION => $this->invokeHostFunctionOperation->encode() ?? "",
            XdrOperationType::BUMP_FOOTPRINT_EXPIRATION => $this->bumpFootprintExpirationOp->encode() ?? "",
            XdrOperationType::RESTORE_FOOTPRINT => $this->restoreFootprintOp->encode() ?? ""
        };
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrOperationBody {
        $type = XdrOperationType::decode($xdr);
        $result = new XdrOperationBody($type);
        switch ($type->getValue()) {
            case XdrOperationType::CREATE_ACCOUNT:
                $result->setCreateAccountOp(XdrCreateAccountOperation::decode($xdr));
                break;
            case XdrOperationType::PAYMENT:
                $result->setPaymentOp(XdrPaymentOperation::decode($xdr));
                break;
            case XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE:
                $result->setPathPaymentStrictReceiveOp(XdrPathPaymentStrictReceiveOperation::decode($xdr));
                break;
            case XdrOperationType::PATH_PAYMENT_STRICT_SEND:
                $result->setPathPaymentStrictSendOp(XdrPathPaymentStrictSendOperation::decode($xdr));
                break;
            case XdrOperationType::CREATE_PASSIVE_SELL_OFFER:
                $result->setCreatePassiveSellOfferOp(XdrCreatePassiveSellOfferOperation::decode($xdr));
                break;
            case XdrOperationType::SET_OPTIONS:
                $result->setSetOptionsOp(XdrSetOptionsOperation::decode($xdr));
                break;
            case XdrOperationType::CHANGE_TRUST:
                $result->setChangeTrustOp(XdrChangeTrustOperation::decode($xdr));
                break;
            case XdrOperationType::BUMP_SEQUENCE:
                $result->setBumpSequenceOp(XdrBumpSequenceOperation::decode($xdr));
                break;
            case XdrOperationType::ACCOUNT_MERGE:
                $result->setAccountMergeOp(XdrAccountMergeOperation::decode($xdr));
                break;
            case XdrOperationType::MANAGE_SELL_OFFER:
                $result->setManageSellOfferOp(XdrManageSellOfferOperation::decode($xdr));
                break;
            case XdrOperationType::MANAGE_BUY_OFFER:
                $result->setManageBuyOfferOp(XdrManageBuyOfferOperation::decode($xdr));
                break;
            case XdrOperationType::ALLOW_TRUST:
                $result->setAllowTrustOperation(XdrAllowTrustOperation::decode($xdr));
                break;
            case XdrOperationType::MANAGE_DATA:
                $result->setManageDataOperation(XdrManageDataOperation::decode($xdr));
                break;
            case XdrOperationType::CREATE_CLAIMABLE_BALANCE:
                $result->setCreateClaimableBalanceOperation(XdrCreateClaimableBalanceOperation::decode($xdr));
                break;
            case XdrOperationType::CLAIM_CLAIMABLE_BALANCE:
                $result->setClaimClaimableBalanceOperation(XdrClaimClaimableBalanceOperation::decode($xdr));
                break;
            case XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES:
                $result->setBeginSponsoringFutureReservesOperation(XdrBeginSponsoringFutureReservesOperation::decode($xdr));
                break;
            case XdrOperationType::END_SPONSORING_FUTURE_RESERVES:
                break;
            case XdrOperationType::REVOKE_SPONSORSHIP:
                $result->setRevokeSponsorshipOperation(XdrRevokeSponsorshipOperation::decode($xdr));
                break;
            case XdrOperationType::CLAWBACK:
                $result->setClawbackOperation(XdrClawbackOperation::decode($xdr));
                break;
            case XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE:
                $result->setClawbackClaimableBalanceOperation(XdrClawbackClaimableBalanceOperation::decode($xdr));
                break;
            case XdrOperationType::SET_TRUST_LINE_FLAGS:
                $result->setSetTrustLineFlagsOperation(XdrSetTrustLineFlagsOperation::decode($xdr));
                break;
            case XdrOperationType::LIQUIDITY_POOL_DEPOSIT:
                $result->setLiquidityPoolDepositOperation(XdrLiquidityPoolDepositOperation::decode($xdr));
                break;
            case XdrOperationType::LIQUIDITY_POOL_WITHDRAW:
                $result->setLiquidityPoolWithdrawOperation(XdrLiquidityPoolWithdrawOperation::decode($xdr));
                break;
            case XdrOperationType::INVOKE_HOST_FUNCTION:
                $result->setInvokeHostFunctionOperation(XdrInvokeHostFunctionOp::decode($xdr));
                break;
            case XdrOperationType::BUMP_FOOTPRINT_EXPIRATION:
                $result->setBumpFootprintExpirationOp(XdrBumpFootprintExpirationOp::decode($xdr));
                break;
            case XdrOperationType::RESTORE_FOOTPRINT:
                $result->setRestoreFootprintOp(XdrRestoreFootprintOp::decode($xdr));
                break;
        }
        return $result;
    }
}