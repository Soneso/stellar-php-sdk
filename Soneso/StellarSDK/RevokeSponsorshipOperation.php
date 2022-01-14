<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipSigner;
use Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipType;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

class RevokeSponsorshipOperation extends AbstractOperation
{
    private ?XdrLedgerKey $ledgerKey = null;
    private ?string $signerAccount = null;
    private ?XdrSignerKey $signerKey = null;

    /**
     * @return XdrLedgerKey|null
     */
    public function getLedgerKey(): ?XdrLedgerKey
    {
        return $this->ledgerKey;
    }

    /**
     * @param XdrLedgerKey|null $ledgerKey
     */
    public function setLedgerKey(?XdrLedgerKey $ledgerKey): void
    {
        $this->ledgerKey = $ledgerKey;
    }

    /**
     * @return string|null
     */
    public function getSignerAccount(): ?string
    {
        return $this->signerAccount;
    }

    /**
     * @param string|null $signerAccount
     */
    public function setSignerAccount(?string $signerAccount): void
    {
        $this->signerAccount = $signerAccount;
    }

    /**
     * @return XdrSignerKey|null
     */
    public function getSignerKey(): ?XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * @param XdrSignerKey|null $signerKey
     */
    public function setSignerKey(?XdrSignerKey $signerKey): void
    {
        $this->signerKey = $signerKey;
    }

    public static function fromXdrOperation(XdrRevokeSponsorshipOperation $xdrOp): RevokeSponsorshipOperation {
        $result = new RevokeSponsorshipOperation();
        if ($xdrOp->getType()->getValue() == XdrRevokeSponsorshipType::LEDGER_ENTRY) {
            $result->setLedgerKey($xdrOp->getLedgerKey());
        } else if ($xdrOp->getType()->getValue() == XdrRevokeSponsorshipType::SIGNER) {
            $result->setSignerAccount($xdrOp->getSigner()->getAccountId()->getAccountId());
            $result->setSignerKey($xdrOp->getSigner()->getSignerKey());
        }
        return $result;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $op = new XdrRevokeSponsorshipOperation();
        if ($this->ledgerKey) {
            $op->setType(new XdrRevokeSponsorshipType(XdrRevokeSponsorshipType::LEDGER_ENTRY));
            $op->setLedgerKey($this->ledgerKey);
        } else if ($this->signerAccount && $this->signerKey) {
            $op->setType(new XdrRevokeSponsorshipType(XdrRevokeSponsorshipType::SIGNER));
            $accID = XdrAccountID::fromAccountId($this->signerAccount);
            $signer = new XdrRevokeSponsorshipSigner($accID, $this->signerKey);
            $op->setSigner($signer);
        }
        $type = new XdrOperationType(XdrOperationType::REVOKE_SPONSORSHIP);
        $result = new XdrOperationBody($type);
        $result->setRevokeSponsorshipOperation($op);
        return $result;
    }
}