<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use RuntimeException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimage;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimageSorobanAuthorization;
use Soneso\StellarSDK\Xdr\XdrHashIDPreimageSorobanAuthorizationWithAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Soroban authorization entry for smart contract invocations.
 *
 * Each entry grants permission to execute a specific contract invocation tree. Credentials
 * select which signing scheme applies; the rootInvocation tree identifies the authorized calls.
 *
 * Three credential arms support active signing:
 * - ADDRESS (legacy): preimage is ENVELOPE_TYPE_SOROBAN_AUTHORIZATION (not address-bound).
 * - ADDRESS_V2 (Protocol 27, CAP-71): preimage is ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS
 *   (address-bound). Invalid on networks below Protocol 27.
 * - ADDRESS_WITH_DELEGATES (Protocol 27, CAP-71): ADDRESS_V2 with a recursive delegate tree.
 *   All nodes (top-level and every delegate at any depth) sign the same payload hash.
 *   Invalid on networks below Protocol 27.
 *
 * Signature write-back: sign() appends to the existing signature vector; a void signature
 * becomes a one-element vec. Calling sign() twice on the same node with the same key appends
 * a duplicate that the host will reject. Callers are responsible for call order; the SDK does
 * not sort signatures.
 *
 * For G-address verification the host requires signatures to be in ascending public-key order.
 * The SDK appends in call order — callers must sign in ascending key order for multi-sig nodes.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanCredentials
 * @see SorobanAuthorizedInvocation
 */
class SorobanAuthorizationEntry
{
    /**
     * Maximum delegate tree traversal depth, matching the XDR decode limit.
     *
     * Prevents stack exhaustion when walking a hostile deep tree at the application layer.
     */
    private const DELEGATE_DEPTH_LIMIT = 128;

    /**
     * @var SorobanCredentials credentials authorizing the invocation
     */
    public SorobanCredentials $credentials;

    /**
     * @var SorobanAuthorizedInvocation root of the authorized invocation tree
     */
    public SorobanAuthorizedInvocation $rootInvocation;

    /**
     * Creates a new Soroban authorization entry.
     *
     * @param SorobanCredentials $credentials the credentials authorizing the invocation
     * @param SorobanAuthorizedInvocation $rootInvocation the root invocation being authorized
     */
    public function __construct(SorobanCredentials $credentials, SorobanAuthorizedInvocation $rootInvocation)
    {
        $this->credentials    = $credentials;
        $this->rootInvocation = $rootInvocation;
    }

