<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
use Soneso\StellarSDK\SEP\Derivation\WordList;

class SEP005Test extends TestCase
{

    /*
     * see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-1
     */
    public function testStellarTest1(): void
    {
        $words = "illness spike retreat truth genius clock brain pass fit cave bargain toe";
        $mnemonic = Mnemonic::mnemonicFromWords($words);

        $expectedBip39Seed = "e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497ee4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186";
        $this->assertEquals($expectedBip39Seed, $mnemonic->bip39SeedHex());

        $expectedM44148Key = "e0eec84fe165cd427cb7bc9b6cfdef0555aa1cb6f9043ff1fe986c3c8ddd22e3";
        $this->assertEquals($expectedM44148Key, $mnemonic->m44148keyHex());

        $expectedKey0Id = "GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6";
        $expectedKey0Seed = "SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2G4O34RMTB343OYPXU5DJDVMN";

        $kp0 = KeyPair::fromMnemonic($mnemonic, 0);
        $this->assertEquals($expectedKey0Id, $kp0->getAccountId());
        $this->assertEquals($expectedKey0Seed, $kp0->getSecretSeed());

        $expectedKey1Id = "GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX";
        $expectedKey1Seed = "SCEPFFWGAG5P2VX5DHIYK3XEMZYLTYWIPWYEKXFHSK25RVMIUNJ7CTIS";

        $kp1 = KeyPair::fromMnemonic($mnemonic, 1);
        $this->assertEquals($expectedKey1Id, $kp1->getAccountId());
        $this->assertEquals($expectedKey1Seed, $kp1->getSecretSeed());

        $expectedKey5Id = "GBRQY5JFN5UBG5PGOSUOL4M6D7VRMAYU6WW2ZWXBMCKB7GPT3YCBU2XZ";
        $expectedKey5Seed = "SCK27SFHI3WUDOEMJREV7ZJQG34SCBR6YWCE6OLEXUS2VVYTSNGCRS6X";

        $kp5 = KeyPair::fromMnemonic($mnemonic, 5);
        $this->assertEquals($expectedKey5Id, $kp5->getAccountId());
        $this->assertEquals($expectedKey5Seed, $kp5->getSecretSeed());

        $expectedKey9Id = "GBTVYYDIYWGUQUTKX6ZMLGSZGMTESJYJKJWAATGZGITA25ZB6T5REF44";
        $expectedKey9Seed = "SCJGVMJ66WAUHQHNLMWDFGY2E72QKSI3XGSBYV6BANDFUFE7VY4XNXXR";

        $kp9 = KeyPair::fromMnemonic($mnemonic, 9);
        $this->assertEquals($expectedKey9Id, $kp9->getAccountId());
        $this->assertEquals($expectedKey9Seed, $kp9->getSecretSeed());

    }

