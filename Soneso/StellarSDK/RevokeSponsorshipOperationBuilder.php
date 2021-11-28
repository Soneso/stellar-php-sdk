<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.



namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKey;

class RevokeSponsorshipOperationBuilder
{
    private ?XdrLedgerKey $ledgerKey = null;
    private string $signerAccount;
    private XdrSignerKey $signerKey;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param XdrLedgerKey|null $ledgerKey
     * @return RevokeSponsorshipOperationBuilder
     */
    public function setLedgerKey(?XdrLedgerKey $ledgerKey) : RevokeSponsorshipOperationBuilder {
        $this->ledgerKey = $ledgerKey;
        return $this;
    }

    /**
     * @param string $signerAccount
     * @return RevokeSponsorshipOperationBuilder
     */
    public function setSignerAccount(string $signerAccount) : RevokeSponsorshipOperationBuilder {
        $this->signerAccount = $signerAccount;
        return $this;
    }

    /**
     * @param XdrSignerKey $signerKey
     * @return RevokeSponsorshipOperationBuilder
     */
    public function setSignerKey(XdrSignerKey $signerKey) : RevokeSponsorshipOperationBuilder {
        $this->signerKey = $signerKey;
        return $this;
    }

    public function setSourceAccount(string $accountId) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : RevokeSponsorshipOperationBuilder {
        $this->sourceAccount = $sourceAccount;
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