<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

/**
 * Integration tests for SEP-10 Web Authentication against live services.
 *
 * These exercise the full authentication flow over the network and are not part
 * of the unit suite.
 */
class SEP010Test extends TestCase
{
    /**
     * SEP-10 authentication with testanchor.stellar.org.
     */
    public function testWithStellarTestAnchor(): void {
        $webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());

        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();
        $jwt = $webAuth->jwtToken($userAccountId, [$userKeyPair]);
        $this->assertNotEmpty($jwt);
    }

    /**
     * SEP-10 authentication with testanchor.stellar.org and client domain.
     *
     * Uses phpsepsigner.stellargate.com as the client domain signing server.
     *
     * @see https://github.com/Soneso/php-server-signer
     */
    public function testWithStellarTestAnchorAndClientDomain(): void {
        $webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());

        $clientDomain = "phpsepsigner.stellargate.com";
        $bearerToken = "103e1e6234ac2cc1a30d983dba367db2b194ea5b269433c316ad36d21e1e8235";

        $callback = function (string $b64EncodedEnvelope) use ($bearerToken) {
            $httpClient = new Client();
            $response = $httpClient->request('POST', 'https://phpsepsigner.stellargate.com/sign-sep-10', [
                'json' => [
                    'transaction' => $b64EncodedEnvelope,
                    'network_passphrase' => 'Test SDF Network ; September 2015'
                ],
                'headers' => ['Authorization' => 'Bearer ' . $bearerToken]
            ]);
            $content = $response->getBody()->__toString();
            $jsonData = json_decode($content, true);
            if (isset($jsonData['transaction'])) {
                return $jsonData['transaction'];
            }
            throw new Exception("Invalid server response: " . $content);
        };

        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();
        $jwt = $webAuth->jwtToken(
            $userAccountId,
            [$userKeyPair],
            clientDomain: $clientDomain,
            clientDomainSigningCallback: $callback
        );
        $this->assertNotEmpty($jwt);
    }
}
