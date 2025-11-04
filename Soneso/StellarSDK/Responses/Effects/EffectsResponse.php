<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Iterable collection of effect responses
 *
 * This class provides an iterator-based collection for managing multiple EffectResponse
 * objects. Supports standard iteration operations and array conversion. Used within
 * EffectsPageResponse for effect collections.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see EffectsPageResponse
 */
class EffectsResponse extends \IteratorIterator
{

    /**
     * Constructs a new effects collection
     *
     * @param EffectResponse ...$responses Variable number of effect responses
     */
    public function __construct(EffectResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Gets the current effect in the iteration
     *
     * @return EffectResponse The current effect
     */
    public function current(): EffectResponse
    {
        return parent::current();
    }

    /**
     * Adds an effect to the collection
     *
     * @param EffectResponse $response The effect to add
     * @return void
     */
    public function add(EffectResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the number of effects in the collection
     *
     * @return int The effect count
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<EffectResponse> Array of effects
     */
    public function toArray() : array {
        /**
         * @var array<EffectResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}
