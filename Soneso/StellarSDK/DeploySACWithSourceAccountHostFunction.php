<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionArgs;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrSCContractExecutableType;

class DeploySACWithSourceAccountHostFunction extends HostFunction
{
    public string $salt;

    /**
     * @param string|null $salt
     * @param array|null $auth
     * @throws Exception
     */
    public function __construct(?string $salt = null, ?array $auth = array())
    {
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct($auth);
    }

    public function toXdr() : XdrHostFunction {
        $args = XdrHostFunctionArgs::forDeploySACWithSourceAccount($this->salt);
        return new XdrHostFunction($args, self::convertToXdrAuth($this->auth));
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : DeploySACWithSourceAccountHostFunction {
        $args = $xdr->args;
        $type = $args->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $args->createContract == null
            || $args->createContract->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT
            || $args->createContract->executable->type->value != XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_TOKEN) {
            throw new Exception("Invalid argument");
        }

        return new DeploySACWithSourceAccountHostFunction($args->createContract->contractID->salt, self::convertFromXdrAuth($xdr->auth));
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }
}