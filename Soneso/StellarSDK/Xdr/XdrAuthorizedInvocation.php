<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAuthorizedInvocation
{

    public string $contractId; // hex
    public string $functionName;
    public array $args; // [XdrSCVal]
    public array $subInvocations; // [XdrAuthorizedInvocation]

    /**
     * @param string $contractId
     * @param string $functionName
     * @param array $args
     * @param array $subInvocations
     */
    public function __construct(string $contractId, string $functionName, array $args, array $subInvocations)
    {
        $this->contractId = $contractId;
        $this->functionName = $functionName;
        $this->args = $args;
        $this->subInvocations = $subInvocations;
    }

    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed(hex2bin($this->contractId),32);
        $bytes .= XdrEncoder::string($this->functionName);
        $bytes .= XdrEncoder::integer32(count($this->args));
        foreach($this->args as $val) {
            if ($val instanceof XdrSCVal) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->subInvocations));
        foreach($this->subInvocations as $val) {
            if ($val instanceof XdrAuthorizedInvocation) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAuthorizedInvocation {
        $contractID = bin2hex($xdr->readOpaqueFixed(32));
        $functionName = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $args = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($args, XdrSCVal::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $sub = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($sub, XdrAuthorizedInvocation::decode($xdr));
        }
        return new XdrAuthorizedInvocation($contractID, $functionName, $args, $sub);
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
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
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
    } // [XdrAuthorizedInvocation]



}