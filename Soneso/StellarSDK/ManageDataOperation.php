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
 * Represents ManageData operation.
 *
 * Sets, modifies, or deletes a data entry (key/value pair) attached to an account. This allows for storing
 * arbitrary data on the ledger in the form of key/value pairs.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see ManageDataOperationBuilder For building this operation
 * @since 1.0.0
 */
class ManageDataOperation extends AbstractOperation
{
    /**
     * @var string The name of the data entry (key).
     */
    private string $key;

    /**
     * @var string|null The value of the data entry. If null, the data entry is deleted.
     */
    private ?string $value = null;

    /**
     * Constructs a new ManageDataOperation object.
     *
     * @param string $key The name of the data entry (up to 64 bytes).
     * @param string|null $value The value to store (up to 64 bytes), or null to delete the entry.
     */
    public function __construct(string $key, ?string $value = null) {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Returns the name of the data entry.
     *
     * @return string The data entry key.
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * Returns the value of the data entry.
     *
     * @return string|null The data entry value, or null if the entry is being deleted.
     */
    public function getValue(): ?string {
        return $this->value;
    }

    /**
     * Creates a ManageDataOperation from XDR operation object.
     *
     * @param XdrManageDataOperation $xdrOp The XDR operation object to convert.
     * @return ManageDataOperation The created operation instance.
     */
    public static function fromXdrOperation(XdrManageDataOperation $xdrOp): ManageDataOperation {
        $key = $xdrOp->getKey();
        $value = $xdrOp->getValue()->getValue();
        return new ManageDataOperation($key, $value);
    }

    /**
     * Converts the operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body.
     */
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