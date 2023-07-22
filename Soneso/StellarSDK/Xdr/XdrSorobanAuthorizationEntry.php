<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizationEntry
{
    public XdrSorobanCredentials $credentials;
    public XdrSorobanAuthorizedInvocation $rootInvocation;

    /**
     * @param XdrSorobanCredentials $credentials
     * @param XdrSorobanAuthorizedInvocation $rootInvocation
     */
    public function __construct(XdrSorobanCredentials $credentials, XdrSorobanAuthorizedInvocation $rootInvocation)
    {
        $this->credentials = $credentials;
        $this->rootInvocation = $rootInvocation;
    }


    public function encode(): string {
        $bytes = $this->credentials->encode();
        $bytes .= $this->rootInvocation->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanAuthorizationEntry {
        $credentials = XdrSorobanCredentials::decode($xdr);
        $rootInvocation = XdrSorobanAuthorizedInvocation::decode($xdr);
        return new XdrSorobanAuthorizationEntry($credentials, $rootInvocation);
    }

    /**
     * @return XdrSorobanCredentials
     */
    public function getCredentials(): XdrSorobanCredentials
    {
        return $this->credentials;
    }

    /**
     * @param XdrSorobanCredentials $credentials
     */
    public function setCredentials(XdrSorobanCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @return XdrSorobanAuthorizedInvocation
     */
    public function getRootInvocation(): XdrSorobanAuthorizedInvocation
    {
        return $this->rootInvocation;
    }

    /**
     * @param XdrSorobanAuthorizedInvocation $rootInvocation
     */
    public function setRootInvocation(XdrSorobanAuthorizedInvocation $rootInvocation): void
    {
        $this->rootInvocation = $rootInvocation;
    }

}