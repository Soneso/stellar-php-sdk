<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Describes the availability and requirements for the fee endpoint.
 *
 * Contains information about whether the fee endpoint is enabled and if
 * authentication is required to access it.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 */
class AnchorFeeInfo
{
    /**
     * @var bool|null true if the endpoint is available.
     */
    public ?bool $enabled = null;

    /**
     * @var bool|null true if client must be authenticated before accessing the endpoint.
     */
    public ?bool $authenticationRequired = null;

    /**
     * @var string|null Optional. Anchors are encouraged to add a description field to the
     * fee object returned in GET /info containing a short explanation of
     * how fees are calculated so client applications will be able to display
     * this message to their users. This is especially important if the
     * GET /fee endpoint is not supported and fees cannot be models using
     * fixed and percentage values for each Stellar asset.
     */
    public ?string $description = null;

    /**
     * Constructs a new instance of AnchorFeeInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorFeeInfo the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorFeeInfo
    {
        $result = new AnchorFeeInfo();
        if (isset($json['enabled'])) $result->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $result->authenticationRequired = $json['authentication_required'];
        if (isset($json['description'])) $result->description = $json['description'];
        return $result;
    }
}