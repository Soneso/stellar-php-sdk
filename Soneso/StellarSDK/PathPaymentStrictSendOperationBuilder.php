<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builds PathPayment operation.
 * @see PathPaymentStrictSendOperation
 */
class PathPaymentStrictSendOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private Asset $sendAsset;
    private string $sendAmount;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private string $destMin;
    private ?array $path = null; // [Asset]

    /**
     * Creates a new PathPaymentStrictSendOperation builder.
     * @param Asset $sendAsset The asset deducted from the sender's account.
     * @param string $sendAmount The asset deducted from the sender's account.
     * @param string $destinationAccountId Payment destination
     * @param Asset $destAsset The asset the destination account receives.
     * @param string $destMin The minimum amount of destination asset the destination account receives.
     */
    public function __construct(Asset $sendAsset, string $sendAmount, string $destinationAccountId, Asset $destAsset, string $destMin) {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destMin = $destMin;
    }

    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendAmount, MuxedAccount $destination, Asset $destAsset, string $destMin): PathPaymentStrictSendOperationBuilder{
        return new PathPaymentStrictSendOperationBuilder($sendAsset, $sendAmount, $destination->getAccountId(), $destAsset, $destMin);
    }

    /**
     * Sets path for this operation
     * @param array $path The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path field would contain XLM and BTC.
     * @return PathPaymentStrictSendOperationBuilder object so you can chain methods.
     */
    public function setPath(array $path) : PathPaymentStrictSendOperationBuilder {
        $this->path = array();
        foreach ($path as $asset) {
            if ($asset instanceof Asset) {
                array_push($this->path, $asset);
            }
        }
        return $this;
    }

    /**
     * Sets the source account for this operation. G...
     * @param string $accountId The operation's source account.
     * @return PathPaymentStrictSendOperationBuilder Builder object so you can chain methods
     */
    public function setSourceAccount(string $accountId) : PathPaymentStrictSendOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's source account.
     * @return PathPaymentStrictSendOperationBuilder Builder object so you can chain methods
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PathPaymentStrictSendOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation.
     * @return PathPaymentStrictSendOperation
     */
    public function build(): PathPaymentStrictSendOperation {
        $result = new PathPaymentStrictSendOperation($this->sendAsset, $this->sendAmount, $this->destination, $this->destAsset, $this->destMin, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}