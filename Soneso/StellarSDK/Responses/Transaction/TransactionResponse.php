<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Xdr\XdrTransactionResult;

class TransactionResponse extends Response
{
    private string $id;
    private string $pagingToken;
    private bool $successful;
    private string $hash;
    private int $ledger;
    private string $createdAt;
    private string $sourceAccount;
    private ?string $sourceAccountMuxed = null;
    private ?string $sourceAccountMuxedId = null;
    private string $sourceAccountSequence;
    private string $feeAccount;
    private ?string $feeAccountMuxed = null;
    private ?string $feeAccountMuxedId = null;
    private ?string $feeCharged = null;
    private ?string $maxFee = null;
    private int $operationCount;
    private Memo $memo;
    private string $envelopeXdrBase64;
    private XdrTransactionEnvelope $envelopeXdr;
    private string $resultXdrBase64;
    private XdrTransactionResult $resultXdr;
    private string $resultMetaXdrBase64;
    private XdrTransactionMeta $resultMetaXdr;
    private ?string $feeMetaXdrBase64 = null; // todo resolve
    private ?array $feeMetaXdr = null; //
    private ?string $validAfter = null; // [XdrLedgerEntryChange]
    private TransactionSignaturesResponse $signatures;
    private ?FeeBumpTransactionResponse $feeBumpTransactionResponse = null;
    private ?InnerTransactionResponse $innerTransactionResponse = null;
    private TransactionLinksResponse $links;
    private ?TransactionPreconditionsResponse $preconditions;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return int
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getSourceAccount(): string
    {
        return $this->sourceAccount;
    }

    /**
     * @return string|null
     */
    public function getSourceAccountMuxed(): ?string
    {
        return $this->sourceAccountMuxed;
    }

    /**
     * @return string|null
     */
    public function getSourceAccountMuxedId(): ?string
    {
        return $this->sourceAccountMuxedId;
    }

    /**
     * @return string
     */
    public function getSourceAccountSequence(): string
    {
        return $this->sourceAccountSequence;
    }

    /**
     * @return string
     */
    public function getFeeAccount(): string
    {
        return $this->feeAccount;
    }

    /**
     * @return string|null
     */
    public function getFeeAccountMuxed(): ?string
    {
        return $this->feeAccountMuxed;
    }

    /**
     * @return string|null
     */
    public function getFeeAccountMuxedId(): ?string
    {
        return $this->feeAccountMuxedId;
    }

    /**
     * @return string|null
     */
    public function getFeeCharged(): ?string
    {
        return $this->feeCharged;
    }

    /**
     * @return string|null
     */
    public function getMaxFee(): ?string
    {
        return $this->maxFee;
    }

    /**
     * @return int
     */
    public function getOperationCount(): int
    {
        return $this->operationCount;
    }

    /**
     * @return Memo
     */
    public function getMemo(): Memo
    {
        return $this->memo;
    }

    /**
     * @return XdrTransactionEnvelope
     */
    public function getEnvelopeXdr(): XdrTransactionEnvelope
    {
        return $this->envelopeXdr;
    }

    /**
     * @return XdrTransactionResult
     */
    public function getResultXdr(): XdrTransactionResult
    {
        return $this->resultXdr;
    }

    /**
     * @return XdrTransactionMeta
     */
    public function getResultMetaXdr(): XdrTransactionMeta
    {
        return $this->resultMetaXdr;
    }

    /**
     * @return string|null
     */
    public function getFeeMetaXdrBase64(): ?string
    {
        return $this->feeMetaXdrBase64;
    }

    /**
     * @return array|null
     */
    public function getFeeMetaXdr(): ?array
    {
        return $this->feeMetaXdr;
    }

    /**
     * @return string|null
     */
    public function getValidAfter(): ?string
    {
        return $this->validAfter;
    }

    /**
     * @return TransactionSignaturesResponse
     */
    public function getSignatures(): TransactionSignaturesResponse
    {
        return $this->signatures;
    }

    /**
     * @return FeeBumpTransactionResponse|null
     */
    public function getFeeBumpTransactionResponse(): ?FeeBumpTransactionResponse
    {
        return $this->feeBumpTransactionResponse;
    }

    /**
     * @return InnerTransactionResponse|null
     */
    public function getInnerTransactionResponse(): ?InnerTransactionResponse
    {
        return $this->innerTransactionResponse;
    }

    /**
     * @return TransactionLinksResponse
     */
    public function getLinks(): TransactionLinksResponse
    {
        return $this->links;
    }

