<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;


class TransactionPreconditionsResponse
{
    private ?PreconditionsTimeBoundsResponse $timeBounds = null;
    private ?PreconditionsLedgerBoundsResponse $ledgerBounds = null;
    private ?string $minAccountSequence = null;
    private ?string $minAccountSequenceAge = null;
    private ?int $minAccountSequenceLedgerGap = null;
    private ?array $extraSigners = null;

    /**
     * @return PreconditionsTimeBoundsResponse|null
     */
    public function getTimeBounds(): ?PreconditionsTimeBoundsResponse
    {
        return $this->timeBounds;
    }

    /**
     * @return PreconditionsLedgerBoundsResponse|null
     */
    public function getLedgerBounds(): ?PreconditionsLedgerBoundsResponse
    {
        return $this->ledgerBounds;
    }

    /**
     * @return string|null
     */
    public function getMinAccountSequence(): ?string
    {
        return $this->minAccountSequence;
    }

    /**
     * @return string|null
     */
    public function getMinAccountSequenceAge(): ?string
    {
        return $this->minAccountSequenceAge;
    }

    /**
     * @return int|null
     */
    public function getMinAccountSequenceLedgerGap(): ?int
    {
        return $this->minAccountSequenceLedgerGap;
    }

    /**
     * @return array|null
     */
    public function getExtraSigners(): ?array
    {
        return $this->extraSigners;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['timebounds'])) $this->timeBounds = PreconditionsTimeBoundsResponse::fromJson($json['timebounds']);
        if (isset($json['ledgerbounds'])) $this->ledgerBounds = PreconditionsLedgerBoundsResponse::fromJson($json['ledgerbounds']);

        if (isset($json['min_account_sequence'])) $this->minAccountSequence = $json['min_account_sequence'];
        if (isset($json['min_account_sequence_age'])) $this->minAccountSequenceAge = $json['min_account_sequence_age'];
        if (isset($json['min_account_sequence_ledger_gap'])) $this->minAccountSequenceLedgerGap = $json['min_account_sequence_ledger_gap'];

        if (isset($json['extra_signers'])) {
            $this->extraSigners = array();
            foreach ($json['extra_signers'] as $signer) {
                $this->extraSigners[] = $signer;
            }
         }
    }

    public static function fromJson(array $json): TransactionPreconditionsResponse
    {
        $result = new TransactionPreconditionsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}