<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\ClaimableBalances;


class ClaimantPredicateResponse
{
    private ?bool $unconditional = null;
    private ?ClaimantPredicatesResponse $and = null;
    private ?ClaimantPredicatesResponse $or = null;
    private ?ClaimantPredicateResponse $not = null;
    private ?string $beforeAbsoluteTime = null;
    private ?string $beforeRelativeTime = null;

    /**
     * @return bool|null
     */
    public function getUnconditional(): ?bool
    {
        return $this->unconditional;
    }

    /**
     * @return ClaimantPredicatesResponse|null
     */
    public function getAnd(): ?ClaimantPredicatesResponse
    {
        return $this->and;
    }

    /**
     * @return ClaimantPredicatesResponse|null
     */
    public function getOr(): ?ClaimantPredicatesResponse
    {
        return $this->or;
    }

    /**
     * @return ClaimantPredicateResponse|null
     */
    public function getNot(): ?ClaimantPredicateResponse
    {
        return $this->not;
    }

    /**
     * @return string|null
     */
    public function getBeforeAbsoluteTime(): ?string
    {
        return $this->beforeAbsoluteTime;
    }

    /**
     * @return string|null
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