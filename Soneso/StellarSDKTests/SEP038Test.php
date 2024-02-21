<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38BadRequestException;
use Soneso\StellarSDK\SEP\Quote\SEP38NotFoundException;
use Soneso\StellarSDK\SEP\Quote\SEP38PermissionDeniedException;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

class SEP038Test extends TestCase
{
    private string $serviceAddress = "http://api.stellar.org/quote";

    private string $infoResponse = "{  \"assets\":  [    {      \"asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\"    },    {      \"asset\": \"stellar:BRL:GDVKY2GU2DRXWTBEYJJWSFXIGBZV6AZNBVVSUHEPZI54LIS6BA7DVVSP\"    },    {      \"asset\": \"iso4217:BRL\",      \"country_codes\": [\"BRA\"],      \"sell_delivery_methods\": [        {          \"name\": \"cash\",          \"description\": \"Deposit cash BRL at one of our agent locations.\"        },        {          \"name\": \"ACH\",          \"description\": \"Send BRL directly to the Anchor's bank account.\"        },        {          \"name\": \"PIX\",          \"description\": \"Send BRL directly to the Anchor's bank account.\"        }      ],      \"buy_delivery_methods\": [        {          \"name\": \"cash\",          \"description\": \"Pick up cash BRL at one of our payout locations.\"        },        {          \"name\": \"ACH\",          \"description\": \"Have BRL sent directly to your bank account.\"        },        {          \"name\": \"PIX\",          \"description\": \"Have BRL sent directly to the account of your choice.\"        }      ]    }  ]}";
    private string $getPricesResponse = "{  \"buy_assets\": [    {      \"asset\": \"iso4217:BRL\",      \"price\": \"0.18\",      \"decimals\": 2    }  ]}";
    private string $getPriceResponse = "{  \"total_price\": \"0.20\",  \"price\": \"0.18\",  \"sell_amount\": \"100\",  \"buy_amount\": \"500\",  \"fee\": {    \"total\": \"10.00\",    \"asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",    \"details\": [      {        \"name\": \"Service fee\",        \"amount\": \"5.00\"      },      {        \"name\": \"PIX fee\",        \"description\": \"Fee charged in order to process the outgoing BRL PIX transaction.\",        \"amount\": \"5.00\"      }    ]  }}";
    private string $firmQuoteResponse = "{\"id\": \"de762cda-a193-4961-861e-57b31fed6eb3\",\"expires_at\": \"2024-02-01T10:40:14+0000\",  \"total_price\": \"5.42\",   \"price\": \"5.00\",  \"sell_asset\": \"iso4217:BRL\",  \"sell_amount\": \"542\",  \"buy_asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",  \"buy_amount\": \"100\",  \"fee\": {    \"total\": \"8.40\",    \"asset\": \"stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN\",    \"details\": [      {        \"name\": \"Service fee\",        \"amount\": \"8.40\"      }    ]  }}";
    private string $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJHQTZVSVhYUEVXWUZJTE5VSVdBQzM3WTRRUEVaTVFWREpIREtWV0ZaSjJLQ1dVQklVNUlYWk5EQSIsImp0aSI6IjE0NGQzNjdiY2IwZTcyY2FiZmRiZGU2MGVhZTBhZDczM2NjNjVkMmE2NTg3MDgzZGFiM2Q2MTZmODg1MTkwMjQiLCJpc3MiOiJodHRwczovL2ZsYXBweS1iaXJkLWRhcHAuZmlyZWJhc2VhcHAuY29tLyIsImlhdCI6MTUzNDI1Nzk5NCwiZXhwIjoxNTM0MzQ0Mzk0fQ.8nbB83Z6vGBgC1X9r3N6oQCFTBzDiITAfCJasRft0z0";


    public function testGetInfo(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->infoResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);
        $response = $service->info($this->jwtToken);
        $assets = $response->assets;
        $this->assertCount(3, $assets);
        $this->assertEquals(
            'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            $assets[0]->asset
        );
        $this->assertEquals(
            'stellar:BRL:GDVKY2GU2DRXWTBEYJJWSFXIGBZV6AZNBVVSUHEPZI54LIS6BA7DVVSP',
            $assets[1]->asset
        );
        $this->assertEquals('iso4217:BRL',$assets[2]->asset);
        $this->assertNotNull($assets[2]->countryCodes);
        $this->assertCount(1, $assets[2]->countryCodes);
        $this->assertEquals('BRA',$assets[2]->countryCodes[0]);

