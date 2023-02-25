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
    public ?XdrHashIDPreimageEd25519ContractID $ed25519ContractID;
    public ?XdrHashIDPreimageContractID $contractID;
    public ?XdrHashIDPreimageFromAsset $fromAsset;
    public ?XdrHashIDPreimageSourceAccountContractID $sourceAccountContractID;
    public ?XdrHashIDPreimageCreateContractArgs $createContractArgs;
    public ?XdrHashIDPreimageContractAuth $contractAuth;

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
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_ED25519:
                $bytes .= $this->ed25519ContractID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_CONTRACT:
                $bytes .= $this->contractID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_ASSET:
                $bytes .= $this->fromAsset->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_SOURCE_ACCOUNT:
                $bytes .= $this->sourceAccountContractID->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CREATE_CONTRACT_ARGS:
                $bytes .= $this->createContractArgs->encode();
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_AUTH:
                $bytes .= $this->contractAuth->encode();
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
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_ED25519:
                $result->ed25519ContractID = XdrHashIDPreimageEd25519ContractID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_CONTRACT:
                $result->contractID = XdrHashIDPreimageContractID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_ASSET:
                $result->fromAsset = XdrHashIDPreimageFromAsset::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_ID_FROM_SOURCE_ACCOUNT:
                $result->sourceAccountContractID = XdrHashIDPreimageSourceAccountContractID::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CREATE_CONTRACT_ARGS:
                $result->createContractArgs = XdrHashIDPreimageCreateContractArgs::decode($xdr);
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_AUTH:
                $result->contractAuth = XdrHashIDPreimageContractAuth::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forContractAuth(XdrHashIDPreimageContractAuth $contractAuth): XdrHashIDPreimage
    {
        $result = new XdrHashIDPreimage(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_CONTRACT_AUTH));
        $result->contractAuth = $contractAuth;
        return $result;
    }
}