<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a cryptographic operation fails.
 *
 * This includes failures in signing, key derivation, or other
 * operations in the SDK's cryptographic layer.
 */
class CryptoException extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
