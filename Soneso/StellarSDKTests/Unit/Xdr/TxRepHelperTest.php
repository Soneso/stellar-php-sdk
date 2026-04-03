<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrAllowTrustOperationAsset;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum12;
use Soneso\StellarSDK\Xdr\XdrAssetAlphaNum4;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;
use Soneso\StellarSDK\Xdr\TxRepHelper;

class TxRepHelperTest extends TestCase
{
    // Known valid Stellar account IDs for reuse across tests.
    private const ISSUER_A = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const ISSUER_B = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';

    // ---------------------------------------------------------------------------
    // parse()
    // ---------------------------------------------------------------------------

    public function testParseSimpleKeyValueLines(): void
    {
        $map = TxRepHelper::parse("tx.fee: 100\ntx.memo: none");
        $this->assertSame('100', $map['tx.fee']);
        $this->assertSame('none', $map['tx.memo']);
    }

    public function testParseCrlfLineEndings(): void
    {
        $map = TxRepHelper::parse("tx.fee: 100\r\ntx.memo: none\r\n");
        $this->assertSame('100', $map['tx.fee']);
        $this->assertSame('none', $map['tx.memo']);
    }

    public function testParseSkipsBlankLines(): void
    {
        $map = TxRepHelper::parse("tx.fee: 100\n\n\ntx.memo: none");
        $this->assertCount(2, $map);
    }

    public function testParseSkipsCommentOnlyLines(): void
    {
        $map = TxRepHelper::parse(": this is a comment\ntx.fee: 100");
        $this->assertCount(1, $map);
        $this->assertSame('100', $map['tx.fee']);
    }

    public function testParseSkipsLinesWithNoColon(): void
    {
        $map = TxRepHelper::parse("no colon here\ntx.fee: 100");
        $this->assertCount(1, $map);
    }

    public function testParseSplitsOnFirstColonOnly(): void
    {
        $map = TxRepHelper::parse('tx.asset: USD:GISSUER');
        $this->assertSame('USD:GISSUER', $map['tx.asset']);
    }

    public function testParseTrimsValues(): void
    {
        $map = TxRepHelper::parse("tx.fee:   100  ");
        $this->assertSame('100', $map['tx.fee']);
    }

    public function testParseSkipsLinesWithEmptyKeyAfterTrim(): void
    {
        $map = TxRepHelper::parse("  : value\ntx.fee: 100");
        $this->assertCount(1, $map);
    }

    public function testParseReturnsEmptyMapForEmptyInput(): void
    {
        $this->assertSame([], TxRepHelper::parse(''));
    }

    public function testParseReturnsEmptyMapForAllBlankLines(): void
    {
        $this->assertSame([], TxRepHelper::parse("\n\n\n"));
    }

    public function testParsePreservesInsertionOrder(): void
    {
        $map = TxRepHelper::parse("b: 2\na: 1\nc: 3");
        $keys = array_keys($map);
        $this->assertSame(['b', 'a', 'c'], $keys);
    }

    // ---------------------------------------------------------------------------
    // getValue()
    // ---------------------------------------------------------------------------

    public function testGetValueReturnNullForMissingKey(): void
    {
        $this->assertNull(TxRepHelper::getValue([], 'missing'));
    }

    public function testGetValueStripsInlineParenthesizedComment(): void
    {
        $map = ['tx.fee' => '100 (fee)'];
        $this->assertSame('100', TxRepHelper::getValue($map, 'tx.fee'));
    }

    public function testGetValueReturnsPlainValueWhenNoComment(): void
    {
        $map = ['tx.fee' => '100'];
        $this->assertSame('100', TxRepHelper::getValue($map, 'tx.fee'));
    }

    public function testGetValueReturnsNullWhenKeyAbsent(): void
    {
        $map = ['tx.fee' => '100'];
        $this->assertNull(TxRepHelper::getValue($map, 'tx.memo'));
    }

    // ---------------------------------------------------------------------------
    // removeComment()
    // ---------------------------------------------------------------------------

    public function testRemoveCommentTrimsTrailingWhitespace(): void
    {
        $this->assertSame('hello', TxRepHelper::removeComment('hello  '));
    }

    public function testRemoveCommentRemovesParenthesizedComment(): void
    {
        $this->assertSame('100', TxRepHelper::removeComment('100 (fee amount)'));
    }

    public function testRemoveCommentHandlesQuotedStringWithParensInside(): void
    {
        $this->assertSame('"hello (world)"', TxRepHelper::removeComment('"hello (world)"'));
    }

    public function testRemoveCommentHandlesQuotedStringWithEscapedQuote(): void
    {
        $this->assertSame('"say \"hi\""', TxRepHelper::removeComment('"say \"hi\""'));
    }

    public function testRemoveCommentHandlesQuotedStringWithNoClosingQuote(): void
    {
        $this->assertSame('"unclosed', TxRepHelper::removeComment('"unclosed'));
    }

    public function testRemoveCommentHandlesValueStartingWithOpenParen(): void
    {
        $this->assertSame('', TxRepHelper::removeComment('(comment)'));
    }

    public function testRemoveCommentReturnsEmptyForOnlyWhitespace(): void
    {
        $this->assertSame('', TxRepHelper::removeComment('   '));
    }

    public function testRemoveCommentReturnsEmptyStringForEmptyInput(): void
    {
        $this->assertSame('', TxRepHelper::removeComment(''));
    }

