<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builds PathPaymentStrictReceive operation.
 * @see PathPaymentStrictReceiveOperation
 */
class PathPaymentStrictReceiveOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private Asset $sendAsset;
    private string $sendMax;
    private MuxedAccount $destination;
    private Asset $destAsset;
    private String $destAmount;
    private ?array $path = null; // [Asset]

    /**
     * Creates a new PathPaymentStrictReceiveOperation builder.
     * @param Asset $sendAsset The asset deducted from the sender's account.
     * @param string $sendMax The asset deducted from the sender's account.
     * @param string $destinationAccountId Payment destination.
     * @param Asset $destAsset The asset the destination account receives.
     * @param string $destAmount The amount of destination asset the destination account receives.
     */
    public function __construct(Asset $sendAsset, string $sendMax, string $destinationAccountId, Asset $destAsset, string $destAmount) {
        $this->sendAsset = $sendAsset;
        $this->sendMax = $sendMax;
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destAmount = $destAmount;
    }

    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendMax, MuxedAccount $destination, Asset $destAsset, string $destAmount) : PathPaymentStrictReceiveOperationBuilder {
        return new PathPaymentStrictReceiveOperationBuilder($sendAsset, $sendMax, $destination->getAccountId(), $destAsset, $destAmount);
    }

    /**
     * @param array $path The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path field would contain XLM and BTC.
     * @return PathPaymentStrictReceiveOperationBuilder Builder object so you can chain methods.
     */
    public function setPath(array $path) : PathPaymentStrictReceiveOperationBuilder {
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
     * @return PathPaymentStrictReceiveOperationBuilder Builder object so you can chain methods
     */
    public function setSourceAccount(string $accountId) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     * @param MuxedAccount $sourceAccount The operation's source account.
     * @return PathPaymentStrictReceiveOperationBuilder Builder object so you can chain methods
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds an operation.
     * @return PathPaymentStrictReceiveOperation
     */
    public function build(): PathPaymentStrictReceiveOperation {
        $result = new PathPaymentStrictReceiveOperation($this->sendAsset, $this->sendMax, $this->destination, $this->destAsset, $this->destAmount, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}