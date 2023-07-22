<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Util\StellarAmount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

abstract class AbstractOperation
{
    const STROOP_SCALE = 10000000;

    private ?MuxedAccount $sourceAccount = null;

    /**
     * @return MuxedAccount|null
     */
    public function getSourceAccount(): ?MuxedAccount
    {
        return $this->sourceAccount;
    }

    /**
     * @param MuxedAccount|null $sourceAccount
     */
    public function setSourceAccount(?MuxedAccount $sourceAccount): void
    {
        $this->sourceAccount = $sourceAccount;
    }

    public abstract function toOperationBody() : XdrOperationBody;

    public function toXdr() : XdrOperation {
        $body = $this->toOperationBody();
        return new XdrOperation($body, $this->sourceAccount?->toXdr());
    }

    public static function toXdrAmount(string $strAmount) : BigInteger {
        $stellarAmount = StellarAmount::fromString($strAmount);
        return $stellarAmount->getStroops();
    }

    public static function fromXdrAmount(BigInteger $stroops) : string {
        $stellarAmount = new StellarAmount($stroops);
        return $stellarAmount->getDecimalValueAsString();
    }

    public static function fromXdr(XdrOperation $xdrOp) : AbstractOperation {
        $body = $xdrOp->getBody();
        $sourceAccount = null;
        if ($xdrOp->getSourceAccount() != null) {
            $sourceAccount = MuxedAccount::fromXdr($xdrOp->getSourceAccount());
        }
        $type = $body->getType()->getValue();
        $result = match ($type) {
            XdrOperationType::CREATE_ACCOUNT => self::creatAccountOperation($body),
            XdrOperationType::PAYMENT => self::paymentOperation($body),
            XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE => self::pathPaymentStrictReceiveOperation($body),
            XdrOperationType::MANAGE_SELL_OFFER => self::manageSellOfferOperation($body),
            XdrOperationType::CREATE_PASSIVE_SELL_OFFER => self::createPassiveSellOfferOperation($body),
            XdrOperationType::SET_OPTIONS => self::setOptionsOperation($body),
            XdrOperationType::CHANGE_TRUST => self::changeTrustOperation($body),
            XdrOperationType::ALLOW_TRUST => self::allowTrustOperation($body),
            XdrOperationType::ACCOUNT_MERGE => self::accountMerge($body),
            XdrOperationType::MANAGE_DATA => self::manageData($body),
            XdrOperationType::BUMP_SEQUENCE => self::bumpSequence($body),
            XdrOperationType::MANAGE_BUY_OFFER => self::manageBuyOfferOperation($body),
            XdrOperationType::PATH_PAYMENT_STRICT_SEND => self::pathPaymentStrictSendOperation($body),
            XdrOperationType::CREATE_CLAIMABLE_BALANCE => self::createClaimableBalance($body),
            XdrOperationType::CLAIM_CLAIMABLE_BALANCE => self::claimClaimableClaimableBalance($body),
            XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES => self::beginSponsoringFutureReserves($body),
            XdrOperationType::END_SPONSORING_FUTURE_RESERVES => new EndSponsoringFutureReservesOperation(),
            XdrOperationType::REVOKE_SPONSORSHIP => self::revokeSponsorship($body),
            XdrOperationType::CLAWBACK => self::clawback($body),
            XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE => self::clawbackClaimableBalance($body),
            XdrOperationType::SET_TRUST_LINE_FLAGS => self::setTrustlineFlags($body),
            XdrOperationType::LIQUIDITY_POOL_DEPOSIT => self::liquidityPoolDeposit($body),
            XdrOperationType::LIQUIDITY_POOL_WITHDRAW => self::liquidityPoolWithdraw($body),
            XdrOperationType::INVOKE_HOST_FUNCTION => self::invokeHostFunction($body),
            XdrOperationType::BUMP_FOOTPRINT_EXPIRATION => self::bumpFootprintExpiration($body),
            XdrOperationType::RESTORE_FOOTPRINT => self::restoreFootprint($body),
            default => throw new InvalidArgumentException(sprintf("Unknown operation type: %s", $type))
        };
        $result->setSourceAccount($sourceAccount);
        return $result;
    }

    private static function restoreFootprint(XdrOperationBody $body) : RestoreFootprintOperation {
        $op = $body->getRestoreFootprintOp();
        if ($op != null) {
            return RestoreFootprintOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing invoke host function operation in xdr operation body");
        }
    }

    private static function bumpFootprintExpiration(XdrOperationBody $body) : BumpFootprintExpirationOperation {
        $op = $body->getBumpFootprintExpirationOp();
        if ($op != null) {
            return BumpFootprintExpirationOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing invoke host function operation in xdr operation body");
        }
    }

    private static function invokeHostFunction(XdrOperationBody $body) : InvokeHostFunctionOperation {
        $op = $body->getInvokeHostFunctionOperation();
        if ($op != null) {
            return InvokeHostFunctionOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing invoke host function operation in xdr operation body");
        }
    }

    private static function liquidityPoolWithdraw(XdrOperationBody $body) : LiquidityPoolWithdrawOperation {
        $op = $body->getLiquidityPoolWithdrawOperation();
        if ($op != null) {
            return LiquidityPoolWithdrawOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing liquidity pool withdraw operation in xdr operation body");
        }
    }

    private static function liquidityPoolDeposit(XdrOperationBody $body) : LiquidityPoolDepositOperation {
        $op = $body->getLiquidityPoolDepositOperation();
        if ($op != null) {
            return LiquidityPoolDepositOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing liquidity pool deposit operation in xdr operation body");
        }
    }

