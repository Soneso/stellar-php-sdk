<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK;

use RuntimeException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceIDType;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyAccount;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyData;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyOffer;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyTrustLine;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;

class RevokeSponsorshipOperationBuilder
{
    private ?XdrLedgerKey $ledgerKey = null;
    private ?string $signerAccount = null;
    private ?XdrSignerKey $signerKey = null;
    private ?MuxedAccount $sourceAccount = null;


    public function setSourceAccount(string $accountId) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

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

    public function revokeClaimableBalanceSponsorship(string $balanceId) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }
        $this->ledgerKey = new XdrLedgerKey(new XdrLedgerEntryType(XdrLedgerEntryType::CLAIMABLE_BALANCE));

        $bId = new XdrClaimableBalanceID(new XdrClaimableBalanceIDType(XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0), $balanceId);
        $this->ledgerKey->setBalanceID($bId);
        return $this;
    }

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

    public function revokePreAuthTxSigner(string $signerAccountId, string $preAuthTx) : RevokeSponsorshipOperationBuilder {
        if ($this->ledgerKey || $this->signerKey) {
            throw new RuntimeException("can not revoke multiple entries per builder");
        }

        $this->signerKey = new XdrSignerKey();
        $this->signerKey->setType(new XdrSignerKeyType(XdrSignerKeyType::PRE_AUTH_TX));
        $this->signerKey->setPreAuthTx(StrKey::decodePreAuth($preAuthTx));
        $this->signerAccount = $signerAccountId;
        return $this;
    }

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