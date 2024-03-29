<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30Request
{
    /**
     * @var array<SEP30RequestIdentity> $identities
     */
     public array $identities;

    /**
     * @param array<SEP30RequestIdentity> $identities of type SEP30RequestIdentity
     */
    public function __construct(array $identities)
    {
        $this->identities = $identities;
    }

    public function toJson() : array {

        $identitiesJson = array();

        foreach ($this->identities as $identity) {
            if ($identity instanceof SEP30RequestIdentity) {
                array_push($identitiesJson, $identity->toJson());
            }
        }
        return array(
            'identities' => $identitiesJson
        );
    }

    /**
     * @return array<SEP30RequestIdentity> of SEP30RequestIdentity
     */
    public function getIdentities(): array
    {
        return $this->identities;
    }

    /**
     * @param array<SEP30RequestIdentity> $identities of SEP30RequestIdentity
     */
    public function setIdentities(array $identities): void
    {
        $this->identities = $identities;
    }

}