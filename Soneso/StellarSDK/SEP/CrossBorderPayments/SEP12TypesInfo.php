<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

/**
 * An array containing the accepted sender and receiver values for the type parameter
 * in SEP-12 requests. This object is used in the context of SEP-31.
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