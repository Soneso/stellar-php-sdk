<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionResponse;

class SEP08PostActionNextUrl extends SEP08PostActionResponse
{
    /**
     * @var string A URL where the user can complete the required actions with all the
     * parameters included in the original POST pre-filled or already accepted.
     */
    public String $nextUrl;
    /**
     * @var string|null (optional) A human-readable string containing information
     * regarding the further action required.
     */
    public ?String $message = null;

    /**
     * Constructor
     * @param String $nextUrl A URL where the user can complete the required actions with all the
     *  parameters included in the original POST pre-filled or already accepted.
     * @param String|null $message (optional) A human-readable string containing information
     *  regarding the further action required.
     */
    public function __construct(string $nextUrl, ?string $message)
    {
        $this->nextUrl = $nextUrl;
        $this->message = $message;
    }

}