    public function testRemoveCommentPreservesCompleteQuotedString(): void
    {
        $this->assertSame('"abc"', TxRepHelper::removeComment('"abc"'));
    }

    // ---------------------------------------------------------------------------
    // bytesToHex()
    // ---------------------------------------------------------------------------

    public function testBytesToHexEncodesBytes(): void
    {
        $this->assertSame('abcd', TxRepHelper::bytesToHex("\xAB\xCD"));
    }

    public function testBytesToHexEmptyReturnsZero(): void
    {
        $this->assertSame('0', TxRepHelper::bytesToHex(''));
    }

    public function testBytesToHexSingleByte(): void
    {
        $this->assertSame('ff', TxRepHelper::bytesToHex("\xFF"));
    }

    public function testBytesToHexSingleZeroByte(): void
    {
        $this->assertSame('00', TxRepHelper::bytesToHex("\x00"));
    }

    public function testBytesToHexReturnsLowercaseHex(): void
    {
        $result = TxRepHelper::bytesToHex("\xAB\xCD\xEF");
        $this->assertSame('abcdef', $result);
    }

    public function testBytesToHexThirtyTwoBytes(): void
    {
        $bytes = str_repeat("\xAB", 32);
        $result = TxRepHelper::bytesToHex($bytes);
        $this->assertSame(64, strlen($result));
        $this->assertSame(str_repeat('ab', 32), $result);
    }

    // ---------------------------------------------------------------------------
    // hexToBytes()
    // ---------------------------------------------------------------------------

    public function testHexToBytesDecodesHexString(): void
    {
        $this->assertSame("\xAB\xCD", TxRepHelper::hexToBytes('abcd'));
    }

    public function testHexToBytesReturnsEmptyForZero(): void
    {
        $this->assertSame('', TxRepHelper::hexToBytes('0'));
    }

    public function testHexToBytesHandlesOddLengthHex(): void
    {
        // 'abc' is odd; should left-pad to '0abc' => 0x0A 0xBC
        $this->assertSame("\x0A\xBC", TxRepHelper::hexToBytes('abc'));
    }

    public function testHexToBytesHandlesUppercaseHex(): void
    {
        $this->assertSame("\xAB\xCD", TxRepHelper::hexToBytes('ABCD'));
    }

    public function testHexToBytesRoundtripsWithBytesToHex(): void
    {
        $original = "\x01\x02\xFE\xFF";
        $hex = TxRepHelper::bytesToHex($original);
        $decoded = TxRepHelper::hexToBytes($hex);
        $this->assertSame($original, $decoded);
    }

