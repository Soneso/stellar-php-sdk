<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class ChangeTrustOperationBuilder
{
    private Asset $asset;
    private ?string $limit = null;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(Asset $asset, ?string $limit = null) {
        $this->asset = $asset;
        $this->limit = $limit;
    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): ChangeTrustOperation {
        $result = new ChangeTrustOperation($this->asset, $this->limit);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}