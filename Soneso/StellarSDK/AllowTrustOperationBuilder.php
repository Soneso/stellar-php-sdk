<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

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
        $this->authorizedToMaintainLiabilities = $authorizedToMaintainLiabilities;
    }

    public function setSourceAccount(string $accountId) : AllowTrustOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : AllowTrustOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): AllowTrustOperation {
        $result = new AllowTrustOperation($this->trustor, $this->assetCode, $this->authorized, $this->authorizedToMaintainLiabilities);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}