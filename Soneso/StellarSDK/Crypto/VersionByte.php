<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

class VersionByte
{
    const ACCOUNT_ID = 6 << 3; // G
    const MUXED_ACCOUNT_ID = 12 << 3; // M
    const SEED = 18 << 3; // S
    const PRE_AUTH_TX = 19 << 3; // T
    const SHA256_HASH = 23 << 3; //X
    const SIGNED_PAYLOAD = 15 << 3; // P
    const CONTRACT_ID = 2 << 3; // C
}