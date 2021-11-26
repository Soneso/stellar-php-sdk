<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

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
        return match ($type) {
            XdrOperationType::CREATE_ACCOUNT => self::creatAccountOperation($body),
            default => throw new \InvalidArgumentException(sprintf("Unknown operation type: %s", $type))
        };
    }

    private static function creatAccountOperation(XdrOperationBody $body) : CreateAccountOperation {
        $caOp = $body->getCreateAccountOp();
        if ($caOp != null) {
            return CreateAccountOperation::fromXdrOperation($caOp);
        } else {
            throw new \InvalidArgumentException("missing create account operation in xdr operation body");
        }
    }
}