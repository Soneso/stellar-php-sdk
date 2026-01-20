<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Errors\HorizonErrorResponse;
use Soneso\StellarSDK\Responses\Errors\HorizonErrorResponseExtras;

/**
 * Unit tests for Error Response classes
 *
 * Tests JSON parsing and getter methods for HorizonErrorResponse and HorizonErrorResponseExtras.
 * Covers various error scenarios including 404, 400, transaction failures, and operation-level errors.
 */
class ErrorResponseTest extends TestCase
{
    // HorizonErrorResponse Tests

    public function testNotFoundError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/not_found',
            'title' => 'Resource Missing',
            'status' => 404,
            'detail' => 'The resource at the url requested was not found. This usually occurs for one of two reasons: The url requested is not valid, or no data in our database could be found with the parameters provided.'
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/not_found', $error->getType());
        $this->assertEquals('Resource Missing', $error->getTitle());
        $this->assertEquals(404, $error->getStatus());
        $this->assertEquals('The resource at the url requested was not found. This usually occurs for one of two reasons: The url requested is not valid, or no data in our database could be found with the parameters provided.', $error->getDetail());
        $this->assertNull($error->getExtras());
        $this->assertNull($error->getInstance());
        $this->assertNull($error->getExtrasJson());
    }

    public function testBadRequestError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/bad_request',
            'title' => 'Bad Request',
            'status' => 400,
            'detail' => 'The request you sent was invalid in some way.',
            'instance' => 'horizon-req-12345'
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/bad_request', $error->getType());
        $this->assertEquals('Bad Request', $error->getTitle());
        $this->assertEquals(400, $error->getStatus());
        $this->assertEquals('The request you sent was invalid in some way.', $error->getDetail());
        $this->assertEquals('horizon-req-12345', $error->getInstance());
        $this->assertNull($error->getExtras());
    }

    public function testTransactionFailedError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/transaction_failed',
            'title' => 'Transaction Failed',
            'status' => 400,
            'detail' => 'The transaction failed when submitted to the stellar network. The extras.result_codes field on this response contains further details.',
            'extras' => [
                'envelope_xdr' => 'AAAAAgAAAABRms7LD3Z8xJCh3MfXnmII1P8KeSpRLl4IgsI5FUunNAAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAABgNUO0AAAAAAAAAAEAAAABAAAAACEHLqkO+hRTLAROj/XYWiX22Llwa7F/EN/FPca3iiAvAAAABwAAAABRms7LD3Z8xJCh3MfXnmII1P8KeSpRLl4IgsI5FUunNAAAABBURVNUAAAAAAAhBy6pDvoUUywETo/12Fol9ti5cGuxfxDfxT3Gt4ogLwAAAAAAAABkAAAAAQAAAABRms7LD3Z8xJCh3MfXnmII1P8KeSpRLl4IgsI5FUunNAAAABBURVNUAAAAAAAhBy6pDvoUUywETo/12Fol9ti5cGuxfxDfxT3Gt4ogLwAAAAA7msoAAAAAAAAAAtxLpzQAAABAwCWDd3c1Y9L6j+vQ5cPTMVDaU+2hAiW+RXD3lzWMNj1VFvJT88UJMt0W4dBY6v8l6x6x7EG3VpkxOPz0T5LBCiaKIC8AAABAjB4BEdA5WpjqSXjdBhJlULJvl+/1gsgBVR9y0KlVcHMXWFWvnHw5rnqyp0bJqVzfJPpXHOCKhPGDC9C4GkrQCw==',
                'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAH////+wAAAAA=',
                'result_codes' => [
                    'transaction' => 'tx_failed',
                    'operations' => [
                        'op_no_trust'
                    ]
                ]
            ]
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/transaction_failed', $error->getType());
        $this->assertEquals('Transaction Failed', $error->getTitle());
        $this->assertEquals(400, $error->getStatus());
        $this->assertStringContainsString('The transaction failed when submitted', $error->getDetail());

        $extras = $error->getExtras();
        $this->assertInstanceOf(HorizonErrorResponseExtras::class, $extras);
        $this->assertNotNull($extras->getEnvelopeXdr());
        $this->assertStringContainsString('AAAAAgAAAABRms7LD3Z8xJCh3MfXnmII', $extras->getEnvelopeXdr());
        $this->assertNotNull($extras->getResultXdr());
        $this->assertEquals('AAAAAAAAAGT/////AAAAAQAAAAAAAAAH////+wAAAAA=', $extras->getResultXdr());
        $this->assertEquals('tx_failed', $extras->getResultCodesTransaction());
        $this->assertIsArray($extras->getResultCodesOperation());
        $this->assertCount(1, $extras->getResultCodesOperation());
        $this->assertEquals('op_no_trust', $extras->getResultCodesOperation()[0]);
        $this->assertNull($extras->getTxHash());

        $extrasJson = $error->getExtrasJson();
        $this->assertNotNull($extrasJson);
        $this->assertIsArray($extrasJson);
        $this->assertArrayHasKey('envelope_xdr', $extrasJson);
        $this->assertArrayHasKey('result_codes', $extrasJson);
    }

    public function testTransactionFailedWithMultipleOperations(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/transaction_failed',
            'title' => 'Transaction Failed',
            'status' => 400,
            'detail' => 'The transaction failed when submitted to the stellar network.',
            'extras' => [
                'envelope_xdr' => 'AAAAAGL8HQvQkbK2HA3WVjRrKmjX00fG8sLI7m0ERwJW/AX3AAAACgAAAAAAAAABAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAArqN6LeOagjxMaUP96Bzfs9e77YB7hxRY6TWLGPcGxWUAAAAXSHboAAAAAAAAAAABVvwF9wAAAEAKZ7IPj/46PuWU6ZOcTQqF8OnsD51r0G7NZcSPHnXTULXX3o54+3hPLv1eKKh7R5d9FzXhTUbGpYRUjhp5FJ4E',
                'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAA////+wAAAAA=',
                'result_codes' => [
                    'transaction' => 'tx_failed',
                    'operations' => [
                        'op_underfunded',
                        'op_success',
                        'op_no_destination'
                    ]
                ],
                'hash' => 'd9c1c7f3d2f8e4b1a0c6d5e2f8a9b7c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a8'
            ]
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals(400, $error->getStatus());

        $extras = $error->getExtras();
        $this->assertInstanceOf(HorizonErrorResponseExtras::class, $extras);
        $this->assertEquals('tx_failed', $extras->getResultCodesTransaction());

        $opCodes = $extras->getResultCodesOperation();
        $this->assertIsArray($opCodes);
        $this->assertCount(3, $opCodes);
        $this->assertEquals('op_underfunded', $opCodes[0]);
        $this->assertEquals('op_success', $opCodes[1]);
        $this->assertEquals('op_no_destination', $opCodes[2]);

        $this->assertEquals('d9c1c7f3d2f8e4b1a0c6d5e2f8a9b7c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a8', $extras->getTxHash());
    }

    public function testRateLimitError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/rate_limit_exceeded',
            'title' => 'Rate Limit Exceeded',
            'status' => 429,
            'detail' => 'The rate limit for the requesting IP address is over its alloted quota. The current request rate quota is 3600 requests per hour.'
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/rate_limit_exceeded', $error->getType());
        $this->assertEquals('Rate Limit Exceeded', $error->getTitle());
        $this->assertEquals(429, $error->getStatus());
        $this->assertStringContainsString('rate limit', $error->getDetail());
    }

    public function testInternalServerError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/server_error',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'An error occurred while processing this request. We have been notified.'
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/server_error', $error->getType());
        $this->assertEquals('Internal Server Error', $error->getTitle());
        $this->assertEquals(500, $error->getStatus());
        $this->assertStringContainsString('error occurred while processing', $error->getDetail());
    }

    public function testTimeoutError(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/timeout',
            'title' => 'Timeout',
            'status' => 504,
            'detail' => 'Your request timed out before completing. Please try your request again. If you are submitting a transaction, please check the status before retrying.'
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/timeout', $error->getType());
        $this->assertEquals('Timeout', $error->getTitle());
        $this->assertEquals(504, $error->getStatus());
        $this->assertStringContainsString('timed out', $error->getDetail());
    }

    // HorizonErrorResponseExtras Tests

    public function testExtrasWithAllFields(): void
    {
        $json = [
            'envelope_xdr' => 'AAAAAgAAAABRms7LD3Z8xJCh3MfXnmII1P8KeSpRLl4IgsI5FUunNAAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAABgNUO0AAAAAAAAAA==',
            'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAH////+wAAAAA=',
            'result_codes' => [
                'transaction' => 'tx_bad_seq',
                'operations' => [
                    'op_success'
                ]
            ],
            'hash' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2'
        ];

        $extras = HorizonErrorResponseExtras::fromJson($json);

        $this->assertStringContainsString('AAAAAgAAAABRms7LD3Z8xJCh', $extras->getEnvelopeXdr());
        $this->assertEquals('AAAAAAAAAGT/////AAAAAQAAAAAAAAAH////+wAAAAA=', $extras->getResultXdr());
        $this->assertEquals('tx_bad_seq', $extras->getResultCodesTransaction());
        $this->assertIsArray($extras->getResultCodesOperation());
        $this->assertCount(1, $extras->getResultCodesOperation());
        $this->assertEquals('op_success', $extras->getResultCodesOperation()[0]);
        $this->assertEquals('a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2', $extras->getTxHash());
    }

    public function testExtrasWithMinimalFields(): void
    {
        $json = [
            'result_codes' => [
                'transaction' => 'tx_insufficient_fee'
            ]
        ];

        $extras = HorizonErrorResponseExtras::fromJson($json);

        $this->assertNull($extras->getEnvelopeXdr());
        $this->assertNull($extras->getResultXdr());
        $this->assertEquals('tx_insufficient_fee', $extras->getResultCodesTransaction());
        $this->assertIsArray($extras->getResultCodesOperation());
        $this->assertCount(0, $extras->getResultCodesOperation());
        $this->assertNull($extras->getTxHash());
    }

    public function testExtrasWithNoOperations(): void
    {
        $json = [
            'envelope_xdr' => 'AAAAAGL8HQvQkbK2HA3WVjRrKmjX00fG8sLI7m0ERwJW/AX3AAAACgAAAAAAAAABAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAA',
            'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAA/////AAAAAA=',
            'result_codes' => [
                'transaction' => 'tx_bad_auth'
            ],
            'hash' => 'tx123456'
        ];

        $extras = HorizonErrorResponseExtras::fromJson($json);

        $this->assertNotNull($extras->getEnvelopeXdr());
        $this->assertNotNull($extras->getResultXdr());
        $this->assertEquals('tx_bad_auth', $extras->getResultCodesTransaction());
        $this->assertIsArray($extras->getResultCodesOperation());
        $this->assertCount(0, $extras->getResultCodesOperation());
        $this->assertEquals('tx123456', $extras->getTxHash());
    }

    public function testExtrasWithEmptyJson(): void
    {
        $json = [];

        $extras = HorizonErrorResponseExtras::fromJson($json);

        $this->assertNull($extras->getEnvelopeXdr());
        $this->assertNull($extras->getResultXdr());
        $this->assertNull($extras->getResultCodesTransaction());
        $this->assertNull($extras->getResultCodesOperation());
        $this->assertNull($extras->getTxHash());
    }

    public function testErrorResponseWithPartialData(): void
    {
        $json = [
            'type' => 'https://stellar.org/horizon-errors/transaction_malformed',
            'title' => 'Transaction Malformed',
            'status' => 400
        ];

        $error = HorizonErrorResponse::fromJson($json);

        $this->assertEquals('https://stellar.org/horizon-errors/transaction_malformed', $error->getType());
        $this->assertEquals('Transaction Malformed', $error->getTitle());
        $this->assertEquals(400, $error->getStatus());
        $this->assertNull($error->getInstance());
        $this->assertNull($error->getExtras());
    }

    public function testCommonTransactionResultCodes(): void
    {
        $testCases = [
            'tx_failed' => 'One or more operations failed',
            'tx_too_early' => 'Ledger time is before minTime',
            'tx_too_late' => 'Ledger time is after maxTime',
            'tx_missing_operation' => 'No operation was specified',
            'tx_bad_seq' => 'Sequence number does not match source account',
            'tx_bad_auth' => 'Too few valid signatures or wrong network',
            'tx_insufficient_balance' => 'Fee would bring account below reserve',
            'tx_no_source_account' => 'Source account not found',
            'tx_insufficient_fee' => 'Fee is too small',
            'tx_bad_auth_extra' => 'Unused signatures attached to transaction',
            'tx_internal_error' => 'Unknown error occurred'
        ];

        foreach ($testCases as $code => $description) {
            $json = [
                'type' => 'https://stellar.org/horizon-errors/transaction_failed',
                'title' => 'Transaction Failed',
                'status' => 400,
                'detail' => $description,
                'extras' => [
                    'result_codes' => [
                        'transaction' => $code
                    ]
                ]
            ];

            $error = HorizonErrorResponse::fromJson($json);
            $extras = $error->getExtras();

            $this->assertNotNull($extras);
            $this->assertEquals($code, $extras->getResultCodesTransaction());
        }
    }

    public function testCommonOperationResultCodes(): void
    {
        $operationCodes = [
            'op_inner',
            'op_bad_auth',
            'op_no_source_account',
            'op_not_supported',
            'op_too_many_subentries',
            'op_exceeded_work_limit',
            'op_too_many_sponsoring',
            'op_underfunded',
            'op_no_trust',
            'op_no_issuer',
            'op_line_full',
            'op_no_destination',
            'op_malformed',
            'op_success'
        ];

        $json = [
            'type' => 'https://stellar.org/horizon-errors/transaction_failed',
            'title' => 'Transaction Failed',
            'status' => 400,
            'detail' => 'Transaction failed with operation errors',
            'extras' => [
                'result_codes' => [
                    'transaction' => 'tx_failed',
                    'operations' => $operationCodes
                ]
            ]
        ];

        $error = HorizonErrorResponse::fromJson($json);
        $extras = $error->getExtras();

        $this->assertNotNull($extras);
        $this->assertEquals('tx_failed', $extras->getResultCodesTransaction());

        $opCodes = $extras->getResultCodesOperation();
        $this->assertIsArray($opCodes);
        $this->assertCount(count($operationCodes), $opCodes);

        foreach ($operationCodes as $index => $code) {
            $this->assertEquals($code, $opCodes[$index]);
        }
    }
}
