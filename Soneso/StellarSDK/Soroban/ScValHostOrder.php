<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

/**
 * Soroban host ordering for SCVal keys and values.
 * @see https://github.com/stellar/rs-soroban-env/blob/f54ab964/soroban-env-host/src/host/comparison.rs#L301-L380
 */
class ScValHostOrder
{
    /**
     * Compares by SCVal type, then content in host order; returns negative, zero or positive.
     * Signed integers compare numerically, byte strings lexicographically and collections recursively.
     * @throws InvalidArgumentException for absent vectors/maps or unsupported types
     */
    public static function compare(XdrSCVal $a, XdrSCVal $b): int
    {
        $type = $a->type->value;
        if ($type !== $b->type->value) {
            return $type <=> $b->type->value;
        }
        switch ($type) {
            case XdrSCValType::SCV_VOID:
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE:
                return 0;
            case XdrSCValType::SCV_BOOL:
                return $a->b <=> $b->b;
            case XdrSCValType::SCV_I32:
                return $a->i32 <=> $b->i32;
            case XdrSCValType::SCV_I64:
                return $a->i64 <=> $b->i64;
            case XdrSCValType::SCV_I128:
                return ($a->i128->hi <=> $b->i128->hi) ?: strcmp($a->i128->encode(), $b->i128->encode());
            case XdrSCValType::SCV_I256:
                return ($a->i256->hiHi <=> $b->i256->hiHi) ?: strcmp($a->i256->encode(), $b->i256->encode());
            case XdrSCValType::SCV_LEDGER_KEY_NONCE:
                return $a->nonceKey->nonce <=> $b->nonceKey->nonce;
            case XdrSCValType::SCV_BYTES:
                return strcmp($a->bytes->getValue(), $b->bytes->getValue());
            case XdrSCValType::SCV_STRING:
                return strcmp($a->str, $b->str);
            case XdrSCValType::SCV_SYMBOL:
                return strcmp($a->sym, $b->sym);
            case XdrSCValType::SCV_EXECUTABLE_TAG:
                return strcmp($a->executableTag, $b->executableTag);
            case XdrSCValType::SCV_VEC:
                return self::compareSequence($a->vec, $b->vec);
            case XdrSCValType::SCV_MAP:
                return self::compareSequence($a->map, $b->map);
            case XdrSCValType::SCV_CONTRACT_INSTANCE:
                $order = self::compareExecutable($a->instance->executable, $b->instance->executable);
                if ($order !== 0) {
                    return $order;
                }
                $as = $a->instance->storage;
                $bs = $b->instance->storage;
                return $as === null || $bs === null
                    ? (($as !== null) <=> ($bs !== null)) : self::compareSequence($as, $bs);
            case XdrSCValType::SCV_ERROR:
            case XdrSCValType::SCV_U32:
            case XdrSCValType::SCV_U64:
            case XdrSCValType::SCV_TIMEPOINT:
            case XdrSCValType::SCV_DURATION:
            case XdrSCValType::SCV_U128:
            case XdrSCValType::SCV_U256:
            case XdrSCValType::SCV_ADDRESS:
                // These arms contain fixed-width unsigned fields in comparison order.
                return strcmp($a->encode(), $b->encode());
            default:
                throw new InvalidArgumentException("Unsupported SCVal type {$type}");
        }
    }

    /**
     * @param array<XdrSCVal>|array<XdrSCMapEntry>|null $a
     * @param array<XdrSCVal>|array<XdrSCMapEntry>|null $b
     */
    private static function compareSequence(?array $a, ?array $b): int
    {
        if ($a === null || $b === null) {
            throw new InvalidArgumentException('SCVal vectors and maps must be present');
        }
        $a = array_values($a);
        $b = array_values($b);
        for ($i = 0, $n = min(count($a), count($b)); $i < $n; $i++) {
            $av = $a[$i];
            $bv = $b[$i];
            $order = $av instanceof XdrSCMapEntry && $bv instanceof XdrSCMapEntry
                ? (self::compare($av->key, $bv->key) ?: self::compare($av->val, $bv->val))
                : self::compare($av, $bv);
            if ($order !== 0) {
                return $order;
            }
        }
        return count($a) <=> count($b);
    }

    private static function compareExecutable(XdrContractExecutable $a, XdrContractExecutable $b): int
    {
        $order = $a->type->value <=> $b->type->value;
        if ($order !== 0) {
            return $order;
        }
        if ($a->type->value === XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF) {
            return strcmp($a->externalRef->executableOwner->encode(), $b->externalRef->executableOwner->encode())
                ?: strcmp($a->externalRef->tag, $b->externalRef->tag);
        }
        return strcmp($a->encode(), $b->encode());
    }
}
