<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

class CustomerInformationNeededResponse
{
    /**
     * @var array<string> $fields A list of field names that need to be transmitted via SEP-12 for the deposit to proceed.
     */
    public array $fields = array();

    /**
     * Constructs a new instance of CustomerInformationNeededResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return CustomerInformationNeededResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : CustomerInformationNeededResponse
    {
        $result = new CustomerInformationNeededResponse();
        if (isset($json['fields'])) {
            foreach ($json['fields'] as $field) {
                $result->fields[] = $field;
            }
        }
        return $result;
    }

}