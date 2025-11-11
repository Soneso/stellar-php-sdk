<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Builder for creating PathPaymentStrictSend operations.
 *
 * This builder implements the builder pattern to construct PathPaymentStrictSendOperation
 * instances with a fluent interface. This operation sends a payment where the amount sent
 * is specified, and the destination receives assets converted through a path.
 *
 * @package Soneso\StellarSDK
 * @see PathPaymentStrictSendOperation
 * @see https://developers.stellar.org/docs/fundamentals-and-concepts/list-of-operations#path-payment-strict-send
 * @since 1.0.0
 *
 * @example
 * $operation = (new PathPaymentStrictSendOperationBuilder($sendAsset, '100', $destId, $destAsset, '95'))
 *     ->setPath([$intermediateAsset1, $intermediateAsset2])
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class PathPaymentStrictSendOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @var Asset The asset being sent
     */
    private Asset $sendAsset;

    /**
     * @var string The amount of send asset to send
     */
    private string $sendAmount;

    /**
     * @var MuxedAccount The destination account
     */
    private MuxedAccount $destination;

    /**
     * @var Asset The asset the destination receives
     */
    private Asset $destAsset;

    /**
     * @var string The minimum amount of destination asset to receive
     */
    private string $destMin;

    /**
     * @var array<Asset>|null The intermediate assets in the payment path
     */
    private ?array $path = null;

    /**
     * Creates a new PathPaymentStrictSend operation builder.
     *
     * @param Asset $sendAsset The asset being sent
     * @param string $sendAmount The amount of send asset to send
     * @param string $destinationAccountId The destination account ID
     * @param Asset $destAsset The asset the destination receives
     * @param string $destMin The minimum amount of destination asset to receive
     */
    public function __construct(Asset $sendAsset, string $sendAmount, string $destinationAccountId, Asset $destAsset, string $destMin) {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = MuxedAccount::fromAccountId($destinationAccountId);
        $this->destAsset = $destAsset;
        $this->destMin = $destMin;
    }

    /**
     * Creates a builder for a muxed destination account.
     *
     * @param Asset $sendAsset The asset being sent
     * @param string $sendAmount The amount of send asset to send
     * @param MuxedAccount $destination The muxed destination account
     * @param Asset $destAsset The asset the destination receives
     * @param string $destMin The minimum amount of destination asset to receive
     * @return PathPaymentStrictSendOperationBuilder The builder instance
     */
    public static function forMuxedDestinationAccount(Asset $sendAsset, string $sendAmount, MuxedAccount $destination, Asset $destAsset, string $destMin): PathPaymentStrictSendOperationBuilder{
        return new PathPaymentStrictSendOperationBuilder($sendAsset, $sendAmount, $destination->getAccountId(), $destAsset, $destMin);
    }

    /**
     * Sets path for this operation
     * @param array<Asset> $path The assets (other than send asset and destination asset) involved in the offers the path takes. For example, if you can only find a path from USD to EUR through XLM and BTC, the path would be USD -&raquo; XLM -&raquo; BTC -&raquo; EUR and the path field would contain XLM and BTC.
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
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : PathPaymentStrictSendOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : PathPaymentStrictSendOperationBuilder  {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the PathPaymentStrictSend operation.
     *
     * @return PathPaymentStrictSendOperation The constructed operation
     */
    public function build(): PathPaymentStrictSendOperation {
        $result = new PathPaymentStrictSendOperation($this->sendAsset, $this->sendAmount, $this->destination, $this->destAsset, $this->destMin, $this->path);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}