<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentials;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Credentials for Soroban authorization.
 *
 * Represents one of four credential arms:
 * - SOURCE_ACCOUNT: uses the transaction source account; no address credentials.
 * - ADDRESS (legacy): address credentials without an address-bound preimage.
 * - ADDRESS_V2 (Protocol 27, CAP-71): address credentials with an address-bound preimage
 *   (ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS). Opt-in; invalid on pre-27 networks.
 * - ADDRESS_WITH_DELEGATES (Protocol 27, CAP-71): ADDRESS_V2 with a recursive delegate
 *   tree. Opt-in; invalid on pre-27 networks.
 *
 * The property $addressCredentials carries the inner SorobanAddressCredentials for the
 * ADDRESS and ADDRESS_V2 arms. For ADDRESS_WITH_DELEGATES, $addressWithDelegates carries
 * the full credentials-plus-delegates payload; $addressCredentials is null in that arm.
 *
 * Legacy behavior is preserved: the default arm is ADDRESS when address credentials are
 * set; existing callers that only use forSourceAccount() / forAddressCredentials() are
 * unaffected.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAddressCredentials
 * @see SorobanAddressCredentialsWithDelegates
 * @see SorobanDelegateSignature
 * @see SorobanAuthorizationEntry
 */
class SorobanCredentials
{
    /**
     * @var int one of XdrSorobanCredentialsType constants
     */
    public int $credentialType;

    /**
     * @var SorobanAddressCredentials|null address credentials for ADDRESS and ADDRESS_V2 arms; null otherwise
     */
    public ?SorobanAddressCredentials $addressCredentials = null;

    /**
     * @var SorobanAddressCredentialsWithDelegates|null credentials-with-delegates for ADDRESS_WITH_DELEGATES arm; null otherwise
     */
    public ?SorobanAddressCredentialsWithDelegates $addressWithDelegates = null;

    /**
     * Creates new Soroban credentials.
     *
     * The first argument is normally one of the XdrSorobanCredentialsType constants (int).
     * Passing a SorobanAddressCredentials object as the first argument is also accepted for
     * backward compatibility with the legacy single-argument calling convention — in that case
     * the arm defaults to ADDRESS and addressCredentials is set from the object.
     *
     * @param int|SorobanAddressCredentials $credentialType one of XdrSorobanCredentialsType constants,
     *        or a SorobanAddressCredentials object for legacy compatibility
     * @param SorobanAddressCredentials|null $addressCredentials required for ADDRESS and ADDRESS_V2 arms
     * @param SorobanAddressCredentialsWithDelegates|null $addressWithDelegates required for ADDRESS_WITH_DELEGATES arm
     */
    public function __construct(
        int|SorobanAddressCredentials              $credentialType = XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT,
        ?SorobanAddressCredentials                 $addressCredentials = null,
        ?SorobanAddressCredentialsWithDelegates    $addressWithDelegates = null,
    ) {
        // Backward-compatible: if a SorobanAddressCredentials is passed as the first arg,
        // treat it as legacy ADDRESS credentials.
        if ($credentialType instanceof SorobanAddressCredentials) {
            $this->credentialType     = XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS;
            $this->addressCredentials = $credentialType;
            return;
        }
        $this->credentialType       = $credentialType;
        $this->addressCredentials   = $addressCredentials;
        $this->addressWithDelegates = $addressWithDelegates;
    }

