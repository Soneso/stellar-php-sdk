<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builds AccountMerge operation.
 * @see AccountMergeOperation
 */
class AccountMergeOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private MuxedAccount $destination;

    /**
     * Creates a new AccountMerge builder.
     * @param string $destinationAccountId The account that receives the remaining XLM balance of the source account.
     */
    public function __construct(string $destinationAccountId) {
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
    }

    public static function forMuxedDestinationAccount(MuxedAccount $destination) : AccountMergeOperationBuilder {
        return new AccountMergeOperationBuilder($destination->getAccountId());
    }

    /**
     * Set source account of this operation.
     * @param string $accountId source account.
     * @return AccountMergeOperationBuilder Builder object so you can chain methods.
     */
    public function setSourceAccount(string $accountId) : AccountMergeOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Set muxed source account of this operation.
     * @param MuxedAccount $sourceAccount muxed source account.
     * @return AccountMergeOperationBuilder Builder object so you can chain methods.
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : AccountMergeOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation.
     * @return AccountMergeOperation
     */
    public function build(): AccountMergeOperation {
        $result = new AccountMergeOperation($this->destination);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}