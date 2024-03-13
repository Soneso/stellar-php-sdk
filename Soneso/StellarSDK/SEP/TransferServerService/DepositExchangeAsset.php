<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class DepositExchangeAsset
{
    /**
     * @var bool $enabled true if SEP-6 deposit exchange for this asset is supported
     */
    public bool $enabled;
    /**
     * @var bool|null $authenticationRequired Optional. true if client must be authenticated before accessing the
     * deposit endpoint for this asset. false if not specified.
     */
    public ?bool $authenticationRequired = null;

    /**
     * @var array<string, AnchorField>|null $fields (Deprecated) Accepting personally identifiable information through
     * request parameters is a security risk due to web server request logging.
     * KYC information should be supplied to the Anchor via SEP-12).
     */
    public ?array $fields = null;

    /**
     * @param bool $enabled true if SEP-6 deposit exchange for this asset is supported
     */
    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Constructs a new instance of DepositAsset by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return DepositExchangeAsset the object containing the parsed data.
     */
    public static function fromJson(array $json) : DepositExchangeAsset
    {
        $enabled = false;
        if (isset($json['enabled'])) $enabled = $json['enabled'];

        $result = new DepositExchangeAsset($enabled);

        if (isset($json['authentication_required'])) $result->authenticationRequired = $json['authentication_required'];
        if (isset($json['fields'])) {
            $result->fields = array();
            $jsonFields = $json['fields'];
            foreach(array_keys($jsonFields) as $key) {
                $value = AnchorField::fromJson($jsonFields[$key]);
                $result->fields += [$key => $value];
            }
        }
        return $result;
    }
}