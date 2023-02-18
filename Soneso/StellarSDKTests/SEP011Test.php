<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\TxRep\TxRep;

class SEP011Test extends TestCase {

    public function testTxRepAndBack(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.fee: 1400
tx.seqNum: 1102902109202
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 1595282368
tx.cond.timeBounds.maxTime: 1595284000
tx.memo.type: MEMO_TEXT
tx.memo.text: "Enjoy this transaction"
tx.operations.len: 14
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: CREATE_ACCOUNT
tx.operations[0].body.createAccountOp.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[0].body.createAccountOp.startingBalance: 9223372036854775807
tx.operations[1].sourceAccount._present: true
tx.operations[1].sourceAccount: GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[1].body.type: PAYMENT
tx.operations[1].body.paymentOp.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[1].body.paymentOp.asset: XLM
tx.operations[1].body.paymentOp.amount: 9223372036854775807
tx.operations[2].sourceAccount._present: true
tx.operations[2].sourceAccount: GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[2].body.type: PAYMENT
tx.operations[2].body.paymentOp.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[2].body.paymentOp.asset: USD:GAZFEVBSEGJJ63WPVVIWXLZLWN2JYZECECGT6GUNP4FJDVZVNXWQWMYI
tx.operations[2].body.paymentOp.amount: 9223372036854775807
tx.operations[3].sourceAccount._present: false
tx.operations[3].body.type: PATH_PAYMENT_STRICT_RECEIVE
tx.operations[3].body.pathPaymentStrictReceiveOp.sendAsset: IOM:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[3].body.pathPaymentStrictReceiveOp.sendMax: 20000000
tx.operations[3].body.pathPaymentStrictReceiveOp.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[3].body.pathPaymentStrictReceiveOp.destAsset: MOON:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[3].body.pathPaymentStrictReceiveOp.destAmount: 80000000
tx.operations[3].body.pathPaymentStrictReceiveOp.path.len: 2
tx.operations[3].body.pathPaymentStrictReceiveOp.path[0]: ECO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[3].body.pathPaymentStrictReceiveOp.path[1]: ASTRO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[4].sourceAccount._present: false
tx.operations[4].body.type: PATH_PAYMENT_STRICT_SEND
tx.operations[4].body.pathPaymentStrictSendOp.sendAsset: IOM:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[4].body.pathPaymentStrictSendOp.sendAmount: 4000000000
tx.operations[4].body.pathPaymentStrictSendOp.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[4].body.pathPaymentStrictSendOp.destAsset: MOON:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[4].body.pathPaymentStrictSendOp.destMin: 12000000000
tx.operations[4].body.pathPaymentStrictSendOp.path.len: 2
tx.operations[4].body.pathPaymentStrictSendOp.path[0]: ECO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[4].body.pathPaymentStrictSendOp.path[1]: ASTRO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[5].sourceAccount._present: false
tx.operations[5].body.type: SET_OPTIONS
tx.operations[5].body.setOptionsOp.inflationDest._present: true
tx.operations[5].body.setOptionsOp.inflationDest: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[5].body.setOptionsOp.clearFlags._present: true
tx.operations[5].body.setOptionsOp.clearFlags: 2
tx.operations[5].body.setOptionsOp.setFlags._present: true
tx.operations[5].body.setOptionsOp.setFlags: 4
tx.operations[5].body.setOptionsOp.masterWeight._present: true
tx.operations[5].body.setOptionsOp.masterWeight: 122
tx.operations[5].body.setOptionsOp.lowThreshold._present: true
tx.operations[5].body.setOptionsOp.lowThreshold: 10
tx.operations[5].body.setOptionsOp.medThreshold._present: true
tx.operations[5].body.setOptionsOp.medThreshold: 50
tx.operations[5].body.setOptionsOp.highThreshold._present: true
tx.operations[5].body.setOptionsOp.highThreshold: 122
tx.operations[5].body.setOptionsOp.homeDomain._present: true
tx.operations[5].body.setOptionsOp.homeDomain: "https://www.soneso.com/blubber"
tx.operations[5].body.setOptionsOp.signer._present: true
tx.operations[5].body.setOptionsOp.signer.key: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[5].body.setOptionsOp.signer.weight: 50
tx.operations[6].sourceAccount._present: false
tx.operations[6].body.type: MANAGE_SELL_OFFER
tx.operations[6].body.manageSellOfferOp.selling: ECO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[6].body.manageSellOfferOp.buying: XLM
tx.operations[6].body.manageSellOfferOp.amount: 82820000000
tx.operations[6].body.manageSellOfferOp.price.n: 7
tx.operations[6].body.manageSellOfferOp.price.d: 10
tx.operations[6].body.manageSellOfferOp.offerID: 9298298398333
tx.operations[7].sourceAccount._present: false
tx.operations[7].body.type: CREATE_PASSIVE_SELL_OFFER
tx.operations[7].body.createPassiveSellOfferOp.selling: ASTRO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[7].body.createPassiveSellOfferOp.buying: MOON:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[7].body.createPassiveSellOfferOp.amount: 28280000000
tx.operations[7].body.createPassiveSellOfferOp.price.n: 1
tx.operations[7].body.createPassiveSellOfferOp.price.d: 2
tx.operations[8].sourceAccount._present: false
tx.operations[8].body.type: CHANGE_TRUST
tx.operations[8].body.changeTrustOp.line: ASTRO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[8].body.changeTrustOp.limit: 100000000000
tx.operations[9].sourceAccount._present: false
tx.operations[9].body.type: ALLOW_TRUST
tx.operations[9].body.allowTrustOp.trustor: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[9].body.allowTrustOp.asset: MOON
tx.operations[9].body.allowTrustOp.authorize: 1
tx.operations[10].sourceAccount._present: false
tx.operations[10].body.type: ACCOUNT_MERGE
tx.operations[10].body.destination: GALKCFFI5YT2D2SR2WPXAPFN7AWYIMU4DYSPN6HNBHH37YAD2PNFIGXE
tx.operations[11].sourceAccount._present: false
tx.operations[11].body.type: MANAGE_DATA
tx.operations[11].body.manageDataOp.dataName: "Sommer"
tx.operations[11].body.manageDataOp.dataValue._present: true
tx.operations[11].body.manageDataOp.dataValue: 446965204df662656c2073696e6420686569df21
tx.operations[12].sourceAccount._present: false
tx.operations[12].body.type: BUMP_SEQUENCE
tx.operations[12].body.bumpSequenceOp.bumpTo: 1102902109211
tx.operations[13].sourceAccount._present: false
tx.operations[13].body.type: MANAGE_BUY_OFFER
tx.operations[13].body.manageBuyOfferOp.selling: MOON:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[13].body.manageBuyOfferOp.buying: ECO:GDICQ4HZOFVPJF7QNLHOUFUBNAH3TN4AJSRHZKFQH25I465VDVQE4ZS2
tx.operations[13].body.manageBuyOfferOp.buyAmount: 120000000
tx.operations[13].body.manageBuyOfferOp.price.n: 1
tx.operations[13].body.manageBuyOfferOp.price.d: 5
tx.operations[13].body.manageBuyOfferOp.offerID: 9298298398334
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: b51d604e
signatures[0].signature: c52a9c15a60a9b7281cb9e932e0eb1ffbe9a759b6cc242eeb08dda88cfff3faaa47b5d817153617825941d1d0c46523f54d9b3790f1cee1370af08a5c29dfe03';

        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testFeeBumpTxRep(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX_FEE_BUMP
feeBump.tx.feeSource: GBD4KWT3HXUGS4ACUZZELY67UJXLOFTZAPR5DT5QIMBO6BX53FXFSLQS
feeBump.tx.fee: 1515
feeBump.tx.innerTx.type: ENVELOPE_TYPE_TX
feeBump.tx.innerTx.tx.sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.fee: 1400
feeBump.tx.innerTx.tx.seqNum: 1102902109202
feeBump.tx.innerTx.tx.cond.type: PRECOND_TIME
feeBump.tx.innerTx.tx.cond.timeBounds.minTime: 1595282368
feeBump.tx.innerTx.tx.cond.timeBounds.maxTime: 1595284000
feeBump.tx.innerTx.tx.memo.type: MEMO_TEXT
feeBump.tx.innerTx.tx.memo.text: "Enjoy this transaction"
feeBump.tx.innerTx.tx.operations.len: 14
feeBump.tx.innerTx.tx.operations[0].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[0].body.type: CREATE_ACCOUNT
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.startingBalance: 9223372036854775807
feeBump.tx.innerTx.tx.operations[1].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[1].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[1].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.asset: XLM
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.amount: 9223372036854775807
feeBump.tx.innerTx.tx.operations[2].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[2].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[2].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.asset: USD:GAZFEVBSEGJJ63WPVVIWXLZLWN2JYZECECGT6GUNP4FJDVZVNXWQWMYI
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.amount: 9223372036854775807
feeBump.tx.innerTx.tx.operations[3].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[3].body.type: PATH_PAYMENT_STRICT_RECEIVE
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendMax: 20000000
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAmount: 80000000
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path.len: 2
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[4].body.type: PATH_PAYMENT_STRICT_SEND
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAmount: 4000000000
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destMin: 12000000000
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path.len: 2
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[5].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[5].body.type: SET_OPTIONS
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags: 2
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags: 4
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold: 10
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold: 50
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain: "https://www.soneso.com/blubber"
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.key: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.weight: 50
feeBump.tx.innerTx.tx.operations[6].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[6].body.type: MANAGE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.selling: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.buying: XLM
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.amount: 82820000000
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.n: 7
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.d: 10
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.offerID: 9298298398333
feeBump.tx.innerTx.tx.operations[7].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[7].body.type: CREATE_PASSIVE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.selling: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.buying: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.amount: 28280000000
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.d: 2
feeBump.tx.innerTx.tx.operations[8].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[8].body.type: CHANGE_TRUST
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.line: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.limit: 100000000000
feeBump.tx.innerTx.tx.operations[9].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[9].body.type: ALLOW_TRUST
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.trustor: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.asset: MOON
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.authorize: 1
feeBump.tx.innerTx.tx.operations[10].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[10].body.type: ACCOUNT_MERGE
feeBump.tx.innerTx.tx.operations[10].body.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[11].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[11].body.type: MANAGE_DATA
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataName: "Sommer"
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue._present: true
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue: 446965204df662656c2073696e6420686569df21
feeBump.tx.innerTx.tx.operations[12].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[12].body.type: BUMP_SEQUENCE
feeBump.tx.innerTx.tx.operations[12].body.bumpSequenceOp.bumpTo: 1102902109211
feeBump.tx.innerTx.tx.operations[13].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[13].body.type: MANAGE_BUY_OFFER
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.selling: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buying: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buyAmount: 120000000
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.d: 5
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.offerID: 9298298398334
feeBump.tx.innerTx.tx.ext.v: 0
feeBump.tx.innerTx.signatures.len: 1
feeBump.tx.innerTx.signatures[0].hint: 7b21e7e3
feeBump.tx.innerTx.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a
feeBump.tx.ext.v: 0
feeBump.signatures.len: 1
feeBump.signatures[0].hint: 7b21e7e3
feeBump.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a';

        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testTxRepFromStc(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX_FEE_BUMP
feeBump.tx.feeSource: GBD4KWT3HXUGS4ACUZZELY67UJXLOFTZAPR5DT5QIMBO6BX53FXFSLQS
feeBump.tx.fee: 1515 (0.0001515e7)
feeBump.tx.innerTx.type: ENVELOPE_TYPE_TX
feeBump.tx.innerTx.tx.sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.fee: 1400
feeBump.tx.innerTx.tx.seqNum: 1102902109202
feeBump.tx.innerTx.tx.cond.type: PRECOND_TIME
feeBump.tx.innerTx.tx.cond.timeBounds.minTime: 1595282368 (Mon Jul 20 23:59:28 CEST 2020)
feeBump.tx.innerTx.tx.cond.timeBounds.maxTime: 1595284000 (Tue Jul 21 00:26:40 CEST 2020)
feeBump.tx.innerTx.tx.memo.type: MEMO_TEXT
feeBump.tx.innerTx.tx.memo.text: "Enjoy this transaction"
feeBump.tx.innerTx.tx.operations.len: 14
feeBump.tx.innerTx.tx.operations[0].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[0].body.type: CREATE_ACCOUNT
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.startingBalance: 9223372036854775807 (922,337,203,685.4775807e7)
feeBump.tx.innerTx.tx.operations[1].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[1].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[1].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.asset: XLM
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.amount: 9223372036854775807 (922,337,203,685.4775807e7)
feeBump.tx.innerTx.tx.operations[2].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[2].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[2].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.asset: USD:GAZFEVBSEGJJ63WPVVIWXLZLWN2JYZECECGT6GUNP4FJDVZVNXWQWMYI
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.amount: 9223372036854775807 (922,337,203,685.4775807e7)
feeBump.tx.innerTx.tx.operations[3].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[3].body.type: PATH_PAYMENT_STRICT_RECEIVE
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendMax: 20000000 (2e7)
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAmount: 80000000 (8e7)
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path.len: 2
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[4].body.type: PATH_PAYMENT_STRICT_SEND
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAmount: 4000000000 (400e7)
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destMin: 12000000000 (1,200e7)
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path.len: 2
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[5].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[5].body.type: SET_OPTIONS
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags: 2
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags: 4
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold: 10
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold: 50
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain: "https://www.soneso.com/blubber"
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.key: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.weight: 50
feeBump.tx.innerTx.tx.operations[6].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[6].body.type: MANAGE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.selling: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.buying: XLM
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.amount: 82820000000 (8,282e7)
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.n: 7
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.d: 10
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.offerID: 9298298398333 (929,829.8398333e7)
feeBump.tx.innerTx.tx.operations[7].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[7].body.type: CREATE_PASSIVE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.selling: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.buying: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.amount: 28280000000 (2,828e7)
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.d: 2
feeBump.tx.innerTx.tx.operations[8].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[8].body.type: CHANGE_TRUST
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.line: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.limit: 100000000000 (10,000e7)
feeBump.tx.innerTx.tx.operations[9].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[9].body.type: ALLOW_TRUST
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.trustor: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.asset: MOON
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.authorize: 1
feeBump.tx.innerTx.tx.operations[10].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[10].body.type: ACCOUNT_MERGE
feeBump.tx.innerTx.tx.operations[10].body.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[11].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[11].body.type: MANAGE_DATA
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataName: "Sommer"
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue._present: true
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue: 446965204df662656c2073696e6420686569df21
feeBump.tx.innerTx.tx.operations[12].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[12].body.type: BUMP_SEQUENCE
feeBump.tx.innerTx.tx.operations[12].body.bumpSequenceOp.bumpTo: 1102902109211
feeBump.tx.innerTx.tx.operations[13].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[13].body.type: MANAGE_BUY_OFFER
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.selling: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buying: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buyAmount: 120000000 (12e7)
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.d: 5
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.offerID: 9298298398334 (929,829.8398334e7)
feeBump.tx.innerTx.tx.ext.v: 0
feeBump.tx.innerTx.signatures.len: 1
feeBump.tx.innerTx.signatures[0].hint: 7b21e7e3 (bad signature/unknown key/main is wrong network)
feeBump.tx.innerTx.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a
feeBump.tx.ext.v: 0
feeBump.signatures.len: 1
feeBump.signatures[0].hint: 7b21e7e3 (bad signature/unknown key/main is wrong network)
feeBump.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a';

        $txRepWithoutComments = 'type: ENVELOPE_TYPE_TX_FEE_BUMP
feeBump.tx.feeSource: GBD4KWT3HXUGS4ACUZZELY67UJXLOFTZAPR5DT5QIMBO6BX53FXFSLQS
feeBump.tx.fee: 1515
feeBump.tx.innerTx.type: ENVELOPE_TYPE_TX
feeBump.tx.innerTx.tx.sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.fee: 1400
feeBump.tx.innerTx.tx.seqNum: 1102902109202
feeBump.tx.innerTx.tx.cond.type: PRECOND_TIME
feeBump.tx.innerTx.tx.cond.timeBounds.minTime: 1595282368
feeBump.tx.innerTx.tx.cond.timeBounds.maxTime: 1595284000
feeBump.tx.innerTx.tx.memo.type: MEMO_TEXT
feeBump.tx.innerTx.tx.memo.text: "Enjoy this transaction"
feeBump.tx.innerTx.tx.operations.len: 14
feeBump.tx.innerTx.tx.operations[0].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[0].body.type: CREATE_ACCOUNT
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[0].body.createAccountOp.startingBalance: 9223372036854775807
feeBump.tx.innerTx.tx.operations[1].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[1].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[1].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.asset: XLM
feeBump.tx.innerTx.tx.operations[1].body.paymentOp.amount: 9223372036854775807
feeBump.tx.innerTx.tx.operations[2].sourceAccount._present: true
feeBump.tx.innerTx.tx.operations[2].sourceAccount: GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[2].body.type: PAYMENT
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.asset: USD:GAZFEVBSEGJJ63WPVVIWXLZLWN2JYZECECGT6GUNP4FJDVZVNXWQWMYI
feeBump.tx.innerTx.tx.operations[2].body.paymentOp.amount: 9223372036854775807
feeBump.tx.innerTx.tx.operations[3].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[3].body.type: PATH_PAYMENT_STRICT_RECEIVE
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.sendMax: 20000000
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.destAmount: 80000000
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path.len: 2
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[3].body.pathPaymentStrictReceiveOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[4].body.type: PATH_PAYMENT_STRICT_SEND
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAsset: IOM:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.sendAmount: 4000000000
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destAsset: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.destMin: 12000000000
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path.len: 2
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[0]: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[4].body.pathPaymentStrictSendOp.path[1]: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[5].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[5].body.type: SET_OPTIONS
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.inflationDest: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.clearFlags: 2
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.setFlags: 4
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.masterWeight: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.lowThreshold: 10
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.medThreshold: 50
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.highThreshold: 122
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.homeDomain: "https://www.soneso.com/blubber"
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer._present: true
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.key: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[5].body.setOptionsOp.signer.weight: 50
feeBump.tx.innerTx.tx.operations[6].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[6].body.type: MANAGE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.selling: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.buying: XLM
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.amount: 82820000000
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.n: 7
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.price.d: 10
feeBump.tx.innerTx.tx.operations[6].body.manageSellOfferOp.offerID: 9298298398333
feeBump.tx.innerTx.tx.operations[7].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[7].body.type: CREATE_PASSIVE_SELL_OFFER
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.selling: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.buying: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.amount: 28280000000
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[7].body.createPassiveSellOfferOp.price.d: 2
feeBump.tx.innerTx.tx.operations[8].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[8].body.type: CHANGE_TRUST
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.line: ASTRO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[8].body.changeTrustOp.limit: 100000000000
feeBump.tx.innerTx.tx.operations[9].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[9].body.type: ALLOW_TRUST
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.trustor: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.asset: MOON
feeBump.tx.innerTx.tx.operations[9].body.allowTrustOp.authorize: 1
feeBump.tx.innerTx.tx.operations[10].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[10].body.type: ACCOUNT_MERGE
feeBump.tx.innerTx.tx.operations[10].body.destination: GBNOLI2TBDKDO32TRJ37YR62KQXZOHPUHA5VMWRL534A7C4OJRNLWOJP
feeBump.tx.innerTx.tx.operations[11].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[11].body.type: MANAGE_DATA
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataName: "Sommer"
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue._present: true
feeBump.tx.innerTx.tx.operations[11].body.manageDataOp.dataValue: 446965204df662656c2073696e6420686569df21
feeBump.tx.innerTx.tx.operations[12].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[12].body.type: BUMP_SEQUENCE
feeBump.tx.innerTx.tx.operations[12].body.bumpSequenceOp.bumpTo: 1102902109211
feeBump.tx.innerTx.tx.operations[13].sourceAccount._present: false
feeBump.tx.innerTx.tx.operations[13].body.type: MANAGE_BUY_OFFER
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.selling: MOON:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buying: ECO:GDXLKEY5TR4IDEV7FZWYFG6MA6M24YDCX5HENQ7DTESBE233EHT6HHGK
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.buyAmount: 120000000
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.n: 1
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.price.d: 5
feeBump.tx.innerTx.tx.operations[13].body.manageBuyOfferOp.offerID: 9298298398334
feeBump.tx.innerTx.tx.ext.v: 0
feeBump.tx.innerTx.signatures.len: 1
feeBump.tx.innerTx.signatures[0].hint: 7b21e7e3
feeBump.tx.innerTx.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a
feeBump.tx.ext.v: 0
feeBump.signatures.len: 1
feeBump.signatures[0].hint: 7b21e7e3
feeBump.signatures[0].signature: 085a2ee61be0d5bc2c2c7c7e90cc4c921febfe25aa54b6e99c8aa2e9cdcbf7b8b24872e129e645501dbddb427d400fa92af69768fe62a80b041d0efefa5fc90a';
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        self::assertEquals($txRepRes, $txRepWithoutComments);
    }

    public function testXdr(): void {
        $xdr = 'AAAABQAAAQAAAAAAAAGtsOOrRmu/xF4rbMovk4QBTYc1ydWNJAf3WpoR7x2Lex9bAAAAAAAABesAAAACAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAFeAAAAQDKFqQSAAAAAQAAAABfFhPAAAAAAF8WGiAAAAABAAAAFkVuam95IHRoaXMgdHJhbnNhY3Rpb24AAAAAAA4AAAAAAAAAAAAAAAAemS6K/ajuBs2Ihxw4YmSBQ8M3j7Si3/jYv4JGf3JrD3//////////AAAAAQAAAADcPJlDOaau9h7W+f73IUHROTLM/h4SYdrW3bDmmCkOEgAAAAEAAAAAHpkuiv2o7gbNiIccOGJkgUPDN4+0ot/42L+CRn9yaw8AAAAAf/////////8AAAABAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAQAAAAAemS6K/ajuBs2Ihxw4YmSBQ8M3j7Si3/jYv4JGf3JrDwAAAAFVU0QAAAAAADJSVDIhkp9uz61Ra68rs3ScZIIgjT8ajX8Kkdc1be0Lf/////////8AAAAAAAAAAgAAAAFJT00AAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAAExLQAAAAAAHpkuiv2o7gbNiIccOGJkgUPDN4+0ot/42L+CRn9yaw8AAAABTU9PTgAAAADcPJlDOaau9h7W+f73IUHROTLM/h4SYdrW3bDmmCkOEgAAAAAExLQAAAAAAgAAAAFFQ08AAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAkFTVFJPAAAAAAAAAAAAAADcPJlDOaau9h7W+f73IUHROTLM/h4SYdrW3bDmmCkOEgAAAAAAAAANAAAAAUlPTQAAAAAA3DyZQzmmrvYe1vn+9yFB0TkyzP4eEmHa1t2w5pgpDhIAAAAA7msoAAAAAAAemS6K/ajuBs2Ihxw4YmSBQ8M3j7Si3/jYv4JGf3JrDwAAAAFNT09OAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAstBeAAAAAACAAAAAUVDTwAAAAAA3DyZQzmmrvYe1vn+9yFB0TkyzP4eEmHa1t2w5pgpDhIAAAACQVNUUk8AAAAAAAAAAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAAAAAAUAAAABAAAAAB6ZLor9qO4GzYiHHDhiZIFDwzePtKLf+Ni/gkZ/cmsPAAAAAQAAAAIAAAABAAAABAAAAAEAAAB6AAAAAQAAAAoAAAABAAAAMgAAAAEAAAB6AAAAAQAAAB5odHRwczovL3d3dy5zb25lc28uY29tL2JsdWJiZXIAAAAAAAEAAAAAHpkuiv2o7gbNiIccOGJkgUPDN4+0ot/42L+CRn9yaw8AAAAyAAAAAAAAAAMAAAABRUNPAAAAAADcPJlDOaau9h7W+f73IUHROTLM/h4SYdrW3bDmmCkOEgAAAAAAAAATSHTpAAAAAAcAAAAKAAAIdO3F5n0AAAAAAAAABAAAAAJBU1RSTwAAAAAAAAAAAAAA3DyZQzmmrvYe1vn+9yFB0TkyzP4eEmHa1t2w5pgpDhIAAAABTU9PTgAAAADcPJlDOaau9h7W+f73IUHROTLM/h4SYdrW3bDmmCkOEgAAAAaVno4AAAAAAQAAAAIAAAAAAAAABgAAAAJBU1RSTwAAAAAAAAAAAAAA3DyZQzmmrvYe1vn+9yFB0TkyzP4eEmHa1t2w5pgpDhIAAAAXSHboAAAAAAAAAAAHAAAAAB6ZLor9qO4GzYiHHDhiZIFDwzePtKLf+Ni/gkZ/cmsPAAAAAU1PT04AAAABAAAAAAAAAAgAAAEAAAAAADwzjFYemS6K/ajuBs2Ihxw4YmSBQ8M3j7Si3/jYv4JGf3JrDwAAAAAAAAAKAAAABlNvbW1lcgAAAAAAAQAAABREaWUgTfZiZWwgc2luZCBoZWnfIQAAAAAAAAALAAABAMoWpBsAAAAAAAAADAAAAAFNT09OAAAAANw8mUM5pq72Htb5/vchQdE5Msz+HhJh2tbdsOaYKQ4SAAAAAUVDTwAAAAAA3DyZQzmmrvYe1vn+9yFB0TkyzP4eEmHa1t2w5pgpDhIAAAAABycOAAAAAAEAAAAFAAAIdO3F5n4AAAAAAAAAAZgpDhIAAABAQSbEej2c50P7CzaYVjrNdwYXT2Fp7f8FR/z/zfr1nvKN/yat6MejrxGDwggvd6+S4TGsLCFB4c+pwmjTPGrkDwAAAAAAAAABi3sfWwAAAEDU+XI0BUiTphcT6iMFPGdQDhBHW1sordweRvMTv5DS13ZO/9DnMY604278Qj5H07Iwu2aoZNryy+gca9sblhEP';
        $txrep = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($xdr, $xdr2);
    }

    public function testCreateClaimableBalance(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 100
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: true
tx.operations[0].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.type: CREATE_CLAIMABLE_BALANCE
tx.operations[0].body.createClaimableBalanceOp.asset: XLM
tx.operations[0].body.createClaimableBalanceOp.amount: 2900000000
tx.operations[0].body.createClaimableBalanceOp.claimants.len: 6
tx.operations[0].body.createClaimableBalanceOp.claimants[0].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[0].v0.destination: GAF2EOTBIWV45XDG5O2QSIVXQ5KPI6EJIALVGI7VFOX7ENDNI6ONBYQO
tx.operations[0].body.createClaimableBalanceOp.claimants[0].v0.predicate.type: CLAIM_PREDICATE_UNCONDITIONAL
tx.operations[0].body.createClaimableBalanceOp.claimants[1].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[1].v0.destination: GCUEJ6YLQFWETNAXLIM3B3VN7CJISN6XLGXGDHQDVLWTYZODGSHRJWPS
tx.operations[0].body.createClaimableBalanceOp.claimants[1].v0.predicate.type: CLAIM_PREDICATE_BEFORE_RELATIVE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[1].v0.predicate.relBefore: 400
tx.operations[0].body.createClaimableBalanceOp.claimants[2].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[2].v0.destination: GCWV5WETMS3RD2ZZUF7S3NQPEVMCXBCODMV7MIOUY4D3KR66W7ACL4LE
tx.operations[0].body.createClaimableBalanceOp.claimants[2].v0.predicate.type: CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[2].v0.predicate.absBefore: 1683723100
tx.operations[0].body.createClaimableBalanceOp.claimants[3].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.destination: GBOAHYPSVULLKLH4OMESGA5BGZTK37EYEPZVI2AHES6LANTCIUPFHUPE
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.type: CLAIM_PREDICATE_AND
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates.len: 2
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[0].type: CLAIM_PREDICATE_NOT
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[0].notPredicate._present: true
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[0].notPredicate.type: CLAIM_PREDICATE_BEFORE_RELATIVE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[0].notPredicate.relBefore: 600
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[1].type: CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[3].v0.predicate.andPredicates[1].absBefore: 1683723100
tx.operations[0].body.createClaimableBalanceOp.claimants[4].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.destination: GDOA4UYIQ3A74WTHQ4BA56Z7F7NU7F34WP2KOGYHV4UXP2T5RXVEYLLF
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.type: CLAIM_PREDICATE_OR
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.orPredicates.len: 2
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.orPredicates[0].type: CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.orPredicates[0].absBefore: 1646723251
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.orPredicates[1].type: CLAIM_PREDICATE_BEFORE_ABSOLUTE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[4].v0.predicate.orPredicates[1].absBefore: 1645723269
tx.operations[0].body.createClaimableBalanceOp.claimants[5].type: CLAIMANT_TYPE_V0
tx.operations[0].body.createClaimableBalanceOp.claimants[5].v0.destination: GBCZ2KRFMG7IGUSBTHXTJP3ULN2TK4F3EAYSVMS5X4MLOO3DT2LSISOR
tx.operations[0].body.createClaimableBalanceOp.claimants[5].v0.predicate.type: CLAIM_PREDICATE_NOT
tx.operations[0].body.createClaimableBalanceOp.claimants[5].v0.predicate.notPredicate._present: true
tx.operations[0].body.createClaimableBalanceOp.claimants[5].v0.predicate.notPredicate.type: CLAIM_PREDICATE_BEFORE_RELATIVE_TIME
tx.operations[0].body.createClaimableBalanceOp.claimants[5].v0.predicate.notPredicate.relBefore: 8000
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 98f329b240374d898cfcb0171b37f495c488db1abd0e290c0678296e6db09d773e6e73f14a51a017808584d1c4dae13189e4539f4af8b81b6cc830fc43e9d500';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAADgAAAAAAAAAArNp9AAAAAAYAAAAAAAAAAAuiOmFFq87cZuu1CSK3h1T0eIlAF1Mj9Suv8jRtR5zQAAAAAAAAAAAAAAAAqET7C4FsSbQXWhmw7q34kok311muYZ4Dqu08ZcM0jxQAAAAFAAAAAAAAAZAAAAAAAAAAAK1e2JNktxHrOaF/LbYPJVgrhE4bK/Yh1McHtUfet8AlAAAABAAAAABkW5NcAAAAAAAAAABcA+HyrRa1LPxzCSMDoTZmrfyYI/NUaAckvLA2YkUeUwAAAAEAAAACAAAAAwAAAAEAAAAFAAAAAAAAAlgAAAAEAAAAAGRbk1wAAAAAAAAAANwOUwiGwf5aZ4cCDvs/L9tPl3yz9KcbB68pd+p9jepMAAAAAgAAAAIAAAAEAAAAAGInALMAAAAEAAAAAGIXvoUAAAAAAAAAAEWdKiVhvoNSQZnvNL90W3U1cLsgMSqyXb8YtztjnpckAAAAAwAAAAEAAAAFAAAAAAAAH0AAAAAAAAAAAezRl+8AAABAmPMpskA3TYmM/LAXGzf0lcSI2xq9DikMBngpbm2wnXc+bnPxSlGgF4CFhNHE2uExieRTn0r4uBtsyDD8Q+nVAA==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        print($expected);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testClaimClaimableBalance(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 100
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: CLAIM_CLAIMABLE_BALANCE
tx.operations[0].body.claimClaimableBalanceOp.balanceID.type: CLAIMABLE_BALANCE_ID_TYPE_V0
tx.operations[0].body.claimClaimableBalanceOp.balanceID.v0: ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 9475bef299458bb105f63ac58df4201064d60f7cfd8ffec8ac8fd34198b94e279a257f9b7bae7f2e3a759268612b565043dacb689f7df7c99cd55d9d51bb0b06';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAADwAAAADOqxTuu9v+JaGDDjnjEcIYCEbfdJR7oko4a4MUzLpmIgAAAAAAAAAB7NGX7wAAAECUdb7ymUWLsQX2OsWN9CAQZNYPfP2P/sisj9NBmLlOJ5olf5t7rn8uOnWSaGErVlBD2ston333yZzVXZ1RuwsG";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }
    public function testSponsoring(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 200
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 2
tx.operations[0].sourceAccount._present: true
tx.operations[0].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.type: BEGIN_SPONSORING_FUTURE_RESERVES
tx.operations[0].body.beginSponsoringFutureReservesOp.sponsoredID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[1].sourceAccount._present: true
tx.operations[1].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[1].body.type: END_SPONSORING_FUTURE_RESERVES
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 194a962d2f51ae1af1c4bfa3e8eeca7aa2b6654a84ac03de37d1738171e43f8ece2101fe6bd44cacd9f0bf10c93616cdfcf04639727a08ca84339fade990d40e';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAMgAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAEAAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAEAAAAARJW9RyahO88Zbk3lbcGLzpOAM9MN5KwMZ0rgROzRl+8AAAARAAAAAAAAAAHs0ZfvAAAAQBlKli0vUa4a8cS/o+juynqitmVKhKwD3jfRc4Fx5D+OziEB/mvUTKzZ8L8QyTYWzfzwRjlyegjKhDOfremQ1A4=";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testRevokeSponsorship(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 800
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 8
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: REVOKE_SPONSORSHIP
tx.operations[0].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY
tx.operations[0].body.revokeSponsorshipOp.ledgerKey.type: ACCOUNT
tx.operations[0].body.revokeSponsorshipOp.ledgerKey.account.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[1].sourceAccount._present: false
tx.operations[1].body.type: REVOKE_SPONSORSHIP
tx.operations[1].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY
tx.operations[1].body.revokeSponsorshipOp.ledgerKey.type: TRUSTLINE
tx.operations[1].body.revokeSponsorshipOp.ledgerKey.trustLine.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[1].body.revokeSponsorshipOp.ledgerKey.trustLine.asset: ACC:GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[2].sourceAccount._present: false
tx.operations[2].body.type: REVOKE_SPONSORSHIP
tx.operations[2].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY
tx.operations[2].body.revokeSponsorshipOp.ledgerKey.type: OFFER
tx.operations[2].body.revokeSponsorshipOp.ledgerKey.offer.sellerID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[2].body.revokeSponsorshipOp.ledgerKey.offer.offerID: 293893
tx.operations[3].sourceAccount._present: false
tx.operations[3].body.type: REVOKE_SPONSORSHIP
tx.operations[3].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY
tx.operations[3].body.revokeSponsorshipOp.ledgerKey.type: DATA
tx.operations[3].body.revokeSponsorshipOp.ledgerKey.data.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[3].body.revokeSponsorshipOp.ledgerKey.data.dataName: "Soneso"
tx.operations[4].sourceAccount._present: false
tx.operations[4].body.type: REVOKE_SPONSORSHIP
tx.operations[4].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_LEDGER_ENTRY
tx.operations[4].body.revokeSponsorshipOp.ledgerKey.type: CLAIMABLE_BALANCE
tx.operations[4].body.revokeSponsorshipOp.ledgerKey.claimableBalance.balanceID.type: CLAIMABLE_BALANCE_ID_TYPE_V0
tx.operations[4].body.revokeSponsorshipOp.ledgerKey.claimableBalance.balanceID.v0: ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622
tx.operations[5].sourceAccount._present: true
tx.operations[5].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[5].body.type: REVOKE_SPONSORSHIP
tx.operations[5].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_SIGNER
tx.operations[5].body.revokeSponsorshipOp.signer.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[5].body.revokeSponsorshipOp.signer.signerKey: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[6].sourceAccount._present: false
tx.operations[6].body.type: REVOKE_SPONSORSHIP
tx.operations[6].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_SIGNER
tx.operations[6].body.revokeSponsorshipOp.signer.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[6].body.revokeSponsorshipOp.signer.signerKey: XD3J3C5TAC4FCWIKWL45L3Z6LE3KK4OZ3DN3AC3CAE4HHYIGVW4TUVTH
tx.operations[7].sourceAccount._present: false
tx.operations[7].body.type: REVOKE_SPONSORSHIP
tx.operations[7].body.revokeSponsorshipOp.type: REVOKE_SPONSORSHIP_SIGNER
tx.operations[7].body.revokeSponsorshipOp.signer.accountID: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[7].body.revokeSponsorshipOp.signer.signerKey: TD3J3C5TAC4FCWIKWL45L3Z6LE3KK4OZ3DN3AC3CAE4HHYIGVW4TVRW6
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 73c223f85c34f1399e9af3322a638a8877987724567e452179a9f2b159a96a1dd4e63cfb8c54e7803aa2f3787492f255698ea536070fc3e3ad9f87e36a0e660c';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAyAAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAEgAAAAAAAAAAAAAAANsckvAQBXW3k2y4RII0grJp/OOnH95cepXI17IxxLNzAAAAAAAAABIAAAAAAAAAAQAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAFBQ0MAAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAAAAAABIAAAAAAAAAAgAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAAABHwFAAAAAAAAABIAAAAAAAAAAwAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAZTb25lc28AAAAAAAAAAAASAAAAAAAAAAQAAAAAzqsU7rvb/iWhgw454xHCGAhG33SUe6JKOGuDFMy6ZiIAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAEgAAAAEAAAAA2xyS8BAFdbeTbLhEgjSCsmn846cf3lx6lcjXsjHEs3MAAAAARJW9RyahO88Zbk3lbcGLzpOAM9MN5KwMZ0rgROzRl+8AAAAAAAAAEgAAAAEAAAAA2xyS8BAFdbeTbLhEgjSCsmn846cf3lx6lcjXsjHEs3MAAAAC9p2LswC4UVkKsvnV7z5ZNqVx2djbsAtiAThz4QatuToAAAAAAAAAEgAAAAEAAAAA2xyS8BAFdbeTbLhEgjSCsmn846cf3lx6lcjXsjHEs3MAAAAB9p2LswC4UVkKsvnV7z5ZNqVx2djbsAtiAThz4QatuToAAAAAAAAAAezRl+8AAABAc8Ij+Fw08TmemvMyKmOKiHeYdyRWfkUheanysVmpah3U5jz7jFTngDqi83h0kvJVaY6lNgcPw+Otn4fjag5mDA==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }
    public function testClwaback(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 100
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: true
tx.operations[0].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.type: CLAWBACK
tx.operations[0].body.clawbackOp.asset: ACC:GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.clawbackOp.from: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[0].body.clawbackOp.amount: 2330000000
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 336998785b7815aac464789d04735d06d0421c5f92d1307a9d164e270fa1a214d30d3f00260146a80a3bb0318c92058c05f6de07589b1172c4b6ab630c628c04';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAEwAAAAFBQ0MAAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAANsckvAQBXW3k2y4RII0grJp/OOnH95cepXI17IxxLNzAAAAAIrg+oAAAAAAAAAAAezRl+8AAABAM2mYeFt4FarEZHidBHNdBtBCHF+S0TB6nRZOJw+hohTTDT8AJgFGqAo7sDGMkgWMBfbeB1ibEXLEtqtjDGKMBA==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testClwabackClaimableBalance(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 100
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: CLAWBACK_CLAIMABLE_BALANCE
tx.operations[0].body.clawbackClaimableBalanceOp.balanceID.type: CLAIMABLE_BALANCE_ID_TYPE_V0
tx.operations[0].body.clawbackClaimableBalanceOp.balanceID.v0: f69d8bb300b851590ab2f9d5ef3e5936a571d9d8dbb00b62013873e106adb93a
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 6db5b9ff8e89c2103971550a485754286d1f782aa7fac17e2553bbaec9ab3969794d0fd5ba6d0b4575b9c75c1c464337fee1b4e5592eb77877b7a72487acb909';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAGQAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAFAAAAAD2nYuzALhRWQqy+dXvPlk2pXHZ2NuwC2IBOHPhBq25OgAAAAAAAAAB7NGX7wAAAEBttbn/jonCEDlxVQpIV1QobR94Kqf6wX4lU7uuyas5aXlND9W6bQtFdbnHXBxGQzf+4bTlWS63eHe3pySHrLkJ";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testSetTrustlineFlags(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 200
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 2
tx.operations[0].sourceAccount._present: true
tx.operations[0].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.type: SET_TRUST_LINE_FLAGS
tx.operations[0].body.setTrustLineFlagsOp.trustor: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[0].body.setTrustLineFlagsOp.asset: ACC:GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.setTrustLineFlagsOp.clearFlags: 6
tx.operations[0].body.setTrustLineFlagsOp.setFlags: 1
tx.operations[1].sourceAccount._present: false
tx.operations[1].body.type: SET_TRUST_LINE_FLAGS
tx.operations[1].body.setTrustLineFlagsOp.trustor: GDNRZEXQCACXLN4TNS4EJARUQKZGT7HDU4P54XD2SXENPMRRYSZXGYUX
tx.operations[1].body.setTrustLineFlagsOp.asset: BCC:GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[1].body.setTrustLineFlagsOp.clearFlags: 5
tx.operations[1].body.setTrustLineFlagsOp.setFlags: 2
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: 5d4569d07068fd4824c87bf531061cf962a820d9ac5d4fdda0a2728f035d154e5cc842aa8aa398bf8ba2f42577930af129c593832ab14ff02c25989eaf8fbf0b';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAMgAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAFQAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAFBQ0MAAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAABgAAAAEAAAAAAAAAFQAAAADbHJLwEAV1t5NsuESCNIKyafzjpx/eXHqVyNeyMcSzcwAAAAFCQ0MAAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAABQAAAAIAAAAAAAAAAezRl+8AAABAXUVp0HBo/UgkyHv1MQYc+WKoINmsXU/doKJyjwNdFU5cyEKqiqOYv4ui9CV3kwrxKcWTgyqxT/AsJZier4+/Cw==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }
    public function testLiquidityPool(): void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.fee: 200
tx.seqNum: 2916609211498497
tx.cond.type: PRECOND_TIME
tx.cond.timeBounds.minTime: 0
tx.cond.timeBounds.maxTime: 0
tx.memo.type: MEMO_NONE
tx.operations.len: 2
tx.operations[0].sourceAccount._present: true
tx.operations[0].sourceAccount: GBCJLPKHE2QTXTYZNZG6K3OBRPHJHABT2MG6JLAMM5FOARHM2GL67VCW
tx.operations[0].body.type: LIQUIDITY_POOL_DEPOSIT
tx.operations[0].body.liquidityPoolDepositOp.liquidityPoolID: f69d8bb300b851590ab2f9d5ef3e5936a571d9d8dbb00b62013873e106adb93a
tx.operations[0].body.liquidityPoolDepositOp.maxAmountA: 1000000000
tx.operations[0].body.liquidityPoolDepositOp.maxAmountB: 2000000000
tx.operations[0].body.liquidityPoolDepositOp.minPrice.n: 20
tx.operations[0].body.liquidityPoolDepositOp.minPrice.d: 1
tx.operations[0].body.liquidityPoolDepositOp.maxPrice.n: 30
tx.operations[0].body.liquidityPoolDepositOp.maxPrice.d: 1
tx.operations[1].sourceAccount._present: false
tx.operations[1].body.type: LIQUIDITY_POOL_WITHDRAW
tx.operations[1].body.liquidityPoolWithdrawOp.liquidityPoolID: ceab14eebbdbfe25a1830e39e311c2180846df74947ba24a386b8314ccba6622
tx.operations[1].body.liquidityPoolWithdrawOp.amount: 9000000000
tx.operations[1].body.liquidityPoolWithdrawOp.minAmountA: 2000000000
tx.operations[1].body.liquidityPoolWithdrawOp.minAmountB: 4000000000
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: ecd197ef
signatures[0].signature: ed97d0d018a671c5a914a15346c1b38912d6695d1d152ffe976b8c9689ce2e7770b0e6cc8889c4a2423323898b087e5fbf43306ef7e63a75366befd3e2a9bd03';

        $expected = "AAAAAgAAAABElb1HJqE7zxluTeVtwYvOk4Az0w3krAxnSuBE7NGX7wAAAMgAClykAAAAAQAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAABAAAAAESVvUcmoTvPGW5N5W3Bi86TgDPTDeSsDGdK4ETs0ZfvAAAAFvadi7MAuFFZCrL51e8+WTalcdnY27ALYgE4c+EGrbk6AAAAADuaygAAAAAAdzWUAAAAABQAAAABAAAAHgAAAAEAAAAAAAAAF86rFO672/4loYMOOeMRwhgIRt90lHuiSjhrgxTMumYiAAAAAhhxGgAAAAAAdzWUAAAAAADuaygAAAAAAAAAAAHs0ZfvAAAAQO2X0NAYpnHFqRShU0bBs4kS1mldHRUv/pdrjJaJzi53cLDmzIiJxKJCMyOJiwh+X79DMG735jp1Nmvv0+KpvQM=";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testPreconditions1(): void {

        $xdr = "AAAAAgAAAQAAAAAAABODoXOW2Y6q7AdenusH1X8NBxVPFXEW+/PQFDiBQV05qf4DAAAAZAAKAJMAAAACAAAAAgAAAAEAAAAAYnk1lQAAAABobxaVAAAAAQANnJQAHN7UAAAAAQAKAJMAAAABAAAAAAAAAAEAAAABAAAAAgAAAACUkeBPpCcGYCoqeszK1YjZ1Ww1qY6fRI02d2hKG1nqvwAAAAHW9EEhELfDtkfmtBrXuEgEpTBlO8E/iQ2ZI/uNXLDV9AAAAAEAAAAEdGVzdAAAAAEAAAABAAABAAAAAAAAE4Ohc5bZjqrsB16e6wfVfw0HFU8VcRb789AUOIFBXTmp/gMAAAABAAABAAAAAAJPOttvlJHgT6QnBmAqKnrMytWI2dVsNamOn0SNNndoShtZ6r8AAAAAAAAAAADk4cAAAAAAAAAAATmp/gMAAABAvm+8CxO9sj4KEDwSS6hDxZAiUGdpIN2l+KOxTIkdI2joBFjT9B1U9YaORVDx4LTrLd4QM2taUuzXB51QtDQYDA==";
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRepRes);
        self::assertEquals($xdr2, $xdr);
    }

    public function testPreconditions2(): void {

        $xdr = "AAAAAgAAAQAAAAAAABODoa9e0m5apwHpUf3/HzJOJeQ5q7+CwSWrnHXENS8XoAfmAAAAZAAJ/s4AAAACAAAAAgAAAAEAAAAAYnk1lQAAAABobxaVAAAAAQANnJQAHN7UAAAAAQAJ/s4AAAABAAAAAAAAAAEAAAABAAAAAgAAAAJulGoyRpAB8JhKT+ffEiXh8Kgd8qrEXfiG3aK69JgQlAAAAAM/DDS/k60NmXHQTMyQ9wVRHIOKrZc0pKL7DXoD/H/omgAAACABAgMEBQYHCAkKCwwNDg8QERITFBUWFxgZGhscHR4fIAAAAAEAAAAEdGVzdAAAAAEAAAABAAABAAAAAAAAE4Ohr17SblqnAelR/f8fMk4l5Dmrv4LBJaucdcQ1LxegB+YAAAABAAABAAAAAAJPOttvipEw04NyfzwAhgQlf2S77YVGYbytcXKVNuM46+sMNAYAAAAAAAAAAADk4cAAAAAAAAAAARegB+YAAABAJG8wTpECV0rpq3TV9d26UL0MULmDxXKXGmKSJLiy9NCNJW3WMcrvrA6wiBsLHuCN7sIurD3o1/AKgntagup3Cw==";
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        $xdr2 = TxRep::transactionEnvelopeXdrBase64FromTxRep($txRepRes);
        self::assertEquals($xdr2, $xdr);
    }

    public function testPreconditions3() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBGZGXYWXZ65XBD4Q4UTOMIDXRZ5X5OJGNC54IQBLSPI2DDB5VGFZO2V
tx.fee: 6000
tx.seqNum: 5628434382323746
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: PAYMENT
tx.operations[0].body.paymentOp.destination: GD53ZDEHFQPY25NBF6NPDYEA5IWXSS5FYMLQ3AE6AIGAO75XQK7SIVNU
tx.operations[0].body.paymentOp.asset: XLM
tx.operations[0].body.paymentOp.amount: 100000000
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 61ed4c5c
signatures[0].signature: bd33b8de6ca4354d653329e4cfd2f012a3c155c816bca8275721bd801defb868642e2cd49330e904d2df270b4a2c95359536ba81eed9775c5982e411ac9c3909';
        $expected = 'AAAAAgAAAABNk18Wvn3bhHyHKTcxA7xz2/XJM0XeIgFcno0MYe1MXAAAF3AAE/8IAAAAIgAAAAAAAAAAAAAAAQAAAAAAAAABAAAAAPu8jIcsH411oS+a8eCA6i15S6XDFw2AngIMB3+3gr8kAAAAAAAAAAAF9eEAAAAAAAAAAAFh7UxcAAAAQL0zuN5spDVNZTMp5M/S8BKjwVXIFryoJ1chvYAd77hoZC4s1JMw6QTS3ycLSiyVNZU2uoHu2XdcWYLkEaycOQk=';
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testSorobanInstallContractCode() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBRMOPMN6AT6MOW2TJGLHK2SA73K3JKSGRKZ3U5ET6VWNNSPF5ZYANRI
tx.fee: 100
tx.seqNum: 2468657007427585
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.function.installContractCodeArgs.code: 0061736d0100000001150460017e017e60027e7e017e60027f7e017e6000000219040178013900000176015f00000176013400010176013600010304030200030503010001060b027f0141000b7f0141000b071d030568656c6c6f0005066d656d6f727902000873646b737461727400060c01060a9004033900200041ff0171410849200142808080808080808010547145044041064208100410001a0b200041017441ff0171ad2001420486844201840bc10302067f027e410242001004100121082300220441046a2201411c6a22053f002203411074410f6a41707122064b04402003200520066b41ffff036a4180807c714110762206200320064a1b40004100480440200640004100480440000b0b0b200524002004411c360200200141046b22034100360204200341003602082003410336020c200341083602102001420037031020012008370310419c09280200410176410a4b044041064208100410001a0b03402002419c092802004101764804402002419c092802004101764f047f417f05200241017441a0096a2f01000b220341fa004c200341304e7104402007420686210842002107200341ff017141df004604404201210705200341ff0171220441394d200441304f710440200341ff0171ad422e7d210705200341ff0171220441da004d200441c1004f710440200341ff0171ad42357d210705200341ff0171220441fa004d200441e1004f710440200341ff0171ad423b7d21070541064208100410001a0b0b0b0b200720088421070541064208100410001a0b200241016a21020c010b0b41042007100421072001200129031020071002370310200120012903102000100337031020012903100b1100230104400f0b4101240141ac0924000b0b8d010600418c080b013c004198080b2f010000002800000041006c006c006f0063006100740069006f006e00200074006f006f0020006c00610072006700650041cc080b013c0041d8080b25010000001e0000007e006c00690062002f00720074002f0073007400750062002e0074007300418c090b011c004198090b11010000000a000000480065006c006c006f001e11636f6e7472616374656e766d657461763000000000000000000000001b00370e636f6e7472616374737065637630000000000000000568656c6c6f0000000000000100000002746f0000000000080000000100000000
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractCode.hash: 2a146935481b243ba90218ee28a43d0c8538debbbd733213df90394c6a6d67b4
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 4f2f7380
signatures[0].signature: c2887fa3cafbc29597e1341a0a528584728f6e5eacb6da0aba6733e26b0f7a65e358ac8a614d25c0f70af790334dc8253f82bc19085848ccee935935bdcfef09';
        $expected = "AAAAAgAAAABixz2N8CfmOtqaTLOrUgf2raVSNFWd06Sfq2a2Ty9zgAAAAGQACMU7AAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAgAAA3AAYXNtAQAAAAEVBGABfgF+YAJ+fgF+YAJ/fgF+YAAAAhkEAXgBOQAAAXYBXwAAAXYBNAABAXYBNgABAwQDAgADBQMBAAEGCwJ/AUEAC38BQQALBx0DBWhlbGxvAAUGbWVtb3J5AgAIc2Rrc3RhcnQABgwBBgqQBAM5ACAAQf8BcUEISSABQoCAgICAgICAEFRxRQRAQQZCCBAEEAAaCyAAQQF0Qf8Bca0gAUIEhoRCAYQLwQMCBn8CfkECQgAQBBABIQgjACIEQQRqIgFBHGoiBT8AIgNBEHRBD2pBcHEiBksEQCADIAUgBmtB//8DakGAgHxxQRB2IgYgAyAGShtAAEEASARAIAZAAEEASARAAAsLCyAFJAAgBEEcNgIAIAFBBGsiA0EANgIEIANBADYCCCADQQM2AgwgA0EINgIQIAFCADcDECABIAg3AxBBnAkoAgBBAXZBCksEQEEGQggQBBAAGgsDQCACQZwJKAIAQQF2SARAIAJBnAkoAgBBAXZPBH9BfwUgAkEBdEGgCWovAQALIgNB+gBMIANBME5xBEAgB0IGhiEIQgAhByADQf8BcUHfAEYEQEIBIQcFIANB/wFxIgRBOU0gBEEwT3EEQCADQf8Bca1CLn0hBwUgA0H/AXEiBEHaAE0gBEHBAE9xBEAgA0H/AXGtQjV9IQcFIANB/wFxIgRB+gBNIARB4QBPcQRAIANB/wFxrUI7fSEHBUEGQggQBBAAGgsLCwsgByAIhCEHBUEGQggQBBAAGgsgAkEBaiECDAELC0EEIAcQBCEHIAEgASkDECAHEAI3AxAgASABKQMQIAAQAzcDECABKQMQCxEAIwEEQA8LQQEkAUGsCSQACwuNAQYAQYwICwE8AEGYCAsvAQAAACgAAABBAGwAbABvAGMAYQB0AGkAbwBuACAAdABvAG8AIABsAGEAcgBnAGUAQcwICwE8AEHYCAslAQAAAB4AAAB+AGwAaQBiAC8AcgB0AC8AcwB0AHUAYgAuAHQAcwBBjAkLARwAQZgJCxEBAAAACgAAAEgAZQBsAGwAbwAeEWNvbnRyYWN0ZW52bWV0YXYwAAAAAAAAAAAAAAAbADcOY29udHJhY3RzcGVjdjAAAAAAAAAABWhlbGxvAAAAAAAAAQAAAAJ0bwAAAAAACAAAAAEAAAAAAAAAAQAAAAcqFGk1SBskO6kCGO4opD0MhTjeu71zMhPfkDlMam1ntAAAAAAAAAAAAAAAAU8vc4AAAABAwoh/o8r7wpWX4TQaClKFhHKPbl6sttoKumcz4msPemXjWKyKYU0lwPcK95AzTcglP4K8GQhYSMzuk1k1vc/vCQ==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }


    public function testSorobanCreateContract() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBRMOPMN6AT6MOW2TJGLHK2SA73K3JKSGRKZ3U5ET6VWNNSPF5ZYANRI
tx.fee: 100
tx.seqNum: 2468657007427586
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.source.type: SCCONTRACT_CODE_WASM_REF
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.source.wasm_id: 2a146935481b243ba90218ee28a43d0c8538debbbd733213df90394c6a6d67b4
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.type: CONTRACT_ID_FROM_SOURCE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.salt: 0609eaad2a2ec802062335628211174fb15d2f385fbff0486fa1ca4f57d69bf1
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractCode.hash: 2a146935481b243ba90218ee28a43d0c8538debbbd733213df90394c6a6d67b4
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 9df0aa24237452f055093f35d3b688a67b5e2bc89223e0ad18dad67fa3cbe149
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_STATIC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.ic: SCS_LEDGER_KEY_CONTRACT_CODE
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 4f2f7380
signatures[0].signature: 2129e1caace09d1b11f7552d4c48132a2a96f3641e1e0c1ed45d067b549b7e9613867a527942eb59ae763dd3684494ee8141362ae189d51c54037e8eea33290a';
        $expected = "AAAAAgAAAABixz2N8CfmOtqaTLOrUgf2raVSNFWd06Sfq2a2Ty9zgAAAAGQACMU7AAAAAgAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAAGCeqtKi7IAgYjNWKCERdPsV0vOF+/8EhvocpPV9ab8QAAAAAqFGk1SBskO6kCGO4opD0MhTjeu71zMhPfkDlMam1ntAAAAAEAAAAHKhRpNUgbJDupAhjuKKQ9DIU43ru9czIT35A5TGptZ7QAAAABAAAABp3wqiQjdFLwVQk/NdO2iKZ7XivIkiPgrRja1n+jy+FJAAAAAwAAAAMAAAAAAAAAAU8vc4AAAABAISnhyqzgnRsR91UtTEgTKiqW82QeHgwe1F0Ge1SbfpYThnpSeULrWa52PdNoRJTugUE2KuGJ1RxUA36O6jMpCg==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testSorobanInvokeContract() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBRMOPMN6AT6MOW2TJGLHK2SA73K3JKSGRKZ3U5ET6VWNNSPF5ZYANRI
tx.fee: 100
tx.seqNum: 2468657007427587
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INVOKE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs.len: 3
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[0].type: SCV_OBJECT
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[0].obj.type: SCO_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[0].obj.bin: 9df0aa24237452f055093f35d3b688a67b5e2bc89223e0ad18dad67fa3cbe149
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[1].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[1].sym: hello
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[2].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeArgs[2].sym: friend
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 2
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.contractID: 9df0aa24237452f055093f35d3b688a67b5e2bc89223e0ad18dad67fa3cbe149
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.key.type: SCV_STATIC
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.key.ic: SCS_LEDGER_KEY_CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].contractCode.hash: 2a146935481b243ba90218ee28a43d0c8538debbbd733213df90394c6a6d67b4
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 4f2f7380
signatures[0].signature: 7c974a3fd94725ef96f244836701abcecb3d84971a12d1428b5ee2c127ccdb61012c8be5bb82216ebcde47f6452828506b1fe1c4327b8e356f253213f3505907';
        $expected = "AAAAAgAAAABixz2N8CfmOtqaTLOrUgf2raVSNFWd06Sfq2a2Ty9zgAAAAGQACMU7AAAAAwAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAAAAAAMAAAAEAAAAAQAAAAYAAAAgnfCqJCN0UvBVCT8107aIpnteK8iSI+CtGNrWf6PL4UkAAAAFAAAABWhlbGxvAAAAAAAABQAAAAZmcmllbmQAAAAAAAIAAAAGnfCqJCN0UvBVCT8107aIpnteK8iSI+CtGNrWf6PL4UkAAAADAAAAAwAAAAcqFGk1SBskO6kCGO4opD0MhTjeu71zMhPfkDlMam1ntAAAAAAAAAAAAAAAAU8vc4AAAABAfJdKP9lHJe+W8kSDZwGrzss9hJcaEtFCi17iwSfM22EBLIvlu4IhbrzeR/ZFKChQax/hxDJ7jjVvJTIT81BZBw==";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testSorobanDeploySACSrcAcc() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GC6UQJT35QNGSLOKFOGILLZ27QXVC23XCYYI5NZDUHWYNLGUEJZ3FTMZ
tx.fee: 100
tx.seqNum: 2468794446381060
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.source.type: SCCONTRACT_CODE_TOKEN
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.type: CONTRACT_ID_FROM_SOURCE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.salt: 3efab88e0e7f359cc50298a516621b531e747a8c6028c9b057d9323c01fb4e4a
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 0
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 81c1644678aa7b404aae784b6a079ab745443c6e504113a311789b7ec54b8722
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_STATIC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.ic: SCS_LEDGER_KEY_CONTRACT_CODE
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: d42273b2
signatures[0].signature: 6ae8d4a185460b295f5aff561da88c5d9e3c2d0147ab777ef5f9309eeeef9a28e195709e82d44af1234fb7f3721b5bb3656fa5b195fa18b959b989047ef6f604';
        $expected = "AAAAAgAAAAC9SCZ77BppLcorjIWvOvwvUWt3FjCOtyOh7Yas1CJzsgAAAGQACMVbAAAABAAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAA++riODn81nMUCmKUWYhtTHnR6jGAoybBX2TI8AftOSgAAAAEAAAAAAAAAAQAAAAaBwWRGeKp7QEqueEtqB5q3RUQ8blBBE6MReJt+xUuHIgAAAAMAAAADAAAAAAAAAAHUInOyAAAAQGro1KGFRgspX1r/Vh2ojF2ePC0BR6t3fvX5MJ7u75oo4ZVwnoLUSvEjT7fzchtbs2VvpbGV+hi5WbmJBH729gQ=";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }

    public function testSorobanDeploySACAsset() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GAHAUKVWCNB6VVFBTM4DJX6GVJV3XM7OUGIY4X7PP4UJFL3TGB7SOGSU
tx.fee: 100
tx.seqNum: 2468815921217538
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.source.type: SCCONTRACT_CODE_TOKEN
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.type: CONTRACT_ID_FROM_ASSET
tx.operations[0].body.invokeHostFunctionOp.function.createContractArgs.contractID.asset: IOM:GC6UQJT35QNGSLOKFOGILLZ27QXVC23XCYYI5NZDUHWYNLGUEJZ3FTMZ
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 0
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 3
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 5933983188546e84c5acc1732875a4c7c4b9c21bbe8467e519ca6aac6ea86f61
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_STATIC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.ic: SCS_LEDGER_KEY_CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.contractID: 5933983188546e84c5acc1732875a4c7c4b9c21bbe8467e519ca6aac6ea86f61
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.type: SCV_OBJECT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.obj._present: true
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.obj.type: SCO_VEC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.obj.vec.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.obj.vec[0].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.obj.vec[0].sym: Admin
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.contractID: 5933983188546e84c5acc1732875a4c7c4b9c21bbe8467e519ca6aac6ea86f61
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.type: SCV_OBJECT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.obj._present: true
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.obj.type: SCO_VEC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.obj.vec.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.obj.vec[0].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.obj.vec[0].sym: Metadata
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 73307f27
signatures[0].signature: 99d609d709e4ea992a5a327cc94ededd4adc8340dab058520b20e352c2f8ef24a5c5b1134a74a77bc72cf1a1f0b828538003b55f58dfcc190ae7c1c5e3e73a01';
        $expected = "AAAAAgAAAAAOCiq2E0Pq1KGbODTfxqpru7PuoZGOX+9/KJKvczB/JwAAAGQACMVgAAAAAgAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAIAAAABSU9NAAAAAAC9SCZ77BppLcorjIWvOvwvUWt3FjCOtyOh7Yas1CJzsgAAAAEAAAAAAAAAAwAAAAZZM5gxiFRuhMWswXModaTHxLnCG76EZ+UZymqsbqhvYQAAAAMAAAADAAAABlkzmDGIVG6ExazBcyh1pMfEucIbvoRn5RnKaqxuqG9hAAAABAAAAAEAAAAAAAAAAQAAAAUAAAAFQWRtaW4AAAAAAAAGWTOYMYhUboTFrMFzKHWkx8S5whu+hGflGcpqrG6ob2EAAAAEAAAAAQAAAAAAAAABAAAABQAAAAhNZXRhZGF0YQAAAAAAAAABczB/JwAAAECZ1gnXCeTqmSpaMnzJTt7dStyDQNqwWFILIONSwvjvJKXFsRNKdKd7xyzxofC4KFOAA7VfWN/MGQrnwcXj5zoB";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }
}




