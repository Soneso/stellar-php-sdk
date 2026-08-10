<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use function PHPUnit\Framework\assertEquals;

class TestUtils
{

    /**
     * Funds $accountId via Friendbot and waits until the account is visible to
     * the given endpoints.
     *
     * A Friendbot success does not imply Soroban RPC or Horizon can serve the
     * account yet: both ingest ledgers independently and answer from
     * load-balanced replicas that can disagree while ingestion converges, so a
     * single successful lookup is weak evidence. This helper requires
     * $requiredConsecutiveSuccesses rounds in which every given endpoint
     * serves the account before it returns. A fixed sleep cannot express
     * this: it is either too short on a lagging day or wasted time on a fast
     * one.
     *
     * Pass the endpoint instance(s) the test uses next; at least one is
     * required. The caller keeps ownership of them.
     *
     * @param string $accountId account to fund and await
     * @param SorobanServer|null $rpc Soroban RPC server that must serve the account
     * @param StellarSDK|null $horizon Horizon instance that must serve the account
     * @param bool $useFuturenet fund via the Futurenet Friendbot instead of testnet
     * @param int $pollIntervalSeconds delay before retrying after a failed round
     * @param int $confirmationIntervalSeconds delay between successful confirmation rounds
     * @param int $requiredConsecutiveSuccesses rounds in which every endpoint must serve the account, consecutively
     * @param int $timeoutSeconds maximum elapsed wall-clock time to wait, including the lookups themselves
     * @throws RuntimeException when visibility is not confirmed within $timeoutSeconds;
     *         a failed funding request (for example Friendbot answering 400 for an
     *         already-funded account, or 429 under rate limiting) is not itself
     *         fatal - the visibility poll decides the outcome
     */
    public static function fundTestAccountAndAwaitVisibility(
        string $accountId,
        ?SorobanServer $rpc = null,
        ?StellarSDK $horizon = null,
        bool $useFuturenet = false,
        int $pollIntervalSeconds = 3,
        int $confirmationIntervalSeconds = 1,
        int $requiredConsecutiveSuccesses = 3,
        int $timeoutSeconds = 90,
    ): void {
        if ($rpc === null && $horizon === null) {
            throw new RuntimeException('At least one of rpc or horizon must be provided to await visibility');
        }

        // Friendbot's HTTP errors (400 for an already-funded account, 429 under
        // rate limiting) are not fatal: the account may well be visible, and the
        // visibility poll below is the authority on the outcome.
        $funded = false;
        $fundingFailure = null;
        try {
            $funded = $useFuturenet
                ? FuturenetFriendBot::fundTestAccount($accountId)
                : FriendBot::fundTestAccount($accountId);
        } catch (GuzzleException $e) {
            $fundingFailure = $e->getMessage();
        }

        $streak = 0;
        $lastRpcFailure = null;
        $lastHorizonFailure = null;
        $start = microtime(true);

        while (true) {
            $roundOk = true;
            if ($rpc !== null) {
                try {
                    if ($rpc->getAccount($accountId) === null) {
                        $roundOk = false;
                        $lastRpcFailure = 'account entry not found';
                    }
                } catch (Exception $e) {
                    $roundOk = false;
                    $lastRpcFailure = $e->getMessage();
                }
            }
            if ($horizon !== null) {
                try {
                    $horizon->requestAccount($accountId);
                } catch (Exception $e) {
                    $roundOk = false;
                    $lastHorizonFailure = $e->getMessage();
                }
            }

            $streak = $roundOk ? $streak + 1 : 0;
            if ($streak >= $requiredConsecutiveSuccesses) {
                return;
            }
            if (microtime(true) - $start >= $timeoutSeconds) {
                $message = sprintf(
                    'Account %s was not served consistently within %d s after Friendbot %s',
                    $accountId,
                    $timeoutSeconds,
                    $funded ? 'funded it' : 'did not confirm funding',
                );
                if ($fundingFailure !== null) {
                    $message .= '; funding request failed: ' . $fundingFailure;
                }
                if ($lastRpcFailure !== null) {
                    $message .= '; last RPC failure: ' . $lastRpcFailure;
                }
                if ($lastHorizonFailure !== null) {
                    $message .= '; last Horizon failure: ' . $lastHorizonFailure;
                }
                throw new RuntimeException($message);
            }
            sleep($roundOk ? $confirmationIntervalSeconds : $pollIntervalSeconds);
        }
    }

    public static function resultDeAndEncodingTest(TestCase $testCase, AbstractTransaction $transaction, SubmitTransactionResponse $response) {
        // check decoding & encoding
        $meta = $response->getResultMetaXdr();
        $responseMeta = $response->getResultMetaXdrBase64();
        if ($responseMeta !== null && $meta !== null) {
            $testCase->assertEquals($responseMeta, $meta->toBase64Xdr());
        }
        $envelopeBase64 = $response->getEnvelopeXdrBase64();
        $testCase->assertEquals($envelopeBase64, $transaction->toEnvelopeXdrBase64());
        $result = $response->getResultXdr();
        $testCase->assertEquals($response->getResultXdrBase64(), $result->toBase64Xdr());
        $feeMeta = $response->getFeeMetaXdrBase64();
        $feeMetaXdr = $response->getFeeMetaXdr();
        if ($feeMetaXdr != null) {
            $bytes = XdrEncoder::integer32(count($feeMetaXdr));
            foreach($feeMetaXdr as $val) {
                if ($val instanceof XdrLedgerEntryChange) {
                    $bytes .= $val->encode();
                }
            }
            assertEquals($feeMeta, base64_encode($bytes));
        }
    }
}