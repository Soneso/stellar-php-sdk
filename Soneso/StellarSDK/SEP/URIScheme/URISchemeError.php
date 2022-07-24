<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

use ErrorException;

class URISchemeError extends ErrorException
{
    const invalidSignature = 0;
    const invalidOriginDomain = 1;
    const missingOriginDomain = 2;
    const missingSignature = 3;
    const tomlNotFoundOrInvalid = 4;
    const tomlSignatureMissing = 5;

    public function toString() : string {
        return match ($this->code) {
            URISchemeError::invalidSignature => "URISchemeError: invalid Signature",
            URISchemeError::invalidOriginDomain => "URISchemeError: invalid Origin Domain",
            URISchemeError::missingOriginDomain => "URISchemeError: missing Origin Domain",
            URISchemeError::missingSignature => "URISchemeError: missing Signature",
            URISchemeError::tomlNotFoundOrInvalid => "URISchemeError: toml not found or invalid",
            URISchemeError::tomlSignatureMissing => "URISchemeError: Toml Signature Missing",
            default => "URISchemeError: unknown error",
        };
    }
}