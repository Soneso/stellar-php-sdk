<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Errors;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents an error response from Horizon
 *
 * Contains detailed information about errors returned by Horizon, including type, title, status code,
 * details, and optional extras with transaction failure information. Follows RFC 7807 Problem Details format.
 *
 * @package Soneso\StellarSDK\Responses\Errors
 * @see HorizonErrorResponseExtras For additional error details
 * @see https://developers.stellar.org/api/errors Horizon Error Handling
 * @since 1.0.0
 */
class HorizonErrorResponse extends Response
{
    public string $type;
    public string $title;
    public int $status;
    public string $detail;
    public ?string $instance = null;
    public ?HorizonErrorResponseExtras $extras = null;
    /**
     * @var array<string, mixed>|null all extras from the response as an array.
     */
    public ?array $extrasJson = null;

    /**
     * Gets the type of status code returned
     *
     * URL to lookup for more information about the error.
     *
     * @return string The type of status code
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets a short title describing the status code
     *
     * The title can be used to look up more information about the error.
     *
     * @return string The error title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Gets the HTTP status code
     *
     * @return int The status code
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Gets details about the error
     *
     * @return string The error details
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * Gets additional error details for transaction failures
     *
     * If the status code indicates a transaction failure, this field contains the result code
     * returned by Stellar Core describing why the transaction failed.
     *
     * @return HorizonErrorResponseExtras|null The extras object, or null if not available
     */
    public function getExtras(): ?HorizonErrorResponseExtras
    {
        return $this->extras;
    }

    /**
     * Gets the Horizon instance identifier
     *
     * @return string|null The instance identifier, or null if not available
     */
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * Gets all extras from the response as an array
     *
     * @return array<string,mixed>|null The extras array, or null if not available
     */
    public function getExtrasJson(): ?array
    {
        return $this->extrasJson;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['title'])) $this->title = $json['title'];
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['detail'])) $this->detail = $json['detail'];
        if (isset($json['instance'])) $this->instance = $json['instance'];
        if (isset($json['extras'])) $this->extras = HorizonErrorResponseExtras::fromJson($json['extras']);
        if (isset($json['extras'])) $this->extrasJson = $json['extras'];
    }

    public static function fromJson(array $json) : HorizonErrorResponse
    {
        $result = new HorizonErrorResponse();
        $result->loadFromJson($json);
        return $result;
    }
}