    private static function setTrustlineFlags(XdrOperationBody $body) : SetTrustlineFlagsOperation {
        $op = $body->getSetTrustLineFlagsOperation();
        if ($op != null) {
            return SetTrustLineFlagsOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing set trustline flags operation in xdr operation body");
        }
    }

    private static function clawbackClaimableBalance(XdrOperationBody $body) : ClawbackClaimableBalanceOperation {
        $op = $body->getClawbackClaimableBalanceOperation();
        if ($op != null) {
            return ClawbackClaimableBalanceOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing clawback claimable operation in xdr operation body");
        }
    }

    private static function clawback(XdrOperationBody $body) : ClawbackOperation {
        $op = $body->getClawbackOperation();
        if ($op != null) {
            return ClawbackOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing clawback operation in xdr operation body");
        }
    }

    private static function revokeSponsorship(XdrOperationBody $body) : RevokeSponsorshipOperation {
        $op = $body->getRevokeSponsorshipOperation();
        if ($op != null) {
            return RevokeSponsorshipOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing revoke sponsorship operation in xdr operation body");
        }
    }

    private static function beginSponsoringFutureReserves(XdrOperationBody $body) : BeginSponsoringFutureReservesOperation {
        $op = $body->getBeginSponsoringFutureReservesOperation();
        if ($op != null) {
            return BeginSponsoringFutureReservesOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing begin sponsoring future reserves operation in xdr operation body");
        }
    }

    private static function claimClaimableClaimableBalance(XdrOperationBody $body) : ClaimClaimableBalanceOperation {
        $op = $body->getClaimClaimableBalanceOperation();
        if ($op != null) {
            return ClaimClaimableBalanceOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing claim claimable balance operation in xdr operation body");
        }
    }

    private static function createClaimableBalance(XdrOperationBody $body) : CreateClaimableBalanceOperation {
        $op = $body->getCreateClaimableBalanceOperation();
        if ($op != null) {
            return CreateClaimableBalanceOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing create claimable balance operation in xdr operation body");
        }
    }

    private static function pathPaymentStrictSendOperation(XdrOperationBody $body) : PathPaymentStrictSendOperation {
        $op = $body->getPathPaymentStrictSendOp();
        if ($op != null) {
            return PathPaymentStrictSendOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing path payment strict send operation in xdr operation body");
        }
    }

    private static function manageBuyOfferOperation(XdrOperationBody $body) : ManageBuyOfferOperation {
        $op = $body->getManageBuyOfferOp();
        if ($op != null) {
            return ManageBuyOfferOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing manage buy offer operation in xdr operation body");
        }
    }

    private static function bumpSequence(XdrOperationBody $body) : BumpSequenceOperation {
        $op = $body->getBumpSequenceOp();
        if ($op != null) {
            return BumpSequenceOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing bump sequence operation in xdr operation body");
        }
    }

    private static function manageData(XdrOperationBody $body) : ManageDataOperation {
        $op = $body->getManageDataOperation();
        if ($op != null) {
            return ManageDataOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing manage data operation in xdr operation body");
        }
    }

    private static function accountMerge(XdrOperationBody $body) : AccountMergeOperation {
        $op = $body->getAccountMergeOp();
        if ($op != null) {
            return AccountMergeOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing account merge operation in xdr operation body");
        }
    }

    private static function creatAccountOperation(XdrOperationBody $body) : CreateAccountOperation {
        $caOp = $body->getCreateAccountOp();
        if ($caOp != null) {
            return CreateAccountOperation::fromXdrOperation($caOp);
        } else {
            throw new InvalidArgumentException("missing create account operation in xdr operation body");
        }
    }

    private static function paymentOperation(XdrOperationBody $body) : PaymentOperation {
        $op = $body->getPaymentOp();
        if ($op != null) {
            return PaymentOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing payment operation in xdr operation body");
        }
    }

    private static function pathPaymentStrictReceiveOperation(XdrOperationBody $body) : PathPaymentStrictReceiveOperation {
        $op = $body->getPathPaymentStrictReceiveOp();
        if ($op != null) {
            return PathPaymentStrictReceiveOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing path payment strict receive operation in xdr operation body");
        }
    }

    private static function manageSellOfferOperation(XdrOperationBody $body) : ManageSellOfferOperation {
        $op = $body->getManageSellOfferOp();
        if ($op != null) {
            return ManageSellOfferOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing manage sell offer operation in xdr operation body");
        }
    }

    private static function createPassiveSellOfferOperation(XdrOperationBody $body) : CreatePassiveSellOfferOperation {
        $op = $body->getCreatePassiveSellOfferOp();
        if ($op != null) {
            return CreatePassiveSellOfferOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing create passive sell offer operation in xdr operation body");
        }
    }

    private static function setOptionsOperation(XdrOperationBody $body) : SetOptionsOperation {
        $op = $body->getSetOptionsOp();
        if ($op != null) {
            return SetOptionsOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing set options operation in xdr operation body");
        }
    }

    private static function changeTrustOperation(XdrOperationBody $body) : ChangeTrustOperation {
        $op = $body->getChangeTrustOp();
        if ($op != null) {
            return ChangeTrustOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing change trust operation in xdr operation body");
        }
    }

    private static function allowTrustOperation(XdrOperationBody $body) : AllowTrustOperation {
        $op = $body->getAllowTrustOperation();
        if ($op != null) {
            return AllowTrustOperation::fromXdrOperation($op);
        } else {
            throw new InvalidArgumentException("missing allow trust operation in xdr operation body");
        }
    }
}