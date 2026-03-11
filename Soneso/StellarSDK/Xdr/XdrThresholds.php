<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrThresholds {

    public string $thresholds;

    public function __construct(string $thresholds) {
        $this->thresholds = $thresholds;
    }

    public function encode(): string {
        return XdrEncoder::opaqueFixed($this->thresholds, 4);
    }

    public static function decode(XdrBuffer $xdr): XdrThresholds {
        return new XdrThresholds($xdr->readOpaqueFixed(4));
    }

    public function toBase64Xdr(): string {
        return base64_encode($this->encode());
    }

    public static function fromBase64Xdr(string $xdr): static {
        $decoded = base64_decode($xdr, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded XDR');
        }
        return static::decode(new XdrBuffer($decoded));
    }
}
