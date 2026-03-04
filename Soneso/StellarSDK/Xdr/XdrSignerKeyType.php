<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSignerKeyType extends XdrSignerKeyTypeBase
{
    // Short-name aliases for backward compatibility
    const ED25519 = self::SIGNER_KEY_TYPE_ED25519;
    const PRE_AUTH_TX = self::SIGNER_KEY_TYPE_PRE_AUTH_TX;
    const HASH_X = self::SIGNER_KEY_TYPE_HASH_X;
    const ED25519_SIGNED_PAYLOAD = self::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD;
    const MUXED_ED25519 = 0x100;
}