    /*
    * see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-2
    */
    public function testStellarTest2(): void
    {
        $words = "resource asthma orphan phone ice canvas fire useful arch jewel impose vague theory cushion top";
        $mnemonic = Mnemonic::mnemonicFromWords($words);

        $expectedBip39Seed = "7b36d4e725b48695c3ffd2b4b317d5552cb157c1a26c46d36a05317f0d3053eb8b3b6496ba39ebd9312d10e3f9937b47a6790541e7c577da027a564862e92811";
        $this->assertEquals($expectedBip39Seed, $mnemonic->bip39SeedHex());

        $expectedM44148Key = "2e5d4e6b54a4b96c5e887c9ec92f619a3c134d8b1059dcef15c1a9b228ae3751";
        $this->assertEquals($expectedM44148Key, $mnemonic->m44148keyHex());

        $expectedKey0Id = "GAVXVW5MCK7Q66RIBWZZKZEDQTRXWCZUP4DIIFXCCENGW2P6W4OA34RH";
        $expectedKey0Seed = "SAKS7I2PNDBE5SJSUSU2XLJ7K5XJ3V3K4UDFAHMSBQYPOKE247VHAGDB";

        $kp0 = KeyPair::fromMnemonic($mnemonic,0);
        $this->assertEquals($expectedKey0Id, $kp0->getAccountId());
        $this->assertEquals($expectedKey0Seed, $kp0->getSecretSeed());

        $expectedKey1Id = "GDFCYVCICATX5YPJUDS22KM2GW5QU2KKSPPPT2IC5AQIU6TP3BZSLR5K";
        $expectedKey1Seed = "SAZ2H5GLAVWCUWNPQMB6I3OHRI63T2ACUUAWSH7NAGYYPXGIOPLPW3Q4";

        $kp1 = KeyPair::fromMnemonic($mnemonic, 1);
        $this->assertEquals($expectedKey1Id, $kp1->getAccountId());
        $this->assertEquals($expectedKey1Seed, $kp1->getSecretSeed());

        $expectedKey5Id = "GDKWYAJE3W6PWCXDZNMFNFQSPTF6BUDANE6OVRYMJKBYNGL62VKKCNCC";
        $expectedKey5Seed = "SAVS4CDQZI6PSA5DPCC42S5WLKYIPKXPCJSFYY4N3VDK25T2XX2BTGVX";

        $kp5 = KeyPair::fromMnemonic($mnemonic, 5);
        $this->assertEquals($expectedKey5Id, $kp5->getAccountId());
        $this->assertEquals($expectedKey5Seed, $kp5->getSecretSeed());

        $expectedKey9Id = "GB3C6RRQB3V7EPDXEDJCMTS45LVDLSZQ46PTIGKZUY37DXXEOAKJIWSV";
        $expectedKey9Seed = "SDHRG2J34MGDAYHMOVKVJC6LX2QZMCTIKRO5I4JQ6BJQ36KVL6QUTT72";

        $kp9 = KeyPair::fromMnemonic($mnemonic, 9);
        $this->assertEquals($expectedKey9Id, $kp9->getAccountId());
        $this->assertEquals($expectedKey9Seed, $kp9->getSecretSeed());
    }

    /*
    * see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-3
    */
    public function testStellarTest3(): void
    {
        $words = "bench hurt jump file august wise shallow faculty impulse spring exact slush thunder author capable act festival slice deposit sauce coconut afford frown better";
        $mnemonic = Mnemonic::mnemonicFromWords($words);

        $expectedBip39Seed = "937ae91f6ab6f12461d9936dfc1375ea5312d097f3f1eb6fed6a82fbe38c85824da8704389831482db0433e5f6c6c9700ff1946aa75ad8cc2654d6e40f567866";
        $this->assertEquals($expectedBip39Seed, $mnemonic->bip39SeedHex());

        $expectedM44148Key = "df474e0dc2711089b89af6b089aceeb77e73120e9f895bd330a36fa952835ea8";
        $this->assertEquals($expectedM44148Key, $mnemonic->m44148keyHex());

        $expectedKey0Id = "GC3MMSXBWHL6CPOAVERSJITX7BH76YU252WGLUOM5CJX3E7UCYZBTPJQ";
        $expectedKey0Seed = "SAEWIVK3VLNEJ3WEJRZXQGDAS5NVG2BYSYDFRSH4GKVTS5RXNVED5AX7";

        $kp0 = KeyPair::fromMnemonic($mnemonic, 0);
        $this->assertEquals($expectedKey0Id, $kp0->getAccountId());
        $this->assertEquals($expectedKey0Seed, $kp0->getSecretSeed());

        $expectedKey1Id = "GB3MTYFXPBZBUINVG72XR7AQ6P2I32CYSXWNRKJ2PV5H5C7EAM5YYISO";
        $expectedKey1Seed = "SBKSABCPDWXDFSZISAVJ5XKVIEWV4M5O3KBRRLSPY3COQI7ZP423FYB4";

        $kp1 = KeyPair::fromMnemonic($mnemonic, 1);
        $this->assertEquals($expectedKey1Id, $kp1->getAccountId());
        $this->assertEquals($expectedKey1Seed, $kp1->getSecretSeed());

        $expectedKey5Id = "GA6RUD4DZ2NEMAQY4VZJ4C6K6VSEYEJITNSLUQKLCFHJ2JOGC5UCGCFQ";
        $expectedKey5Seed = "SCVM6ZNVRUOP4NMCMMKLTVBEMAF2THIOMHPYSSMPCD2ZU7VDPARQQ6OY";

        $kp5 = KeyPair::fromMnemonic($mnemonic, 5);
        $this->assertEquals($expectedKey5Id, $kp5->getAccountId());
        $this->assertEquals($expectedKey5Seed, $kp5->getSecretSeed());

        $expectedKey9Id = "GDXOY6HXPIDT2QD352CH7VWX257PHVFR72COWQ74QE3TEV4PK2KCKZX7";
        $expectedKey9Seed = "SCPA5OX4EYINOPAUEQCPY6TJMYICUS5M7TVXYKWXR3G5ZRAJXY3C37GF";

        $kp9 = KeyPair::fromMnemonic($mnemonic, 9);
        $this->assertEquals($expectedKey9Id, $kp9->getAccountId());
        $this->assertEquals($expectedKey9Seed, $kp9->getSecretSeed());
    }

