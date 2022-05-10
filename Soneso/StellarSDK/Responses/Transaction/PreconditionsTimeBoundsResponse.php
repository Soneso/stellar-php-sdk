<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

class PreconditionsTimeBoundsResponse
{
    private ?string $minTime = null;
    private ?string $maxTime = null;

    /**
     * @return string|null
     */
    public function getMinTime(): ?string
    {
        return $this->minTime;
    }

    /**
     * @return string|null
     */
    public function getMaxTime(): ?string
    {
        return $this->maxTime;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['min_time'])) $this->minTime = $json['min_time'];
        if (isset($json['max_time'])) $this->maxTime = $json['max_time'];
    }

    public static function fromJson(array $json): PreconditionsTimeBoundsResponse
    {
        $result = new PreconditionsTimeBoundsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}