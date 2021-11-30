<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builds ChangeTrust operation.
 * @see ChangeTrustOperation
 */
class ChangeTrustOperationBuilder
{
    private Asset $asset;
    private ?string $limit = null;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ChangeTrust builder.
     * @param Asset $asset The asset of the trustline. For example, if a gateway extends a trustline of up to 200 USD to a user, the line is USD.
     * @param string|null $limit The limit of the trustline. For example, if a gateway extends a trustline of up to 200 USD to a user, the limit is 200.
     */
    public function __construct(Asset $asset, ?string $limit = null) {
        $this->asset = $asset;
        $this->limit = $limit;
    }

    /**
     * Sets the source account for this operation. G...
     * @param string $accountId The operation's source account.
     * @return ChangeTrustOperationBuilder Builder object so you can chain methods
     */
    public function setSourceAccount(string $accountId) : ChangeTrustOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's source account.
     * @return ChangeTrustOperationBuilder Builder object so you can chain methods
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ChangeTrustOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation.
     * @return ChangeTrustOperation
     */
    public function build(): ChangeTrustOperation {
        $result = new ChangeTrustOperation($this->asset, $this->limit);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}