<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK;

use RuntimeException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyOffer;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTrustLine;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

/**
 * Builder for creating RevokeSponsorship operations.
 *
 * This builder implements the builder pattern to construct RevokeSponsorshipOperation
 * instances with a fluent interface. This operation revokes sponsorship of various
 * ledger entries including accounts, trustlines, offers, data, claimable balances, and signers.
 *
 * @package Soneso\StellarSDK
 * @see RevokeSponsorshipOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#revoke-sponsorship
 * @since 1.0.0
 *
 * @example
 * $operation = (new RevokeSponsorshipOperationBuilder())
 *     ->revokeAccountSponsorship($accountId)
 *     ->setSourceAccount($sponsorId)
 *     ->build();
 */
class RevokeSponsorshipOperationBuilder
{
    /**
     * @var XdrLedgerKey|null The ledger entry key for which to revoke sponsorship
     */
    private ?XdrLedgerKey $ledgerKey = null;

    /**
     * @var string|null The account ID for signer sponsorship revocation
     */
    private ?string $signerAccount = null;

    /**
     * @var XdrSignerKey|null The signer key for which to revoke sponsorship
     */
    private ?XdrSignerKey $signerKey = null;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Revokes sponsorship of an account.
     *
     * @param string $accountId The account ID for which to revoke sponsorship
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeAccountSponsorship(string $accountId) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::ACCOUNT));
        $accId = new XdrAccountID($accountId);
        $lacc = new XdrLedgerKeyAccount($accId);
        $this->ledgerKey->setAccount($lacc);
        return $this;
    }

    /**
     * Revokes sponsorship of a data entry.
     *
     * @param string $accountId The account ID holding the data entry
     * @param string $dataName The name of the data entry
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeDataSponsorship(string $accountId, string $dataName) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::DATA));
        $accId = new XdrAccountID($accountId);
        $data = new XdrLedgerKeyData($accId, $dataName);
        $this->ledgerKey->setData($data);
        return $this;
    }

    /**
     * Revokes sponsorship of a trustline.
     *
     * @param string $accountId The account ID holding the trustline
     * @param Asset $asset The asset for the trustline
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeTrustlineSponsorship(string $accountId, Asset $asset) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::TRUSTLINE));
        $accId = new XdrAccountID($accountId);
        $tr = new XdrLedgerKeyTrustLine($accId, $asset->toXdrTrustlineAsset());
        $this->ledgerKey->setTrustLine($tr);
        return $this;
    }

    /**
     * Revokes sponsorship of a claimable balance.
     *
     * @param string $balanceId The claimable balance ID
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeClaimableBalanceSponsorship(string $balanceId) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::CLAIMABLE_BALANCE));
        $bId = XdrClaimableBalanceID::forClaimableBalanceId($balanceId);
        $this->ledgerKey->setBalanceID($bId);
        return $this;
    }

    /**
     * Revokes sponsorship of an offer.
     *
     * @param string $accountId The account ID that created the offer
     * @param int $offerId The offer ID
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeOfferSponsorship(string $accountId, int $offerId) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::OFFER));
        $accId = new XdrAccountID($accountId);
        $offer = new XdrLedgerKeyOffer($accId, $offerId);
        $this->ledgerKey->setOffer($offer);
        return $this;
    }

    /**
     * Revokes sponsorship of an Ed25519 signer.
     *
     * @param string $signerAccountId The account ID that has the signer
     * @param string $ed25519AccountId The Ed25519 public key of the signer
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeEd25519Signer(string $signerAccountId, string $ed25519AccountId) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }

        $this->signerKey = new XdrSignerKey();
        $this->signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $this->signerKey->setEd25519(StrKey::decodeAccountId($ed25519AccountId));
        $this->signerAccount = $signerAccountId;
        return $this;
    }

    /**
     * Revokes sponsorship of a pre-authorized transaction signer.
     *
     * @param string $signerAccountId The account ID that has the signer
     * @param string $preAuthTx The pre-authorized transaction hash
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokePreAuthTxSigner(string $signerAccountId, string $preAuthTx) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }

        $this->signerKey = new XdrSignerKey();
        $this->signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
        $this->signerKey->setPreAuthTx(StrKey::decodePreAuthTx($preAuthTx));
        $this->signerAccount = $signerAccountId;
        return $this;
    }

    /**
     * Revokes sponsorship of a SHA256 hash signer.
     *
     * @param string $signerAccountId The account ID that has the signer
     * @param string $sha256Hash The SHA256 hash value
     * @return $this Returns the builder instance for method chaining
     * @throws RuntimeException If attempting to revoke multiple entries per builder
     */
    public function revokeSha256HashSigner(string $signerAccountId, string $sha256Hash) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }

        $this->signerKey = new XdrSignerKey();
        $this->signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::HASH_X));
        $this->signerKey->setHashX(StrKey::decodeSha256Hash($sha256Hash));
        $this->signerAccount = $signerAccountId;
        return $this;
    }

    /**
     * Builds the RevokeSponsorship operation.
     *
     * @return RevokeSponsorshipOperation The constructed operation
     */
    public function build(): RevokeSponsorshipOperation {
        $result = new RevokeSponsorshipOperation();
        $result->setLedgerKey($this->ledgerKey);
        $result->setSignerAccount($this->signerAccount);
        $result->setSignerKey($this->signerKey);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}