        $this->assertNotNull($assets[2]->sellDeliveryMethods);
        $this->assertCount(3, $assets[2]->sellDeliveryMethods);
        $this->assertEquals('cash',$assets[2]->sellDeliveryMethods[0]->name);
        $this->assertEquals('Deposit cash BRL at one of our agent locations.',
            $assets[2]->sellDeliveryMethods[0]->description);
        $this->assertEquals('ACH',$assets[2]->sellDeliveryMethods[1]->name);
        $this->assertEquals("Send BRL directly to the Anchor's bank account.",
            $assets[2]->sellDeliveryMethods[1]->description);
        $this->assertEquals('PIX',$assets[2]->sellDeliveryMethods[2]->name);
        $this->assertEquals("Send BRL directly to the Anchor's bank account.",
            $assets[2]->sellDeliveryMethods[2]->description);

        $this->assertNotNull($assets[2]->buyDeliveryMethods);
        $this->assertCount(3, $assets[2]->buyDeliveryMethods);
        $this->assertEquals('cash',$assets[2]->buyDeliveryMethods[0]->name);
        $this->assertEquals('Pick up cash BRL at one of our payout locations.',
            $assets[2]->buyDeliveryMethods[0]->description);
        $this->assertEquals('ACH',$assets[2]->buyDeliveryMethods[1]->name);
        $this->assertEquals("Have BRL sent directly to your bank account.",
            $assets[2]->buyDeliveryMethods[1]->description);
        $this->assertEquals('PIX',$assets[2]->buyDeliveryMethods[2]->name);
        $this->assertEquals("Have BRL sent directly to the account of your choice.",
            $assets[2]->buyDeliveryMethods[2]->description);
    }

    public function testGetPrices(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->getPricesResponse)
        ]);

        $sellAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $sellAmount = '100';
        $countryCode = 'BRA';
        $buyDeliveryMethod = 'ACH';

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request)
        use ($buyDeliveryMethod, $countryCode, $sellAmount, $sellAsset) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($sellAsset, $query_array["sell_asset"]);
            $this->assertEquals($sellAmount, $query_array["sell_amount"]);
            $this->assertEquals($countryCode, $query_array["country_code"]);
            $this->assertEquals($buyDeliveryMethod, $query_array["buy_delivery_method"]);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);
        $response = $service->prices(
            sellAsset:$sellAsset,
            sellAmount: $sellAmount,
            buyDeliveryMethod: $buyDeliveryMethod,
            countryCode: $countryCode,
            jwt: $this->jwtToken,
        );

        $buyAssets = $response->buyAssets;
        $this->assertCount(1, $buyAssets);

        $this->assertEquals('iso4217:BRL', $buyAssets[0]->asset);
        $this->assertEquals('0.18', $buyAssets[0]->price);
        $this->assertEquals(2, $buyAssets[0]->decimals);
    }


    public function testGetPrice(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->getPriceResponse)
        ]);

        $sellAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $buyAsset = 'iso4217:BRL';
        $buyAmount = '500';
        $buyDeliveryMethod = 'PIX';
        $countryCode = 'BRA';
        $context = 'sep31';

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request)
        use ($context, $buyAmount, $buyAsset, $buyDeliveryMethod, $countryCode, $sellAsset) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals($sellAsset, $query_array["sell_asset"]);
            $this->assertEquals($buyAsset, $query_array["buy_asset"]);
            $this->assertEquals($buyAmount, $query_array["buy_amount"]);
            $this->assertEquals($context, $query_array["context"]);
            $this->assertEquals($countryCode, $query_array["country_code"]);
            $this->assertEquals($buyDeliveryMethod, $query_array["buy_delivery_method"]);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);

        $response = $service->price(
            context: $context,
            sellAsset: $sellAsset,
            buyAsset: $buyAsset,
            buyAmount: $buyAmount,
            buyDeliveryMethod: $buyDeliveryMethod,
            countryCode: $countryCode,
            jwt: $this->jwtToken,
        );


        $this->assertEquals('0.20', $response->totalPrice);
        $this->assertEquals('0.18', $response->price);
        $this->assertEquals('100', $response->sellAmount);
        $this->assertEquals('500', $response->buyAmount);
        $fee = $response->fee;
        $this->assertEquals('10.00', $fee->total);
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $fee->asset);
        $feeDetails = $fee->details;
        $this->assertNotNull($feeDetails);
        $this->assertCount(2, $feeDetails);
        $this->assertEquals('Service fee', $feeDetails[0]->name);
        $this->assertNull($feeDetails[0]->description);
        $this->assertEquals('5.00', $feeDetails[0]->amount);
        $this->assertEquals('PIX fee', $feeDetails[1]->name);
        $this->assertEquals('Fee charged in order to process the outgoing BRL PIX transaction.', $feeDetails[1]->description);
        $this->assertEquals('5.00', $feeDetails[1]->amount);

        $thrown = false;
        try {
            $service->price(
                context: $context,
                sellAsset: $sellAsset,
                buyAsset: $buyAsset,
                sellAmount: $buyAmount,
                buyAmount: $buyAmount,
                buyDeliveryMethod: $buyDeliveryMethod,
                countryCode: $countryCode,
                jwt: $this->jwtToken,
            );
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->price(
                context: $context,
                sellAsset: $sellAsset,
                buyAsset: $buyAsset,
                buyDeliveryMethod: $buyDeliveryMethod,
                countryCode: $countryCode,
                jwt: $this->jwtToken,
            );
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testPostQuote(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->firmQuoteResponse)
        ]);

        $sellAsset = 'iso4217:BRL';
        $buyAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $buyAmount = '100';
        $expireAfter = new DateTime();
        $sellDeliveryMethod = 'PIX';
        $countryCode = 'BRA';
        $context = 'sep31';


        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($countryCode, $sellDeliveryMethod, $buyAmount, $buyAsset, $context, $sellAsset) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("POST", $request->getMethod());
            $body = $request->getBody()->__toString();
            $jsonData = @json_decode($body, true);
            $this->assertEquals($sellAsset, $jsonData['sell_asset']);
            $this->assertEquals($buyAsset, $jsonData['buy_asset']);
            $this->assertEquals($buyAmount, $jsonData['buy_amount']);
            $this->assertEquals($sellDeliveryMethod, $jsonData['sell_delivery_method']);
            $this->assertEquals($countryCode, $jsonData['country_code']);
            $this->assertEquals($context, $jsonData['context']);

            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);
        $request = new SEP38PostQuoteRequest(
            context: $context,
            sellAsset: $sellAsset,
            buyAsset: $buyAsset,
            buyAmount: $buyAmount,
            expireAfter: $expireAfter,
            sellDeliveryMethod: $sellDeliveryMethod,
            countryCode: $countryCode);
        $response = $service->postQuote($request, $this->jwtToken);

        $this->assertEquals('de762cda-a193-4961-861e-57b31fed6eb3', $response->id);
        $this->assertEquals(DateTime::createFromFormat(DateTimeInterface::ATOM, '2024-02-01T10:40:14+0000'), $response->expiresAt);
        $this->assertEquals('5.42', $response->totalPrice);
        $this->assertEquals('5.00', $response->price);
        $this->assertEquals($sellAsset, $response->sellAsset);
        $this->assertEquals($buyAsset, $response->buyAsset);
        $this->assertEquals('542', $response->sellAmount);
        $this->assertEquals($buyAmount, $response->buyAmount);
        $fee = $response->fee;
        $this->assertEquals('8.40', $fee->total);
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $fee->asset);
        $feeDetails = $fee->details;
        $this->assertNotNull($feeDetails);
        $this->assertCount(1, $feeDetails);
        $this->assertEquals('Service fee', $feeDetails[0]->name);
        $this->assertEquals('8.40', $feeDetails[0]->amount);

        $thrown = false;
        try {
            $request = new SEP38PostQuoteRequest(
                context: $context,
                sellAsset: $sellAsset,
                buyAsset: $buyAsset,
                sellAmount: $buyAmount,
                buyAmount: $buyAmount,
                expireAfter: $expireAfter,
                sellDeliveryMethod: $sellDeliveryMethod,
                countryCode: $countryCode);
            $service->postQuote($request, $this->jwtToken);
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $request = new SEP38PostQuoteRequest(
                context: $context,
                sellAsset: $sellAsset,
                buyAsset: $buyAsset,
                expireAfter: $expireAfter,
                sellDeliveryMethod: $sellDeliveryMethod,
                countryCode: $countryCode);
            $service->postQuote($request, $this->jwtToken);
        } catch (InvalidArgumentException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

    }

    public function testGetQuote(): void {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->firmQuoteResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            $this->assertEquals("GET", $request->getMethod());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);
        $response = $service->getQuote(id: 'de762cda-a193-4961-861e-57b31fed6eb3', jwt: $this->jwtToken);

        $sellAsset = 'iso4217:BRL';
        $buyAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $buyAmount = '100';

        $this->assertEquals('de762cda-a193-4961-861e-57b31fed6eb3', $response->id);
        $this->assertEquals(DateTime::createFromFormat(DateTimeInterface::ATOM, '2024-02-01T10:40:14+0000'), $response->expiresAt);
        $this->assertEquals('5.42', $response->totalPrice);
        $this->assertEquals('5.00', $response->price);
        $this->assertEquals($sellAsset, $response->sellAsset);
        $this->assertEquals($buyAsset, $response->buyAsset);
        $this->assertEquals('542', $response->sellAmount);
        $this->assertEquals($buyAmount, $response->buyAmount);
        $fee = $response->fee;
        $this->assertEquals('8.40', $fee->total);
        $this->assertEquals('stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $fee->asset);
        $feeDetails = $fee->details;
        $this->assertNotNull($feeDetails);
        $this->assertCount(1, $feeDetails);
        $this->assertEquals('Service fee', $feeDetails[0]->name);
        $this->assertEquals('8.40', $feeDetails[0]->amount);
    }

    public function testErrorResponses(): void {

        $mock = new MockHandler([
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}'),
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}'),
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}'),
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}'),
            new Response(403, ['X-Foo' => 'Bar'], '{"error": "permission denied"}'),
            new Response(400, ['X-Foo' => 'Bar'], '{"error": "Bad request"}'),
            new Response(403, ['X-Foo' => 'Bar'], '{"error": "permission denied"}'),
            new Response(404, ['X-Foo' => 'Bar'], '{"error": "not found"}'),
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $headers = $request->getHeaders();
            $auth = $headers["Authorization"][0];
            $this->assertEquals("Bearer " . $this->jwtToken, $auth);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $service = new QuoteService($this->serviceAddress, $httpClient);

        $sellAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $sellAmount = '100';
        $countryCode = 'BRA';
        $buyDeliveryMethod = 'ACH';
        $buyAsset = 'iso4217:BRL';
        $buyAmount = '500';
        $buyDeliveryMethod = 'PIX';
        $context = 'sep31';
        $sellAsset = 'iso4217:BRL';
        $buyAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
        $buyAmount = '100';
        $expireAfter = new DateTime();
        $sellDeliveryMethod = 'PIX';

        $postQuoteRequest = new SEP38PostQuoteRequest(
            context: $context,
            sellAsset: $sellAsset,
            buyAsset: $buyAsset,
            buyAmount: $buyAmount,
            expireAfter: $expireAfter,
            sellDeliveryMethod: $sellDeliveryMethod,
            countryCode: $countryCode);

        $thrown = false;
        try {
            $service->info( $this->jwtToken);
        } catch (SEP38BadRequestException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->prices(
                sellAsset:$sellAsset,
                sellAmount: $sellAmount,
                buyDeliveryMethod: $buyDeliveryMethod,
                countryCode: $countryCode,
                jwt: $this->jwtToken,
            );
        } catch (SEP38BadRequestException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);


        $thrown = false;
        try {
            $service->price(
                context: $context,
                sellAsset: $sellAsset,
                buyAsset: $buyAsset,
                buyAmount: $buyAmount,
                buyDeliveryMethod: $buyDeliveryMethod,
                countryCode: $countryCode,
                jwt: $this->jwtToken,
            );
        } catch (SEP38BadRequestException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->postQuote($postQuoteRequest, $this->jwtToken);
        } catch (SEP38BadRequestException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->postQuote($postQuoteRequest, $this->jwtToken);
        } catch (SEP38PermissionDeniedException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->getQuote("123", $this->jwtToken);
        } catch (SEP38BadRequestException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->getQuote("123", $this->jwtToken);
        } catch (SEP38PermissionDeniedException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $service->getQuote("999", $this->jwtToken);
        } catch (SEP38NotFoundException) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}