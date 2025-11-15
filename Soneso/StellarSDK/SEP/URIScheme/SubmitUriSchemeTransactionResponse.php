<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;

/**
 * Response container for SEP-7 transaction submission operations.
 *
 * This class encapsulates the result of signAndSubmitTransaction() operations.
 * Depending on whether a callback URL was specified in the SEP-7 URI, the response
 * will contain either a Horizon transaction submission response or an HTTP callback response.
 *
 * Only one of the two response properties will be populated:
 * - submitTransactionResponse: Set when transaction submitted directly to Stellar network
 * - callBackResponse: Set when transaction POSTed to callback URL
 *
 * @package Soneso\StellarSDK\SEP\URIScheme
 */
class SubmitUriSchemeTransactionResponse
{
    /**
     * Creates a new response container with exactly one response populated.
     *
     * Either submitTransactionResponse or callBackResponse will be non-null,
     * but never both. This reflects the mutually exclusive submission paths.
     *
     * @param SubmitTransactionResponse|null $submitTransactionResponse Horizon response when transaction submitted directly to Stellar network. Null if transaction was sent to callback URL instead.
     * @param ResponseInterface|null $callBackResponse HTTP response from callback URL when transaction submitted via callback. Null if transaction was submitted directly to network instead.
     */
    public function __construct(
        private ?SubmitTransactionResponse $submitTransactionResponse = null,
        private ?ResponseInterface $callBackResponse = null,
    ) {
    }

    /**
     * Returns the Horizon transaction submission response.
     *
     * Non-null only when transaction was submitted directly to Stellar network
     * (no callback URL specified in SEP-7 URI).
     *
     * @return SubmitTransactionResponse|null Transaction submission result or null
     */
    public function getSubmitTransactionResponse(): ?SubmitTransactionResponse
    {
        return $this->submitTransactionResponse;
    }

    /**
     * Returns the HTTP callback response.
     *
     * Non-null only when transaction was POSTed to callback URL
     * (callback parameter present in SEP-7 URI).
     *
     * @return ResponseInterface|null HTTP response from callback or null
     */
    public function getCallBackResponse(): ?ResponseInterface
    {
        return $this->callBackResponse;
    }

}