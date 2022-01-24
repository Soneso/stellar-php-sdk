<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class CustomerInformationNeededResponse extends Response
{
    private array $fields = array();

    /**
     * /// A list of field names [string] that need to be transmitted via SEP-12 for the deposit to proceed.
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['fields'])) {
            foreach ($json['fields'] as $field) {
                array_push($this->fields, $field);
            }
        }
    }

    public static function fromJson(array $json) : CustomerInformationNeededResponse
    {
        $result = new CustomerInformationNeededResponse();
        $result->loadFromJson($json);
        return $result;
    }

}