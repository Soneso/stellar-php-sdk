<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * KYC type definitions for cross-border payment participants.
 *
 * This class represents the accepted SEP-12 customer types for senders and receivers
 * in cross-border payments. Each type maps to specific KYC field requirements that must
 * be satisfied via SEP-12 customer registration before initiating a transaction.
 *
 * @package Soneso\StellarSDK\SEP\CrossBorderPayments
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0012.md
 * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#get-info
 * @see SEP31ReceiveAssetInfo
 */
class SEP12TypesInfo
{
    /**
     * @var array<string,string> $senderTypes An array containing the accepted sender values for the type parameter
     * in SEP-12 requests. Each key maps to a human-readable description.
     */
    public array $senderTypes;

    /**
     * @var array<string,string> $receiverTypes An array containing the accepted receiver values for the type parameter
     *  in SEP-12 requests. Each key maps to a human-readable description.
     */
    public array $receiverTypes;

    /**
     * @param array<string,string> $senderTypes An array containing the accepted sender values for the type parameter
     *  in SEP-12 requests. Each key maps to a human-readable description.
     * @param array<string,string> $receiverTypes An array containing the accepted receiver values for the type parameter
     *  in SEP-12 requests. Each key maps to a human-readable description.
     */
    public function __construct(array $senderTypes, array $receiverTypes)
    {
        $this->senderTypes = $senderTypes;
        $this->receiverTypes = $receiverTypes;
    }


    /**
     * Constructs a new instance of SEP12TypesInfo by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP12TypesInfo the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP12TypesInfo {
        /**
         * @var array<string,string> $senderTypes
         */
        $senderTypes = array();
        if (isset($json['sender'])) {
            $senderJson = $json['sender'];
            if(isset($senderJson['types'])) {
                $senderTypesJson = $senderJson['types'];
                $keys = array_keys($senderTypesJson);
                foreach ($keys as $key) {
                    if (isset($senderTypesJson[$key]['description']))
                    $senderTypes[$key] = $senderTypesJson[$key]['description'];
                }
            }
        }

        /**
         * @var array<string,string> $receiverTypes
         */
        $receiverTypes = array();
        if (isset($json['receiver'])) {
            $receiverJson = $json['receiver'];
            if(isset($receiverJson['types'])) {
                $receiverTypesJson = $receiverJson['types'];
                $keys = array_keys($receiverTypesJson);
                foreach ($keys as $key) {
                    if (isset($receiverTypesJson[$key]['description']))
                        $receiverTypes[$key] = $receiverTypesJson[$key]['description'];
                }
            }
        }

        return new SEP12TypesInfo($senderTypes, $receiverTypes);
    }
}