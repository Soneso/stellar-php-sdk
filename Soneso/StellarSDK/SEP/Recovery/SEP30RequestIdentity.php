<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

class SEP30RequestIdentity
{
    public string $role;
    /**
     * @var array<SEP30AuthMethod> $authMethods
     */
    public array $authMethods;

    /**
     * @param string $role
     * @param array<SEP30AuthMethod> $authMethods
     */
    public function __construct(string $role, array $authMethods)
    {
        $this->role = $role;
        $this->authMethods = $authMethods;
    }

    public function toJson() : array {

        $authMethodsJson = array();

        foreach ($this->authMethods as $authMethod) {
            if ($authMethod instanceof SEP30AuthMethod) {
                $authMethodsJson[] = $authMethod->toJson();
            }
        }
        return array(
            'role' => $this->role,
            'auth_methods' => $authMethodsJson
        );
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
     * @return array<SEP30AuthMethod>
     */
    public function getAuthMethods(): array
    {
        return $this->authMethods;
    }

    /**
     * @param array<SEP30AuthMethod> $authMethods
     */
    public function setAuthMethods(array $authMethods): void
    {
        $this->authMethods = $authMethods;
    }
}