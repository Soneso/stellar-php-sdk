<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrAuthorizedInvocation;

/**
 * Represents an authorized invocation.
 * See Soroban Documentation - Authorization <https://soroban.stellar.org/docs/learn/authorization> for more information.
 */
class AuthorizedInvocation
{
    public string $contractId; // hex
    public string $functionName;
    public array $args; // [XdrSCVal]
    public array $subInvocations; // [AuthorizedInvocation]

    /**
     * @param string $contractId The ID of the contract to invoke.
     * @param string $functionName The name of the function to invoke.
     * @param ?array $args The arguments to pass to the function. array of XdrSCVal.
     * @param ?array $subInvocations The sub-invocations to pass to the function. array of AuthorizedInvocation.
     */
    public function __construct(string $contractId, string $functionName, ?array $args = array(), ?array $subInvocations = array())
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->args = $args;
        $this->subInvocations = $subInvocations;
    }

    public static function fromXdr(XdrAuthorizedInvocation $xdr) : AuthorizedInvocation {
        $subs = array();
        foreach ($xdr->getSubInvocations() as $sub) {
            if($sub instanceof  XdrAuthorizedInvocation) {
                array_push($subs, AuthorizedInvocation::fromXdr($sub));
            }
        }
        return new AuthorizedInvocation($xdr->contractId, $xdr->functionName, $xdr->args, $subs);
    }

    public function toXdr() : XdrAuthorizedInvocation {
        $subs = array();
        foreach ($this->subInvocations as $sub) {
            if($sub instanceof  AuthorizedInvocation) {
                array_push($subs, $sub->toXdr());
            }
        }
        return new XdrAuthorizedInvocation($this->contractId, $this->functionName, $this->args, $subs);
    }

    /**
     * @return string
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * @param string $contractId
     */
    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return string
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @param string $functionName
     */
    public function setFunctionName(string $functionName): void
    {
        $this->functionName = $functionName;
    }

    /**
     * @return array|null
     */
    public function getArgs(): ?array
    {
        return $this->args;
    }

    /**
     * @param array|null $args
     */
    public function setArgs(?array $args): void
    {
        $this->args = $args;
    }

    /**
     * @return array|null
     */
    public function getSubInvocations(): ?array
    {
        return $this->subInvocations;
    }

    /**
     * @param array|null $subInvocations
     */
    public function setSubInvocations(?array $subInvocations): void
    {
        $this->subInvocations = $subInvocations;
    }

}