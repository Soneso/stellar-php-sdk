<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class SetTrustLineFlagsOperationBuilder
{
    private string $trustorId;
    private Asset $asset;
    private int $clearFlags;
    private int $setFlags;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param string $trustorId
     * @param Asset $asset
     * @param int $clearFlags
     * @param int $setFlags
     */
    public function __construct(string $trustorId, Asset $asset, int $clearFlags, int $setFlags) {
        $this->trustorId = $trustorId;
        $this->asset = $asset;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
    }

    public function setSourceAccount(string $accountId) : SetTrustLineFlagsOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : SetTrustLineFlagsOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): SetTrustLineFlagsOperation {
        $result = new SetTrustLineFlagsOperation($this->trustorId, $this->asset, $this->clearFlags, $this->setFlags);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}