
### SEP-0005 - Key Derivation Methods for Stellar Keys

Methods for key derivation for Stellar are described in [SEP-005](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md). This improves key storage and moving keys between wallets and apps.

In the following examples you can see how to generate 12 or 24 words mnemonics for different languages using the PHP SDK, how to generate key pairs from a mnemonic (with and without BIP 39 passphrase) and how to generate key pairs from a BIP 39 seed.

### Generate mnemonic

```php
$mnemonic =  Mnemonic::generate12WordsMnemonic();
print implode(" ", $mnemonic->words) . PHP_EOL;
// bind struggle sausage repair machine fee setup finish transfer stamp benefit economy

$mnemonic =  Mnemonic::generate24WordsMnemonic();
print implode(" ", $mnemonic->words) . PHP_EOL;
// cabbage verb depart erase cable eye crowd approve tower umbrella violin tube island tortoise suspect resemble harbor twelve romance away rug current robust practice

```
Default language is english.

### Generate other language mnemonic 

```php
$frenchMnemonic = Mnemonic::generate12WordsMnemonic(WordList::LANGUAGE_FRENCH);
print implode(" ", $frenchMnemonic->words) . PHP_EOL;
// traction maniable punaise flasque digital maussade usuel joueur volcan vaccin tasse concert

$koreanMnemonic =  Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_KOREAN);
print implode(" ", $koreanMnemonic->words) . PHP_EOL;
// 배꼽 복숭아 오십 방바닥 변호사 교육 평가 휴일 고무신 여론 복사 부근 반응 페인트 예감 악수 하순 양주 줄거리 용기 온종일 의학 핑계 학급
```
Supported languages are: 

- english 
- french 
- spanish 
- italian 
- korean
- japanese
- simplified chinese
- traditional chinese
- malay

### Generate key pairs from mnemonic

```php
$mnemonic = Mnemonic::mnemonicFromWords("shell green recycle learn purchase able oxygen right echo claim hill again hidden evidence nice decade panic enemy cake version say furnace garment glue");

$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
// GCVSEBHB6CTMEHUHIUY4DDFMWQ7PJTHFZGOK2JUD5EG2ARNVS6S22E3K : SATLGMF3SP2V47SJLBFVKZZJQARDOBDQ7DNSSPUV7NLQNPN3QB7M74XH

$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
// GBPHPX7SZKYEDV5CVOA5JOJE2RHJJDCJMRWMV4KBOIE5VSDJ6VAESR2W : SCAYXPIDEUVDGDTKF4NGVMN7HCZOTZJ43E62EEYKVUYXEE7HMU4DFQA6
```

### Generate key pairs from mnemonic of other language

```php
$mnemonic = Mnemonic::mnemonicFromWords("절차 튀김 건강 평가 테스트 민족 몹시 어른 주민 형제 발레 만점 산길 물고기 방면 여학생 결국 수명 애정 정치 관심 상자 축하 고무신",
    WordList::LANGUAGE_KOREAN);

$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0);
print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
// GBEAH7ADD5NRYA5YGXDMSWB7PK7J44DYG5I7SVL2FYHCPH5ZH4EJC3YP : SAINP4ANECVGSF5SBNWZIQDX3XTGFLSTCWVHJN4BE5AFY42DOCPS6MEW

$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1);
print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
// GCCSXBOX7Y54MT74FGBTL3OI6IOPTB7LSCSLZXKMHG4X56DYN2DPKUTF : SAB26ECJ3TATPR3MHA75IL4KPRXAQWMCGRYKIK3DWXW7Y53DOPVA2YZP
```

### Generate key pairs from mnemonic with BIP 39 passphrase

```php
$mnemonic = Mnemonic::mnemonicFromWords("cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube");
$passphrase = "p4ssphr4se";

$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0, $passphrase);
print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
// GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ : SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X

$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1, $passphrase);
print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
// GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC : SBQPDFUGLMWJYEYXFRM5TQX3AX2BR47WKI4FDS7EJQUSEUUVY72MZPJF
```

### Generate key pairs from BIP 39 seed

```php
$bip39SeedHex = "e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497ee4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186";

$keyPair0 = KeyPair::fromBip39SeedHex($bip39SeedHex, 0);
print($keyPair0->getAccountId() . " : " . $keyPair0->getSecretSeed() . PHP_EOL);
// GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6 : SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2G4O34RMTB343OYPXU5DJDVMN

$keyPair1 = KeyPair::fromBip39SeedHex($bip39SeedHex, 1);
print($keyPair1->getAccountId() . " : " . $keyPair1->getSecretSeed() . PHP_EOL);
// GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX : SCEPFFWGAG5P2VX5DHIYK3XEMZYLTYWIPWYEKXFHSK25RVMIUNJ7CTIS
```

### Generate BIP 39 seed and m/44'/148' key from mnemonic

```php
$mnemonic = Mnemonic::generate24WordsMnemonic(WordList::LANGUAGE_ITALIAN);

print("BIP 39 seed: " .  $mnemonic->bip39SeedHex() . PHP_EOL);
// BIP 39 seed: 54e3061b46c2ceeb9acb29b7c879f5c06414ecc70938ac9c8579fd7d188e9b96162d0477d3af08c86d8cda34949783849518b7be031da5b1fc068735846df573

print("BIP 39 seed with passphrase: " .  $mnemonic->bip39SeedHex("p4ssphr4se") . PHP_EOL);
// BIP 39 seed with passphrase: a277e7c3670cf371692a6ef6f9c4177dac6cba69d467b577a430193def40a1512bfedaec8a7cddc7b38573518f242f2b0178048389eeb5dbccaf4ee5556027a2

print("m/44'/148' key: " .  $mnemonic->m44148keyHex() . PHP_EOL);
// m/44'/148' key: c5af1061efaa129ef0e0b56e38b8139a27dcfef0f4cbbdfa45b8128a1ac89fbe

print("m/44'/148' key with passphrase: " .  $mnemonic->m44148keyHex("p4ssphr4se") . PHP_EOL);
// m/44'/148' key with passphrase: 896dacfe28cac6362e5df6a98482d82719853313feab117a01081110a5e5ca25
```