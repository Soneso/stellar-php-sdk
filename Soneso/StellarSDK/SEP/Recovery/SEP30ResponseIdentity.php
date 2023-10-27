<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30ResponseIdentity
{
    public string $role;
    public ?bool $authenticated = null;

    /**
     * @param string $role
     * @param bool|null $authenticated
     */
    public function __construct(string $role, ?bool $authenticated = null)
    {
        $this->role = $role;
        $this->authenticated = $authenticated;
    }

    public static function fromJson(array $json) : SEP30ResponseIdentity
    {
        $auth = null;
        if (isset($json['authenticated'])) {
            $auth = $json['authenticated'];
        }
        return new SEP30ResponseIdentity($json['role'], $auth);
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @return bool|null
     */
    public function getAuthenticated(): ?bool
    {
        return $this->authenticated;
    }

    /**
     * @param bool|null $authenticated
     */
    public function setAuthenticated(?bool $authenticated): void
    {
        $this->authenticated = $authenticated;
    }
}