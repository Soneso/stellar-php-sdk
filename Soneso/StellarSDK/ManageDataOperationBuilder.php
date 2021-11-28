<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builds ManageData operation
 * @see ManageDataOperation
 */
class ManageDataOperationBuilder
{
    private string $key;
    private ?string $value = null;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new ManageData builder. If you want to delete data entry pass null as a <code>value</code> param.
     * @param string $key The name of data entry.
     * @param string|null $value The value of data entry. <code>null</code>null will delete data entry.
     */
    public function __construct(string $key, ?string $value = null) {
        $this->key = $key;
        $this->value = $value;
    }

    public function setSourceAccount(string $accountId) : ManageDataOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ManageDataOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): ManageDataOperation {
        $result = new ManageDataOperation($this->key, $this->value);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}