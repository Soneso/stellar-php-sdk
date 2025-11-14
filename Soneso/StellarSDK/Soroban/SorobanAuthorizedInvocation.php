<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedInvocation;

/**
 * Authorized invocation tree for Soroban authorization
 *
 * This class represents a node in the tree of authorized invocations. Each node contains
 * a function to be authorized and a list of sub-invocations that the function is allowed
 * to make. This creates a complete authorization tree for complex contract call chains.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAuthorizedFunction
 * @see SorobanAuthorizationEntry
 * @see https://developers.stellar.org/docs/learn/smart-contract-internals/authorization Soroban Authorization
 * @since 1.0.0
 */
class SorobanAuthorizedInvocation
{
    /**
     * @var SorobanAuthorizedFunction the function being authorized at this node
     */
    public SorobanAuthorizedFunction $function;

    /**
     * @var array<SorobanAuthorizedInvocation> sub-invocations this function is authorized to make
     */
    public array $subInvocations;

    /**
     * Creates a new authorized invocation node.
     *
     * @param SorobanAuthorizedFunction $function the function to authorize
     * @param array<SorobanAuthorizedInvocation> $subInvocations authorized sub-invocations (defaults to empty)
     */
    public function __construct(SorobanAuthorizedFunction $function, array $subInvocations = array())
    {
        $this->function = $function;
        $this->subInvocations = $subInvocations;
    }

    /**
     * Creates SorobanAuthorizedInvocation from its XDR representation.
     *
     * @param XdrSorobanAuthorizedInvocation $xdr the XDR object to decode
     * @return SorobanAuthorizedInvocation the decoded authorized invocation tree
     */
    public static function fromXdr(XdrSorobanAuthorizedInvocation $xdr) : SorobanAuthorizedInvocation {
        $subs = array();
        foreach ($xdr->getSubInvocations() as $sub) {
            if($sub instanceof  XdrSorobanAuthorizedInvocation) {
                array_push($subs, SorobanAuthorizedInvocation::fromXdr($sub));
            }
        }
        return new SorobanAuthorizedInvocation(SorobanAuthorizedFunction::fromXdr($xdr->function), $subs);
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAuthorizedInvocation the XDR encoded invocation tree
     */
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
     * Returns the function being authorized.
     *
     * @return SorobanAuthorizedFunction the authorized function
     */
    public function getFunction(): SorobanAuthorizedFunction
    {
        return $this->function;
    }

    /**
     * Sets the function being authorized.
     *
     * @param SorobanAuthorizedFunction $function the authorized function
     */
    public function setFunction(SorobanAuthorizedFunction $function): void
    {
        $this->function = $function;
    }

    /**
     * Returns the authorized sub-invocations.
     *
     * @return array<SorobanAuthorizedInvocation> the list of authorized sub-invocations
     */
    public function getSubInvocations(): array
    {
        return $this->subInvocations;
    }

    /**
     * Sets the authorized sub-invocations.
     *
     * @param array<SorobanAuthorizedInvocation> $subInvocations the list of authorized sub-invocations
     */
    public function setSubInvocations(array $subInvocations): void
    {
        $this->subInvocations = $subInvocations;
    }
}