    public function testHexToBytesThrowsForInvalidHex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::hexToBytes('ZZZZ');
    }

    // ---------------------------------------------------------------------------
    // escapeString()
    // ---------------------------------------------------------------------------

    public function testEscapeStringWrapsInDoubleQuotes(): void
    {
        $this->assertSame('"hello"', TxRepHelper::escapeString('hello'));
    }

    public function testEscapeStringEscapesBackslash(): void
    {
        $this->assertSame('"a\\\\b"', TxRepHelper::escapeString('a\\b'));
    }

    public function testEscapeStringEscapesDoubleQuote(): void
    {
        $this->assertSame('"a\\"b"', TxRepHelper::escapeString('a"b'));
    }

    public function testEscapeStringEscapesNewline(): void
    {
        $this->assertSame('"a\\nb"', TxRepHelper::escapeString("a\nb"));
    }

    public function testEscapeStringEscapesCarriageReturn(): void
    {
        $this->assertSame('"a\\rb"', TxRepHelper::escapeString("a\rb"));
    }

    public function testEscapeStringEscapesTab(): void
    {
        $this->assertSame('"a\\tb"', TxRepHelper::escapeString("a\tb"));
    }

    public function testEscapeStringEncodesNonAsciiAsHex(): void
    {
        // \xFF is a single byte 0xFF, encoded as \xff
        $result = TxRepHelper::escapeString("\xFF");
        $this->assertSame('"\\xff"', $result);
    }

    public function testEscapeStringEncodesMultiByteUtf8AsHex(): void
    {
        // U+00FF (ÿ) is encoded as 0xC3 0xBF in UTF-8
        $result = TxRepHelper::escapeString("\u{00FF}");
        $this->assertSame('"\\xc3\\xbf"', $result);
    }

    public function testEscapeStringPassesPrintableAsciiThrough(): void
    {
        $this->assertSame('"abc 123!@#"', TxRepHelper::escapeString('abc 123!@#'));
    }

    public function testEscapeStringHandlesEmptyString(): void
    {
        $this->assertSame('""', TxRepHelper::escapeString(''));
    }

    public function testEscapeStringHandlesNullByte(): void
    {
        $result = TxRepHelper::escapeString("\x00");
        $this->assertSame('"\\x00"', $result);
    }

    // ---------------------------------------------------------------------------
    // unescapeString()
    // ---------------------------------------------------------------------------

    public function testUnescapeStringStripsEnclosingQuotes(): void
    {
        $this->assertSame('hello', TxRepHelper::unescapeString('"hello"'));
    }

    public function testUnescapeStringHandlesNoQuotes(): void
    {
        $this->assertSame('hello', TxRepHelper::unescapeString('hello'));
    }

    public function testUnescapeStringUnescapesBackslash(): void
    {
        $this->assertSame('a\\b', TxRepHelper::unescapeString('"a\\\\b"'));
    }

    public function testUnescapeStringUnescapesDoubleQuote(): void
    {
        $this->assertSame('a"b', TxRepHelper::unescapeString('"a\\"b"'));
    }

    public function testUnescapeStringUnescapesNewline(): void
    {
        $this->assertSame("a\nb", TxRepHelper::unescapeString('"a\\nb"'));
    }

    public function testUnescapeStringUnescapesCarriageReturn(): void
    {
        $this->assertSame("a\rb", TxRepHelper::unescapeString('"a\\rb"'));
    }

    public function testUnescapeStringUnescapesTab(): void
    {
        $this->assertSame("a\tb", TxRepHelper::unescapeString('"a\\tb"'));
    }

    public function testUnescapeStringUnescapesHexSequences(): void
    {
        // \xc3\xbf decodes to U+00FF (ÿ) in UTF-8
        $result = TxRepHelper::unescapeString('"\\xc3\\xbf"');
        $this->assertSame("\u{00FF}", $result);
    }

    public function testUnescapeStringHandlesInvalidHexGracefully(): void
    {
        // \xZZ is not valid hex — the backslash is passed through
        $result = TxRepHelper::unescapeString('"\\xZZ"');
        $this->assertSame('\\xZZ', $result);
    }

    public function testUnescapeStringHandlesUnknownEscapeSequence(): void
    {
        // \q is not a known sequence — the backslash is passed through
        $result = TxRepHelper::unescapeString('"\\q"');
        $this->assertSame('\\q', $result);
    }

    public function testUnescapeStringRoundtripsWithEscapeString(): void
    {
        $original = 'hello "world"' . "\n" . 'new line \\ backslash';
        $escaped = TxRepHelper::escapeString($original);
        $unescaped = TxRepHelper::unescapeString($escaped);
        $this->assertSame($original, $unescaped);
    }

    public function testUnescapeStringRoundtripsNonAscii(): void
    {
        $original = "\xC3\xBF\xC4\x80"; // U+00FF U+0100 in UTF-8
        $escaped = TxRepHelper::escapeString($original);
        $unescaped = TxRepHelper::unescapeString($escaped);
        $this->assertSame($original, $unescaped);
    }

    public function testUnescapeStringHandlesEmptyQuotedString(): void
    {
        $this->assertSame('', TxRepHelper::unescapeString('""'));
    }

    // ---------------------------------------------------------------------------
    // parseInt()
    // ---------------------------------------------------------------------------

    public function testParseIntDecimal(): void
    {
        $this->assertSame(42, TxRepHelper::parseInt('42'));
    }

    public function testParseIntHexWithLowercasePrefix(): void
    {
        $this->assertSame(255, TxRepHelper::parseInt('0xff'));
    }

    public function testParseIntHexWithUppercasePrefix(): void
    {
        $this->assertSame(255, TxRepHelper::parseInt('0XFF'));
    }

    public function testParseIntNegativeDecimal(): void
    {
        $this->assertSame(-42, TxRepHelper::parseInt('-42'));
    }

    public function testParseIntNegativeHex(): void
    {
        $this->assertSame(-255, TxRepHelper::parseInt('-0xFF'));
    }

    public function testParseIntTrimsWhitespace(): void
    {
        $this->assertSame(42, TxRepHelper::parseInt('  42  '));
    }

    public function testParseIntZero(): void
    {
        $this->assertSame(0, TxRepHelper::parseInt('0'));
    }

    public function testParseIntThrowsForNonNumericString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseInt('abc');
    }

    public function testParseIntThrowsForDecimalPoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseInt('3.14');
    }

    // ---------------------------------------------------------------------------
    // parseBigInt()
    // ---------------------------------------------------------------------------

    public function testParseBigIntDecimal(): void
    {
        $result = TxRepHelper::parseBigInt('123456789');
        $this->assertInstanceOf(BigInteger::class, $result);
        $this->assertSame('123456789', $result->toString());
    }

    public function testParseBigIntHexWithLowercasePrefix(): void
    {
        $result = TxRepHelper::parseBigInt('0xff');
        $this->assertSame('255', $result->toString());
    }

    public function testParseBigIntHexWithUppercasePrefix(): void
    {
        $result = TxRepHelper::parseBigInt('0XFF');
        $this->assertSame('255', $result->toString());
    }

    public function testParseBigIntNegativeDecimal(): void
    {
        $result = TxRepHelper::parseBigInt('-42');
        $this->assertSame('-42', $result->toString());
    }

    public function testParseBigIntNegativeHex(): void
    {
        $result = TxRepHelper::parseBigInt('-0xFF');
        $this->assertSame('-255', $result->toString());
    }

    public function testParseBigIntTrimsWhitespace(): void
    {
        $result = TxRepHelper::parseBigInt('  42  ');
        $this->assertSame('42', $result->toString());
    }

    public function testParseBigIntLargeValue(): void
    {
        // Max int64: 9223372036854775807
        $result = TxRepHelper::parseBigInt('9223372036854775807');
        $this->assertSame('9223372036854775807', $result->toString());
    }

    public function testParseBigIntZero(): void
    {
        $result = TxRepHelper::parseBigInt('0');
        $this->assertSame('0', $result->toString());
    }

    // ---------------------------------------------------------------------------
    // formatAmount() / parseAmount()
    // ---------------------------------------------------------------------------

    public function testFormatAmountWholeXlm(): void
    {
        // 1 XLM = 10,000,000 stroops
        $this->assertSame('1.0000000', TxRepHelper::formatAmount(10000000));
    }

    public function testFormatAmountHundredXlm(): void
    {
        $this->assertSame('100.0000000', TxRepHelper::formatAmount(1000000000));
    }

    public function testFormatAmountFractional(): void
    {
        $this->assertSame('0.5000000', TxRepHelper::formatAmount(5000000));
    }

    public function testFormatAmountMinimumStroop(): void
    {
        $this->assertSame('0.0000001', TxRepHelper::formatAmount(1));
    }

    public function testFormatAmountZero(): void
    {
        $this->assertSame('0.0000000', TxRepHelper::formatAmount(0));
    }

    public function testFormatAmountNegative(): void
    {
        $this->assertSame('-1.0000000', TxRepHelper::formatAmount(-10000000));
    }

    public function testFormatAmountPadsLeadingZerosInFraction(): void
    {
        // 100 stroops = 0.0000100
        $this->assertSame('0.0000100', TxRepHelper::formatAmount(100));
    }

    public function testParseAmountDecimalToStroops(): void
    {
        $this->assertSame(1000000000, TxRepHelper::parseAmount('100.0000000'));
    }

    public function testParseAmountNoDecimalPoint(): void
    {
        $this->assertSame(100 * 10000000, TxRepHelper::parseAmount('100'));
    }

    public function testParseAmountTooFewDecimalPlaces(): void
    {
        // "100.5" should treat missing digits as zeros: 100.5000000
        $this->assertSame(1005000000, TxRepHelper::parseAmount('100.5'));
    }

    public function testParseAmountTooManyDecimalPlacesTruncated(): void
    {
        // More than 7 decimal places are truncated, not rounded.
        // "0.00000019" truncated to 7 places = "0.0000001" = 1 stroop
        $this->assertSame(1, TxRepHelper::parseAmount('0.00000019'));
    }

    public function testParseAmountZero(): void
    {
        $this->assertSame(0, TxRepHelper::parseAmount('0.0000000'));
    }

    public function testParseAmountMinimumStroop(): void
    {
        $this->assertSame(1, TxRepHelper::parseAmount('0.0000001'));
    }

    public function testParseAmountRoundtripsWithFormatAmount(): void
    {
        $stroops = 123456789;
        $formatted = TxRepHelper::formatAmount($stroops);
        $parsed = TxRepHelper::parseAmount($formatted);
        $this->assertSame($stroops, $parsed);
    }

    public function testParseAmountThrowsForNonNumericInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAmount('notanumber');
    }

    // ---------------------------------------------------------------------------
    // formatAccountId() / parseAccountId()
    // ---------------------------------------------------------------------------

    public function testFormatAccountIdReturnsGAddress(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::ISSUER_A);
        $formatted = TxRepHelper::formatAccountId($accountId);
        $this->assertStringStartsWith('G', $formatted);
    }

    public function testParseAccountIdRoundtrip(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::ISSUER_A);
        $formatted = TxRepHelper::formatAccountId($accountId);
        $parsed = TxRepHelper::parseAccountId($formatted);
        $this->assertSame($accountId->getAccountId(), $parsed->getAccountId());
    }

    public function testParseAccountIdThrowsForInvalidStrkey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAccountId('INVALID_ACCOUNT_ID');
    }

    public function testParseAccountIdThrowsForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAccountId('');
    }

    public function testFormatAndParseAccountIdRoundtripsIssuerB(): void
    {
        $accountId = XdrAccountID::fromAccountId(self::ISSUER_B);
        $formatted = TxRepHelper::formatAccountId($accountId);
        $parsed = TxRepHelper::parseAccountId($formatted);
        $this->assertSame(self::ISSUER_B, $parsed->getAccountId());
    }

    // ---------------------------------------------------------------------------
    // formatMuxedAccount() / parseMuxedAccount()
    // ---------------------------------------------------------------------------

    public function testFormatMuxedAccountEd25519ReturnsGAddress(): void
    {
        $mux = new XdrMuxedAccount(StrKey::decodeAccountId(self::ISSUER_A));
        $formatted = TxRepHelper::formatMuxedAccount($mux);
        $this->assertStringStartsWith('G', $formatted);
    }

    public function testParseMuxedAccountGAddressRoundtrip(): void
    {
        $mux = new XdrMuxedAccount(StrKey::decodeAccountId(self::ISSUER_A));
        $formatted = TxRepHelper::formatMuxedAccount($mux);
        $parsed = TxRepHelper::parseMuxedAccount($formatted);
        $this->assertSame($mux->getEd25519(), $parsed->getEd25519());
    }

    public function testFormatAndParseMuxedAccountMAddressRoundtrip(): void
    {
        // Build a muxed account with ID = 1234 over ISSUER_A
        $muxedAccount = new MuxedAccount(self::ISSUER_A, 1234);
        $mAddress = $muxedAccount->getAccountId(); // M... address
        $this->assertStringStartsWith('M', $mAddress);

        $xdrMux = TxRepHelper::parseMuxedAccount($mAddress);
        $formatted = TxRepHelper::formatMuxedAccount($xdrMux);
        $this->assertSame($mAddress, $formatted);
    }

    public function testParseMuxedAccountThrowsForInvalidStrkey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseMuxedAccount('INVALID');
    }

    // ---------------------------------------------------------------------------
    // formatAsset() / parseAsset()
    // ---------------------------------------------------------------------------

    public function testFormatAssetNativeReturnsNative(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $this->assertSame('XLM', TxRepHelper::formatAsset($asset));
    }

    public function testFormatAssetAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $alphaNum4 = new XdrAssetAlphaNum4('USD', $issuer);
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4($alphaNum4);
        $formatted = TxRepHelper::formatAsset($asset);
        $this->assertStringStartsWith('USD:G', $formatted);
    }

    public function testFormatAssetAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $alphaNum12 = new XdrAssetAlphaNum12('LONGCODE', $issuer);
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12($alphaNum12);
        $formatted = TxRepHelper::formatAsset($asset);
        $this->assertStringStartsWith('LONGCODE:G', $formatted);
    }

    public function testParseAssetHandlesNative(): void
    {
        $asset = TxRepHelper::parseAsset('native');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseAssetHandlesXlm(): void
    {
        $asset = TxRepHelper::parseAsset('XLM');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseAssetRoundtripsAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $alphaNum4 = new XdrAssetAlphaNum4('USD', $issuer);
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4($alphaNum4);
        $formatted = TxRepHelper::formatAsset($asset);
        $parsed = TxRepHelper::parseAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
        $this->assertSame('USD', $parsed->getAlphaNum4()->getAssetCode());
        $this->assertSame(self::ISSUER_A, $parsed->getAlphaNum4()->getIssuer()->getAccountId());
    }

    public function testParseAssetRoundtripsAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $alphaNum12 = new XdrAssetAlphaNum12('LONGCODE', $issuer);
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12($alphaNum12);
        $formatted = TxRepHelper::formatAsset($asset);
        $parsed = TxRepHelper::parseAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
        $this->assertSame('LONGCODE', $parsed->getAlphaNum12()->getAssetCode());
    }

    public function testParseAssetThrowsForInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAsset('invalid:format:extra');
    }

    public function testParseAssetThrowsForCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAsset('TOOLONGASSETCODE:' . self::ISSUER_A);
    }

    public function testParseAssetThrowsForSinglePartNonNative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAsset('NOTANASSET');
    }

    // ---------------------------------------------------------------------------
    // formatChangeTrustAsset() / parseChangeTrustAsset()
    // ---------------------------------------------------------------------------

    public function testFormatChangeTrustAssetNative(): void
    {
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $this->assertSame('XLM', TxRepHelper::formatChangeTrustAsset($asset));
    }

    public function testFormatChangeTrustAssetAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4(new XdrAssetAlphaNum4('USD', $issuer));
        $this->assertStringStartsWith('USD:G', TxRepHelper::formatChangeTrustAsset($asset));
    }

    public function testFormatChangeTrustAssetAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12(new XdrAssetAlphaNum12('LONGCODE', $issuer));
        $this->assertStringStartsWith('LONGCODE:G', TxRepHelper::formatChangeTrustAsset($asset));
    }

    public function testFormatChangeTrustAssetThrowsForPoolShare(): void
    {
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatChangeTrustAsset($asset);
    }

    public function testParseChangeTrustAssetHandlesNative(): void
    {
        $asset = TxRepHelper::parseChangeTrustAsset('native');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseChangeTrustAssetHandlesXlm(): void
    {
        $asset = TxRepHelper::parseChangeTrustAsset('XLM');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseChangeTrustAssetRoundtripsAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4(new XdrAssetAlphaNum4('USD', $issuer));
        $formatted = TxRepHelper::formatChangeTrustAsset($asset);
        $parsed = TxRepHelper::parseChangeTrustAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
        $this->assertSame('USD', $parsed->getAlphaNum4()->getAssetCode());
    }

    public function testParseChangeTrustAssetRoundtripsAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12(new XdrAssetAlphaNum12('LONGCODE', $issuer));
        $formatted = TxRepHelper::formatChangeTrustAsset($asset);
        $parsed = TxRepHelper::parseChangeTrustAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
        $this->assertSame('LONGCODE', $parsed->getAlphaNum12()->getAssetCode());
    }

    public function testParseChangeTrustAssetThrowsForInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseChangeTrustAsset('bad:format:extra');
    }

    public function testParseChangeTrustAssetThrowsForCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseChangeTrustAsset('TOOLONGASSETCODE:' . self::ISSUER_A);
    }

    // ---------------------------------------------------------------------------
    // formatTrustlineAsset() / parseTrustlineAsset()
    // ---------------------------------------------------------------------------

    public function testFormatTrustlineAssetNative(): void
    {
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $this->assertSame('XLM', TxRepHelper::formatTrustlineAsset($asset));
    }

    public function testFormatTrustlineAssetAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4(new XdrAssetAlphaNum4('USD', $issuer));
        $this->assertStringStartsWith('USD:G', TxRepHelper::formatTrustlineAsset($asset));
    }

    public function testFormatTrustlineAssetAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12(new XdrAssetAlphaNum12('LONGCODE', $issuer));
        $this->assertStringStartsWith('LONGCODE:G', TxRepHelper::formatTrustlineAsset($asset));
    }

    public function testFormatTrustlineAssetPoolShareReturnsHex(): void
    {
        $poolIdBytes = str_repeat("\xAB", 32);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $asset->setLiquidityPoolID($poolIdBytes);
        $formatted = TxRepHelper::formatTrustlineAsset($asset);
        $this->assertSame(64, strlen($formatted));
        $this->assertSame(str_repeat('ab', 32), $formatted);
    }

    public function testParseTrustlineAssetHandlesNative(): void
    {
        $asset = TxRepHelper::parseTrustlineAsset('native');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseTrustlineAssetHandlesXlm(): void
    {
        $asset = TxRepHelper::parseTrustlineAsset('XLM');
        $this->assertSame(XdrAssetType::ASSET_TYPE_NATIVE, $asset->getType()->getValue());
    }

    public function testParseTrustlineAssetRoundtripsPoolShare(): void
    {
        $poolIdBytes = str_repeat("\xAB", 32);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $asset->setLiquidityPoolID($poolIdBytes);
        $formatted = TxRepHelper::formatTrustlineAsset($asset);
        $parsed = TxRepHelper::parseTrustlineAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_POOL_SHARE, $parsed->getType()->getValue());
        $this->assertSame($poolIdBytes, $parsed->getLiquidityPoolID());
    }

    public function testParseTrustlineAssetRoundtripsAlphaNum4(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAlphaNum4(new XdrAssetAlphaNum4('USD', $issuer));
        $formatted = TxRepHelper::formatTrustlineAsset($asset);
        $parsed = TxRepHelper::parseTrustlineAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
        $this->assertSame('USD', $parsed->getAlphaNum4()->getAssetCode());
    }

    public function testParseTrustlineAssetRoundtripsAlphaNum12(): void
    {
        $issuer = XdrAccountID::fromAccountId(self::ISSUER_A);
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12(new XdrAssetAlphaNum12('LONGCODE', $issuer));
        $formatted = TxRepHelper::formatTrustlineAsset($asset);
        $parsed = TxRepHelper::parseTrustlineAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
        $this->assertSame('LONGCODE', $parsed->getAlphaNum12()->getAssetCode());
    }

    public function testParseTrustlineAssetThrowsForInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseTrustlineAsset('bad:format:extra');
    }

    public function testParseTrustlineAssetThrowsForCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseTrustlineAsset('TOOLONGASSETCODE:' . self::ISSUER_A);
    }

    // ---------------------------------------------------------------------------
    // formatSignerKey() / parseSignerKey()
    // ---------------------------------------------------------------------------

    public function testFormatSignerKeyEd25519ReturnsGAddress(): void
    {
        $keyBytes = StrKey::decodeAccountId(self::ISSUER_A);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
        $key->setEd25519($keyBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $this->assertStringStartsWith('G', $formatted);
    }

    public function testFormatAndParseSignerKeyEd25519Roundtrip(): void
    {
        $keyBytes = StrKey::decodeAccountId(self::ISSUER_A);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
        $key->setEd25519($keyBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $parsed = TxRepHelper::parseSignerKey($formatted);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519, $parsed->getType()->getValue());
        $this->assertSame($keyBytes, $parsed->getEd25519());
    }

    public function testFormatSignerKeyPreAuthTxStartsWithT(): void
    {
        $hashBytes = str_repeat("\xCD", 32);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
        $key->setPreAuthTx($hashBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $this->assertStringStartsWith('T', $formatted);
    }

    public function testFormatAndParseSignerKeyPreAuthTxRoundtrip(): void
    {
        $hashBytes = str_repeat("\xCD", 32);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
        $key->setPreAuthTx($hashBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $parsed = TxRepHelper::parseSignerKey($formatted);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX, $parsed->getType()->getValue());
        $this->assertSame($hashBytes, $parsed->getPreAuthTx());
    }

    public function testFormatSignerKeyHashXStartsWithX(): void
    {
        $hashBytes = str_repeat("\xEF", 32);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
        $key->setHashX($hashBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $this->assertStringStartsWith('X', $formatted);
    }

    public function testFormatAndParseSignerKeyHashXRoundtrip(): void
    {
        $hashBytes = str_repeat("\xEF", 32);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
        $key->setHashX($hashBytes);
        $formatted = TxRepHelper::formatSignerKey($key);
        $parsed = TxRepHelper::parseSignerKey($formatted);
        $this->assertSame(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X, $parsed->getType()->getValue());
        $this->assertSame($hashBytes, $parsed->getHashX());
    }

    public function testFormatSignerKeySignedPayloadStartsWithP(): void
    {
        $ed25519Bytes = str_repeat("\xAB", 32);
        $payloadBytes = "\x01\x02\x03\x04";
        $signedPayload = new XdrSignedPayload($ed25519Bytes, $payloadBytes);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
        $key->setSignedPayload($signedPayload);
        $formatted = TxRepHelper::formatSignerKey($key);
        $this->assertStringStartsWith('P', $formatted);
    }

    public function testFormatAndParseSignerKeySignedPayloadRoundtrip(): void
    {
        $ed25519Bytes = str_repeat("\xAB", 32);
        $payloadBytes = "\x01\x02\x03\x04";
        $signedPayload = new XdrSignedPayload($ed25519Bytes, $payloadBytes);
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
        $key->setSignedPayload($signedPayload);
        $formatted = TxRepHelper::formatSignerKey($key);
        $parsed = TxRepHelper::parseSignerKey($formatted);
        $this->assertSame(
            XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD,
            $parsed->getType()->getValue()
        );
        $this->assertNotNull($parsed->getSignedPayload());
        $this->assertSame($ed25519Bytes, $parsed->getSignedPayload()->getEd25519());
        $this->assertSame($payloadBytes, $parsed->getSignedPayload()->getPayload());
    }

    public function testParseSignerKeyThrowsForUnknownPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseSignerKey('Z1234');
    }

    public function testParseSignerKeyThrowsForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseSignerKey('');
    }

    // ---------------------------------------------------------------------------
    // formatAllowTrustAsset() / parseAllowTrustAsset()
    // ---------------------------------------------------------------------------

    public function testFormatAllowTrustAssetAlphaNum4(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAssetCode4('USD');
        $this->assertSame('USD', TxRepHelper::formatAllowTrustAsset($asset));
    }

    public function testFormatAllowTrustAssetAlphaNum12(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAssetCode12('LONGCODE');
        $this->assertSame('LONGCODE', TxRepHelper::formatAllowTrustAsset($asset));
    }

    public function testFormatAllowTrustAssetStripsTrailingNulls(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAssetCode4("USD\x00");
        $this->assertSame('USD', TxRepHelper::formatAllowTrustAsset($asset));
    }

    public function testParseAllowTrustAssetAlphaNum4(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('USD');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
        $this->assertSame('USD', $parsed->getAssetCode4());
    }

    public function testParseAllowTrustAssetAlphaNum12(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('LONGASSET');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
        $this->assertSame('LONGASSET', $parsed->getAssetCode12());
    }

    public function testParseAllowTrustAssetSingleCharIsAlphaNum4(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('A');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
    }

    public function testParseAllowTrustAssetFourCharIsAlphaNum4(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('EURO');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
    }

    public function testParseAllowTrustAssetFiveCharIsAlphaNum12(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('ASSET');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
    }

    public function testParseAllowTrustAssetTwelveCharIsAlphaNum12(): void
    {
        $parsed = TxRepHelper::parseAllowTrustAsset('TWELVECHARCO');
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
    }

    public function testParseAllowTrustAssetThrowsForCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAllowTrustAsset('TOOLONGASSETCODE');
    }

    public function testParseAllowTrustAssetThrowsForEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAllowTrustAsset('');
    }

    public function testFormatAndParseAllowTrustAssetAlphaNum4Roundtrip(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $asset->setAssetCode4('USD');
        $formatted = TxRepHelper::formatAllowTrustAsset($asset);
        $parsed = TxRepHelper::parseAllowTrustAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4, $parsed->getType()->getValue());
        $this->assertSame('USD', $parsed->getAssetCode4());
    }

    public function testFormatAndParseAllowTrustAssetAlphaNum12Roundtrip(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAssetCode12('LONGCODE');
        $formatted = TxRepHelper::formatAllowTrustAsset($asset);
        $parsed = TxRepHelper::parseAllowTrustAsset($formatted);
        $this->assertSame(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12, $parsed->getType()->getValue());
        $this->assertSame('LONGCODE', $parsed->getAssetCode12());
    }

    // ---------------------------------------------------------------------------
    // formatAsset() — error branches
    // ---------------------------------------------------------------------------

    public function testFormatAssetThrowsForUnsupportedType(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAsset($asset);
    }

    public function testFormatAssetThrowsForAlphaNum4WhenMissingAlphaNum4(): void
    {
        // AlphaNum4 type but no alphaNum4 payload set
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        // alphaNum4 is null by default — should throw
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAsset($asset);
    }

    public function testFormatAssetThrowsForAlphaNum12WhenMissingAlphaNum12(): void
    {
        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAsset($asset);
    }

    // ---------------------------------------------------------------------------
    // parseAsset() — invalid issuer
    // ---------------------------------------------------------------------------

    public function testParseAssetThrowsForInvalidIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAsset('USD:NOTAVALIDISSUER');
    }

    public function testParseAssetThrowsForEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseAsset(':' . self::ISSUER_A);
    }

    // ---------------------------------------------------------------------------
    // formatChangeTrustAsset() — missing payload throws
    // ---------------------------------------------------------------------------

    public function testFormatChangeTrustAssetThrowsForAlphaNum4WhenMissing(): void
    {
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatChangeTrustAsset($asset);
    }

    public function testFormatChangeTrustAssetThrowsForAlphaNum12WhenMissing(): void
    {
        $asset = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatChangeTrustAsset($asset);
    }

    // ---------------------------------------------------------------------------
    // parseChangeTrustAsset() — invalid issuer and empty code
    // ---------------------------------------------------------------------------

    public function testParseChangeTrustAssetThrowsForInvalidIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseChangeTrustAsset('USD:NOTVALID');
    }

    public function testParseChangeTrustAssetThrowsForEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseChangeTrustAsset(':' . self::ISSUER_A);
    }

    // ---------------------------------------------------------------------------
    // formatTrustlineAsset() — missing fields throws
    // ---------------------------------------------------------------------------

    public function testFormatTrustlineAssetThrowsForAlphaNum4WhenMissing(): void
    {
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatTrustlineAsset($asset);
    }

    public function testFormatTrustlineAssetThrowsForAlphaNum12WhenMissing(): void
    {
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatTrustlineAsset($asset);
    }

    public function testFormatTrustlineAssetThrowsForPoolShareWhenMissingId(): void
    {
        $asset = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
        // liquidityPoolID is null by default — should throw
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatTrustlineAsset($asset);
    }

    // ---------------------------------------------------------------------------
    // parseTrustlineAsset() — invalid issuer, empty code
    // ---------------------------------------------------------------------------

    public function testParseTrustlineAssetThrowsForInvalidIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseTrustlineAsset('USD:NOTVALID');
    }

    public function testParseTrustlineAssetThrowsForEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseTrustlineAsset(':' . self::ISSUER_A);
    }

    public function testParseTrustlineAssetSinglePartThrowsWhenNotNativeOrHex(): void
    {
        // A value with no colon and not exactly 64 chars and not native/XLM
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseTrustlineAsset('UNKNOWN');
    }

    // ---------------------------------------------------------------------------
    // formatSignerKey() — error branches
    // ---------------------------------------------------------------------------

    public function testFormatSignerKeyThrowsForMissingEd25519Bytes(): void
    {
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
        // ed25519 is null by default
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatSignerKey($key);
    }

    public function testFormatSignerKeyThrowsForMissingPreAuthTxBytes(): void
    {
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatSignerKey($key);
    }

    public function testFormatSignerKeyThrowsForMissingHashXBytes(): void
    {
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatSignerKey($key);
    }

    public function testFormatSignerKeyThrowsForMissingSignedPayload(): void
    {
        $key = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatSignerKey($key);
    }

    // ---------------------------------------------------------------------------
    // parseSignerKey() — invalid G, T, X prefixes
    // ---------------------------------------------------------------------------

    public function testParseSignerKeyThrowsForInvalidGAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseSignerKey('GINVALID');
    }

    public function testParseSignerKeyThrowsForInvalidTAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseSignerKey('TINVALID');
    }

    public function testParseSignerKeyThrowsForInvalidXAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::parseSignerKey('XINVALID');
    }

    // ---------------------------------------------------------------------------
    // formatAllowTrustAsset() — missing asset code throws
    // ---------------------------------------------------------------------------

    public function testFormatAllowTrustAssetThrowsForMissingAlphaNum4Code(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
        // assetCode4 is null — should throw
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAllowTrustAsset($asset);
    }

    public function testFormatAllowTrustAssetThrowsForMissingAlphaNum12Code(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAllowTrustAsset($asset);
    }

    public function testFormatAllowTrustAssetThrowsForUnsupportedType(): void
    {
        $asset = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        $this->expectException(InvalidArgumentException::class);
        TxRepHelper::formatAllowTrustAsset($asset);
    }

    // ---------------------------------------------------------------------------
    // parseBigInt() — edge cases
    // ---------------------------------------------------------------------------

    public function testParseBigIntNegativeHexValue(): void
    {
        $result = TxRepHelper::parseBigInt('-0xff');
        $this->assertSame('-255', $result->toString());
    }

    public function testParseBigIntLargeUnsignedHex(): void
    {
        $result = TxRepHelper::parseBigInt('0xFFFFFFFFFFFFFFFF');
        $this->assertGreaterThan(new BigInteger(0), $result);
    }

    // ---------------------------------------------------------------------------
    // removeComment() — additional edge cases
    // ---------------------------------------------------------------------------

    public function testRemoveCommentPreservesValueWithNoParenOrQuote(): void
    {
        $this->assertSame('ENVELOPE_TYPE_TX', TxRepHelper::removeComment('ENVELOPE_TYPE_TX'));
    }

    public function testRemoveCommentStripsCommentAfterEnumValue(): void
    {
        $this->assertSame('ENVELOPE_TYPE_TX', TxRepHelper::removeComment('ENVELOPE_TYPE_TX (fee bump)'));
    }

    public function testRemoveCommentHandlesMultipleSpacesBeforeComment(): void
    {
        $this->assertSame('100', TxRepHelper::removeComment('100   (fee)'));
    }

    // ---------------------------------------------------------------------------
    // parse() — additional edge cases
    // ---------------------------------------------------------------------------

    public function testParseHandlesValueWithTabInsideAsPartOfValue(): void
    {
        // Tab-separated data in the value is trimmed along with surrounding spaces.
        // The entire line "tx.fee: 100\ttx.memo: hash" is one line (no newline).
        // The key is "tx.fee", the value is "100\ttx.memo: hash" — trimmed to
        // "100\ttx.memo: hash" (tab survives trim but is interior, so value is
        // "100	tx.memo: hash"). The test simply verifies the entry is parsed.
        $map = TxRepHelper::parse("tx.fee: 100\ttx.memo: hash");
        $this->assertCount(1, $map);
        $this->assertArrayHasKey('tx.fee', $map);
    }

    public function testParseHandlesValueWithLeadingWhitespace(): void
    {
        $map = TxRepHelper::parse("tx.fee:   500");
        $this->assertSame('500', $map['tx.fee']);
    }

    public function testParseHandlesMultipleColonsInValue(): void
    {
        $map = TxRepHelper::parse('tx.asset: USD:' . self::ISSUER_A);
        $this->assertSame('USD:' . self::ISSUER_A, $map['tx.asset']);
    }
}
