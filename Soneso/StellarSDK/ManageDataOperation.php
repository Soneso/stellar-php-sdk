<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrManageDataOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#manage-data" target="_blank">ManageData</a> operation.
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 */
class ManageDataOperation extends AbstractOperation
{
    private string $key;
    private ?string $value = null;

    public function __construct(string $key, ?string $value = null) {
        $this->key = $key;
        $this->value = $value;
    }

    /** The name of the data value.
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * Data value
     * @return string|null
     */
    public function getValue(): ?string {
        return $this->value;
    }

    public static function fromXdrOperation(XdrManageDataOperation $xdrOp): ManageDataOperation {
        $key = $xdrOp->getKey();
        $value = $xdrOp->getValue()->getValue();
        return new ManageDataOperation($key, $value);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $value = new XdrDataValue($this->value);
        $op = new XdrManageDataOperation($this->key, $value);
        $type = new XdrOperationType(XdrOperationType::MANAGE_DATA);
        $result = new XdrOperationBody($type);
        $result->setManageDataOperation($op);
        return $result;
    }
}