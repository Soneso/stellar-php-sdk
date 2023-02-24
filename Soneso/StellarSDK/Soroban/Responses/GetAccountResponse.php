<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\TransactionBuilderAccount;
/**
 * Response for fetching current info about a stellar account.
 */
class GetAccountResponse extends SorobanRpcResponse implements TransactionBuilderAccount
{
    /// Account Id of the account
    public ?string $id = null;

    /// Current sequence number of the account
    public ?string $sequence = null;

    public static function fromJson(array $json) : GetAccountResponse {
        $result = new GetAccountResponse($json);
        if (isset($json['result'])) {
            $result->id = $json['result']['id'];
            $result->sequence = $json['result']['sequence'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null account id.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null current sequence number.
     */
    public function getSequence(): ?string
    {
        return $this->sequence;
    }

    /**
     * @param string|null $sequence
     */
    public function setSequence(?string $sequence): void
    {
        $this->sequence = $sequence;
    }


    // TransactionBuilder account implementation

    /**
     * @return string accountID it available, otherwise throws RuntimeException.
     */
    public function getAccountId() : string {
        if ($this->id != null) {
            return $this->id;
        } else {
            throw new \RuntimeException("Response has error and no accountID");
        }
    }

    /**
     * @return BigInteger sequenceNumber if available, otherwise throws RuntimeException.
     */
    public function getSequenceNumber() : BigInteger {
        if ($this->sequence != null) {
            return new BigInteger($this->sequence);
        } else {
            throw new \RuntimeException("Response has error and no sequenceNumber");
        }
    }

    /**
     * @return BigInteger returns incremented sequenceNumber if available, otherwise throws RuntimeException.
     */
    public function getIncrementedSequenceNumber() : BigInteger {
        return $this->getSequenceNumber()->add(new BigInteger(1));
    }

    /**
     * Increments sequenceNumber if available, otherwise throws RuntimeException.
     */
    public function incrementSequenceNumber() : void {
        $this->sequence = $this->getIncrementedSequenceNumber()->toString();
    }


    /**
     * @return MuxedAccount sourceAccount if available, otherwise throws RuntimeException.
     */
    public function getMuxedAccount() : MuxedAccount {
        return new MuxedAccount($this->getAccountId());
    }
}