<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30AccountResponse
{
    public string $address;
    public array $identities; // [SEP30ResponseIdentity]
    public array $signers; // [SEP30ResponseSigner]

    /**
     * @param string $address
     * @param array $identities
     * @param array $signers
     */
    public function __construct(string $address, array $identities, array $signers)
    {
        $this->address = $address;
        $this->identities = $identities;
        $this->signers = $signers;
    }


    public static function fromJson(array $json) : SEP30AccountResponse
    {
        $address = $json['address'];
        $identities = array();
        foreach ($json['identities'] as $identity) {
            array_push($identities, SEP30ResponseIdentity::fromJson($identity));
        }

        $signers = array();
        foreach ($json['signers'] as $signer) {
            array_push($signers, SEP30ResponseSigner::fromJson($signer));
        }

        return new SEP30AccountResponse($address, $identities, $signers);
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return array of SEP30ResponseIdentity
     */
    public function getIdentities(): array
    {
        return $this->identities;
    }

    /**
     * @param array $identities of SEP30ResponseIdentity
     */
    public function setIdentities(array $identities): void
    {
        $this->identities = $identities;
    }

    /**
     * @return array of SEP30ResponseSigner
     */
    public function getSigners(): array
    {
        return $this->signers;
    }

    /**
     * @param array $signers of SEP30ResponseSigner
     */
    public function setSigners(array $signers): void
    {
        $this->signers = $signers;
    }

}