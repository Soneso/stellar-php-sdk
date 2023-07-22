<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizedInvocation
{

    public XdrSorobanAuthorizedFunction $function; // hex
    public array $subInvocations; // [XdrSorobanAuthorizedFunction]

    /**
     * @param XdrSorobanAuthorizedFunction $function
     * @param array $subInvocations
     */
    public function __construct(XdrSorobanAuthorizedFunction $function, array $subInvocations)
    {
        $this->function = $function;
        $this->subInvocations = $subInvocations;
    }


    public function encode(): string {
        $bytes = $this->function->encode();
        $bytes .= XdrEncoder::integer32(count($this->subInvocations));
        foreach($this->subInvocations as $val) {
            if ($val instanceof XdrSorobanAuthorizedInvocation) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanAuthorizedInvocation {
        $function = XdrSorobanAuthorizedFunction::decode($xdr);
        $valCount = $xdr->readInteger32();
        $sub = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($sub, XdrSorobanAuthorizedInvocation::decode($xdr));
        }
        return new XdrSorobanAuthorizedInvocation($function, $sub);
    }

    /**
     * @return XdrSorobanAuthorizedFunction
     */
    public function getFunction(): XdrSorobanAuthorizedFunction
    {
        return $this->function;
    }

    /**
     * @param XdrSorobanAuthorizedFunction $function
     */
    public function setFunction(XdrSorobanAuthorizedFunction $function): void
    {
        $this->function = $function;
    }

    /**
     * @return array
     */
    public function getSubInvocations(): array
    {
        return $this->subInvocations;
    }

    /**
     * @param array $subInvocations
     */
    public function setSubInvocations(array $subInvocations): void
    {
        $this->subInvocations = $subInvocations;
    }

}