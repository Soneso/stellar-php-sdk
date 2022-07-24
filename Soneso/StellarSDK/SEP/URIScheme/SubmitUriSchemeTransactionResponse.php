<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;

class SubmitUriSchemeTransactionResponse
{

    private ?SubmitTransactionResponse $submitTransactionResponse = null;
    private ?ResponseInterface $callBackResponse = null;

    /**
     * @param SubmitTransactionResponse|null $submitTransactionResponse
     * @param ResponseInterface|null $callBackResponse
     */
    public function __construct(?SubmitTransactionResponse $submitTransactionResponse, ?ResponseInterface $callBackResponse)
    {
        $this->submitTransactionResponse = $submitTransactionResponse;
        $this->callBackResponse = $callBackResponse;
    }

    /**
     * @return SubmitTransactionResponse|null
     */
    public function getSubmitTransactionResponse(): ?SubmitTransactionResponse
    {
        return $this->submitTransactionResponse;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getCallBackResponse(): ?ResponseInterface
    {
        return $this->callBackResponse;
    }

}