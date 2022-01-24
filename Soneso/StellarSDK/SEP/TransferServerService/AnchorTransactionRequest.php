<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class AnchorTransactionRequest {

    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// (optional) The id of the transaction.
    public ?string $id = null;

    /// (optional) The stellar transaction id of the transaction.
    public ?string $stallarTransactionId = null;

    /// (optional) The external transaction id of the transaction.
    public ?string $externalTransactionId = null;
}