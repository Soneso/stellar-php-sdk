<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class AnchorTransactionRequest {

    /**
     * @var string|null $id (optional) The id of the transaction.
     */
    public ?string $id = null;

    /**
     * @var string|null $stellarTransactionId (optional) The stellar transaction id of the transaction.
     */
    public ?string $stellarTransactionId = null;

    /**
     * @var string|null $externalTransactionId (optional) The external transaction id of the transaction.
     */
    public ?string $externalTransactionId = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language
     * is not supported. Language code specified using RFC 4646. Error fields and other human readable messages
     * in the response should be in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public ?string $jwt = null;
}