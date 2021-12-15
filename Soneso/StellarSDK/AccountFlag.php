<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class AccountFlag
{
    const AUTH_REQUIRED_FLAG = 1;
    const AUTH_REVOCABLE_FLAG = 2;
    const AUTH_IMMUTABLE_FLAG = 4;
    const AUTH_CLAWBACK_ENABLED_FLAG = 8;
}