    /**
     * Creates source-account credentials.
     *
     * Source-account credentials authorize using the transaction source account without
     * additional signatures.
     *
     * @return SorobanCredentials credentials using the source account
     */
    public static function forSourceAccount(): SorobanCredentials
    {
        return new SorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT);
    }

    /**
     * Creates legacy ADDRESS credentials.
     *
     * Uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION (not address-bound). This is the default
     * arm and is valid on all protocol versions.
     *
     * @param Address $address the address to authorize
     * @param int $nonce unique nonce for replay protection
     * @param int $signatureExpirationLedger ledger after which the signature expires
     * @param XdrSCVal $signature the signature data
     * @return SorobanCredentials legacy ADDRESS credentials
     */
    public static function forAddress(
        Address $address,
        int     $nonce,
        int     $signatureExpirationLedger,
        XdrSCVal $signature,
    ): SorobanCredentials {
        $addressCredentials = new SorobanAddressCredentials($address, $nonce, $signatureExpirationLedger, $signature);
        return new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
            $addressCredentials,
        );
    }

    /**
     * Creates legacy ADDRESS credentials from existing address credentials.
     *
     * @param SorobanAddressCredentials $addressCredentials the address credentials
     * @return SorobanCredentials legacy ADDRESS credentials
     */
    public static function forAddressCredentials(SorobanAddressCredentials $addressCredentials): SorobanCredentials
    {
        return new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
            $addressCredentials,
        );
    }

    /**
     * Creates ADDRESS_V2 credentials (Protocol 27, CAP-71).
     *
     * Uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS (address-bound preimage).
     * Invalid on networks below Protocol 27.
     *
     * @param SorobanAddressCredentials $addressCredentials the address credentials
     * @return SorobanCredentials ADDRESS_V2 credentials
     */
    public static function forAddressCredentialsV2(SorobanAddressCredentials $addressCredentials): SorobanCredentials
    {
        return new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
            $addressCredentials,
        );
    }

    /**
     * Creates ADDRESS_WITH_DELEGATES credentials (Protocol 27, CAP-71).
     *
     * Uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS with a recursive delegate tree.
     * Invalid on networks below Protocol 27.
     *
     * @param SorobanAddressCredentialsWithDelegates $addressWithDelegates the credentials-plus-delegates payload
     * @return SorobanCredentials ADDRESS_WITH_DELEGATES credentials
     */
    public static function forAddressWithDelegates(
        SorobanAddressCredentialsWithDelegates $addressWithDelegates,
    ): SorobanCredentials {
        return new SorobanCredentials(
            XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
            null,
            $addressWithDelegates,
        );
    }

    /**
     * Decodes a SorobanCredentials from its XDR representation.
     *
     * All four credential arms are decoded faithfully. An unknown arm value throws
     * InvalidArgumentException.
     *
     * @param XdrSorobanCredentials $xdr the XDR object to decode
     * @return SorobanCredentials the decoded credentials
     * @throws InvalidArgumentException for unknown credential arm values
     */
    public static function fromXdr(XdrSorobanCredentials $xdr): SorobanCredentials
    {
        switch ($xdr->type->value) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT:
                return new SorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT);

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                if ($xdr->address === null) {
                    throw new InvalidArgumentException(
                        'XdrSorobanCredentials arm ADDRESS is missing address payload'
                    );
                }
                return new SorobanCredentials(
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
                    SorobanAddressCredentials::fromXdr($xdr->address),
                );

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2:
                if ($xdr->addressV2 === null) {
                    throw new InvalidArgumentException(
                        'XdrSorobanCredentials arm ADDRESS_V2 is missing addressV2 payload'
                    );
                }
                return new SorobanCredentials(
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
                    SorobanAddressCredentials::fromXdr($xdr->addressV2),
                );

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES:
                if ($xdr->addressWithDelegates === null) {
                    throw new InvalidArgumentException(
                        'XdrSorobanCredentials arm ADDRESS_WITH_DELEGATES is missing addressWithDelegates payload'
                    );
                }
                return new SorobanCredentials(
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
                    null,
                    SorobanAddressCredentialsWithDelegates::fromXdr($xdr->addressWithDelegates),
                );

            default:
                throw new InvalidArgumentException(
                    'Unknown XdrSorobanCredentialsType value: ' . $xdr->type->value
                );
        }
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanCredentials the XDR encoded credentials
     * @throws InvalidArgumentException if required payload is missing for the current arm
     */
    public function toXdr(): XdrSorobanCredentials
    {
        switch ($this->credentialType) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT:
                return XdrSorobanCredentials::forSourceAccount();

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                if ($this->addressCredentials === null) {
                    throw new InvalidArgumentException(
                        'ADDRESS arm requires addressCredentials to be set'
                    );
                }
                return XdrSorobanCredentials::forAddressCredentials($this->addressCredentials->toXdr());

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2:
                if ($this->addressCredentials === null) {
                    throw new InvalidArgumentException(
                        'ADDRESS_V2 arm requires addressCredentials to be set'
                    );
                }
                return XdrSorobanCredentials::forAddressCredentialsV2($this->addressCredentials->toXdr());

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES:
                if ($this->addressWithDelegates === null) {
                    throw new InvalidArgumentException(
                        'ADDRESS_WITH_DELEGATES arm requires addressWithDelegates to be set'
                    );
                }
                return XdrSorobanCredentials::forAddressWithDelegates($this->addressWithDelegates->toXdr());

            default:
                throw new InvalidArgumentException(
                    'Unknown credential type: ' . $this->credentialType
                );
        }
    }

    /**
     * Returns true when this is a source-account credential.
     *
     * @return bool true for SOURCE_ACCOUNT arm
     */
    public function isSourceAccount(): bool
    {
        return $this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT;
    }

    /**
     * Returns true when this is an address-based credential (any of ADDRESS, ADDRESS_V2,
     * or ADDRESS_WITH_DELEGATES).
     *
     * @return bool true for all three address arms
     */
    public function isAddressBased(): bool
    {
        return $this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS
            || $this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2
            || $this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES;
    }

    /**
     * Returns the inner SorobanAddressCredentials for any address arm.
     *
     * - ADDRESS: returns $addressCredentials directly.
     * - ADDRESS_V2: returns $addressCredentials directly.
     * - ADDRESS_WITH_DELEGATES: returns $addressWithDelegates->addressCredentials.
     * - SOURCE_ACCOUNT: returns null.
     *
     * @return SorobanAddressCredentials|null the inner address credentials, or null for source-account
     */
    public function getAddressCredentials(): ?SorobanAddressCredentials
    {
        if ($this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES) {
            return $this->addressWithDelegates?->addressCredentials;
        }
        return $this->addressCredentials;
    }

    /**
     * Writes back updated inner SorobanAddressCredentials while preserving the credential arm.
     *
     * ADDRESS and ADDRESS_V2: sets $addressCredentials.
     * ADDRESS_WITH_DELEGATES: sets $addressWithDelegates->addressCredentials.
     * SOURCE_ACCOUNT: no-op.
     *
     * @param SorobanAddressCredentials $addressCredentials the updated address credentials
     */
    public function writeBackAddressCredentials(SorobanAddressCredentials $addressCredentials): void
    {
        if ($this->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES) {
            if ($this->addressWithDelegates !== null) {
                $this->addressWithDelegates->addressCredentials = $addressCredentials;
            }
        } elseif ($this->credentialType !== XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT) {
            $this->addressCredentials = $addressCredentials;
        }
    }

    /**
     * Sets the address credentials for ADDRESS and ADDRESS_V2 arms.
     *
     * @param SorobanAddressCredentials|null $addressCredentials the address credentials
     */
    public function setAddressCredentials(?SorobanAddressCredentials $addressCredentials): void
    {
        $this->addressCredentials = $addressCredentials;
    }

    /**
     * Returns the ADDRESS_WITH_DELEGATES payload, or null for other arms.
     *
     * @return SorobanAddressCredentialsWithDelegates|null
     */
    public function getAddressWithDelegates(): ?SorobanAddressCredentialsWithDelegates
    {
        return $this->addressWithDelegates;
    }

    /**
     * Sets the ADDRESS_WITH_DELEGATES payload.
     *
     * @param SorobanAddressCredentialsWithDelegates|null $addressWithDelegates
     */
    public function setAddressWithDelegates(?SorobanAddressCredentialsWithDelegates $addressWithDelegates): void
    {
        $this->addressWithDelegates = $addressWithDelegates;
    }

    /**
     * Returns the credential type constant.
     *
     * @return int one of XdrSorobanCredentialsType constants
     */
    public function getCredentialType(): int
    {
        return $this->credentialType;
    }

    /**
     * Sets the credential type constant.
     *
     * @param int $credentialType one of XdrSorobanCredentialsType constants
     */
    public function setCredentialType(int $credentialType): void
    {
        $this->credentialType = $credentialType;
    }
}
