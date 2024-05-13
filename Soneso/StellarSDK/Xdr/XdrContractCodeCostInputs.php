<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrContractCodeCostInputs
{
    public XdrExtensionPoint $ext;
    public int $nInstructions;
    public int $nFunctions;
    public int $nGlobals;
    public int $nTableEntries;
    public int $nTypes;
    public int $nDataSegments;
    public int $nElemSegments;
    public int $nImports;
    public int $nExports;
    public int $nDataSegmentBytes;

    /**
     * @param XdrExtensionPoint $ext
     * @param int $nInstructions
     * @param int $nFunctions
     * @param int $nGlobals
     * @param int $nTableEntries
     * @param int $nTypes
     * @param int $nDataSegments
     * @param int $nElemSegments
     * @param int $nImports
     * @param int $nExports
     * @param int $nDataSegmentBytes
     */
    public function __construct(
        XdrExtensionPoint $ext,
        int $nInstructions,
        int $nFunctions,
        int $nGlobals,
        int $nTableEntries,
        int $nTypes,
        int $nDataSegments,
        int $nElemSegments,
        int $nImports,
        int $nExports,
        int $nDataSegmentBytes,
    )
    {
        $this->ext = $ext;
        $this->nInstructions = $nInstructions;
        $this->nFunctions = $nFunctions;
        $this->nGlobals = $nGlobals;
        $this->nTableEntries = $nTableEntries;
        $this->nTypes = $nTypes;
        $this->nDataSegments = $nDataSegments;
        $this->nElemSegments = $nElemSegments;
        $this->nImports = $nImports;
        $this->nExports = $nExports;
        $this->nDataSegmentBytes = $nDataSegmentBytes;
    }

    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->nInstructions);
        $bytes .= XdrEncoder::unsignedInteger32($this->nFunctions);
        $bytes .= XdrEncoder::unsignedInteger32($this->nGlobals);
        $bytes .= XdrEncoder::unsignedInteger32($this->nTableEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->nTypes);
        $bytes .= XdrEncoder::unsignedInteger32($this->nDataSegments);
        $bytes .= XdrEncoder::unsignedInteger32($this->nElemSegments);
        $bytes .= XdrEncoder::unsignedInteger32($this->nImports);
        $bytes .= XdrEncoder::unsignedInteger32($this->nExports);
        $bytes .= XdrEncoder::unsignedInteger32($this->nDataSegmentBytes);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractCodeCostInputs {
        $ext = XdrExtensionPoint::decode($xdr);
        $nInstructions = $xdr->readUnsignedInteger32();
        $nFunctions = $xdr->readUnsignedInteger32();
        $nGlobals = $xdr->readUnsignedInteger32();
        $nTableEntries = $xdr->readUnsignedInteger32();
        $nTypes = $xdr->readUnsignedInteger32();
        $nDataSegments = $xdr->readUnsignedInteger32();
        $nElemSegments = $xdr->readUnsignedInteger32();
        $nImports = $xdr->readUnsignedInteger32();
        $nExports = $xdr->readUnsignedInteger32();
        $nDataSegmentBytes = $xdr->readUnsignedInteger32();

        return new XdrContractCodeCostInputs(
            $ext,
            $nInstructions,
            $nFunctions,
            $nGlobals,
            $nTableEntries,
            $nTypes,
            $nDataSegments,
            $nElemSegments,
            $nImports,
            $nExports,
            $nDataSegmentBytes,
        );
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return int
     */
    public function getNInstructions(): int
    {
        return $this->nInstructions;
    }

    /**
     * @param int $nInstructions
     */
    public function setNInstructions(int $nInstructions): void
    {
        $this->nInstructions = $nInstructions;
    }

    /**
     * @return int
     */
    public function getNFunctions(): int
    {
        return $this->nFunctions;
    }

    /**
     * @param int $nFunctions
     */
    public function setNFunctions(int $nFunctions): void
    {
        $this->nFunctions = $nFunctions;
    }

    /**
     * @return int
     */
    public function getNGlobals(): int
    {
        return $this->nGlobals;
    }

    /**
     * @param int $nGlobals
     */
    public function setNGlobals(int $nGlobals): void
    {
        $this->nGlobals = $nGlobals;
    }

    /**
     * @return int
     */
    public function getNTableEntries(): int
    {
        return $this->nTableEntries;
    }

    /**
     * @param int $nTableEntries
     */
    public function setNTableEntries(int $nTableEntries): void
    {
        $this->nTableEntries = $nTableEntries;
    }

    /**
     * @return int
     */
    public function getNTypes(): int
    {
        return $this->nTypes;
    }

    /**
     * @param int $nTypes
     */
    public function setNTypes(int $nTypes): void
    {
        $this->nTypes = $nTypes;
    }

    /**
     * @return int
     */
    public function getNDataSegments(): int
    {
        return $this->nDataSegments;
    }

    /**
     * @param int $nDataSegments
     */
    public function setNDataSegments(int $nDataSegments): void
    {
        $this->nDataSegments = $nDataSegments;
    }

    /**
     * @return int
     */
    public function getNElemSegments(): int
    {
        return $this->nElemSegments;
    }

    /**
     * @param int $nElemSegments
     */
    public function setNElemSegments(int $nElemSegments): void
    {
        $this->nElemSegments = $nElemSegments;
    }

    /**
     * @return int
     */
    public function getNImports(): int
    {
        return $this->nImports;
    }

    /**
     * @param int $nImports
     */
    public function setNImports(int $nImports): void
    {
        $this->nImports = $nImports;
    }

    /**
     * @return int
     */
    public function getNExports(): int
    {
        return $this->nExports;
    }

    /**
     * @param int $nExports
     */
    public function setNExports(int $nExports): void
    {
        $this->nExports = $nExports;
    }

    /**
     * @return int
     */
    public function getNDataSegmentBytes(): int
    {
        return $this->nDataSegmentBytes;
    }

    /**
     * @param int $nDataSegmentBytes
     */
    public function setNDataSegmentBytes(int $nDataSegmentBytes): void
    {
        $this->nDataSegmentBytes = $nDataSegmentBytes;
    }

}