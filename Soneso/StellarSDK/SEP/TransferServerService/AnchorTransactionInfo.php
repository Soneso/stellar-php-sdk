<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class AnchorTransactionInfo extends Response {

    private bool $enabled;
    private ?bool $authenticationRequired = null;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool|null
     */
    public function getAuthenticationRequired(): ?bool
    {
        return $this->authenticationRequired;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $this->authenticationRequired = $json['authentication_required'];
    }

    public static function fromJson(array $json) : AnchorTransactionInfo
    {
        $result = new AnchorTransactionInfo();
        $result->loadFromJson($json);
        return $result;
    }
}