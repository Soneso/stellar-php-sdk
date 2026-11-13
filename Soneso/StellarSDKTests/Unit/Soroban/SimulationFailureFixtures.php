<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\Assert;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\Exceptions\SorobanSimulationException;
use Soneso\StellarSDK\Soroban\Responses\ContractErrorProvenance;
use Soneso\StellarSDK\Soroban\Responses\ReportedContractError;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrContractEventBody;
use Soneso\StellarSDK\Xdr\XdrContractEventBodyV0;
use Soneso\StellarSDK\Xdr\XdrContractEventType;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Provides captured simulation failures, diagnostic events and assertions for contract error tests.
 */
final class SimulationFailureFixtures
{
    public const POLICY = 'CAZJ3UVRY3R3S5C5BH32GMYBRSN23N75ZEEXEOLXOUUAHDFIMVP4AXUC';
    public const VERIFIER = 'CB26VN37RCVNTHJZDEPK6IRO2MMTS3Z2IEO5JD5BINY2OOJ5KKJG7NKY';
    public const ACCOUNT = 'CABAV4UNU66OZ75MXQDX3FFO35WNQLLXG2HZAHKJNZ27C2LGAX4DK253';
    public const CONTEXT = 'HostError: Error(Context, InvalidAction)';
    public const AUTH = 'HostError: Error(Auth, InvalidAction)';
    public const POLICY_SUFFIX = ' (contract error in diagnostic events: Error(Contract, #3201), contract CAZJ3UVRY3R3S5C5BH32GMYBRSN23N75ZEEXEOLXOUUAHDFIMVP4AXUC)';
    public const VERIFIER_SUFFIX = ' (contract error in diagnostic events: Error(Contract, #3114), contract CB26VN37RCVNTHJZDEPK6IRO2MMTS3Z2IEO5JD5BINY2OOJ5KKJG7NKY)';

    public static function capture(string $name): array
    {
        $path = __DIR__ . '/fixtures/simulation-errors/' . $name . '-response.json';
        Assert::assertFileIsReadable($path);
        $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($json);
        return $json;
    }

    public static function captures(): iterable
    {
        $events = ContractErrorProvenance::DIAGNOSTIC_EVENTS;
        $header = ContractErrorProvenance::HOST_ERROR;
        $captures = [
            'constructor' => ['case-a', 13, [3201, $events, self::POLICY], self::POLICY_SUFFIX],
            'registered signature' => ['case-b1-registered-badsig', 12, [3114, $events, self::VERIFIER], self::VERIFIER_SUFFIX],
            'unregistered signer' => ['case-b2-unregistered', 8, [3016, $events, self::ACCOUNT], ' (contract error in diagnostic events: Error(Contract, #3016), contract CABAV4UNU66OZ75MXQDX3FFO35WNQLLXG2HZAHKJNZ27C2LGAX4DK253)'],
            'crypto error' => ['case-b3-registered-rightchallenge-badsig', 12, null, ''],
            'policy header' => ['case-c1-policy-get-threshold', 4, [3200, $header, null], ''],
            'asset header' => ['case-c2-sac-negative-transfer', 2, [8, $header, null], ''],
        ];
        foreach ($captures as $label => [$name, $count, $expected, $suffix]) {
            foreach (['30.0.1' => '', '30.0.0' => '-rpc30.0.0'] as $version => $ending) {
                yield "$label $version" => [self::capture($name . $ending), $count, $expected, $suffix];
            }
        }
    }

    public static function contractError(int $code): XdrSCVal
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
        $error->contractCode = $code;
        return XdrSCVal::forError($error);
    }

    public static function event(int $code = 3201, ?string $id = self::POLICY, bool $success = false,
                                 ?array $topics = null, ?XdrSCVal $data = null, int $type = XdrContractEventType::CONTRACT_EVENT_TYPE_DIAGNOSTIC, int $bodyVersion = 0): string
    {
        $body = new XdrContractEventBody($bodyVersion);
        $body->v0 = new XdrContractEventBodyV0($topics ?? [XdrSCVal::forSymbol('error'), self::contractError($code)],
            $data ?? XdrSCVal::forVoid());
        return (new XdrDiagnosticEvent($success, new XdrContractEvent(new XdrExtensionPoint(0),
            new XdrContractEventType($type), $body, $id === null ? null : StrKey::decodeContractId($id))))->toBase64Xdr();
    }

    public static function assertReport(?array $expected, ?ReportedContractError $actual): void
    {
        if ($expected === null) {
            Assert::assertNull($actual);
            return;
        }
        Assert::assertNotNull($actual);
        Assert::assertSame($expected[0], $actual->getCode());
        Assert::assertSame($expected[1], $actual->getProvenance());
        Assert::assertSame($expected[2], $actual->getContractId());
    }

    public static function assertFailure(\Exception $error, array $events, ?array $expected, string $message,
                                         ?SorobanRpcErrorResponse $rpcError = null): void
    {
        Assert::assertInstanceOf(SorobanSimulationException::class, $error);
        Assert::assertSame($events, $error->getDiagnosticEventsXdr());
        self::assertReport($expected, $error->getReportedContractError());
        Assert::assertSame($rpcError, $error->getRpcError());
        Assert::assertSame($message, $error->getMessage());
        Assert::assertSame(0, $error->getCode());
    }
}
