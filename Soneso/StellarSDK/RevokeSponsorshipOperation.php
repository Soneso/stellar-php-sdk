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

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#revoke-sponsorship" target="_blank">RevokeSponsorship</a> operation.
 *
 * Removes or transfers sponsorship of a ledger entry or signer. This operation allows the sponsor of a
 * ledger entry or signer to revoke their sponsorship, transferring the responsibility back to the sponsored account.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see RevokeSponsorshipOperationBuilder For building this operation
 * @since 1.0.0
 */
class RevokeSponsorshipOperation extends AbstractOperation
{
    /**
     * @var XdrLedgerKey|null The ledger key identifying the sponsored ledger entry to revoke.
     */
    private ?XdrLedgerKey $ledgerKey = null;

    /**
     * @var string|null The account ID that owns the signer being revoked.
     */
    private ?string $signerAccount = null;

    /**
     * @var XdrSignerKey|null The signer key being revoked.
     */
    private ?XdrSignerKey $signerKey = null;

    /**
     * Returns the ledger key of the sponsored entry to revoke.
     *
     * @return XdrLedgerKey|null The ledger key, or null if revoking a signer.
     */
    public function getLedgerKey(): ?XdrLedgerKey
    {
        return $this->ledgerKey;
    }

    /**
     * Sets the ledger key of the sponsored entry to revoke.
     *
     * @param XdrLedgerKey|null $ledgerKey The ledger key.
     * @return void
     */
    public function setLedgerKey(?XdrLedgerKey $ledgerKey): void
    {
        $this->ledgerKey = $ledgerKey;
    }

    /**
     * Returns the account ID that owns the signer being revoked.
     *
     * @return string|null The signer account ID, or null if revoking a ledger entry.
     */
    public function getSignerAccount(): ?string
    {
        return $this->signerAccount;
    }

    /**
     * Sets the account ID that owns the signer being revoked.
     *
     * @param string|null $signerAccount The signer account ID.
     * @return void
     */
    public function setSignerAccount(?string $signerAccount): void
    {
        $this->signerAccount = $signerAccount;
    }

    /**
     * Returns the signer key being revoked.
     *
     * @return XdrSignerKey|null The signer key, or null if revoking a ledger entry.
     */
    public function getSignerKey(): ?XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * Sets the signer key being revoked.
     *
     * @param XdrSignerKey|null $signerKey The signer key.
     * @return void
     */
    public function setSignerKey(?XdrSignerKey $signerKey): void
    {
        $this->signerKey = $signerKey;
    }

    /**
     * Creates a RevokeSponsorshipOperation from XDR operation object.
     *
     * @param XdrRevokeSponsorshipOperation $xdrOp The XDR operation object to convert.
     * @return RevokeSponsorshipOperation The created operation instance.
     */
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

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
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