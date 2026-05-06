<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;

/**
 * SEP-51 negative-input conformance tests.
 *
 * Per-union per-shape rejection methods. For every generated union class,
 * four or five named methods exercise the rejection paths in fromJsonValue,
 * grouped by the shape category the union falls into:
 *
 *   void_only:  unknownString, intInput, nullInput, arrayInput
 *   non_void:   unknownDiscriminant, multiKeyDict, bareStringWrongPlace, wrongType
 *   mixed:      unknownDiscriminant, multiKeyDict, bareStringWrongPlace, wrongType, unknownArmKey
 *   int_cased:  unknownVersionString, integerInput, multiKeyDict, nullInput
 *
 * Each method asserts InvalidArgumentException; the method name encodes the
 * union and the negative-input suffix so failures are self-locating.
 */
class NegativeInputTest extends TestCase
{
    /**
     * Generic per-shape rejection assertion. Each shape's negative inputs
     * are constructed deterministically from a small table; the contract is
     * that fromJsonValue raises InvalidArgumentException for every entry.
     *
     * @param class-string $unionClass
     */
    private function assertUnionRejects(string $unionClass, string $shape, string $suffix): void
    {
        $input = $this->negativeInputFor($shape, $suffix);
        try {
            $unionClass::fromJsonValue($input);
            $this->fail(
                "Expected $unionClass::fromJsonValue to reject input for shape=$shape suffix=$suffix"
                . ' but it returned a value.'
            );
        } catch (\InvalidArgumentException $e) {
            // Sanity-check the message names the union; this catches accidental
            // delegation through a different class's exception path.
            $this->assertNotEmpty($e->getMessage(),
                "Expected non-empty exception message from $unionClass for $shape/$suffix");
        }
    }

