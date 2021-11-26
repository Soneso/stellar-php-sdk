<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class PathPaymentStrictSendOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private Asset $sendAsset;
    private string $sendAmount;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private string $destMin;
    private ?array $path = null; // [Asset]

    public function __construct(Asset $sendAsset, string $sendAmount, string $destinationAccountId, Asset $destAsset, string $destMin)
    {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = new MuxedAccount($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destMin = $destMin;
    }

    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendAmount, MuxedAccount $destination, Asset $destAsset, string $destMin): PathPaymentStrictSendOperationBuilder
    {
        return new PathPaymentStrictSendOperationBuilder($sendAsset, $sendAmount, $destination->getAccountId(), $destAsset, $destMin);
    }

    public function setSourceAccount(string $accountId)
    {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setPath(array $path)
    {
        $this->path = array();
        foreach ($path as $asset) {
            if ($asset instanceof Asset) {
                array_push($this->path, $asset);
            }
        }
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount)
    {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): PathPaymentStrictSendOperation
    {
        $result = new PathPaymentStrictSendOperation($this->sendAsset, $this->sendAmount, $this->destination, $this->destAsset, $this->destMin, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}