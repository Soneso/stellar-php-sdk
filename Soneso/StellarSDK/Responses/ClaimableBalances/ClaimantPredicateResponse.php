<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\ClaimableBalances;

/**
 * Represents conditions that must be satisfied to claim a claimable balance
 *
 * Predicates can be unconditional, time-based (absolute or relative), or logical
 * combinations (AND, OR, NOT) of multiple predicates. This allows for complex
 * claim authorization logic.
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimantResponse For the parent claimant details
 * @since 1.0.0
 */
class ClaimantPredicateResponse
{
    private ?bool $unconditional = null;
    private ?ClaimantPredicatesResponse $and = null;
    private ?ClaimantPredicatesResponse $or = null;
    private ?ClaimantPredicateResponse $not = null;
    private ?string $beforeAbsoluteTime = null;
    private ?string $beforeRelativeTime = null;

    /**
     * Gets whether this is an unconditional predicate
     *
     * When true, the balance can be claimed without any conditions.
     *
     * @return bool|null True if unconditional, null if not applicable
     */
    public function getUnconditional(): ?bool
    {
        return $this->unconditional;
    }

    /**
     * Gets the AND logical combination of predicates
     *
     * All predicates in the collection must be satisfied.
     *
     * @return ClaimantPredicatesResponse|null The AND predicates, or null if not applicable
     */
    public function getAnd(): ?ClaimantPredicatesResponse
    {
        return $this->and;
    }

    /**
     * Gets the OR logical combination of predicates
     *
     * At least one predicate in the collection must be satisfied.
     *
     * @return ClaimantPredicatesResponse|null The OR predicates, or null if not applicable
     */
    public function getOr(): ?ClaimantPredicatesResponse
    {
        return $this->or;
    }

    /**
     * Gets the NOT logical negation of a predicate
     *
     * The nested predicate must not be satisfied.
     *
     * @return ClaimantPredicateResponse|null The negated predicate, or null if not applicable
     */
    public function getNot(): ?ClaimantPredicateResponse
    {
        return $this->not;
    }

    /**
     * Gets the absolute time before which the balance can be claimed
     *
     * Uses Unix timestamp format.
     *
     * @return string|null The absolute time threshold, or null if not applicable
     */
    public function getBeforeAbsoluteTime(): ?string
    {
        return $this->beforeAbsoluteTime;
    }

    /**
     * Gets the relative time before which the balance can be claimed
     *
     * Specified as seconds relative to the close time of the ledger where the balance was created.
     *
     * @return string|null The relative time threshold in seconds, or null if not applicable
     */
    public function getBeforeRelativeTime(): ?string
    {
        return $this->beforeRelativeTime;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['unconditional'])) $this->unconditional = $json['unconditional'];

        if (isset($json['abs_before'])) {
            $this->beforeAbsoluteTime = $json['abs_before'];
        } else if (isset($json['absBefore'])) {
            $this->beforeAbsoluteTime = $json['absBefore'];
        }
        if (isset($json['rel_before'])) {
            $this->beforeRelativeTime = $json['rel_before'];
        } else if (isset($json['relBefore'])) {
            $this->beforeRelativeTime = $json['relBefore'];
        }

        if (isset($json['and'])) {
            $this->and = new ClaimantPredicatesResponse();
            foreach ($json['and'] as $jsonPredicate) {
                $predicate = ClaimantPredicateResponse::fromJson($jsonPredicate);
                $this->and->add($predicate);
            }
        }

        if (isset($json['or'])) {
            $this->or = new ClaimantPredicatesResponse();
            foreach ($json['or'] as $jsonPredicate) {
                $predicate = ClaimantPredicateResponse::fromJson($jsonPredicate);
                $this->or->add($predicate);
            }
        }

        if (isset($json['not'])) {
            $this->not = ClaimantPredicateResponse::fromJson($json['not']);
        }
    }

    public static function fromJson(array $json) : ClaimantPredicateResponse
    {
        $result = new ClaimantPredicateResponse();
        $result->loadFromJson($json);
        return $result;
    }
}