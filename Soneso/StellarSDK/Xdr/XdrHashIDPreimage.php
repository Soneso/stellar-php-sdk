<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrHashIDPreimage
{

    public XdrEnvelopeType $type;
    public ?XdrHashIDPreimageOperationID $operationID;
    public ?XdrHashIDPreimageRevokeID $revokeID;
    public ?XdrHashIDPreimageContractID $contractID;
    public ?XdrHashIDPreimageSorobanAuthorization $sorobanAuthorization;

    /**
     * @param XdrEnvelopeType $type
     */
    public function __construct(XdrEnvelopeType $type)
    {
        $this->type = $type;
    }


    public function encode(): string
    {
        $bytes = $this->type->encode();

        switch ($this->type->getValue()) {
            case XdrEnvelopeType::ENVELOPE_TYPE_OP_ID:
                $bytes .= $this->operationID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_POOL_REVOKE_OP_ID:
                $bytes .= $this->revokeID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID:
                $bytes .= $this->contractID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION:
                $bytes .= $this->sorobanAuthorization->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrHashIDPreimage
    {
        $result = new XdrHashIDPreimage(XdrEnvelopeType::decode($xdr));
        switch ($result->type->getValue()) {
            case XdrEnvelopeType::ENVELOPE_TYPE_OP_ID:
                $result->operationID = XdrHashIDPreimageOperationID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_POOL_REVOKE_OP_ID:
                $result->revokeID = XdrHashIDPreimageRevokeID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID:
                $result->contractID = XdrHashIDPreimageContractID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION:
                $result->sorobanAuthorization = XdrHashIDPreimageSorobanAuthorization::decode($xdr);
                break;
        }
        return $result;
    }
}