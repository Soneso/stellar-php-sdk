<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

use Soneso\StellarSDK\Xdr\XdrSCVal;

class SegmentFilter
{
    public ?string $wildcard = null;
    public ?array $scval = null; // [XdrSCVal]

    /**
     * @param string|null $wildcard
     * @param array|null $scval
     */
    public function __construct(?string $wildcard = null, ?array $scval = null)
    {
        $this->wildcard = $wildcard;
        $this->scval = $scval;
    }

    public function getRequestParams() : array {
        $params = array();
        if ($this->wildcard != null) {
            $params['wildcard'] = $this->wildcard;
        }
        if ($this->scval != null) {
            $xdrValues = array();
            foreach ($this->scval as $xdrValue) {
                if ($xdrValue instanceof XdrSCVal) {
                    array_push($xdrValues, $xdrValue->toBase64Xdr());
                }
            }
            $params['scval'] = $xdrValues;
        }

        return $params;
    }

    /**
     * @return string|null
     */
    public function getWildcard(): ?string
    {
        return $this->wildcard;
    }

    /**
     * @param string|null $wildcard
     */
    public function setWildcard(?string $wildcard): void
    {
        $this->wildcard = $wildcard;
    }

    /**
     * @return array|null
     */
    public function getScval(): ?array
    {
        return $this->scval;
    }

    /**
     * @param array|null $scval
     */
    public function setScval(?array $scval): void
    {
        $this->scval = $scval;
    }

}