    /**
     * Creates SorobanAuthorizationEntry from its XDR representation.
     *
     * @param XdrSorobanAuthorizationEntry $xdr the XDR object to decode
     * @return SorobanAuthorizationEntry the decoded authorization entry
     */
    public static function fromXdr(XdrSorobanAuthorizationEntry $xdr): SorobanAuthorizationEntry
    {
        return new SorobanAuthorizationEntry(
            SorobanCredentials::fromXdr($xdr->credentials),
            SorobanAuthorizedInvocation::fromXdr($xdr->rootInvocation),
        );
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAuthorizationEntry the XDR encoded authorization entry
     */
    public function toXdr(): XdrSorobanAuthorizationEntry
    {
        return new XdrSorobanAuthorizationEntry(
            $this->credentials->toXdr(),
            $this->rootInvocation->toXdr(),
        );
    }

    /**
     * Creates SorobanAuthorizationEntry from base64-encoded XDR.
     *
     * @param string $base64Xdr the base64-encoded XDR string
     * @return SorobanAuthorizationEntry the decoded authorization entry
     * @throws InvalidArgumentException if base64 or XDR decoding fails, or the depth guard trips
     */
    public static function fromBase64Xdr(string $base64Xdr): SorobanAuthorizationEntry
    {
        $xdr = base64_decode($base64Xdr, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $xdrBuffer = new XdrBuffer($xdr);
        return SorobanAuthorizationEntry::fromXdr(XdrSorobanAuthorizationEntry::decode($xdrBuffer));
    }

    /**
     * Encodes this authorization entry as base64 XDR.
     *
     * @return string the base64-encoded XDR representation
     */
    public function toBase64Xdr(): string
    {
        return base64_encode($this->toXdr()->encode());
    }

    /**
     * Builds the XdrHashIDPreimage for this entry based on its credential arm.
     *
     * Preimage selection:
     * - ADDRESS arm: ENVELOPE_TYPE_SOROBAN_AUTHORIZATION (not address-bound, legacy preimage).
     * - ADDRESS_V2 and ADDRESS_WITH_DELEGATES arms: ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS
     *   (address-bound preimage; the address is always the top-level credential address, never a
     *   delegate address).
     *
     * signatureExpirationLedger must be set on the credentials before calling this method;
     * the network reconstructs the same preimage from the submitted credentials.
     *
     * @param Network $network the network whose passphrase is included in the preimage
     * @return XdrHashIDPreimage the preimage ready for SHA-256 hashing
     * @throws RuntimeException if the credentials are source-account or have no address credentials
     */
    public function buildPreimage(Network $network): XdrHashIDPreimage
    {
        $credType = $this->credentials->credentialType;
        $networkId = Hash::generate($network->getNetworkPassphrase());

        switch ($credType) {
            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS:
                $addressCreds = $this->credentials->addressCredentials;
                if ($addressCreds === null) {
                    throw new RuntimeException('ADDRESS arm requires addressCredentials');
                }
                $inner = new XdrHashIDPreimageSorobanAuthorization(
                    $networkId,
                    $addressCreds->nonce,
                    $addressCreds->signatureExpirationLedger,
                    $this->rootInvocation->toXdr(),
                );
                $preimage = new XdrHashIDPreimage(
                    new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION)
                );
                $preimage->sorobanAuthorization = $inner;
                return $preimage;

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2:
                $addressCreds = $this->credentials->addressCredentials;
                if ($addressCreds === null) {
                    throw new RuntimeException('ADDRESS_V2 arm requires addressCredentials');
                }
                $inner = new XdrHashIDPreimageSorobanAuthorizationWithAddress(
                    $networkId,
                    $addressCreds->nonce,
                    $addressCreds->signatureExpirationLedger,
                    $addressCreds->address->toXdr(),
                    $this->rootInvocation->toXdr(),
                );
                $preimage = new XdrHashIDPreimage(
                    new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS)
                );
                $preimage->sorobanAuthorizationWithAddress = $inner;
                return $preimage;

            case XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES:
                $withDelegates = $this->credentials->addressWithDelegates;
                if ($withDelegates === null) {
                    throw new RuntimeException('ADDRESS_WITH_DELEGATES arm requires addressWithDelegates');
                }
                $addressCreds = $withDelegates->addressCredentials;
                // The address in the preimage is always the TOP-LEVEL credential address.
                $inner = new XdrHashIDPreimageSorobanAuthorizationWithAddress(
                    $networkId,
                    $addressCreds->nonce,
                    $addressCreds->signatureExpirationLedger,
                    $addressCreds->address->toXdr(),
                    $this->rootInvocation->toXdr(),
                );
                $preimage = new XdrHashIDPreimage(
                    new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS)
                );
                $preimage->sorobanAuthorizationWithAddress = $inner;
                return $preimage;

            default:
                throw new RuntimeException(
                    'Cannot build preimage for credential type: ' . $credType
                        . ' (source-account credentials require no signature)'
                );
        }
    }

    /**
     * Signs the authorization entry with the given keypair.
     *
     * Applies to all three address arms (ADDRESS, ADDRESS_V2, ADDRESS_WITH_DELEGATES).
     * Source-account credentials throw RuntimeException.
     *
     * Expiration: when $signatureExpirationLedger is non-null it is applied to the
     * top-level credentials before the preimage is built. When null, the already-set
     * value is used unchanged. Set expiration before signing — the network reconstructs
     * the preimage from the submitted credentials including expiration.
     *
     * Routing: when $forAddress is null, the signature is written to the top-level
     * credentials. When non-null (strkey, G- or C-prefixed), the signature is written
     * to EVERY node (top-level and delegate, depth-first) whose address matches.
     * If no node matches, InvalidArgumentException is thrown. Muxed M-addresses are
     * rejected as they are not valid Soroban auth addresses.
     *
     * Append semantics: the new signature element is appended to the existing signature
     * vector. A void top-level signature is valid and is not rejected. Calling sign()
     * twice with the same key appends a duplicate; the SDK does not deduplicate or sort.
     *
     * @param KeyPair $signer the keypair to sign with
     * @param Network $network the network this authorization is for
     * @param int|null $signatureExpirationLedger when non-null, sets expiration before hashing
     * @param string|null $forAddress strkey (G- or C-prefixed) routing to a specific address node;
     *                               null signs the top-level credentials
     * @throws RuntimeException if no address credentials are found or the credential arm is unsupported
     * @throws InvalidArgumentException if $forAddress matches no node, or is a muxed M-address
     */
    public function sign(
        KeyPair $signer,
        Network $network,
        ?int    $signatureExpirationLedger = null,
        ?string $forAddress = null,
    ): void {
        $credType = $this->credentials->credentialType;

        if ($credType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT) {
            throw new RuntimeException('no soroban address credentials found');
        }

        // Reject muxed address routing targets — they are not valid Soroban auth addresses.
        if ($forAddress !== null && str_starts_with($forAddress, 'M')) {
            throw new InvalidArgumentException(
                'forAddress must be a G- or C-prefixed strkey; muxed (M-prefixed) addresses are not valid Soroban auth addresses'
            );
        }

        // Apply expiration before building the preimage.
        if ($signatureExpirationLedger !== null) {
            $addressCreds = $this->credentials->getAddressCredentials();
            if ($addressCreds !== null) {
                $addressCreds->signatureExpirationLedger = $signatureExpirationLedger;
                $this->credentials->writeBackAddressCredentials($addressCreds);
            }
        }

        // Build preimage and compute payload hash (same hash for all nodes in this entry).
        $preimage = $this->buildPreimage($network);
        $payload  = Hash::generate($preimage->encode());

        if ($forAddress === null) {
            // Sign top-level credentials.
            $this->appendSignatureToTopLevel($signer, $payload);
        } else {
            // Route to every node (top-level or delegate) whose address matches.
            $matched = $this->appendSignatureToMatchingNodes($signer, $payload, $forAddress, 0);
            if (!$matched) {
                throw new InvalidArgumentException(
                    'forAddress "' . $forAddress . '" matched no node in this authorization entry'
                );
            }
        }
    }

    /**
     * Appends a signature to the top-level credential node.
     *
     * @param KeyPair $signer the signing keypair
     * @param string $payload the payload hash (32 bytes)
     */
    private function appendSignatureToTopLevel(KeyPair $signer, string $payload): void
    {
        $addressCreds = $this->credentials->getAddressCredentials();
        if ($addressCreds === null) {
            throw new RuntimeException('no soroban address credentials found');
        }
        $sigVal = $this->buildSignatureScVal($signer, $payload);
        if ($addressCreds->signature->vec !== null) {
            $addressCreds->signature->vec[] = $sigVal;
        } else {
            $addressCreds->signature = XdrSCVal::forVec([$sigVal]);
        }
        $this->credentials->writeBackAddressCredentials($addressCreds);
    }

    /**
     * Walks the node tree depth-first, appending a signature to every node matching $targetStrkey.
     *
     * Returns true if at least one node matched.
     *
     * @param KeyPair $signer the signing keypair
     * @param string $payload the payload hash (32 bytes)
     * @param string $targetStrkey the strkey of the target address
     * @param int $depth current recursion depth for the depth guard
     * @return bool true if any node matched
     */
    private function appendSignatureToMatchingNodes(
        KeyPair $signer,
        string  $payload,
        string  $targetStrkey,
        int     $depth,
    ): bool {
        if ($depth > self::DELEGATE_DEPTH_LIMIT) {
            throw new InvalidArgumentException(
                'Delegate tree traversal depth limit (' . self::DELEGATE_DEPTH_LIMIT . ') exceeded'
            );
        }

        $matched = false;

        // Check top-level node.
        $addressCreds = $this->credentials->getAddressCredentials();
        if ($addressCreds !== null) {
            $topStrkey = $addressCreds->address->toStrKey();
            if ($topStrkey === $targetStrkey) {
                $sigVal = $this->buildSignatureScVal($signer, $payload);
                if ($addressCreds->signature->vec !== null) {
                    $addressCreds->signature->vec[] = $sigVal;
                } else {
                    $addressCreds->signature = XdrSCVal::forVec([$sigVal]);
                }
                $this->credentials->writeBackAddressCredentials($addressCreds);
                $matched = true;
            }
        }

        // Walk delegates for ADDRESS_WITH_DELEGATES arm.
        if ($this->credentials->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES) {
            $withDelegates = $this->credentials->addressWithDelegates;
            if ($withDelegates !== null) {
                foreach ($withDelegates->delegates as $delegate) {
                    if ($this->appendSignatureToDelegateNode($signer, $payload, $targetStrkey, $delegate, $depth + 1)) {
                        $matched = true;
                    }
                }
            }
        }

        return $matched;
    }

    /**
     * Walks a delegate node tree depth-first, appending a signature to every matching node.
     *
     * @param KeyPair $signer the signing keypair
     * @param string $payload the payload hash (32 bytes)
     * @param string $targetStrkey the strkey of the target address
     * @param SorobanDelegateSignature $node the current delegate node
     * @param int $depth current recursion depth
     * @return bool true if any node matched
     */
    private function appendSignatureToDelegateNode(
        KeyPair                  $signer,
        string                   $payload,
        string                   $targetStrkey,
        SorobanDelegateSignature $node,
        int                      $depth,
    ): bool {
        if ($depth > self::DELEGATE_DEPTH_LIMIT) {
            throw new InvalidArgumentException(
                'Delegate tree traversal depth limit (' . self::DELEGATE_DEPTH_LIMIT . ') exceeded'
            );
        }

        $matched = false;
        $nodeStrkey = $node->address->toStrKey();

        if ($nodeStrkey === $targetStrkey) {
            $sigVal = $this->buildSignatureScVal($signer, $payload);
            if ($node->signature->vec !== null) {
                $node->signature->vec[] = $sigVal;
            } else {
                $node->signature = XdrSCVal::forVec([$sigVal]);
            }
            $matched = true;
        }

        foreach ($node->nestedDelegates as $child) {
            if ($this->appendSignatureToDelegateNode($signer, $payload, $targetStrkey, $child, $depth + 1)) {
                $matched = true;
            }
        }

        return $matched;
    }

    /**
     * Constructs the AccountEd25519Signature XdrSCVal map entry from a keypair and payload.
     *
     * @param KeyPair $signer the signing keypair
     * @param string $payload the 32-byte payload to sign
     * @return XdrSCVal the map entry representing {public_key, signature}
     */
    private function buildSignatureScVal(KeyPair $signer, string $payload): XdrSCVal
    {
        $signatureBytes = $signer->sign($payload);
        $signature = new AccountEd25519Signature($signer->getPublicKey(), $signatureBytes);
        return $signature->toXdrSCVal();
    }

    /**
     * Constructs an ADDRESS_WITH_DELEGATES entry from an existing ADDRESS or ADDRESS_V2 entry.
     *
     * The input entry must use ADDRESS or ADDRESS_V2 credentials. A WITH_DELEGATES input throws.
     * The resulting entry:
     * - Copies the top-level address and nonce from the source entry.
     * - Sets signatureExpirationLedger to $signatureExpirationLedger.
     * - Defaults the top-level signature to void.
     * - Attaches the provided delegate descriptors, sorted by their XDR-encoded address bytes.
     * - Preserves the rootInvocation from the source entry.
     *
     * Delegate sorting: each array (top-level delegates and every nestedDelegates) is sorted
     * ascending by the complete XDR-encoded bytes of XdrSCAddress. This is not strkey order —
     * accounts (XdrSCAddressType 0) sort before contracts (XdrSCAddressType 1) in XDR encoding.
     *
     * Duplicate rejection: no two delegates in the same array may share an address. The same
     * address at different nesting levels is allowed.
     *
     * @param SorobanAuthorizationEntry $source the ADDRESS or ADDRESS_V2 entry to wrap
     * @param int $signatureExpirationLedger the expiration ledger for the resulting entry
     * @param array<SorobanDelegateDescriptor> $delegates delegate descriptors for top-level delegates
     * @return SorobanAuthorizationEntry the new ADDRESS_WITH_DELEGATES entry
     * @throws InvalidArgumentException if source is already WITH_DELEGATES, or contains duplicate addresses
     */
    public static function withDelegates(
        SorobanAuthorizationEntry  $source,
        int                        $signatureExpirationLedger,
        array                      $delegates = [],
    ): SorobanAuthorizationEntry {
        $credType = $source->credentials->credentialType;
        if ($credType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES) {
            throw new InvalidArgumentException(
                'Input entry is already ADDRESS_WITH_DELEGATES; cannot nest delegate trees'
            );
        }
        if ($credType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT) {
            throw new InvalidArgumentException(
                'Input entry uses source-account credentials; cannot attach delegates'
            );
        }

        $sourceAddressCreds = $source->credentials->getAddressCredentials();
        if ($sourceAddressCreds === null) {
            throw new InvalidArgumentException('Source entry has no address credentials');
        }

        // Build top-level address credentials with void signature.
        $topLevelCreds = new SorobanAddressCredentials(
            $sourceAddressCreds->address,
            $sourceAddressCreds->nonce,
            $signatureExpirationLedger,
            XdrSCVal::forVoid(),
        );

        // Build and sort the delegate tree.
        $builtDelegates = [];
        foreach ($delegates as $descriptor) {
            $builtDelegates[] = self::buildDelegateNode($descriptor, 0);
        }
        $builtDelegates = self::sortAndValidateDelegates($builtDelegates);

        $withDelegates = new SorobanAddressCredentialsWithDelegates($topLevelCreds, $builtDelegates);
        $newCredentials = SorobanCredentials::forAddressWithDelegates($withDelegates);

        return new SorobanAuthorizationEntry($newCredentials, $source->rootInvocation);
    }

    /**
     * Builds a SorobanDelegateSignature from a SorobanDelegateDescriptor recursively.
     *
     * @param SorobanDelegateDescriptor $descriptor the descriptor to build from
     * @param int $depth current recursion depth
     * @return SorobanDelegateSignature the built delegate node
     */
    private static function buildDelegateNode(
        SorobanDelegateDescriptor $descriptor,
        int                       $depth,
    ): SorobanDelegateSignature {
        if ($depth > self::DELEGATE_DEPTH_LIMIT) {
            throw new InvalidArgumentException(
                'Delegate tree depth limit (' . self::DELEGATE_DEPTH_LIMIT . ') exceeded during construction'
            );
        }

        $address = self::parseAddressStrkey($descriptor->address);
        $signature = $descriptor->signature ?? XdrSCVal::forVoid();

        $nested = [];
        foreach ($descriptor->nestedDelegates as $childDescriptor) {
            $nested[] = self::buildDelegateNode($childDescriptor, $depth + 1);
        }
        $nested = self::sortAndValidateDelegates($nested);

        return new SorobanDelegateSignature($address, $signature, $nested);
    }

    /**
     * Parses a G- or C-prefixed strkey into an XdrSCAddress.
     *
     * @param string $strkey the strkey to parse
     * @return \Soneso\StellarSDK\Xdr\XdrSCAddress the XDR address
     * @throws InvalidArgumentException for invalid or muxed strkeys
     */
    private static function parseAddressStrkey(string $strkey): \Soneso\StellarSDK\Xdr\XdrSCAddress
    {
        if (StrKey::isValidAccountId($strkey)) {
            return \Soneso\StellarSDK\Xdr\XdrSCAddress::forAccountId($strkey);
        }
        if (StrKey::isValidContractId($strkey)) {
            return \Soneso\StellarSDK\Xdr\XdrSCAddress::forContractId(StrKey::decodeContractIdHex($strkey));
        }
        throw new InvalidArgumentException(
            'Delegate address must be a G- or C-prefixed strkey; got: ' . $strkey
        );
    }

    /**
     * Sorts a flat delegate array ascending by XDR-encoded address bytes and checks for duplicates.
     *
     * Sorting uses the full XDR-encoded bytes of XdrSCAddress (not strkey) for lexicographic
     * comparison. This means account addresses (type 0) sort before contract addresses (type 1),
     * which is the opposite of strkey order where "C" < "G".
     *
     * @param array<SorobanDelegateSignature> $delegates the delegates to sort
     * @return array<SorobanDelegateSignature> sorted delegates
     * @throws InvalidArgumentException if any two delegates in the array share the same address
     */
    private static function sortAndValidateDelegates(array $delegates): array
    {
        if (count($delegates) <= 1) {
            return $delegates;
        }

        // Sort by XDR-encoded address bytes (ascending lexicographic order).
        usort($delegates, static function (
            SorobanDelegateSignature $a,
            SorobanDelegateSignature $b,
        ): int {
            return strcmp($a->address->encode(), $b->address->encode());
        });

        // Check for duplicates after sorting (adjacent elements have equal bytes if duplicate).
        $prevEncoded = null;
        foreach ($delegates as $delegate) {
            $encoded = $delegate->address->encode();
            if ($prevEncoded !== null && $encoded === $prevEncoded) {
                throw new InvalidArgumentException(
                    'Duplicate delegate address within the same array: '
                        . $delegate->address->toStrKey()
                );
            }
            $prevEncoded = $encoded;
        }

        return $delegates;
    }

    /**
     * Returns the credentials for this authorization entry.
     *
     * @return SorobanCredentials the authorization credentials
     */
    public function getCredentials(): SorobanCredentials
    {
        return $this->credentials;
    }

    /**
     * Sets the credentials for this authorization entry.
     *
     * @param SorobanCredentials $credentials the authorization credentials
     */
    public function setCredentials(SorobanCredentials $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * Returns the root authorized invocation.
     *
     * @return SorobanAuthorizedInvocation the root of the invocation tree
     */
    public function getRootInvocation(): SorobanAuthorizedInvocation
    {
        return $this->rootInvocation;
    }

    /**
     * Sets the root authorized invocation.
     *
     * @param SorobanAuthorizedInvocation $rootInvocation the root of the invocation tree
     */
    public function setRootInvocation(SorobanAuthorizedInvocation $rootInvocation): void
    {
        $this->rootInvocation = $rootInvocation;
    }
}
