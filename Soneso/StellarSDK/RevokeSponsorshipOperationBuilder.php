<?php

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
     * @return string
     */
    public function getSignerAccount(): string
    {
        return $this->signerAccount;
    }

    /**
     * @param string $signerAccount
     */
    public function setSignerAccount(string $signerAccount): void
    {
        $this->signerAccount = $signerAccount;
    }

    /**
     * @return XdrSignerKey
     */
    public function getSignerKey(): XdrSignerKey
    {
        return $this->signerKey;
    }

    /**
     * @param XdrSignerKey $signerKey
     */
    public function setSignerKey(XdrSignerKey $signerKey): void
    {
        $this->signerKey = $signerKey;
    }

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