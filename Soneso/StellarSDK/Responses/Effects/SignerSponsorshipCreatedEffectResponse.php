<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class SignerSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $signer;
    private string $sponsor;

    /**
     * @return string
     */
    public function getSigner(): string
    {
        return $this->signer;
    }

    /**
     * @return string
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['signer'])) $this->signer = $json['signer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerSponsorshipCreatedEffectResponse {
        $result = new SignerSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}