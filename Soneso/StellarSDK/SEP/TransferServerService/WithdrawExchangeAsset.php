<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class WithdrawExchangeAsset
{
    /**
     * @var bool $enabled true if SEP-6 withdrawal exchange for this asset is supported
     */
    public bool $enabled;

    /**
     * @var bool|null $authenticationRequired Optional. true if client must be authenticated before accessing
     * the withdraw endpoint for this asset. false if not specified.
     */
    public ?bool $authenticationRequired = null;

    /**
     * A field with each type of withdrawal supported for that asset as a key.
     * Each type can specify a fields object explaining what fields
     * are needed and what they do. Anchors are encouraged to use SEP-9
     * financial account fields, but can also define custom fields if necessary.
     * If a fields object is not specified, the wallet should assume that no
     * extra fields are needed for that type of withdrawal. In the case that
     * the Anchor requires additional fields for a withdrawal, it should set the
     * transaction status to pending_customer_info_update. The wallet can query
     * the /transaction endpoint to get the fields needed to complete the
     * transaction in required_customer_info_updates and then use SEP-12 to
     * collect the information from the user.
     * @var array<array-key, array<array-key, AnchorField>|null>|null
     */
    public ?array $types = null;

    /**
     * @param bool $enabled true if SEP-6 withdrawal exchange for this asset is supported
     */
    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Constructs a new instance of WithdrawAsset by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return WithdrawExchangeAsset the object containing the parsed data.
     */
    public static function fromJson(array $json) : WithdrawExchangeAsset
    {
        $enabled = false;
        if (isset($json['enabled'])) $enabled = $json['enabled'];

        $result = new WithdrawExchangeAsset($enabled);

        if (isset($json['authentication_required'])) $result->authenticationRequired = $json['authentication_required'];
        if (isset($json['types'])) {
            $result->types = array();
            $typesFields = $json['types'];
            foreach(array_keys($typesFields) as $typeKey) {

                if ($typesFields[$typeKey]['fields']) {
                    $fields = array();
                    foreach(array_keys($typesFields[$typeKey]['fields']) as $fieldKey) {
                        $value = AnchorField::fromJson($typesFields[$typeKey]['fields'][$fieldKey]);
                        $fields += [$fieldKey => $value];
                    }
                    $result->types += [$typeKey => $fields];
                } else {
                    $result->types += [$typeKey => null];
                }
            }
        }
        return $result;
    }
}