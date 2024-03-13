<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class ExtraInfo
{
    /**
     * @var string|null $message Additional details about the process.
     */
    public ?string $message = null;

    /**
     * Constructs a new instance of ExtraInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return ExtraInfo the object containing the parsed data.
     */
    public static function fromJson(array $json) : ExtraInfo
    {
        $result = new ExtraInfo();
        if (isset($json['message'])) {
            $result->message = $json['message'];
        }
        return $result;
    }
}