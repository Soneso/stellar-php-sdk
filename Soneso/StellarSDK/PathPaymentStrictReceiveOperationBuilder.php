<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating PathPaymentStrictReceive operations.
 *
 * This builder implements the builder pattern to construct PathPaymentStrictReceiveOperation
 * instances with a fluent interface. PathPaymentStrictReceive operations send assets through
 * a path of offers on the Stellar decentralized exchange, guaranteeing the exact amount received
 * by the destination account.
 *
 * @package Soneso\StellarSDK
 * @see PathPaymentStrictReceiveOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#path-payment-strict-receive
 * @since 1.0.0
 *
 * @example
 * $operation = (new PathPaymentStrictReceiveOperationBuilder($sendAsset, '100.00', $destId, $destAsset, '95.00'))
 *     ->setPath([$intermediateAsset1, $intermediateAsset2])
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class PathPaymentStrictReceiveOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var Asset The asset to be sent (deducted from sender's account)
     */
    private Asset $sendAsset;

    /**
     * @var string The maximum amount of the send asset to deduct (excluding fees)
     */
    private string $sendMax;

    /**
     * @var MuxedAccount The destination account receiving the payment
     */
    private MuxedAccount $destination;

    /**
     * @var Asset The asset to be received by the destination account
     */
    private Asset $destAsset;

    /**
     * @var string The exact amount of destination asset to be received
     */
    private String $destAmount;

    /**
     * @var array<Asset>|null The intermediate assets in the payment path
     */
    private ?array $path = null;

    /**
     * Creates a new PathPaymentStrictReceive operation builder.
     *
     * @param Asset $sendAsset The asset deducted from the sender's account
     * @param string $sendMax The maximum amount of send asset to deduct
     * @param string $destinationAccountId Payment destination account ID
     * @param Asset $destAsset The asset the destination account receives
     * @param string $destAmount The amount of destination asset the destination account receives
     */
    public function __construct(Asset $sendAsset, string $sendMax, string $destinationAccountId, Asset $destAsset, string $destAmount) {
        $this->sendAsset = $sendAsset;
        $this->sendMax = $sendMax;
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destAmount = $destAmount;
    }

    /**
     * Creates a new PathPaymentStrictReceive operation builder for a muxed destination account.
     *
     * @param Asset $sendAsset The asset deducted from the sender's account
     * @param string $sendMax The maximum amount of send asset to deduct
     * @param MuxedAccount $destination The muxed destination account
     * @param Asset $destAsset The asset the destination account receives
     * @param string $destAmount The amount of destination asset the destination account receives
     * @return PathPaymentStrictReceiveOperationBuilder The new builder instance
     */
    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendMax, MuxedAccount $destination, Asset $destAsset, string $destAmount) : PathPaymentStrictReceiveOperationBuilder {
        return new PathPaymentStrictReceiveOperationBuilder($sendAsset, $sendMax, $destination->getAccountId(), $destAsset, $destAmount);
    }

    /**
     * Sets the payment path through intermediate assets.
     *
     * The assets (other than send asset and destination asset) involved in the offers the path takes.
     * For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be
     * USD -> XLM -> BTC -> EUR and the path field would contain XLM and BTC.
     *
     * @param array<Asset> $path Array of intermediate assets in the payment path
     * @return $this Returns the builder instance for method chaining
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
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PathPaymentStrictReceiveOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the PathPaymentStrictReceive operation.
     *
     * @return PathPaymentStrictReceiveOperation The constructed operation
     */
    public function build(): PathPaymentStrictReceiveOperation {
        $result = new PathPaymentStrictReceiveOperation($this->sendAsset, $this->sendMax, $this->destination, $this->destAsset, $this->destAmount, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}