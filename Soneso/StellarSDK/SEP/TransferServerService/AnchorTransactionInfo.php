<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class AnchorTransactionInfo {

    /**
     * @var bool|null true if the endpoint is available.
     */
    public ?bool $enabled = null;

    /**
     * @var bool|null true if client must be authenticated before accessing the endpoint.
     */
    public ?bool $authenticationRequired = null;

    /**
     * Constructs a new instance of AnchorTransactionInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorTransactionInfo the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorTransactionInfo
    {
        $result = new AnchorTransactionInfo();
        if (isset($json['enabled'])) $result->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $result->authenticationRequired = $json['authentication_required'];
        return $result;
    }
}