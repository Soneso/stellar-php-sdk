<?php

namespace Soneso\StellarSDK;

use InvalidArgumentException;

class AllowTrustOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private string $trustor;
    private string $assetCode;
    private bool $authorized;
    private bool $authorizedToMaintainLiabilities;

    public function __construct(string $trustor, string $assetCode, bool $authorized, bool $authorizedToMaintainLiabilities) {
        $len = strlen($assetCode);
        if ($len <= 0 || $len > 12) {
            throw new InvalidArgumentException("invalid asset code: ". $assetCode);
        }
        $this->trustor = $trustor;
        $this->assetCode = $assetCode;
        $this->authorized = $authorized;

    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): AllowTrustOperation {
        $result = new AllowTrustOperation($this->trustor, $this->assetCode, $this->authorized, $this->authorizedToMaintainLiabilities);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}