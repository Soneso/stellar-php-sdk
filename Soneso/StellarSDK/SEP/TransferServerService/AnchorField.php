<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Describes an input field required by the anchor for deposit or withdrawal operations.
 *
 * Contains metadata about a field that the user needs to provide, including a description
 * for display, whether the field is optional, and possible values to choose from.
 *
 * Used in asset definitions to specify what information is required for different
 * operation types. Anchors should prefer SEP-9 standard fields where applicable.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-9 v1.17.0 Financial Account Fields
 * @see DepositAsset
 * @see WithdrawAsset
 */
class AnchorField
{
    /**
     * @var string|null $description description of field to show to user.
     */
    public ?string $description = null;

    /**
     * @var bool|null $optional if field is optional. Defaults to false.
     */
    public ?bool $optional = null;

    /**
     * @var array<string>|null $choices list of possible values for the field.
     */
    public ?array $choices = null;

    /**
     * Constructs a new instance of AnchorField by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorField the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorField
    {
        $result = new AnchorField();
        if (isset($json['description'])) $result->description = $json['description'];
        if (isset($json['optional'])) $result->optional = $json['optional'];
        if (isset($json['choices'])) {
            $result->choices = array();
            foreach ($json['choices'] as $choice) {
                $result->choices[] = $choice;
            }
        }
        return $result;
    }
}