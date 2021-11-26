<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Errors;

use Soneso\StellarSDK\Responses\Response;

class HorizonErrorResponse extends Response
{
    private string $type;
    private string $title;
    private int $status;
    private string $detail;
    private ?string $instance = null;
    private ?HorizonErrorResponseExtras $extras = null;

    /**
     * The type of Status Code returned (URL to lookup for more information).
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * A short title describing the Status Code, which can be used to look up more information about an error.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Status Code.
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Details about the error.
     * @return string
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * If the Status Code is Transaction Failed, this extras field displays the Result Code returned by Stellar Core describing why the transaction failed.
     * @return HorizonErrorResponseExtras|null
     */
    public function getExtras(): ?HorizonErrorResponseExtras
    {
        return $this->extras;
    }

    /**
     * Horizon instance if any.
     * @return string|null
     */
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['title'])) $this->title = $json['title'];
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['detail'])) $this->detail = $json['detail'];
        if (isset($json['instance'])) $this->instance = $json['instance'];
        if (isset($json['extras'])) $this->extras = HorizonErrorResponseExtras::fromJson($json['extras']);
    }

    public static function fromJson(array $json) : HorizonErrorResponse
    {
        $result = new HorizonErrorResponse();
        $result->loadFromJson($json);
        return $result;
    }
}