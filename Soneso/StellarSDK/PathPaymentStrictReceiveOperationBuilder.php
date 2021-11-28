<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class PathPaymentStrictReceiveOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private Asset $sendAsset;
    private string $sendMax;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private String $destAmount;
    private ?array $path = null; // [Asset]

    public function __construct(Asset $sendAsset, string $sendMax, string $destinationAccountId, Asset $destAsset, string $destAmount) {
        $this->sendAsset = $sendAsset;
        $this->sendMax = $sendMax;
        $this->destination = new MuxedAccount($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destAmount = $destAmount;
    }

    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendMax, MuxedAccount $destination, Asset $destAsset, string $destAmount) : PathPaymentStrictReceiveOperationBuilder {
        return new PathPaymentStrictReceiveOperationBuilder($sendAsset, $sendMax, $destination->getAccountId(), $destAsset, $destAmount);
    }

    public function setSourceAccount(string $accountId) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setPath(array $path) : PathPaymentStrictReceiveOperationBuilder {
        $this->path = array();
        foreach ($path as $asset) {
            if ($asset instanceof Asset) {
                array_push($this->path, $asset);
            }
        }
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): PathPaymentStrictReceiveOperation {
        $result = new PathPaymentStrictReceiveOperation($this->sendAsset, $this->sendMax, $this->destination, $this->destAsset, $this->destAmount, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}