    /**
     * @return TransactionPreconditionsResponse|null
     */
    public function getPreconditions(): ?TransactionPreconditionsResponse
    {
        return $this->preconditions;
    }

    /**
     * @return string
     */
    public function getEnvelopeXdrBase64(): string
    {
        return $this->envelopeXdrBase64;
    }

    /**
     * @return string
     */
    public function getResultXdrBase64(): string
    {
        return $this->resultXdrBase64;
    }

    /**
     * @return string
     */
    public function getResultMetaXdrBase64(): string
    {
        return $this->resultMetaXdrBase64;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['successful'])) $this->successful = $json['successful'];
        if (isset($json['hash'])) $this->hash = $json['hash'];
        if (isset($json['ledger'])) $this->ledger = $json['ledger'];
        if (isset($json['created_at'])) $this->createdAt = $json['created_at'];
        if (isset($json['source_account'])) $this->sourceAccount = $json['source_account'];
        if (isset($json['source_account_muxed'])) $this->sourceAccountMuxed = $json['source_account_muxed'];
        if (isset($json['source_account_muxed_id'])) $this->sourceAccountMuxedId = $json['source_account_muxed_id'];
        if (isset($json['source_account_sequence'])) $this->sourceAccountSequence = $json['source_account_sequence'];
        if (isset($json['fee_account'])) $this->feeAccount = $json['fee_account'];
        if (isset($json['fee_account_muxed'])) $this->feeAccountMuxed = $json['fee_account_muxed'];
        if (isset($json['fee_account_muxed_id'])) $this->feeAccountMuxedId = $json['fee_account_muxed_id'];
        if (isset($json['fee_charged'])) $this->feeCharged = $json['fee_charged'];
        if (isset($json['max_fee'])) $this->maxFee = $json['max_fee'];
        if (isset($json['operation_count'])) $this->operationCount = $json['operation_count'];
        if (isset($json['envelope_xdr'])) {
            $this->envelopeXdrBase64 = $json['envelope_xdr'];
            $this->envelopeXdr = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($this->envelopeXdrBase64);
        }
        if (isset($json['result_xdr'])){
            $this->resultXdrBase64 = $json['result_xdr'];
            $this->resultXdr = XdrTransactionResult::fromBase64Xdr($this->resultXdrBase64);
        }
        if (isset($json['result_meta_xdr'])) {
            $this->resultMetaXdrBase64 = $json['result_meta_xdr'];
            $this->resultMetaXdr = XdrTransactionMeta::fromBase64Xdr($this->resultMetaXdrBase64);
        }
        if (isset($json['fee_meta_xdr'])) {
            $this->feeMetaXdrBase64 = $json['fee_meta_xdr'];
            $xdr = base64_decode($this->feeMetaXdrBase64);
            $xdrBuffer = new XdrBuffer($xdr);
            $this->feeMetaXdr = array();
            $valCount = $xdrBuffer->readInteger32();
            for ($i = 0; $i < $valCount; $i++) {
                array_push($this->feeMetaXdr, XdrLedgerEntryChange::decode($xdrBuffer));
            }
        }
        if (isset($json['valid_after'])) $this->validAfter = $json['valid_after'];

        if (isset($json['memo_type'])) {
            $memoTypeStr = $json['memo_type'];
            $this->memo = match ($memoTypeStr) {
                "none" => Memo::none(),
                "text" => Memo::text($json['memo'] ?? ""),
                "id" => Memo::id((int)$json['memo']),
                "hash" => Memo::hash(base64_decode($json['memo'])),
                "return" => Memo::return(base64_decode($json['memo'])),
            };
        } else {
            $this->memo = Memo::none();
        }

        if (isset($json['signatures'])) {
            $this->signatures = new TransactionSignaturesResponse();
            foreach ($json['signatures'] as $signature) {
                $this->signatures->add($signature);
            }
        }

        if (isset($json['fee_bump_transaction'])) $this->feeBumpTransactionResponse = FeeBumpTransactionResponse::fromJson($json['fee_bump_transaction']);
        if (isset($json['inner_transaction'])) $this->innerTransactionResponse = InnerTransactionResponse::fromJson($json['inner_transaction']);
        if (isset($json['preconditions'])) $this->preconditions = TransactionPreconditionsResponse::fromJson($json['preconditions']);
        if (isset($json['_links'])) $this->links = TransactionLinksResponse::fromJson($json['_links']);

        parent::loadFromJson($json);
    }

    public static function fromJson(array $json) : TransactionResponse
    {
        $result = new TransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}