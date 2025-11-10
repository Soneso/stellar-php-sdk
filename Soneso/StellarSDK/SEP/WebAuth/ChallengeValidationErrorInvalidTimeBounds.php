<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction time bounds are invalid or expired.
 *
 * Thrown when the current time falls outside the challenge transaction's time bounds
 * (accounting for grace period). SEP-10 recommends time bounds of approximately 15 minutes
 * to give clients time to sign, while preventing indefinite challenge validity. This validation
 * ensures challenges are used within their intended timeframe.
 *
 * Security Implications:
 * Time bounds validation prevents replay attacks and ensures challenges have limited validity.
 * Without time bounds, a stolen challenge could be used indefinitely to gain unauthorized access.
 * Strict time bounds enforcement limits the window of opportunity for attackers who intercept
 * challenges. The typical 15-minute window balances security (short validity) with usability
 * (enough time for hardware wallet signing).
 *
 * Common Scenarios:
 * - Client took too long to sign challenge (e.g., delayed hardware wallet confirmation)
 * - Challenge intercepted and replayed after expiration
 * - Clock synchronization issues between client and server
 * - Challenge used outside the valid time window (before minTime or after maxTime)
 * - Network delays causing challenge to expire during transmission
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Time Bounds Requirements
 */
class ChallengeValidationErrorInvalidTimeBounds extends \ErrorException
{

}