    /**
     * Build the deterministic negative input for a (shape, suffix) pair.
     *
     * The strings are crafted to be invalid for any union shape:
     * `__sep51_unknown_arm__` is not a valid arm name in any current XDR
     * union and the integer 99 keyword `v99` is not a valid version arm.
     *
     * @return mixed
     */
    private function negativeInputFor(string $shape, string $suffix): mixed
    {
        return match ([$shape, $suffix]) {
            ['void_only', 'unknownString'] => '__sep51_unknown_arm__',
            ['void_only', 'intInput'] => 42,
            ['void_only', 'nullInput'] => null,
            ['void_only', 'arrayInput'] => [],
            ['non_void', 'unknownDiscriminant'] => ['__sep51_unknown_arm__' => 1],
            ['non_void', 'multiKeyDict'] => ['__sep51_unknown_a__' => 1, '__sep51_unknown_b__' => 2],
            ['non_void', 'bareStringWrongPlace'] => '__sep51_unknown_arm__',
            ['non_void', 'wrongType'] => 42,
            ['mixed', 'unknownDiscriminant'] => '__sep51_unknown_arm__',
            ['mixed', 'multiKeyDict'] => ['__sep51_unknown_a__' => 1, '__sep51_unknown_b__' => 2],
            ['mixed', 'bareStringWrongPlace'] => '__sep51_unknown_arm__',
            ['mixed', 'wrongType'] => 42,
            ['mixed', 'unknownArmKey'] => ['__sep51_unknown_arm__' => 1],
            ['int_cased', 'unknownVersionString'] => 'v99',
            ['int_cased', 'integerInput'] => 42,
            ['int_cased', 'multiKeyDict'] => ['__sep51_unknown_a__' => 1, '__sep51_unknown_b__' => 2],
            ['int_cased', 'nullInput'] => null,
            default => throw new \LogicException("Unknown shape/suffix pair: $shape/$suffix"),
        };
    }
    public function testNegative_XdrAccountEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrAccountEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrAccountEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrAccountEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrAccountEntryV1Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrAccountEntryV1Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrAccountEntryV1Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrAccountEntryV1Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV1Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrAccountEntryV2Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrAccountEntryV2Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrAccountEntryV2Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrAccountEntryV2Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountEntryV2Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrAccountMergeResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountMergeResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrAccountMergeResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountMergeResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrAccountMergeResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountMergeResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrAccountMergeResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountMergeResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrAccountMergeResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAccountMergeResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrAllowTrustResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAllowTrustResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrAllowTrustResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAllowTrustResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrAllowTrustResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAllowTrustResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrAllowTrustResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAllowTrustResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrAuthenticatedMessage_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAuthenticatedMessage::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrAuthenticatedMessage_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAuthenticatedMessage::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrAuthenticatedMessage_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAuthenticatedMessage::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrAuthenticatedMessage_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrAuthenticatedMessage::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrBeginSponsoringFutureReservesResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrBeginSponsoringFutureReservesResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrBeginSponsoringFutureReservesResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrBeginSponsoringFutureReservesResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBeginSponsoringFutureReservesResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrBucketEntry_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketEntry::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrBucketEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketEntry::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrBucketEntry_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketEntry::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrBucketEntry_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketEntry::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrBucketMetadataExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketMetadataExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrBucketMetadataExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketMetadataExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrBucketMetadataExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketMetadataExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrBucketMetadataExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBucketMetadataExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrBumpSequenceResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBumpSequenceResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrBumpSequenceResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBumpSequenceResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrBumpSequenceResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBumpSequenceResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrBumpSequenceResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrBumpSequenceResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrChangeTrustAssetBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrChangeTrustAssetBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrChangeTrustAssetBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrChangeTrustAssetBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrChangeTrustAssetBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustAssetBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrChangeTrustResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrChangeTrustResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrChangeTrustResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrChangeTrustResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrChangeTrustResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrClaimAtom_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimAtom::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrClaimAtom_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimAtom::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrClaimAtom_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimAtom::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrClaimAtom_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimAtom::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrClaimClaimableBalanceResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrClaimClaimableBalanceResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrClaimClaimableBalanceResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrClaimClaimableBalanceResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimClaimableBalanceResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrClaimPredicate_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimPredicate::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrClaimPredicate_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimPredicate::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrClaimPredicate_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimPredicate::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrClaimPredicate_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimPredicate::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrClaimPredicate_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimPredicate::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrClaimableBalanceEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrClaimableBalanceEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrClaimableBalanceEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrClaimableBalanceEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrClaimableBalanceEntryExtV1Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrClaimableBalanceEntryExtV1Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrClaimableBalanceEntryExtV1Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrClaimableBalanceEntryExtV1Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimableBalanceEntryExtV1Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrClaimant_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimant::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrClaimant_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimant::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrClaimant_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimant::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrClaimant_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClaimant::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrClawbackClaimableBalanceResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrClawbackClaimableBalanceResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrClawbackClaimableBalanceResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrClawbackClaimableBalanceResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackClaimableBalanceResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrClawbackResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrClawbackResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrClawbackResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrClawbackResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrClawbackResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrConfigSettingEntry_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrConfigSettingEntry::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrConfigSettingEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrConfigSettingEntry::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrConfigSettingEntry_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrConfigSettingEntry::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrConfigSettingEntry_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrConfigSettingEntry::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrContractCodeEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrContractCodeEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrContractCodeEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrContractCodeEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractCodeEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrContractEventBody_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractEventBody::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrContractEventBody_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractEventBody::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrContractEventBody_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractEventBody::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrContractEventBody_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractEventBody::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrContractExecutableBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractExecutableBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrContractExecutableBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractExecutableBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrContractExecutableBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractExecutableBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrContractExecutableBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractExecutableBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrContractExecutableBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractExecutableBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrContractIDPreimageBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractIDPreimageBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrContractIDPreimageBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractIDPreimageBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrContractIDPreimageBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractIDPreimageBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrContractIDPreimageBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrContractIDPreimageBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrCreateAccountResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateAccountResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrCreateAccountResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateAccountResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrCreateAccountResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateAccountResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrCreateAccountResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateAccountResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrCreateClaimableBalanceResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrCreateClaimableBalanceResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrCreateClaimableBalanceResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrCreateClaimableBalanceResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrCreateClaimableBalanceResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrCreateClaimableBalanceResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrDataEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrDataEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrDataEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrDataEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrDataEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrDataEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrDataEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrDataEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrEndSponsoringFutureReservesResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrEndSponsoringFutureReservesResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrEndSponsoringFutureReservesResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrEndSponsoringFutureReservesResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrEndSponsoringFutureReservesResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrEndSponsoringFutureReservesResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrEndSponsoringFutureReservesResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrEndSponsoringFutureReservesResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrExtendFootprintTTLResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrExtendFootprintTTLResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrExtendFootprintTTLResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrExtendFootprintTTLResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtendFootprintTTLResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrExtensionPoint_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtensionPoint::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrExtensionPoint_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtensionPoint::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrExtensionPoint_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtensionPoint::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrExtensionPoint_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrExtensionPoint::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrFeeBumpTransactionExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrFeeBumpTransactionExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrFeeBumpTransactionExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrFeeBumpTransactionExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrFeeBumpTransactionInnerTx_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrFeeBumpTransactionInnerTx_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrFeeBumpTransactionInnerTx_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrFeeBumpTransactionInnerTx_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrGeneralizedTransactionSet_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrGeneralizedTransactionSet::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrGeneralizedTransactionSet_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrGeneralizedTransactionSet::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrGeneralizedTransactionSet_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrGeneralizedTransactionSet::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrGeneralizedTransactionSet_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrGeneralizedTransactionSet::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrHashIDPreimage_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHashIDPreimage::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrHashIDPreimage_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHashIDPreimage::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrHashIDPreimage_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHashIDPreimage::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrHashIDPreimage_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHashIDPreimage::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrHotArchiveBucketEntry_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrHotArchiveBucketEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrHotArchiveBucketEntry_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrHotArchiveBucketEntry_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrHotArchiveBucketEntry::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrInflationResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInflationResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrInflationResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInflationResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrInflationResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInflationResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrInflationResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInflationResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrInflationResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInflationResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrInnerTransactionResultResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInnerTransactionResultResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrInnerTransactionResultResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInnerTransactionResultResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrInnerTransactionResultResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInnerTransactionResultResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrInnerTransactionResultResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInnerTransactionResultResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrInnerTransactionResultResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInnerTransactionResultResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrInvokeHostFunctionResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrInvokeHostFunctionResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrInvokeHostFunctionResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrInvokeHostFunctionResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrInvokeHostFunctionResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrLedgerCloseMeta_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerCloseMeta_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerCloseMeta_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerCloseMeta_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMeta::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerCloseMetaExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerCloseMetaExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerCloseMetaExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerCloseMetaExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerCloseMetaExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerEntryChange_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryChange::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLedgerEntryChange_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryChange::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerEntryChange_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryChange::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLedgerEntryChange_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryChange::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLedgerEntryData_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryData::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLedgerEntryData_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryData::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerEntryData_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryData::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLedgerEntryData_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryData::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLedgerEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerEntryV1Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerEntryV1Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerEntryV1Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerEntryV1Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerEntryV1Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerHeaderExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerHeaderExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerHeaderExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerHeaderExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerHeaderExtensionV1Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExtensionV1Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerHeaderExtensionV1Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExtensionV1Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerHeaderExtensionV1Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExtensionV1Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerHeaderExtensionV1Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderExtensionV1Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerHeaderHistoryEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrLedgerHeaderHistoryEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrLedgerHeaderHistoryEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerHeaderHistoryEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerHeaderHistoryEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrLedgerKeyBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerKeyBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLedgerKeyBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerKeyBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerKeyBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerKeyBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLedgerKeyBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerKeyBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLedgerUpgrade_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerUpgrade::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLedgerUpgrade_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerUpgrade::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLedgerUpgrade_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerUpgrade::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLedgerUpgrade_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLedgerUpgrade::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLiquidityPoolBody_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLiquidityPoolBody_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLiquidityPoolBody_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLiquidityPoolBody_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolBody::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLiquidityPoolDepositResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrLiquidityPoolDepositResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrLiquidityPoolDepositResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrLiquidityPoolDepositResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrLiquidityPoolParameters_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrLiquidityPoolParameters_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrLiquidityPoolParameters_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrLiquidityPoolParameters_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolParameters::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrLiquidityPoolWithdrawResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrLiquidityPoolWithdrawResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrLiquidityPoolWithdrawResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrLiquidityPoolWithdrawResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrManageDataResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageDataResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrManageDataResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageDataResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrManageDataResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageDataResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrManageDataResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageDataResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrManageOfferResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrManageOfferResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrManageOfferResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrManageOfferResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrManageOfferResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrManageOfferSuccessResultOffer_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferSuccessResultOffer::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrManageOfferSuccessResultOffer_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferSuccessResultOffer::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrManageOfferSuccessResultOffer_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferSuccessResultOffer::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrManageOfferSuccessResultOffer_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferSuccessResultOffer::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrManageOfferSuccessResultOffer_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrManageOfferSuccessResultOffer::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrOfferEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOfferEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrOfferEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOfferEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrOfferEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOfferEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrOfferEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOfferEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrOperationBody_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationBody::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrOperationBody_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationBody::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrOperationBody_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationBody::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrOperationBody_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationBody::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrOperationBody_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationBody::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrOperationResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrOperationResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrOperationResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrOperationResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrOperationResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrOperationResultTrBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResultTrBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrOperationResultTrBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResultTrBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrOperationResultTrBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResultTrBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrOperationResultTrBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrOperationResultTrBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrPathPaymentStrictReceiveResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrPathPaymentStrictReceiveResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrPathPaymentStrictReceiveResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrPathPaymentStrictReceiveResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrPathPaymentStrictReceiveResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictReceiveResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrPathPaymentStrictSendResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrPathPaymentStrictSendResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrPathPaymentStrictSendResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrPathPaymentStrictSendResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrPathPaymentStrictSendResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPathPaymentStrictSendResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrPaymentResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPaymentResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrPaymentResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPaymentResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrPaymentResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPaymentResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrPaymentResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPaymentResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrPeerAddressIp_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPeerAddressIp::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrPeerAddressIp_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPeerAddressIp::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrPeerAddressIp_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPeerAddressIp::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrPeerAddressIp_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPeerAddressIp::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrPersistedSCPState_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPersistedSCPState::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrPersistedSCPState_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPersistedSCPState::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrPersistedSCPState_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPersistedSCPState::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrPersistedSCPState_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPersistedSCPState::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrPreconditions_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPreconditions::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrPreconditions_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPreconditions::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrPreconditions_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPreconditions::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrPreconditions_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPreconditions::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrPreconditions_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrPreconditions::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrRestoreFootprintResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRestoreFootprintResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrRestoreFootprintResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRestoreFootprintResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrRestoreFootprintResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRestoreFootprintResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrRestoreFootprintResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRestoreFootprintResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrRevokeSponsorshipOperation_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrRevokeSponsorshipOperation_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrRevokeSponsorshipOperation_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrRevokeSponsorshipOperation_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipOperation::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrRevokeSponsorshipResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrRevokeSponsorshipResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrRevokeSponsorshipResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrRevokeSponsorshipResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrRevokeSponsorshipResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrSCEnvMetaEntry_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCEnvMetaEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCEnvMetaEntry_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCEnvMetaEntry_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCError_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCError::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCError_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCError::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCError_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCError::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCError_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCError::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCMetaEntry_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCMetaEntry::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCMetaEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCMetaEntry::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCMetaEntry_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCMetaEntry::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCMetaEntry_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCMetaEntry::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCPHistoryEntry_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPHistoryEntry::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrSCPHistoryEntry_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPHistoryEntry::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrSCPHistoryEntry_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPHistoryEntry::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrSCPHistoryEntry_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPHistoryEntry::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrSCPStatementPledges_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPStatementPledges::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCPStatementPledges_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPStatementPledges::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCPStatementPledges_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPStatementPledges::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCPStatementPledges_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCPStatementPledges::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCSpecEntryBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecEntryBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCSpecEntryBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecEntryBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCSpecEntryBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecEntryBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCSpecEntryBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecEntryBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCSpecTypeDefBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecTypeDefBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCSpecTypeDefBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecTypeDefBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrSCSpecTypeDefBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecTypeDefBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCSpecTypeDefBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecTypeDefBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrSCSpecTypeDefBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecTypeDefBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrSCSpecUDTUnionCaseV0Base_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Base::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCSpecUDTUnionCaseV0Base_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Base::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSCSpecUDTUnionCaseV0Base_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Base::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCSpecUDTUnionCaseV0Base_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Base::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSCValBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCValBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrSCValBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCValBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrSCValBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCValBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSCValBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCValBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrSCValBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSCValBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrSetOptionsResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetOptionsResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrSetOptionsResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetOptionsResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrSetOptionsResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetOptionsResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrSetOptionsResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetOptionsResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrSetTrustLineFlagsResult_unknownString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsResult::class, 'void_only', 'unknownString');
    }

    public function testNegative_XdrSetTrustLineFlagsResult_intInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsResult::class, 'void_only', 'intInput');
    }

    public function testNegative_XdrSetTrustLineFlagsResult_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsResult::class, 'void_only', 'nullInput');
    }

    public function testNegative_XdrSetTrustLineFlagsResult_arrayInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSetTrustLineFlagsResult::class, 'void_only', 'arrayInput');
    }

    public function testNegative_XdrSorobanAuthorizedFunctionBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSorobanAuthorizedFunctionBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSorobanAuthorizedFunctionBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSorobanAuthorizedFunctionBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrSorobanCredentialsBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanCredentialsBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrSorobanCredentialsBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanCredentialsBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrSorobanCredentialsBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanCredentialsBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSorobanCredentialsBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanCredentialsBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrSorobanCredentialsBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanCredentialsBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrSorobanTransactionDataExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrSorobanTransactionDataExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrSorobanTransactionDataExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrSorobanTransactionDataExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrSorobanTransactionMetaExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrSorobanTransactionMetaExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrSorobanTransactionMetaExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrSorobanTransactionMetaExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSorobanTransactionMetaExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrStellarMessage_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarMessage::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrStellarMessage_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarMessage::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrStellarMessage_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarMessage::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrStellarMessage_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarMessage::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrStellarValueExt_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarValueExt::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrStellarValueExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarValueExt::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrStellarValueExt_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarValueExt::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrStellarValueExt_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarValueExt::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrStellarValueExt_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStellarValueExt::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrStoredTransactionSet_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStoredTransactionSet::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrStoredTransactionSet_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStoredTransactionSet::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrStoredTransactionSet_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStoredTransactionSet::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrStoredTransactionSet_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrStoredTransactionSet::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrSurveyResponseBody_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSurveyResponseBody::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrSurveyResponseBody_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSurveyResponseBody::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrSurveyResponseBody_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSurveyResponseBody::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrSurveyResponseBody_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrSurveyResponseBody::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrTransactionEnvelopeBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelopeBase::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrTransactionEnvelopeBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelopeBase::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionEnvelopeBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelopeBase::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrTransactionEnvelopeBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionEnvelopeBase::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrTransactionExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionHistoryEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionHistoryEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionHistoryEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionHistoryEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionHistoryResultEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryResultEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionHistoryResultEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryResultEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionHistoryResultEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryResultEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionHistoryResultEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionHistoryResultEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionMeta_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionMeta::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionMeta_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionMeta::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionMeta_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionMeta::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionMeta_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionMeta::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionPhase_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionPhase::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionPhase_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionPhase::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionPhase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionPhase::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionPhase_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionPhase::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionResultExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionResultExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionResultExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionResultExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTransactionResultResult_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultResult::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrTransactionResultResult_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultResult::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionResultResult_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultResult::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrTransactionResultResult_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultResult::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrTransactionResultResult_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionResultResult::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrTransactionSignaturePayloadTaggedTransaction_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionSignaturePayloadTaggedTransaction::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrTransactionSignaturePayloadTaggedTransaction_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionSignaturePayloadTaggedTransaction::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionSignaturePayloadTaggedTransaction_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionSignaturePayloadTaggedTransaction::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrTransactionSignaturePayloadTaggedTransaction_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionSignaturePayloadTaggedTransaction::class, 'non_void', 'wrongType');
    }

    public function testNegative_XdrTransactionV0Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionV0Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTransactionV0Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionV0Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTransactionV0Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionV0Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTransactionV0Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTransactionV0Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTrustLineEntryExt_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTrustLineEntryExt_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTrustLineEntryExt_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTrustLineEntryExt_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExt::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTrustLineEntryExtensionV2Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTrustLineEntryExtensionV2Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTrustLineEntryExtensionV2Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTrustLineEntryExtensionV2Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryExtensionV2Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTrustLineEntryV1Ext_unknownVersionString(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1Ext::class, 'int_cased', 'unknownVersionString');
    }

    public function testNegative_XdrTrustLineEntryV1Ext_integerInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1Ext::class, 'int_cased', 'integerInput');
    }

    public function testNegative_XdrTrustLineEntryV1Ext_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1Ext::class, 'int_cased', 'multiKeyDict');
    }

    public function testNegative_XdrTrustLineEntryV1Ext_nullInput(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustLineEntryV1Ext::class, 'int_cased', 'nullInput');
    }

    public function testNegative_XdrTrustlineAssetBase_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase::class, 'mixed', 'unknownDiscriminant');
    }

    public function testNegative_XdrTrustlineAssetBase_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase::class, 'mixed', 'multiKeyDict');
    }

    public function testNegative_XdrTrustlineAssetBase_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase::class, 'mixed', 'bareStringWrongPlace');
    }

    public function testNegative_XdrTrustlineAssetBase_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase::class, 'mixed', 'wrongType');
    }

    public function testNegative_XdrTrustlineAssetBase_unknownArmKey(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTrustlineAssetBase::class, 'mixed', 'unknownArmKey');
    }

    public function testNegative_XdrTxSetComponent_unknownDiscriminant(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTxSetComponent::class, 'non_void', 'unknownDiscriminant');
    }

    public function testNegative_XdrTxSetComponent_multiKeyDict(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTxSetComponent::class, 'non_void', 'multiKeyDict');
    }

    public function testNegative_XdrTxSetComponent_bareStringWrongPlace(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTxSetComponent::class, 'non_void', 'bareStringWrongPlace');
    }

    public function testNegative_XdrTxSetComponent_wrongType(): void
    {
        $this->assertUnionRejects(\Soneso\StellarSDK\Xdr\XdrTxSetComponent::class, 'non_void', 'wrongType');
    }
}