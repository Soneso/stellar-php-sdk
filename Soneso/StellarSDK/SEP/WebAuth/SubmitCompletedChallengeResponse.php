<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Soneso\StellarSDK\Responses\Response;

class SubmitCompletedChallengeResponse extends Response {

    private ?string $jwtToken = null;
    private ?string $error = null;

    /**
     * @return string|null
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $jwtToken
     */
    public function setJwtToken(?string $jwtToken): void
    {
        $this->jwtToken = $jwtToken;
    }

    /**
     * @param string|null $error
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['token'])) $this->jwtToken = $json['token'];
        if (isset($json['error'])) $this->error = $json['error'];
    }

    public static function fromJson(array $json) : SubmitCompletedChallengeResponse
    {
        $result = new SubmitCompletedChallengeResponse();
        $result->loadFromJson($json);
        return $result;
    }

}