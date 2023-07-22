<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;


class SorobanAuthorizedInvocation
{
    public SorobanAuthorizedFunction $function;
    public array $subInvocations; // [SorobanAuthorizedInvocation]

    /**
     * @param SorobanAuthorizedFunction $function
     * @param array $subInvocations
     */
    public function __construct(SorobanAuthorizedFunction $function, array $subInvocations = array())
    {
        $this->function = $function;
        $this->subInvocations = $subInvocations;
    }


    public static function fromXdr(XdrSorobanAuthorizedInvocation $xdr) : SorobanAuthorizedInvocation {
        $subs = array();
        foreach ($xdr->getSubInvocations() as $sub) {
            if($sub instanceof  XdrSorobanAuthorizedInvocation) {
                array_push($subs, SorobanAuthorizedInvocation::fromXdr($sub));
            }
        }
        return new SorobanAuthorizedInvocation(SorobanAuthorizedFunction::fromXdr($xdr->function), $subs);
    }

    public function toXdr() : XdrSorobanAuthorizedInvocation {
        $subs = array();
        foreach ($this->subInvocations as $sub) {
            if($sub instanceof  SorobanAuthorizedInvocation) {
                array_push($subs, $sub->toXdr());
            }
        }
        return new XdrSorobanAuthorizedInvocation($this->function->toXdr(), $subs);
    }

    /**
     * @return SorobanAuthorizedFunction
     */
    public function getFunction(): SorobanAuthorizedFunction
    {
        return $this->function;
    }

    /**
     * @param SorobanAuthorizedFunction $function
     */
    public function setFunction(SorobanAuthorizedFunction $function): void
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