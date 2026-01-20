<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionPreconditionsResponse;
use Soneso\StellarSDK\Responses\Transaction\PreconditionsTimeBoundsResponse;
use Soneso\StellarSDK\Responses\Transaction\PreconditionsLedgerBoundsResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionLinksResponse;
use Soneso\StellarSDK\Responses\Transaction\InnerTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\FeeBumpTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionSignaturesResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsResponse;

/**
 * Unit tests for all Transaction Response classes
 *
 * Tests JSON parsing and getter methods for Transaction-related response classes.
 * Covers TransactionResponse, TransactionsPageResponse, preconditions, links,
 * fee-bump transactions, and all related response types.
 */
class TransactionResponseTest extends TestCase
{
    /**
     * Helper method to create complete transaction JSON data
     */
    private function getCompleteTransactionJson(): array
    {
        return [
            'memo' => '0,075% Daily for Holders',
            'memo_bytes' => 'MCwwNzUlIERhaWx5IGZvciBIb2xkZXJz',
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/transactions/a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486'
                ],
                'account' => [
                    'href' => 'https://horizon.stellar.org/accounts/GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O'
                ],
                'ledger' => [
                    'href' => 'https://horizon.stellar.org/ledgers/52429011'
                ],
                'operations' => [
                    'href' => 'https://horizon.stellar.org/transactions/a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486/operations{?cursor,limit,order}',
                    'templated' => true
                ],
                'effects' => [
                    'href' => 'https://horizon.stellar.org/transactions/a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486/effects{?cursor,limit,order}',
                    'templated' => true
                ],
                'precedes' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=asc&cursor=225180887607500800'
                ],
                'succeeds' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=desc&cursor=225180887607500800'
                ],
                'transaction' => [
                    'href' => 'https://horizon.stellar.org/transactions/a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486'
                ]
            ],
            'id' => 'a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486',
            'paging_token' => '225180887607500800',
            'successful' => true,
            'hash' => 'a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486',
            'ledger' => 52429011,
            'created_at' => '2024-07-05T05:51:31Z',
            'source_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'source_account_sequence' => '224884019467125027',
            'fee_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'fee_charged' => '100',
            'max_fee' => '100',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'text',
            'signatures' => [
                'ua2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDQ==',
                'LbDjlPdLbiyGQnu7JGX8/nEPzpd+LupNjGZvy6Hw8RzCVvmipuclxOVRXa4mzb6DMyWKM20BusKbrA77ViwlDg=='
            ],
            'valid_after' => '1970-01-01T00:00:00Z',
            'valid_before' => '2024-07-05T05:53:07Z',
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0',
                    'max_time' => '1720158787'
                ]
            ]
        ];
    }

    /**
     * Helper method to create failed transaction JSON data
     */
    private function getFailedTransactionJson(): array
    {
        return [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/transactions/ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d'
                ],
                'account' => [
                    'href' => 'https://horizon.stellar.org/accounts/GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD'
                ],
                'ledger' => [
                    'href' => 'https://horizon.stellar.org/ledgers/52429114'
                ],
                'operations' => [
                    'href' => 'https://horizon.stellar.org/transactions/ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d/operations{?cursor,limit,order}',
                    'templated' => true
                ],
                'effects' => [
                    'href' => 'https://horizon.stellar.org/transactions/ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d/effects{?cursor,limit,order}',
                    'templated' => true
                ],
                'precedes' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=asc&cursor=225181329989492736'
                ],
                'succeeds' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=desc&cursor=225181329989492736'
                ],
                'transaction' => [
                    'href' => 'https://horizon.stellar.org/transactions/ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d'
                ]
            ],
            'id' => 'ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d',
            'paging_token' => '225181329989492736',
            'successful' => false,
            'hash' => 'ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d',
            'ledger' => 52429114,
            'created_at' => '2024-07-05T06:01:20Z',
            'source_account' => 'GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD',
            'source_account_sequence' => '169774989149474823',
            'fee_account' => 'GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD',
            'fee_charged' => '100',
            'max_fee' => '101',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAGUCWyl0AAAQBwAAAAEAAAAAAAAAAAAAAABmh4w6AAAAAAAAAAEAAAABAAAAABDR1OY5pIP4DuC0MK3Wk8y/Cq8IWqrdDi5A0Fi5fOrjAAAAAgAAAAAAAAAAB8Ap6QAAAAAQ0dTmOaSD+A7gtDCt1pPMvwqvCFqq3Q4uQNBYuXzq4wAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAfAKekAAAADAAAAAUJWTgAAAAAAEShm+lTZUjjj2ZcQshA+s474NGCWrBqmnq9nd6WvfqgAAAABQVFVQQAAAABblC5TrDPI/QqAzHwbGoXX2DipxBl3qtGLOvBX+OM98AAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAAAAAKxGj/aAAAAQCjuVlhfl6G9ckJsEz4GwbOJWszHxtG7Lpja6yGjhC8W40yf/Uyc2AFlyMPxY3ujPyqc1yA7YeapeGnGNMLO/Ae5fOrjAAAAQK1szEc5G1Tk17+q1DjW39+N/01CtgQ/584UvKjUSbogqp8JHn6PxMK2iZC099p5GVcPE51kBKobhXl46yKPWAk=',
            'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAC////9gAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgAToAAAAAAAAAAAXmRbXLalUCESmqkLc/3yezvSfbqzVjm9ymN+axGj/aAAAAAADw28wCWyl0AAAQBgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAx//TwAAAABmh4DsAAAAAAAAAAEDIAE6AAAAAAAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAAAA8NvMAlspdAAAEAcAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAToAAAAAZoeMMAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDH/9PAAAAAAAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAAAA8NwwAlspdAAAEAYAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMf/08AAAAAZoeA7AAAAAAAAAABAyABOgAAAAAAAAAABeZFtctqVQIRKaqQtz/fJ7O9J9urNWOb3KY35rEaP9oAAAAAAPDbzAJbKXQAABAGAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADH/9PAAAAAGaHgOwAAAAA',
            'memo_type' => 'none',
            'signatures' => [
                'KO5WWF+Xob1yQmwTPgbBs4lazMfG0bsumNrrIaOELxbjTJ/9TJzYAWXIw/Fje6M/KpzXIDth5ql4acY0ws78Bw==',
                'rWzMRzkbVOTXv6rUONbf343/TUK2BD/nzhS8qNRJuiCqnwkefo/EwraJkLT32nkZVw8TnWQEqhuFeXjrIo9YCQ=='
            ],
            'valid_after' => '1970-01-01T00:00:00Z',
            'valid_before' => '2024-07-05T06:01:30Z',
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0',
                    'max_time' => '1720159290'
                ]
            ]
        ];
    }

    /**
     * Helper method to create fee-bump transaction JSON data
     */
    private function getFeeBumpTransactionJson(): array
    {
        return [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/transactions/01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0'
                ],
                'account' => [
                    'href' => 'https://horizon.stellar.org/accounts/GBYODLTHLR7PPEVS2LSXHXHWSL4OGMEFFIINE23RPWJAWVN6WZXZD25O'
                ],
                'ledger' => [
                    'href' => 'https://horizon.stellar.org/ledgers/52428726'
                ],
                'operations' => [
                    'href' => 'https://horizon.stellar.org/transactions/01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0/operations{?cursor,limit,order}',
                    'templated' => true
                ],
                'effects' => [
                    'href' => 'https://horizon.stellar.org/transactions/01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0/effects{?cursor,limit,order}',
                    'templated' => true
                ],
                'precedes' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=asc&cursor=225179663542026240'
                ],
                'succeeds' => [
                    'href' => 'https://horizon.stellar.org/transactions?order=desc&cursor=225179663542026240'
                ],
                'transaction' => [
                    'href' => 'https://horizon.stellar.org/transactions/01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0'
                ]
            ],
            'id' => '01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0',
            'paging_token' => '225179663542026240',
            'successful' => true,
            'hash' => '01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0',
            'ledger' => 52428726,
            'created_at' => '2024-07-05T05:23:21Z',
            'source_account' => 'GBYODLTHLR7PPEVS2LSXHXHWSL4OGMEFFIINE23RPWJAWVN6WZXZD25O',
            'source_account_sequence' => '210576297993224856',
            'fee_account' => 'GA4QH4AJGERVYX4PBY55JYTQJ4RTLJIBYV7OCYIV56LWZE5MVDH3R3UQ',
            'fee_charged' => '200',
            'max_fee' => '2000',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAABQAAAAA5A/AJMSNcX48OO9TicE8jNaUBxX7hYRXvl2yTrKjPuAAAAAAAAAfQAAAAAgAAAABw4a5nXH73krLS5XPc9pL44zCFKhDSa3F9kgtVvrZvkQAAAfQC7B4HAADCmAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABAAAAADkD8AkxI1xfjw471OJwTyM1pQHFfuFhFe+XbJOsqM+4AAAAAgAAAAAAAAAAAK07GwAAAAA5A/AJMSNcX48OO9TicE8jNaUBxX7hYRXvl2yTrKjPuAAAAAAAAAAAAK07GwAAAAQAAAABRVVSQwAAAAAhEu6GOGfk4hn+JUwJGLALyepAB3W/w6tEMJcc5QWHfAAAAAFFVVJDAAAAAM9PWibiCQuzrc8Cx6nXPb/mZZzGkEYUdbhkN/pJxxE2AAAAAlRSRUFEAAAAAAAAAAAAAAAaxUy/lXBD54Ka4qnphtOoloyA2k2TTpM8MuzKyTnoXwAAAAFSSU8AAAAAAFq0owffKWd3GSg2DZx7b/0fDcje28S5tHHITGcCBiZEAAAAAAAAAAK+tm+RAAAAQB5YrqChrpvERbms5JpORIDhgp0tSdij6fsEyOH0op6drai67cXR8en0Zq0V8iPJRYizIYPq5KT/Bier5x8ZeASsqM+4AAAAQHybFBoDIm9sKCY4Xk/l7FQZMD95zVdERcPpmR6h/ZnfQIOMM7Ox3ib62egOs2yYUSfe40CIE0bh13R0/7ARTQcAAAAAAAAAAayoz7gAAABAQsnj9i9Hdyd+zIAyhN5nuPoAPvj86+0lYuKa5GRw1RVwmln9pD+yVpa3khXQl9/ClDmQR0zkeueZ0A/+LOBdBw==',
            'result_xdr' => 'AAAAAAAAAMgAAAAB6Wu236AIXgPBnI37bf+Buz0A4l6Ao9LywceV+Isva3wAAAAAAAAAZAAAAAAAAAABAAAAAAAAAAIAAAAAAAAABQAAAAEAAAAAv5UpyODGsIW9jfBMKuIHYgMqKv4BISRZtczHuquBHYQAAAAAXgEqWAAAAAFFVVJDAAAAACES7oY4Z+TiGf4lTAkYsAvJ6kAHdb/Dq0QwlxzlBYd8AAAAAAAM4PAAAAAAAAAAAACtAbcAAAABAAAAAGDUJt0fH5dFBg0jqqzXHGrOvEYSBqMeBKoAcKLdQ/iNAAAAAF4Ank0AAAABRVVSQwAAAADPT1om4gkLs63PAsep1z2/5mWcxpBGFHW4ZDf6SccRNgAAAAAADODxAAAAAUVVUkMAAAAAIRLuhjhn5OIZ/iVMCRiwC8nqQAd1v8OrRDCXHOUFh3wAAAAAAAzg8AAAAAI+ON0IMwx/sTDDe3bkCEPcDlYoUS3sszh70NPuvLmLxgAAAAJUUkVBRAAAAAAAAAAAAAAAGsVMv5VwQ+eCmuKp6YbTqJaMgNpNk06TPDLsysk56F8AAAAAAAVOGAAAAAFFVVJDAAAAAM9PWibiCQuzrc8Cx6nXPb/mZZzGkEYUdbhkN/pJxxE2AAAAAAAM4PEAAAACLHxDBIARzAJRBjq0ChtQm0P444jEglhZryWWiRSQrLgAAAABUklPAAAAAABatKMH3ylndxkoNg2ce2/9Hw3I3tvEubRxyExnAgYmRAAAAAAADrNGAAAAAlRSRUFEAAAAAAAAAAAAAAAaxUy/lXBD54Ka4qnphtOoloyA2k2TTpM8MuzKyTnoXwAAAAAABU4YAAAAAg67zEwJ0pYWpvibc02exb+/tOcu8ioAIQ7e2x/VCIE3AAAAAAAAAAAArTsbAAAAAVJJTwAAAAAAWrSjB98pZ3cZKDYNnHtv/R8NyN7bxLm0cchMZwIGJkQAAAAAAA6zRgAAAAA5A/AJMSNcX48OO9TicE8jNaUBxX7hYRXvl2yTrKjPuAAAAAAAAAAAAK07GwAAAAAAAAAA',
            'result_meta_xdr' => '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',
            'fee_meta_xdr' => 'AAAAAgAAAAMDH/+2AAAAAAAAAAA5A/AJMSNcX48OO9TicE8jNaUBxX7hYRXvl2yTrKjPuAAAAAAXf10vAtuKcQAADV8AAAADAAAAAQAAAADEccZDcGLJUGqJNC5TihraQE0vQc8dOiVfQyH3xuDhdQAAAAAAAAAJbG9ic3RyLmNvAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMdiZAAAAAAZnlTNwAAAAAAAAABAx//tgAAAAAAAAAAOQPwCTEjXF+PDjvU4nBPIzWlAcV+4WEV75dsk6yoz7gAAAAAF39cZwLbinEAAA1fAAAAAwAAAAEAAAAAxHHGQ3BiyVBqiTQuU4oa2kBNL0HPHTolX0Mh98bg4XUAAAAAAAAACWxvYnN0ci5jbwAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADHYmQAAAAAGZ5UzcAAAAA',
            'memo_type' => 'none',
            'signatures' => [
                'Qsnj9i9Hdyd+zIAyhN5nuPoAPvj86+0lYuKa5GRw1RVwmln9pD+yVpa3khXQl9/ClDmQR0zkeueZ0A/+LOBdBw=='
            ],
            'valid_after' => '1970-01-01T00:00:00Z',
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0'
                ]
            ],
            'fee_bump_transaction' => [
                'hash' => '01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0',
                'signatures' => [
                    'Qsnj9i9Hdyd+zIAyhN5nuPoAPvj86+0lYuKa5GRw1RVwmln9pD+yVpa3khXQl9/ClDmQR0zkeueZ0A/+LOBdBw=='
                ]
            ],
            'inner_transaction' => [
                'hash' => 'e96bb6dfa0085e03c19c8dfb6dff81bb3d00e25e80a3d2f2c1c795f88b2f6b7c',
                'signatures' => [
                    'HliuoKGum8RFuazkmk5EgOGCnS1J2KPp+wTI4fSinp2tqLrtxdHx6fRmrRXyI8lFiLMhg+rkpP8GJ6vnHxl4BA==',
                    'fJsUGgMib2woJjheT+XsVBkwP3nNV0RFw+mZHqH9md9Ag4wzs7HeJvrZ6A6zbJhRJ97jQIgTRuHXdHT/sBFNBw=='
                ],
                'max_fee' => '500'
            ]
        ];
    }

    /**
     * Helper method to create transaction with muxed account JSON data
     */
    private function getMuxedAccountTransactionJson(): array
    {
        return [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/transactions/test123'],
                'account' => ['href' => 'https://horizon.stellar.org/accounts/GA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF3S4R'],
                'ledger' => ['href' => 'https://horizon.stellar.org/ledgers/12345'],
                'operations' => ['href' => 'https://horizon.stellar.org/transactions/test123/operations', 'templated' => false],
                'effects' => ['href' => 'https://horizon.stellar.org/transactions/test123/effects', 'templated' => false],
                'precedes' => ['href' => 'https://horizon.stellar.org/transactions?order=asc&cursor=123'],
                'succeeds' => ['href' => 'https://horizon.stellar.org/transactions?order=desc&cursor=123'],
                'transaction' => ['href' => 'https://horizon.stellar.org/transactions/test123']
            ],
            'id' => 'test123',
            'paging_token' => '123456789',
            'successful' => true,
            'hash' => 'test123',
            'ledger' => 12345,
            'created_at' => '2024-01-01T00:00:00Z',
            'source_account' => 'GA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF3S4R',
            'source_account_muxed' => 'MA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF2AAACOBCKABHCTRYY',
            'source_account_muxed_id' => '21449687443220',
            'source_account_sequence' => '123456789012',
            'fee_account' => 'GA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF3S4R',
            'fee_account_muxed' => 'MA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF2AAACOBCKABHCTRYY',
            'fee_account_muxed_id' => '21449687443220',
            'fee_charged' => '100',
            'max_fee' => '100',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'none',
            'signatures' => ['test_signature'],
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0',
                    'max_time' => '1720158787'
                ]
            ]
        ];
    }

    /**
     * Test TransactionResponse parsing from complete JSON
     */
    public function testTransactionResponseFromCompleteJson(): void
    {
        $json = $this->getCompleteTransactionJson();
        $response = TransactionResponse::fromJson($json);

        // Test basic fields
        $this->assertEquals('a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486', $response->getId());
        $this->assertEquals('225180887607500800', $response->getPagingToken());
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486', $response->getHash());
        $this->assertEquals(52429011, $response->getLedger());
        $this->assertEquals('2024-07-05T05:51:31Z', $response->getCreatedAt());

        // Test account fields
        $this->assertEquals('GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O', $response->getSourceAccount());
        $this->assertEquals('224884019467125027', $response->getSourceAccountSequence());
        $this->assertEquals('GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O', $response->getFeeAccount());
        $this->assertNull($response->getSourceAccountMuxed());
        $this->assertNull($response->getSourceAccountMuxedId());
        $this->assertNull($response->getFeeAccountMuxed());
        $this->assertNull($response->getFeeAccountMuxedId());

        // Test fee fields
        $this->assertEquals('100', $response->getFeeCharged());
        $this->assertEquals('100', $response->getMaxFee());
        $this->assertEquals(1, $response->getOperationCount());

        // Test XDR fields
        $this->assertNotNull($response->getEnvelopeXdr());
        $this->assertEquals($json['envelope_xdr'], $response->getEnvelopeXdrBase64());
        $this->assertNotNull($response->getResultXdr());
        $this->assertEquals($json['result_xdr'], $response->getResultXdrBase64());
        $this->assertNotNull($response->getResultMetaXdr());
        $this->assertEquals($json['result_meta_xdr'], $response->getResultMetaXdrBase64());
        $this->assertEquals($json['fee_meta_xdr'], $response->getFeeMetaXdrBase64());
        $this->assertNotNull($response->getFeeMetaXdr());

        // Test memo
        $memo = $response->getMemo();
        $this->assertInstanceOf(Memo::class, $memo);
        $this->assertEquals('0,075% Daily for Holders', $memo->getValue());

        // Test signatures
        $signatures = $response->getSignatures();
        $this->assertInstanceOf(TransactionSignaturesResponse::class, $signatures);
        $this->assertEquals(2, $signatures->count());
        $sigArray = $signatures->toArray();
        $this->assertEquals('ua2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDQ==', $sigArray[0]);
        $this->assertEquals('LbDjlPdLbiyGQnu7JGX8/nEPzpd+LupNjGZvy6Hw8RzCVvmipuclxOVRXa4mzb6DMyWKM20BusKbrA77ViwlDg==', $sigArray[1]);

        // Test preconditions
        $preconditions = $response->getPreconditions();
        $this->assertInstanceOf(TransactionPreconditionsResponse::class, $preconditions);
        $timeBounds = $preconditions->getTimeBounds();
        $this->assertInstanceOf(PreconditionsTimeBoundsResponse::class, $timeBounds);
        $this->assertEquals('0', $timeBounds->getMinTime());
        $this->assertEquals('1720158787', $timeBounds->getMaxTime());

        // Test links
        $links = $response->getLinks();
        $this->assertInstanceOf(TransactionLinksResponse::class, $links);

        // Test fee-bump transaction fields are null for regular transaction
        $this->assertNull($response->getFeeBumpTransactionResponse());
        $this->assertNull($response->getInnerTransactionResponse());
    }

    /**
     * Test TransactionResponse parsing from failed transaction JSON
     */
    public function testTransactionResponseFromFailedTransactionJson(): void
    {
        $json = $this->getFailedTransactionJson();
        $response = TransactionResponse::fromJson($json);

        $this->assertEquals('ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d', $response->getId());
        $this->assertEquals('225181329989492736', $response->getPagingToken());
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d', $response->getHash());
        $this->assertEquals(52429114, $response->getLedger());
        $this->assertEquals('2024-07-05T06:01:20Z', $response->getCreatedAt());
        $this->assertEquals('GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD', $response->getSourceAccount());
        $this->assertEquals('169774989149474823', $response->getSourceAccountSequence());
        $this->assertEquals('GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD', $response->getFeeAccount());
        $this->assertEquals('100', $response->getFeeCharged());
        $this->assertEquals('101', $response->getMaxFee());
        $this->assertEquals(1, $response->getOperationCount());

        // Test memo type none
        $memo = $response->getMemo();
        $this->assertInstanceOf(Memo::class, $memo);

        // Test signatures
        $signatures = $response->getSignatures();
        $this->assertEquals(2, $signatures->count());
    }

    /**
     * Test TransactionResponse parsing from fee-bump transaction JSON
     */
    public function testTransactionResponseFromFeeBumpTransactionJson(): void
    {
        $json = $this->getFeeBumpTransactionJson();
        $response = TransactionResponse::fromJson($json);

        $this->assertEquals('01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0', $response->getHash());
        $this->assertEquals(52428726, $response->getLedger());
        $this->assertEquals('2024-07-05T05:23:21Z', $response->getCreatedAt());
        $this->assertEquals('GBYODLTHLR7PPEVS2LSXHXHWSL4OGMEFFIINE23RPWJAWVN6WZXZD25O', $response->getSourceAccount());
        $this->assertEquals('200', $response->getFeeCharged());
        $this->assertEquals('2000', $response->getMaxFee());
        $this->assertEquals(1, $response->getOperationCount());
        $this->assertTrue($response->isSuccessful());

        // Test memo type none
        $memo = $response->getMemo();
        $this->assertInstanceOf(Memo::class, $memo);

        // Test inner transaction
        $innerTransaction = $response->getInnerTransactionResponse();
        $this->assertInstanceOf(InnerTransactionResponse::class, $innerTransaction);
        $this->assertEquals('e96bb6dfa0085e03c19c8dfb6dff81bb3d00e25e80a3d2f2c1c795f88b2f6b7c', $innerTransaction->getHash());
        $this->assertEquals('500', $innerTransaction->getMaxFee());

        $innerSignatures = $innerTransaction->getSignatures();
        $this->assertInstanceOf(TransactionSignaturesResponse::class, $innerSignatures);
        $this->assertEquals(2, $innerSignatures->count());
        $innerSigArray = $innerSignatures->toArray();
        $this->assertEquals('HliuoKGum8RFuazkmk5EgOGCnS1J2KPp+wTI4fSinp2tqLrtxdHx6fRmrRXyI8lFiLMhg+rkpP8GJ6vnHxl4BA==', $innerSigArray[0]);
        $this->assertEquals('fJsUGgMib2woJjheT+XsVBkwP3nNV0RFw+mZHqH9md9Ag4wzs7HeJvrZ6A6zbJhRJ97jQIgTRuHXdHT/sBFNBw==', $innerSigArray[1]);

        // Test fee-bump transaction
        $feeBumpTransaction = $response->getFeeBumpTransactionResponse();
        $this->assertInstanceOf(FeeBumpTransactionResponse::class, $feeBumpTransaction);
        $this->assertEquals('01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0', $feeBumpTransaction->getHash());

        $feeBumpSignatures = $feeBumpTransaction->getSignatures();
        $this->assertInstanceOf(TransactionSignaturesResponse::class, $feeBumpSignatures);
        $this->assertEquals(1, $feeBumpSignatures->count());
        $feeBumpSigArray = $feeBumpSignatures->toArray();
        $this->assertEquals('Qsnj9i9Hdyd+zIAyhN5nuPoAPvj86+0lYuKa5GRw1RVwmln9pD+yVpa3khXQl9/ClDmQR0zkeueZ0A/+LOBdBw==', $feeBumpSigArray[0]);
    }

    /**
     * Test TransactionResponse parsing with muxed account
     */
    public function testTransactionResponseWithMuxedAccount(): void
    {
        $json = $this->getMuxedAccountTransactionJson();
        $response = TransactionResponse::fromJson($json);

        $this->assertEquals('GA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF3S4R', $response->getSourceAccount());
        $this->assertEquals('MA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF2AAACOBCKABHCTRYY', $response->getSourceAccountMuxed());
        $this->assertEquals('21449687443220', $response->getSourceAccountMuxedId());

        $this->assertEquals('GA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF3S4R', $response->getFeeAccount());
        $this->assertEquals('MA5ZPIVPAXEG5LMHTKF4PJZ52K5YFUUBSHL746TINKJ7GNMG4KYF2AAACOBCKABHCTRYY', $response->getFeeAccountMuxed());
        $this->assertEquals('21449687443220', $response->getFeeAccountMuxedId());
    }

    /**
     * Test TransactionPreconditionsResponse parsing with all fields
     */
    public function testTransactionPreconditionsResponseComplete(): void
    {
        $json = [
            'timebounds' => [
                'min_time' => '1234567890',
                'max_time' => '1234567900'
            ],
            'ledgerbounds' => [
                'min_ledger' => 100,
                'max_ledger' => 200
            ],
            'min_account_sequence' => '98765432100',
            'min_account_sequence_age' => '86400',
            'min_account_sequence_ledger_gap' => 10,
            'extra_signers' => [
                'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
            ]
        ];

        $response = TransactionPreconditionsResponse::fromJson($json);

        // Test time bounds
        $timeBounds = $response->getTimeBounds();
        $this->assertInstanceOf(PreconditionsTimeBoundsResponse::class, $timeBounds);
        $this->assertEquals('1234567890', $timeBounds->getMinTime());
        $this->assertEquals('1234567900', $timeBounds->getMaxTime());

        // Test ledger bounds
        $ledgerBounds = $response->getLedgerBounds();
        $this->assertInstanceOf(PreconditionsLedgerBoundsResponse::class, $ledgerBounds);
        $this->assertEquals(100, $ledgerBounds->getMinLedger());
        $this->assertEquals(200, $ledgerBounds->getMaxLedger());

        // Test min account sequence fields
        $this->assertEquals('98765432100', $response->getMinAccountSequence());
        $this->assertEquals('86400', $response->getMinAccountSequenceAge());
        $this->assertEquals(10, $response->getMinAccountSequenceLedgerGap());

        // Test extra signers
        $extraSigners = $response->getExtraSigners();
        $this->assertIsArray($extraSigners);
        $this->assertCount(2, $extraSigners);
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $extraSigners[0]);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $extraSigners[1]);
    }

    /**
     * Test PreconditionsTimeBoundsResponse parsing
     */
    public function testPreconditionsTimeBoundsResponse(): void
    {
        $json = [
            'min_time' => '0',
            'max_time' => '1720158787'
        ];

        $response = PreconditionsTimeBoundsResponse::fromJson($json);

        $this->assertEquals('0', $response->getMinTime());
        $this->assertEquals('1720158787', $response->getMaxTime());
    }

    /**
     * Test PreconditionsTimeBoundsResponse with null values
     */
    public function testPreconditionsTimeBoundsResponseWithNullValues(): void
    {
        $json = [];

        $response = PreconditionsTimeBoundsResponse::fromJson($json);

        $this->assertNull($response->getMinTime());
        $this->assertNull($response->getMaxTime());
    }

    /**
     * Test PreconditionsLedgerBoundsResponse parsing
     */
    public function testPreconditionsLedgerBoundsResponse(): void
    {
        $json = [
            'min_ledger' => 1000,
            'max_ledger' => 2000
        ];

        $response = PreconditionsLedgerBoundsResponse::fromJson($json);

        $this->assertEquals(1000, $response->getMinLedger());
        $this->assertEquals(2000, $response->getMaxLedger());
    }

    /**
     * Test PreconditionsLedgerBoundsResponse with missing values defaults to 0
     */
    public function testPreconditionsLedgerBoundsResponseWithMissingValues(): void
    {
        $json = [];

        $response = PreconditionsLedgerBoundsResponse::fromJson($json);

        $this->assertEquals(0, $response->getMinLedger());
        $this->assertEquals(0, $response->getMaxLedger());
    }

    /**
     * Test TransactionSignaturesResponse
     */
    public function testTransactionSignaturesResponse(): void
    {
        $sig1 = 'test_signature_1';
        $sig2 = 'test_signature_2';
        $sig3 = 'test_signature_3';

        $response = new TransactionSignaturesResponse($sig1, $sig2);
        $this->assertEquals(2, $response->count());

        $response->add($sig3);
        $this->assertEquals(3, $response->count());

        $array = $response->toArray();
        $this->assertCount(3, $array);
        $this->assertEquals($sig1, $array[0]);
        $this->assertEquals($sig2, $array[1]);
        $this->assertEquals($sig3, $array[2]);

        // Test iteration
        $count = 0;
        foreach ($response as $signature) {
            $this->assertIsString($signature);
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    /**
     * Test TransactionsResponse collection
     */
    public function testTransactionsResponse(): void
    {
        $json1 = $this->getCompleteTransactionJson();
        $json2 = $this->getFailedTransactionJson();

        $tx1 = TransactionResponse::fromJson($json1);
        $tx2 = TransactionResponse::fromJson($json2);

        $response = new TransactionsResponse($tx1);
        $this->assertEquals(1, $response->count());

        $response->add($tx2);
        $this->assertEquals(2, $response->count());

        $array = $response->toArray();
        $this->assertCount(2, $array);
        $this->assertInstanceOf(TransactionResponse::class, $array[0]);
        $this->assertInstanceOf(TransactionResponse::class, $array[1]);

        // Test iteration
        $count = 0;
        foreach ($response as $transaction) {
            $this->assertInstanceOf(TransactionResponse::class, $transaction);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    /**
     * Test TransactionsPageResponse parsing
     */
    public function testTransactionsPageResponse(): void
    {
        $tx1Json = $this->getCompleteTransactionJson();
        $tx2Json = $this->getFailedTransactionJson();

        $pageJson = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/transactions?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/transactions?cursor=225181329989492736&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/transactions?cursor=225180887607500800&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [$tx1Json, $tx2Json]
            ]
        ];

        $response = TransactionsPageResponse::fromJson($pageJson);

        $transactions = $response->getTransactions();
        $this->assertInstanceOf(TransactionsResponse::class, $transactions);
        $this->assertEquals(2, $transactions->count());

        $txArray = $transactions->toArray();
        $this->assertEquals('a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486', $txArray[0]->getId());
        $this->assertEquals('ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d', $txArray[1]->getId());
    }

    /**
     * Test InnerTransactionResponse parsing
     */
    public function testInnerTransactionResponse(): void
    {
        $json = [
            'hash' => 'e96bb6dfa0085e03c19c8dfb6dff81bb3d00e25e80a3d2f2c1c795f88b2f6b7c',
            'signatures' => [
                'HliuoKGum8RFuazkmk5EgOGCnS1J2KPp+wTI4fSinp2tqLrtxdHx6fRmrRXyI8lFiLMhg+rkpP8GJ6vnHxl4BA==',
                'fJsUGgMib2woJjheT+XsVBkwP3nNV0RFw+mZHqH9md9Ag4wzs7HeJvrZ6A6zbJhRJ97jQIgTRuHXdHT/sBFNBw=='
            ],
            'max_fee' => '500'
        ];

        $response = InnerTransactionResponse::fromJson($json);

        $this->assertEquals('e96bb6dfa0085e03c19c8dfb6dff81bb3d00e25e80a3d2f2c1c795f88b2f6b7c', $response->getHash());
        $this->assertEquals('500', $response->getMaxFee());

        $signatures = $response->getSignatures();
        $this->assertInstanceOf(TransactionSignaturesResponse::class, $signatures);
        $this->assertEquals(2, $signatures->count());
    }

    /**
     * Test FeeBumpTransactionResponse parsing
     */
    public function testFeeBumpTransactionResponse(): void
    {
        $json = [
            'hash' => '01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0',
            'signatures' => [
                'Qsnj9i9Hdyd+zIAyhN5nuPoAPvj86+0lYuKa5GRw1RVwmln9pD+yVpa3khXQl9/ClDmQR0zkeueZ0A/+LOBdBw=='
            ]
        ];

        $response = FeeBumpTransactionResponse::fromJson($json);

        $this->assertEquals('01f25077d5924dc2381055b22befacfdb7d6466325e2511d9cc0739d23bf14f0', $response->getHash());

        $signatures = $response->getSignatures();
        $this->assertInstanceOf(TransactionSignaturesResponse::class, $signatures);
        $this->assertEquals(1, $signatures->count());
    }

    /**
     * Test TransactionLinksResponse parsing
     */
    public function testTransactionLinksResponse(): void
    {
        $json = [
            'self' => ['href' => 'https://horizon.stellar.org/transactions/test123'],
            'account' => ['href' => 'https://horizon.stellar.org/accounts/GABC123'],
            'ledger' => ['href' => 'https://horizon.stellar.org/ledgers/12345'],
            'operations' => [
                'href' => 'https://horizon.stellar.org/transactions/test123/operations{?cursor,limit,order}',
                'templated' => true
            ],
            'effects' => [
                'href' => 'https://horizon.stellar.org/transactions/test123/effects{?cursor,limit,order}',
                'templated' => true
            ],
            'precedes' => ['href' => 'https://horizon.stellar.org/transactions?order=asc&cursor=123'],
            'succeeds' => ['href' => 'https://horizon.stellar.org/transactions?order=desc&cursor=123'],
            'transaction' => ['href' => 'https://horizon.stellar.org/transactions/test123']
        ];

        $response = TransactionLinksResponse::fromJson($json);
        $this->assertInstanceOf(TransactionLinksResponse::class, $response);
    }

    /**
     * Test memo parsing for different memo types
     */
    public function testMemoTypeParsing(): void
    {
        // Test memo text
        $jsonText = array_merge($this->getCompleteTransactionJson(), [
            'memo_type' => 'text',
            'memo' => 'Test memo text'
        ]);
        $responseText = TransactionResponse::fromJson($jsonText);
        $this->assertEquals('Test memo text', $responseText->getMemo()->getValue());

        // Test memo id
        $jsonId = array_merge($this->getCompleteTransactionJson(), [
            'memo_type' => 'id',
            'memo' => '123456789'
        ]);
        $responseId = TransactionResponse::fromJson($jsonId);
        $this->assertEquals(123456789, $responseId->getMemo()->getValue());

        // Test memo hash (must be exactly 32 bytes)
        $hashValue = str_pad('test_hash', 32, '0');
        $jsonHash = array_merge($this->getCompleteTransactionJson(), [
            'memo_type' => 'hash',
            'memo' => base64_encode($hashValue)
        ]);
        $responseHash = TransactionResponse::fromJson($jsonHash);
        $this->assertIsString($responseHash->getMemo()->getValue());

        // Test memo return (must be exactly 32 bytes)
        $returnValue = str_pad('test_return', 32, '0');
        $jsonReturn = array_merge($this->getCompleteTransactionJson(), [
            'memo_type' => 'return',
            'memo' => base64_encode($returnValue)
        ]);
        $responseReturn = TransactionResponse::fromJson($jsonReturn);
        $this->assertIsString($responseReturn->getMemo()->getValue());

        // Test memo none
        $jsonNone = array_merge($this->getCompleteTransactionJson(), [
            'memo_type' => 'none'
        ]);
        $responseNone = TransactionResponse::fromJson($jsonNone);
        $this->assertInstanceOf(Memo::class, $responseNone->getMemo());
    }

    /**
     * Test transaction with minimal required fields only
     */
    public function testTransactionResponseWithMinimalFields(): void
    {
        $json = [
            'id' => 'minimal_tx',
            'paging_token' => '123',
            'successful' => true,
            'hash' => 'minimal_hash',
            'ledger' => 1,
            'created_at' => '2024-01-01T00:00:00Z',
            'source_account' => 'GABC123',
            'source_account_sequence' => '1',
            'fee_account' => 'GABC123',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            '_links' => [
                'self' => ['href' => 'https://test.com'],
                'account' => ['href' => 'https://test.com'],
                'ledger' => ['href' => 'https://test.com'],
                'operations' => ['href' => 'https://test.com'],
                'effects' => ['href' => 'https://test.com'],
                'precedes' => ['href' => 'https://test.com'],
                'succeeds' => ['href' => 'https://test.com'],
                'transaction' => ['href' => 'https://test.com']
            ]
        ];

        $response = TransactionResponse::fromJson($json);

        $this->assertEquals('minimal_tx', $response->getId());
        $this->assertEquals('123', $response->getPagingToken());
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('minimal_hash', $response->getHash());
        $this->assertEquals(1, $response->getLedger());
        $this->assertNull($response->getFeeCharged());
        $this->assertNull($response->getMaxFee());
        $this->assertNull($response->getResultMetaXdr());
        $this->assertInstanceOf(Memo::class, $response->getMemo());
    }
}