    /*
    * see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-4
    */
    public function testStellarTest4(): void
    {
        $words = "cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube";
        $mnemonic = Mnemonic::mnemonicFromWords($words);
        $passphrase = "p4ssphr4se";

        $expectedBip39Seed = "d425d39998fb42ce4cf31425f0eaec2f0a68f47655ea030d6d26e70200d8ff8bd4326b4bdf562ea8640a1501ae93ccd0fd7992116da5dfa24900e570a742a489";
        $this->assertEquals($expectedBip39Seed, $mnemonic->bip39SeedHex($passphrase));

        $expectedM44148Key = "c83c61dc97d37832f0f20e258c3ba4040a258800fd14abaff124a4dee114b17e";
        $this->assertEquals($expectedM44148Key, $mnemonic->m44148keyHex($passphrase));

        $expectedKey0Id = "GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ";
        $expectedKey0Seed = "SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X";

        $kp0 = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);
        $this->assertEquals($expectedKey0Id, $kp0->getAccountId());
        $this->assertEquals($expectedKey0Seed, $kp0->getSecretSeed());

        $expectedKey1Id = "GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC";
        $expectedKey1Seed = "SBQPDFUGLMWJYEYXFRM5TQX3AX2BR47WKI4FDS7EJQUSEUUVY72MZPJF";

        $kp1 = KeyPair::fromMnemonic($mnemonic, 1, $passphrase);
        $this->assertEquals($expectedKey1Id, $kp1->getAccountId());
        $this->assertEquals($expectedKey1Seed, $kp1->getSecretSeed());

        $expectedKey5Id = "GBOWMXTLABFNEWO34UJNSJJNVEF6ESLCNNS36S5SX46UZT2MNYJOLA5L";
        $expectedKey5Seed = "SDEOED2KPHV355YNOLLDLVQB7HDPQVIGKXCAJMA3HTM4325ZHFZSKKUC";

        $kp5 = KeyPair::fromMnemonic($mnemonic, 5, $passphrase);
        $this->assertEquals($expectedKey5Id, $kp5->getAccountId());
        $this->assertEquals($expectedKey5Seed, $kp5->getSecretSeed());

        $expectedKey9Id = "GBOSMFQYKWFDHJWCMCZSMGUMWCZOM4KFMXXS64INDHVCJ2A2JAABCYRR";
        $expectedKey9Seed = "SDXDYPDNRMGOF25AWYYKPHFAD3M54IT7LCLG7RWTGR3TS32A4HTUXNOS";

