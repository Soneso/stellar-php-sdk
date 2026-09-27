<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use TypeError;

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
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $result = static::decode(new XdrBuffer($decoded));
        if (!$result instanceof static) {
            throw new TypeError(sprintf(
                '%s::fromBase64Xdr() needs a decode() override that returns %s, got %s',
                static::class,
                static::class,
                get_class($result)
            ));
        }
        return $result;
    }
}
