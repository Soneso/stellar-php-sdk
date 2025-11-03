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

/**
 * Represents a transaction that has been included in the Stellar ledger
 *
 * This response contains comprehensive transaction details including the source account,
 * fee information, operations, signatures, preconditions, and XDR representations of the
 * transaction envelope, result, and metadata. Transactions can be regular transactions or
 * fee-bump transactions wrapping inner transactions.
 *
 * Key fields:
 * - Transaction hash and ledger sequence
 * - Source account and fee account details
 * - Operation count and memo
 * - Success status and result codes
 * - XDR representations for envelope, result, and metadata
 * - Signatures and preconditions
 * - Fee-bump transaction details if applicable
 *
 * Returned by Horizon endpoints:
 * - GET /transactions/{transaction_hash} - Single transaction details
 * - GET /transactions - List of transactions
 * - GET /accounts/{account_id}/transactions - Account transactions
 * - GET /ledgers/{sequence}/transactions - Ledger transactions
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see SubmitTransactionResponse For transaction submission results
 * @see FeeBumpTransactionResponse For fee-bump transaction details
 * @see InnerTransactionResponse For inner transaction in fee-bump
 * @see https://developers.stellar.org/api/resources/transactions Horizon Transactions API
 * @since 1.0.0
 */
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
    private ?string $resultMetaXdrBase64 = null;
    private ?XdrTransactionMeta $resultMetaXdr = null;
    private ?string $feeMetaXdrBase64 = null; // todo resolve
    private ?array $feeMetaXdr = null;
    private TransactionSignaturesResponse $signatures;
    private ?FeeBumpTransactionResponse $feeBumpTransactionResponse = null;
    private ?InnerTransactionResponse $innerTransactionResponse = null;
    private TransactionLinksResponse $links;
    private ?TransactionPreconditionsResponse $preconditions;

    /**
     * Gets the unique identifier for this transaction
     *
     * @return string The transaction ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the paging token for this transaction in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Checks if the transaction was successful
     *
     * @return bool True if all operations succeeded, false if any failed
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the transaction hash
     *
     * @return string The 64-character hexadecimal transaction hash
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Gets the ledger sequence number where this transaction was included
     *
     * @return int The ledger sequence number
     */
    public function getLedger(): int
    {
        return $this->ledger;
    }

    /**
     * Gets the timestamp when this transaction was created
     *
     * @return string The creation time in ISO 8601 format
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Gets the source account for this transaction
     *
     * @return string The source account ID
     */
    public function getSourceAccount(): string
    {
        return $this->sourceAccount;
    }

    /**
     * Gets the multiplexed source account if applicable
     *
     * @return string|null The muxed source account address, or null if not muxed
     */
    public function getSourceAccountMuxed(): ?string
    {
        return $this->sourceAccountMuxed;
    }

    /**
     * Gets the multiplexed source account ID if applicable
     *
     * @return string|null The muxed account ID, or null if not muxed
     */
    public function getSourceAccountMuxedId(): ?string
    {
        return $this->sourceAccountMuxedId;
    }

    /**
     * Gets the sequence number used by this transaction
     *
     * @return string The source account sequence number
     */
    public function getSourceAccountSequence(): string
    {
        return $this->sourceAccountSequence;
    }

    /**
     * Gets the account that paid the transaction fee
     *
     * For fee-bump transactions, this differs from the source account.
     *
     * @return string The fee account ID
     */
    public function getFeeAccount(): string
    {
        return $this->feeAccount;
    }

    /**
     * Gets the multiplexed fee account if applicable
     *
     * @return string|null The muxed fee account address, or null if not muxed
     */
    public function getFeeAccountMuxed(): ?string
    {
        return $this->feeAccountMuxed;
    }

    /**
     * Gets the multiplexed fee account ID if applicable
     *
     * @return string|null The muxed fee account ID, or null if not muxed
     */
    public function getFeeAccountMuxedId(): ?string
    {
        return $this->feeAccountMuxedId;
    }

    /**
     * Gets the actual fee charged for this transaction in stroops
     *
     * @return string|null The fee charged as a string, or null if not available
     */
    public function getFeeCharged(): ?string
    {
        return $this->feeCharged;
    }

    /**
     * Gets the maximum fee the submitter was willing to pay in stroops
     *
     * @return string|null The max fee as a string, or null if not available
     */
    public function getMaxFee(): ?string
    {
        return $this->maxFee;
    }

    /**
     * Gets the number of operations in this transaction
     *
     * @return int The operation count
     */
    public function getOperationCount(): int
    {
        return $this->operationCount;
    }

    /**
     * Gets the memo attached to this transaction
     *
     * @return Memo The transaction memo
     */
    public function getMemo(): Memo
    {
        return $this->memo;
    }

    /**
     * Gets the parsed transaction envelope XDR
     *
     * @return XdrTransactionEnvelope The transaction envelope containing the transaction and signatures
     */
    public function getEnvelopeXdr(): XdrTransactionEnvelope
    {
        return $this->envelopeXdr;
    }

    /**
     * Gets the parsed transaction result XDR
     *
     * @return XdrTransactionResult The transaction result containing operation results
     */
    public function getResultXdr(): XdrTransactionResult
    {
        return $this->resultXdr;
    }

    /**
     * Gets the parsed transaction metadata XDR
     *
     * @return XdrTransactionMeta|null The transaction metadata containing ledger changes, or null
     */
    public function getResultMetaXdr(): ?XdrTransactionMeta
    {
        return $this->resultMetaXdr;
    }

    /**
     * Gets the base64-encoded fee metadata XDR
     *
     * @return string|null The fee metadata XDR, or null if not available
     */
    public function getFeeMetaXdrBase64(): ?string
    {
        return $this->feeMetaXdrBase64;
    }

    /**
     * Gets the parsed fee metadata XDR as ledger entry changes
     *
     * @return array|null Array of XdrLedgerEntryChange objects, or null
     */
    public function getFeeMetaXdr(): ?array
    {
        return $this->feeMetaXdr;
    }

    /**
     * Gets the signatures attached to this transaction
     *
     * @return TransactionSignaturesResponse Collection of signature strings
     */
    public function getSignatures(): TransactionSignaturesResponse
    {
        return $this->signatures;
    }

    /**
     * Gets the fee-bump transaction details if this is a fee-bump transaction
     *
     * @return FeeBumpTransactionResponse|null Fee-bump details, or null if not fee-bumped
     */
    public function getFeeBumpTransactionResponse(): ?FeeBumpTransactionResponse
    {
        return $this->feeBumpTransactionResponse;
    }

    /**
     * Gets the inner transaction details if this is a fee-bump transaction
     *
     * @return InnerTransactionResponse|null Inner transaction details, or null if not fee-bumped
     */
    public function getInnerTransactionResponse(): ?InnerTransactionResponse
    {
        return $this->innerTransactionResponse;
    }

    /**
     * Gets the hypermedia links to related resources
     *
     * @return TransactionLinksResponse Links to account, ledger, operations, effects, etc.
     */
    public function getLinks(): TransactionLinksResponse
    {
        return $this->links;
    }

    /**
     * Gets the transaction preconditions if any were set
     *
     * @return TransactionPreconditionsResponse|null Preconditions including time bounds, or null
     */
    public function getPreconditions(): ?TransactionPreconditionsResponse
    {
        return $this->preconditions;
    }

    /**
     * Gets the base64-encoded transaction envelope XDR
     *
     * @return string The envelope XDR as a base64 string
     */
    public function getEnvelopeXdrBase64(): string
    {
        return $this->envelopeXdrBase64;
    }

    /**
     * Gets the base64-encoded transaction result XDR
     *
     * @return string The result XDR as a base64 string
     */
    public function getResultXdrBase64(): string
    {
        return $this->resultXdrBase64;
    }

    /**
     * Gets the base64-encoded transaction metadata XDR
     *
     * @return string|null The metadata XDR as a base64 string, or null
     */
    public function getResultMetaXdrBase64(): ?string
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

    /**
     * Creates a TransactionResponse instance from JSON data
     *
     * @param array $json The JSON array containing transaction data from Horizon
     * @return TransactionResponse The parsed transaction response
     */
    public static function fromJson(array $json) : TransactionResponse
    {
        $result = new TransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}