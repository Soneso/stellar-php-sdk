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
        $xdr = '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';
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
/*
    public function testSorobanUploadContractWasm() : void {
        $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GAMLIXLKO3GIC2K5CLQ42573BBRTODKWIQQCUJSLHPUPUWBNFTIKZOND
tx.fee: 100
tx.seqNum: 1410007698505729
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM
tx.operations[0].body.invokeHostFunctionOp.function.uploadContractWasm.code: 0061736d01000000010f0360017e017e60027e7e017e6000000219040176015f000001780138000001760134000101760136000103030200020503010001060b027f0141000b7f0141000b071d030568656c6c6f0004066d656d6f727902000873646b737461727400050c01060ac70302b20302067f027e4202100021082300220441046a2201411c6a22053f002203411074410f6a41707122064b04402003200520066b41ffff036a4180807c714110762206200320064a1b40004100480440200640004100480440000b0b0b200524002004411c360200200141046b22034100360204200341003602082003410336020c200341083602102001420037031020012008370310419c0928020041017641094b044042831010011a0b03402002419c092802004101764804402002419c092802004101764f047f417f05200241017441a0096a2f01000b220341fa004c200341304e7104402007420686210842002107200341ff017141df004604404201210705200341ff0171220441394d200441304f710440200341ff0171ad422e7d210705200341ff0171220441da004d200441c1004f710440200341ff0171ad42357d210705200341ff0171220441fa004d200441e1004f710440200341ff0171ad423b7d21070542831010011a0b0b0b0b200720088421070542831010011a0b200241016a21020c010b0b200120012903102007420886420e841002370310200120012903102000100337031020012903100b1100230104400f0b4101240141ac0924000b0b8d010600418c080b013c004198080b2f010000002800000041006c006c006f0063006100740069006f006e00200074006f006f0020006c00610072006700650041cc080b013c0041d8080b25010000001e0000007e006c00690062002f00720074002f0073007400750062002e0074007300418c090b011c004198090b11010000000a000000480065006c006c006f001e11636f6e7472616374656e766d657461763000000000000000000000002000430e636f6e747261637473706563763000000000000000000000000568656c6c6f000000000000010000000000000002746f00000000001100000001000003ea00000011
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractCode.hash: 3c2852fb06f47f4f371ac1b13472ae65ce3354c8af3001e66896cea08358b554
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 2d2cd0ac
signatures[0].signature: a3bf28aa1433bb74ae8531009355b8921d8ba22b369a3e0e4a922aa3a211d27f5d9fcace1d0415089668f305320e1bf8f1929012accfe4b4990ee2a2d01bda02';
        $expected = "AAAAAgAAAAAYtF1qdsyBaV0S4c13+whjNw1WRCAqJks76PpYLSzQrAAAAGQABQJlAAAAAQAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAgAAAywAYXNtAQAAAAEPA2ABfgF+YAJ+fgF+YAAAAhkEAXYBXwAAAXgBOAAAAXYBNAABAXYBNgABAwMCAAIFAwEAAQYLAn8BQQALfwFBAAsHHQMFaGVsbG8ABAZtZW1vcnkCAAhzZGtzdGFydAAFDAEGCscDArIDAgZ/An5CAhAAIQgjACIEQQRqIgFBHGoiBT8AIgNBEHRBD2pBcHEiBksEQCADIAUgBmtB//8DakGAgHxxQRB2IgYgAyAGShtAAEEASARAIAZAAEEASARAAAsLCyAFJAAgBEEcNgIAIAFBBGsiA0EANgIEIANBADYCCCADQQM2AgwgA0EINgIQIAFCADcDECABIAg3AxBBnAkoAgBBAXZBCUsEQEKDEBABGgsDQCACQZwJKAIAQQF2SARAIAJBnAkoAgBBAXZPBH9BfwUgAkEBdEGgCWovAQALIgNB+gBMIANBME5xBEAgB0IGhiEIQgAhByADQf8BcUHfAEYEQEIBIQcFIANB/wFxIgRBOU0gBEEwT3EEQCADQf8Bca1CLn0hBwUgA0H/AXEiBEHaAE0gBEHBAE9xBEAgA0H/AXGtQjV9IQcFIANB/wFxIgRB+gBNIARB4QBPcQRAIANB/wFxrUI7fSEHBUKDEBABGgsLCwsgByAIhCEHBUKDEBABGgsgAkEBaiECDAELCyABIAEpAxAgB0IIhkIOhBACNwMQIAEgASkDECAAEAM3AxAgASkDEAsRACMBBEAPC0EBJAFBrAkkAAsLjQEGAEGMCAsBPABBmAgLLwEAAAAoAAAAQQBsAGwAbwBjAGEAdABpAG8AbgAgAHQAbwBvACAAbABhAHIAZwBlAEHMCAsBPABB2AgLJQEAAAAeAAAAfgBsAGkAYgAvAHIAdAAvAHMAdAB1AGIALgB0AHMAQYwJCwEcAEGYCQsRAQAAAAoAAABIAGUAbABsAG8AHhFjb250cmFjdGVudm1ldGF2MAAAAAAAAAAAAAAAIABDDmNvbnRyYWN0c3BlY3YwAAAAAAAAAAAAAAAFaGVsbG8AAAAAAAABAAAAAAAAAAJ0bwAAAAAAEQAAAAEAAAPqAAAAEQAAAAEAAAAHPChS+wb0f083GsGxNHKuZc4zVMivMAHmaJbOoINYtVQAAAAAAAAAAAAAAAAAAAABLSzQrAAAAECjvyiqFDO7dK6FMQCTVbiSHYuiKzaaPg5KkiqjohHSf12fys4dBBUIlmjzBTIOG/jxkpASrM/ktJkO4qLQG9oC";
        $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
        self::assertEquals($expected,$xdr);
        $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
        print($txRepRes);
        self::assertEquals($txRepRes,$txrep);
    }


        public function testSorobanCreateContract() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GCB6HTJLKSPZF6GDBKKNUMAFIMUOSIQGLHRF2TWE7TXLJYU5TEJE73JB
tx.fee: 100
tx.seqNum: 1411291893727234
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContract.source.type: SCCONTRACT_CODE_WASM_REF
tx.operations[0].body.invokeHostFunctionOp.function.createContract.source.wasm_id: 3c2852fb06f47f4f371ac1b13472ae65ce3354c8af3001e66896cea08358b554
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.type: CONTRACT_ID_FROM_SOURCE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.salt: 7ccb2e253efe9a989b8fee36ed4579bd9eeaa6800016e2d9b592b643940c1486
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractCode.hash: 3c2852fb06f47f4f371ac1b13472ae65ce3354c8af3001e66896cea08358b554
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 10267e32179a6026aa64a975774d6abcf35b1880aaaaafaaaabbdef5c323530e
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.auth.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 9d99124f
signatures[0].signature: 8b1aae1df97130436042a55c435350e7f12fb93c3c1ead0e7da41d009b730539cfb4183a7c75359ab5f1d2d5d5a46e8e88352bedd107df4d4831d290d9f9590c';
            $expected = "AAAAAgAAAACD480rVJ+S+MMKlNowBUMo6SIGWeJdTsT87rTinZkSTwAAAGQABQOQAAAAAgAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAB8yy4lPv6amJuP7jbtRXm9nuqmgAAW4tm1krZDlAwUhgAAAAA8KFL7BvR/TzcawbE0cq5lzjNUyK8wAeZols6gg1i1VAAAAAEAAAAHPChS+wb0f083GsGxNHKuZc4zVMivMAHmaJbOoINYtVQAAAABAAAABhAmfjIXmmAmqmSpdXdNarzzWxiAqqqvqqq73vXDI1MOAAAAFAAAAAAAAAAAAAAAAZ2ZEk8AAABAixquHflxMENgQqVcQ1NQ5/EvuTw8Hq0OfaQdAJtzBTnPtBg6fHU1mrXx0tXVpG6OiDUr7dEH301IMdKQ2flZDA==";
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanInvokeContract() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBEJEYTTA2DN5UXTLIUWLWMC7FBHH4XMSXE5A6DA7T2PZHC2SYM3Y2FU
tx.fee: 100
tx.seqNum: 1412116527448067
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INVOKE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract.len: 3
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].bytes: e7b601e7e77e1cc41c6de03fd5ca53c0acfa980264932f8fcff79a617b95db0d
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].sym: hello
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].sym: friend
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 2
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.contractID: e7b601e7e77e1cc41c6de03fd5ca53c0acfa980264932f8fcff79a617b95db0d
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].contractCode.hash: 3c2852fb06f47f4f371ac1b13472ae65ce3354c8af3001e66896cea08358b554
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 5a9619bc
signatures[0].signature: 8e7e685df4479133e7b3d60fed6f33b868c88f256495394eabcf27ac0708de910906d21dea6760f34b6fdd0cd9b534ffc9323e728f0c21657dc4b109a7a97201';
            $expected = "AAAAAgAAAABIkmJzBobe0vNaKWXZgvlCc/LslcnQeGD89PycWpYZvAAAAGQABQRQAAAAAwAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAAAAAAMAAAANAAAAIOe2AefnfhzEHG3gP9XKU8Cs+pgCZJMvj8/3mmF7ldsNAAAADwAAAAVoZWxsbwAAAAAAAA8AAAAGZnJpZW5kAAAAAAACAAAABue2AefnfhzEHG3gP9XKU8Cs+pgCZJMvj8/3mmF7ldsNAAAAFAAAAAc8KFL7BvR/TzcawbE0cq5lzjNUyK8wAeZols6gg1i1VAAAAAAAAAAAAAAAAAAAAAFalhm8AAAAQI5+aF30R5Ez57PWD+1vM7hoyI8lZJU5TqvPJ6wHCN6RCQbSHepnYPNLb90M2bU0/8kyPnKPDCFlfcSxCaepcgE=";
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanDeploySACSrcAcc() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GCKN6CVKZWE6VH67G6FMRESA5PEKBU3H3EKYDXCGSTBZMCU2BP3F5BU7
tx.fee: 100
tx.seqNum: 1460817161617412
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContract.source.type: SCCONTRACT_CODE_TOKEN
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.type: CONTRACT_ID_FROM_SOURCE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.salt: 8084ab5bf8fe1b1b20ad25e4a80318143abe222f09985ded4a34ef47b5e5f13c
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 0
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 36ac11aebe84ff51242e5a92e95bf5b0b0f56b5be6d195e575bdc9b9c611c969
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.auth.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 9a0bf65e
signatures[0].signature: 20eda6e11d3a7d748d5a8e7f3ac06a8774d2aa7f9b33d92ab74395db63f1b54eb50f14f23ae877b292f3e936c6c9717524989e95b0758a3addbe8da6b8c28006';
            $expected = "AAAAAgAAAACU3wqqzYnqn983isiSQOvIoNNn2RWB3EaUw5YKmgv2XgAAAGQABTCbAAAABAAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAACAhKtb+P4bGyCtJeSoAxgUOr4iLwmYXe1KNO9HteXxPAAAAAEAAAAAAAAAAQAAAAY2rBGuvoT/USQuWpLpW/WwsPVrW+bRleV1vcm5xhHJaQAAABQAAAAAAAAAAAAAAAGaC/ZeAAAAQCDtpuEdOn10jVqOfzrAaod00qp/mzPZKrdDldtj8bVOtQ8U8jrod7KS8+k2xslxdSSYnpWwdYo63b6NprjCgAY=";
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanDeploySACAsset() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GAES5UHZXUGHLCIFPG2HOP55Z5BNIPHDX25F7T5IRGZRT2AOZZVXDPGU
tx.fee: 100
tx.seqNum: 1460842931421186
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_CREATE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.createContract.source.type: SCCONTRACT_CODE_TOKEN
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.type: CONTRACT_ID_FROM_ASSET
tx.operations[0].body.invokeHostFunctionOp.function.createContract.contractID.asset: IOM:GCKN6CVKZWE6VH67G6FMRESA5PEKBU3H3EKYDXCGSTBZMCU2BP3F5BU7
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 0
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 3
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 31f630224066f7d5be0ec6f108ea6cc9aeb65de5f8343a02e1add61ff3c99a1f
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_VEC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.vec._present: true
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.vec.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.vec[0].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.vec[0].sym: Admin
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.contractID: 31f630224066f7d5be0ec6f108ea6cc9aeb65de5f8343a02e1add61ff3c99a1f
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.type: SCV_VEC
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.vec._present: true
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.vec.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.vec[0].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.vec[0].sym: Metadata
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.contractID: 31f630224066f7d5be0ec6f108ea6cc9aeb65de5f8343a02e1add61ff3c99a1f
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.auth.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 0ece6b71
signatures[0].signature: aa5a440d98b085bed69f223ec351ffb95fb438fd64cdbdda7a79ee784733183ddc685ac5a963b584535dfece6d366d277870e6d2c956800f661397b52a3ebc06';
            $expected = "AAAAAgAAAAAJLtD5vQx1iQV5tHc/vc9C1Dzjvrpfz6iJsxnoDs5rcQAAAGQABTChAAAAAgAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAQAAAAIAAAABSU9NAAAAAACU3wqqzYnqn983isiSQOvIoNNn2RWB3EaUw5YKmgv2XgAAAAEAAAAAAAAAAwAAAAYx9jAiQGb31b4OxvEI6mzJrrZd5fg0OgLhrdYf88maHwAAABAAAAABAAAAAQAAAA8AAAAFQWRtaW4AAAAAAAAGMfYwIkBm99W+DsbxCOpsya62XeX4NDoC4a3WH/PJmh8AAAAQAAAAAQAAAAEAAAAPAAAACE1ldGFkYXRhAAAABjH2MCJAZvfVvg7G8QjqbMmutl3l+DQ6AuGt1h/zyZofAAAAFAAAAAAAAAAAAAAAAQ7Oa3EAAABAqlpEDZiwhb7WnyI+w1H/uV+0OP1kzb3aennueEczGD3caFrFqWO1hFNd/s5tNm0neHDm0slWgA9mE5e1Kj68Bg==";

            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanInvokeAuth1() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GCXYRGD4U77MEGDSXBXGGDI2UASQTLEJVT7YFKKZ2S7YQYFNZKKZJTMQ
tx.fee: 100
tx.seqNum: 1461521536253955
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INVOKE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract.len: 4
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].bytes: 6d7e4ac1fefd1fae961f4bae47836dc8b815f1df3b27ef79c10a9a3e48119b09
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].sym: auth
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.accountId: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].type: SCV_U32
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].u32: 3
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 3
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].account.accountID: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].contractData.contractID: 6d7e4ac1fefd1fae961f4bae47836dc8b815f1df3b27ef79c10a9a3e48119b09
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[2].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[2].contractCode.hash: 185b8c6d92815faa9da6b69fdb8ec62f439bf967ffd51751b6e8c116b15edd26
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 2
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 6d7e4ac1fefd1fae961f4bae47836dc8b815f1df3b27ef79c10a9a3e48119b09
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.address.accountId: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.contractID: 6d7e4ac1fefd1fae961f4bae47836dc8b815f1df3b27ef79c10a9a3e48119b09
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.type: SCV_LEDGER_KEY_NONCE
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.nonce_key.nonce_address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.nonce_key.nonce_address.accountId: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.auth.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.address.accountId: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.nonce: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.contractID: 6d7e4ac1fefd1fae961f4bae47836dc8b815f1df3b27ef79c10a9a3e48119b09
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.functionName: auth
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].address.accountId: GCDANTQJGICA2BJOZWOECPIC33FZZGJA5EIVCODZ6QCNF5XPATYQQXI4
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].type: SCV_U32
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].u32: 3
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].type: SCV_MAP
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].key.sym: public_key
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].val.bytes: 8606ce0932040d052ecd9c413d02decb9c9920e911513879f404d2f6ef04f108
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].key.sym: signature
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].val.bytes: e2da2f393e7c0a71bf8c4de1c4bb21841bc4727864d81457406d362715e1634577e6085a91803e726003c443e8dac679f0da912c5fb24433c1bc2722d4ae2708
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: adca9594
signatures[0].signature: 60046a4ee45a44791aafdd250913a5b1ecf46cf8dfffec4033cec4f526e89eafd0ace2e6dd6c99576d70f2ed5006e9b19144ab4b814c0f754bb3255c57aaa203';
            $expected = "AAAAAgAAAACviJh8p/7CGHK4bmMNGqAlCayJrP+CqVnUv4hgrcqVlAAAAGQABTE/AAAAAwAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAAAAAAQAAAANAAAAIG1+SsH+/R+ulh9LrkeDbci4FfHfOyfvecEKmj5IEZsJAAAADwAAAARhdXRoAAAAEwAAAAAAAAAAhgbOCTIEDQUuzZxBPQLey5yZIOkRUTh59ATS9u8E8QgAAAADAAAAAwAAAAMAAAAAAAAAAIYGzgkyBA0FLs2cQT0C3sucmSDpEVE4efQE0vbvBPEIAAAABm1+SsH+/R+ulh9LrkeDbci4FfHfOyfvecEKmj5IEZsJAAAAFAAAAAcYW4xtkoFfqp2mtp/bjsYvQ5v5Z//VF1G26MEWsV7dJgAAAAIAAAAGbX5Kwf79H66WH0uuR4NtyLgV8d87J+95wQqaPkgRmwkAAAATAAAAAAAAAACGBs4JMgQNBS7NnEE9At7LnJkg6RFROHn0BNL27wTxCAAAAAZtfkrB/v0frpYfS65Hg23IuBXx3zsn73nBCpo+SBGbCQAAABUAAAAAAAAAAIYGzgkyBA0FLs2cQT0C3sucmSDpEVE4efQE0vbvBPEIAAAAAQAAAAEAAAAAAAAAAIYGzgkyBA0FLs2cQT0C3sucmSDpEVE4efQE0vbvBPEIAAAAAAAAAABtfkrB/v0frpYfS65Hg23IuBXx3zsn73nBCpo+SBGbCQAAAARhdXRoAAAAAgAAABMAAAAAAAAAAIYGzgkyBA0FLs2cQT0C3sucmSDpEVE4efQE0vbvBPEIAAAAAwAAAAMAAAAAAAAAAQAAABAAAAABAAAAAQAAABEAAAABAAAAAgAAAA8AAAAKcHVibGljX2tleQAAAAAADQAAACCGBs4JMgQNBS7NnEE9At7LnJkg6RFROHn0BNL27wTxCAAAAA8AAAAJc2lnbmF0dXJlAAAAAAAADQAAAEDi2i85PnwKcb+MTeHEuyGEG8RyeGTYFFdAbTYnFeFjRXfmCFqRgD5yYAPEQ+jaxnnw2pEsX7JEM8G8JyLUricIAAAAAAAAAAGtypWUAAAAQGAEak7kWkR5Gq/dJQkTpbHs9Gz43//sQDPOxPUm6J6v0Kzi5t1smVdtcPLtUAbpsZFEq0uBTA91S7MlXFeqogM=";
            print($txrep . PHP_EOL);
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanInvokeAuth2() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GDIDLIH2NRQZZ74TAI2X67T7YZH52WBW2UOVDNUSUGHATSVJVF5LFUPR
tx.fee: 100
tx.seqNum: 1461328262725635
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INVOKE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract.len: 4
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].bytes: 9f6008b315824093556aef0c9dd50338bb1e1e36b48b68f03f5f510a731e3ab3
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].sym: auth
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.accountId: GDIDLIH2NRQZZ74TAI2X67T7YZH52WBW2UOVDNUSUGHATSVJVF5LFUPR
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].type: SCV_U32
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].u32: 3
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 2
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.contractID: 9f6008b315824093556aef0c9dd50338bb1e1e36b48b68f03f5f510a731e3ab3
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].contractCode.hash: 185b8c6d92815faa9da6b69fdb8ec62f439bf967ffd51751b6e8c116b15edd26
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 1
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 9f6008b315824093556aef0c9dd50338bb1e1e36b48b68f03f5f510a731e3ab3
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.address.accountId: GDIDLIH2NRQZZ74TAI2X67T7YZH52WBW2UOVDNUSUGHATSVJVF5LFUPR
tx.operations[0].body.invokeHostFunctionOp.auth.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce._present: false
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.contractID: 9f6008b315824093556aef0c9dd50338bb1e1e36b48b68f03f5f510a731e3ab3
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.functionName: auth
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].address.accountId: GDIDLIH2NRQZZ74TAI2X67T7YZH52WBW2UOVDNUSUGHATSVJVF5LFUPR
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].type: SCV_U32
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].u32: 3
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs.len: 0
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: a9a97ab2
signatures[0].signature: 4544495e9410e037faaf02e0939bea4ca708c598af51b8b40436c302dbbec6be81ec735df068e600f163190c75b196f79461305a65992b6a94b8cdff47362508';
            $expected = "AAAAAgAAAADQNaD6bGGc/5MCNX9+f8ZP3Vg21R1RtpKhjgnKqal6sgAAAGQABTESAAAAAwAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAAAAAAQAAAANAAAAIJ9gCLMVgkCTVWrvDJ3VAzi7Hh42tIto8D9fUQpzHjqzAAAADwAAAARhdXRoAAAAEwAAAAAAAAAA0DWg+mxhnP+TAjV/fn/GT91YNtUdUbaSoY4JyqmperIAAAADAAAAAwAAAAIAAAAGn2AIsxWCQJNVau8MndUDOLseHja0i2jwP19RCnMeOrMAAAAUAAAABxhbjG2SgV+qnaa2n9uOxi9Dm/ln/9UXUbbowRaxXt0mAAAAAQAAAAafYAizFYJAk1Vq7wyd1QM4ux4eNrSLaPA/X1EKcx46swAAABMAAAAAAAAAANA1oPpsYZz/kwI1f35/xk/dWDbVHVG2kqGOCcqpqXqyAAAAAQAAAACfYAizFYJAk1Vq7wyd1QM4ux4eNrSLaPA/X1EKcx46swAAAARhdXRoAAAAAgAAABMAAAAAAAAAANA1oPpsYZz/kwI1f35/xk/dWDbVHVG2kqGOCcqpqXqyAAAAAwAAAAMAAAAAAAAAAAAAAAAAAAABqal6sgAAAEBFRElelBDgN/qvAuCTm+pMpwjFmK9RuLQENsMC277GvoHsc13waOYA8WMZDHWxlveUYTBaZZkrapS4zf9HNiUI";
            print($txrep . PHP_EOL);
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }

        public function testSorobanInvokeAuth3() : void {
            $txrep = 'type: ENVELOPE_TYPE_TX
tx.sourceAccount: GBRFC45ZFZBDRJS55WJRNJHCIPYV6OFAOCHLNSJBI3SXS6S32WVI6H6I
tx.fee: 100
tx.seqNum: 1461783529259021
tx.cond.type: PRECOND_NONE
tx.memo.type: MEMO_NONE
tx.operations.len: 1
tx.operations[0].sourceAccount._present: false
tx.operations[0].body.type: INVOKE_HOST_FUNCTION
tx.operations[0].body.invokeHostFunctionOp.function.type: HOST_FUNCTION_TYPE_INVOKE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract.len: 10
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[0].bytes: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[1].sym: swap
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[2].address.accountId: GADWQEOKGQVUYHKMCOL2UULZSMZ7AVPVICQ66OUJPG5K4NRXNEXCMHDI
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[3].address.accountId: GBNAILGAXUP2X5R43GAEZPPUMSV2DUWE3YNXULVS5VWNPQNVAET6TYV7
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[4].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[4].bytes: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[5].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[5].bytes: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[6].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[6].i128.lo: 1000
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[6].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[7].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[7].i128.lo: 4500
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[7].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[8].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[8].i128.lo: 5000
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[8].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[9].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[9].i128.lo: 950
tx.operations[0].body.invokeHostFunctionOp.function.invokeContract[9].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly.len: 9
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].type: ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[0].account.accountID: GADWQEOKGQVUYHKMCOL2UULZSMZ7AVPVICQ66OUJPG5K4NRXNEXCMHDI
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].type: ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[1].account.accountID: GBNAILGAXUP2X5R43GAEZPPUMSV2DUWE3YNXULVS5VWNPQNVAET6TYV7
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[2].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[2].contractData.contractID: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[2].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[3].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[3].contractData.contractID: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[3].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[3].contractData.key.sym: Authorizd
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[4].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[4].contractData.contractID: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[4].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[5].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[5].contractData.contractID: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[5].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[5].contractData.key.sym: Authorizd
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[6].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[6].contractData.contractID: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[6].contractData.key.type: SCV_LEDGER_KEY_CONTRACT_EXECUTABLE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[7].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[7].contractCode.hash: 45f7a27e1e9c33ba1ac0f13ad276a1929367624c5edd95dbdfeeba0ab959b991
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[8].type: CONTRACT_CODE
tx.operations[0].body.invokeHostFunctionOp.footprint.readOnly[8].contractCode.hash: ff0071b0fe9460c8ffb06b993822fd121b2bcdabf76facb19852787793cfb4a0
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite.len: 6
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.contractID: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.type: SCV_LEDGER_KEY_NONCE
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.nonce_key.nonce_address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[0].contractData.key.nonce_key.nonce_address.accountId: GADWQEOKGQVUYHKMCOL2UULZSMZ7AVPVICQ66OUJPG5K4NRXNEXCMHDI
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.contractID: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.type: SCV_LEDGER_KEY_NONCE
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.nonce_key.nonce_address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[1].contractData.key.nonce_key.nonce_address.accountId: GBNAILGAXUP2X5R43GAEZPPUMSV2DUWE3YNXULVS5VWNPQNVAET6TYV7
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.contractID: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[2].contractData.key.sym: Allowance
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[3].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[3].contractData.contractID: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[3].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[3].contractData.key.sym: Balance
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[4].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[4].contractData.contractID: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[4].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[4].contractData.key.sym: Allowance
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[5].type: CONTRACT_DATA
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[5].contractData.contractID: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[5].contractData.key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.footprint.readWrite[5].contractData.key.sym: Balance
tx.operations[0].body.invokeHostFunctionOp.auth.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.address.accountId: GADWQEOKGQVUYHKMCOL2UULZSMZ7AVPVICQ66OUJPG5K4NRXNEXCMHDI
tx.operations[0].body.invokeHostFunctionOp.auth[0].addressWithNonce.nonce: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.contractID: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.functionName: swap
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args.len: 4
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[0].bytes: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[1].bytes: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[2].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[2].i128.lo: 1000
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[2].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[3].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[3].i128.lo: 4500
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.args[3].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].contractID: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].functionName: incr_allow
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args.len: 3
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[0].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[0].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[0].address.accountId: GADWQEOKGQVUYHKMCOL2UULZSMZ7AVPVICQ66OUJPG5K4NRXNEXCMHDI
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[1].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[1].address.type: SC_ADDRESS_TYPE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[1].address.contractId: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[2].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[2].i128.lo: 1000
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].args[2].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].rootInvocation.subInvocations[0].subInvocations.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].type: SCV_MAP
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].key.sym: public_key
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[0].val.bytes: 076811ca342b4c1d4c1397aa51799333f055f540a1ef3a8979baae3637692e26
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].key.sym: signature
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[0].signatureArgs[0].map[1].val.bytes: 20137346dfc6bf7aa957e62b6bd0b6bd48fa3ef4334e612dd3d0e9ba75386042f3e725eae7e512f875b6899f76f28ee31736f3ed6a1cf62a448246288a321e0e
tx.operations[0].body.invokeHostFunctionOp.auth[1].addressWithNonce._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[1].addressWithNonce.address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[1].addressWithNonce.address.accountId: GBNAILGAXUP2X5R43GAEZPPUMSV2DUWE3YNXULVS5VWNPQNVAET6TYV7
tx.operations[0].body.invokeHostFunctionOp.auth[1].addressWithNonce.nonce: 0
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.contractID: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.functionName: swap
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args.len: 4
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[0].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[0].bytes: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[1].type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[1].bytes: 81cea9e83693dec2a1ca191cdc644a3b09bbc519ea5530ae03b5a8312d747789
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[2].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[2].i128.lo: 5000
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[2].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[3].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[3].i128.lo: 950
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.args[3].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].contractID: 3af0c6e968c82179ef046f2666b3dc70c35b677913a86f27004bdb32e0278b71
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].functionName: incr_allow
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args.len: 3
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[0].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[0].address.type: SC_ADDRESS_TYPE_ACCOUNT
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[0].address.accountId: GBNAILGAXUP2X5R43GAEZPPUMSV2DUWE3YNXULVS5VWNPQNVAET6TYV7
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[1].type: SCV_ADDRESS
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[1].address.type: SC_ADDRESS_TYPE_CONTRACT
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[1].address.contractId: 112c004b721d38e74658968ad5cd7dd02f527ee73eeb77353b905b4d1517f061
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[2].type: SCV_I128
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[2].i128.lo: 5000
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].args[2].i128.hi: 0
tx.operations[0].body.invokeHostFunctionOp.auth[1].rootInvocation.subInvocations[0].subInvocations.len: 0
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs.len: 1
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].type: SCV_MAP
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map._present: true
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map.len: 2
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[0].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[0].key.sym: public_key
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[0].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[0].val.bytes: 5a042cc0bd1fabf63cd9804cbdf464aba1d2c4de1b7a2eb2ed6cd7c1b50127e9
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[1].key.type: SCV_SYMBOL
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[1].key.sym: signature
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[1].val.type: SCV_BYTES
tx.operations[0].body.invokeHostFunctionOp.auth[1].signatureArgs[0].map[1].val.bytes: f70bf155b1510da9b6465afbfed7541e491045c191e86a8d08d06c50d3b7504b2dc06250d94b4c35e84beb94fc763fdd8b2ee308f0a6d19b734a87194e18fe09
tx.ext.v: 0
signatures.len: 1
signatures[0].hint: 5bd5aa8f
signatures[0].signature: 44f9ab3d05a61577658c799d2c3d2a67eef5cdb64af669dd368e969eb238a980b1293baa096afd01ddb244125f7e4693782aa53828d63f0e8141bef954baec09';
            $expected = "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";
            print($txrep . PHP_EOL);
            $xdr = TxRep::transactionEnvelopeXdrBase64FromTxRep($txrep);
            self::assertEquals($expected,$xdr);
            $txRepRes = TxRep::fromTransactionEnvelopeXdrBase64($xdr);
            print($txRepRes);
            self::assertEquals($txRepRes,$txrep);
        }*/
}