        $kp9 = KeyPair::fromMnemonic($mnemonic, 9, $passphrase);
        $this->assertEquals($expectedKey9Id, $kp9->getAccountId());
        $this->assertEquals($expectedKey9Seed, $kp9->getSecretSeed());
    }

    /*
    * see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md#test-5
    */
    public function testStellarTest5(): void
    {
        $words = "abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about";
        $mnemonic = Mnemonic::mnemonicFromWords($words);

        $expectedBip39Seed = "5eb00bbddcf069084889a8ab9155568165f5c453ccb85e70811aaed6f6da5fc19a5ac40b389cd370d086206dec8aa6c43daea6690f20ad3d8d48b2d2ce9e38e4";
        $this->assertEquals($expectedBip39Seed, $mnemonic->bip39SeedHex());

        $expectedM44148Key = "03df7921b4f789040e361d07d5e4eddad277c376350d7b5d585400a0ef18f2f5";
        $this->assertEquals($expectedM44148Key, $mnemonic->m44148keyHex());

        $expectedKey0Id = "GB3JDWCQJCWMJ3IILWIGDTQJJC5567PGVEVXSCVPEQOTDN64VJBDQBYX";
        $expectedKey0Seed = "SBUV3MRWKNS6AYKZ6E6MOUVF2OYMON3MIUASWL3JLY5E3ISDJFELYBRZ";

        $kp0 = KeyPair::fromMnemonic($mnemonic, 0);
        $this->assertEquals($expectedKey0Id, $kp0->getAccountId());
        $this->assertEquals($expectedKey0Seed, $kp0->getSecretSeed());

        $expectedKey1Id = "GDVSYYTUAJ3ACHTPQNSTQBDQ4LDHQCMNY4FCEQH5TJUMSSLWQSTG42MV";
        $expectedKey1Seed = "SCHDCVCWGAKGIMTORV6K5DYYV3BY4WG3RA4M6MCBGJLHUCWU2MC6DL66";

        $kp1 = KeyPair::fromMnemonic($mnemonic, 1);
        $this->assertEquals($expectedKey1Id, $kp1->getAccountId());
        $this->assertEquals($expectedKey1Seed, $kp1->getSecretSeed());

        $expectedKey5Id = "GDTA7622ZA5PW7F7JL7NOEFGW62M7GW2GY764EQC2TUJ42YJQE2A3QUL";
        $expectedKey5Seed = "SDTWG5AFDI6GRQNLPWOC7IYS7AKOGMI2GX4OXTBTZHHYPMNZ2PX4ONWU";

        $kp5 = KeyPair::fromMnemonic($mnemonic, 5);
        $this->assertEquals($expectedKey5Id, $kp5->getAccountId());
        $this->assertEquals($expectedKey5Seed, $kp5->getSecretSeed());

        $expectedKey9Id = "GAKFARYSPI33KUJE7HYLT47DCX2PFWJ77W3LZMRBPSGPGYPMSDBE7W7X";
        $expectedKey9Seed = "SALJ5LPBTXCFML2CQ7ORP7WJNJOZSVBVRQAAODMVHMUF4P4XXFZB7MKY";

        $kp9 = KeyPair::fromMnemonic($mnemonic, 9);
        $this->assertEquals($expectedKey9Id, $kp9->getAccountId());
        $this->assertEquals($expectedKey9Seed, $kp9->getSecretSeed());
    }

    public function testGenerateMnemonic(): void
    {
        $mnemonic = Mnemonic::generate12WordsMnemonic();
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic();
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic();
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_CHINESE_SIMPLIFIED);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_CHINESE_SIMPLIFIED);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_CHINESE_SIMPLIFIED);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_CHINESE_TRADITIONAL);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_CHINESE_TRADITIONAL);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_CHINESE_TRADITIONAL);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_FRENCH);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_FRENCH);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_FRENCH);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_ITALIAN);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_ITALIAN);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_ITALIAN);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_JAPANESE);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_JAPANESE);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_JAPANESE);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_KOREAN);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_KOREAN);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_KOREAN);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_SPANISH);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_SPANISH);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_SPANISH);
        $this->assertEquals(24, count($mnemonic->words));

        $mnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_MALAY);
        $this->assertEquals(12, count($mnemonic->words));

        $mnemonic = Mnemonic::generate15WordsMnemonic(WordList::LANGUAGE_MALAY);
        $this->assertEquals(15, count($mnemonic->words));

        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_MALAY);
        $this->assertEquals(24, count($mnemonic->words));
    }

    public function testBip39HexKeypair(): void
    {
        $mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_SPANISH);
        $this->assertEquals(24, count($mnemonic->words));

        $bip = $mnemonic->bip39SeedHex();
        $kp0 = KeyPair::fromMnemonic($mnemonic, 0);
        $kp1 = KeyPair::fromBip39SeedHex($bip, 0);
        $this->assertEquals($kp0->getAccountId(), $kp1->getAccountId());
    }

}