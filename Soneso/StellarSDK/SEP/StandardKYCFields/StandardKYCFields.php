<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

/**
 * Container class for standard KYC and AML fields used in Stellar ecosystem protocols.
 *
 * This class provides a structured container for KYC (Know Your Customer) and AML (Anti-Money Laundering)
 * information exchange between entities on the Stellar network. It supports both natural person and
 * organization data collection according to SEP-09 specification.
 *
 * PRIVACY AND SECURITY WARNING:
 * This class handles highly sensitive Personally Identifiable Information (PII) and KYC data.
 * Implementers MUST ensure:
 * - Transmission only over HTTPS/TLS connections
 * - Encryption at rest for all stored KYC data
 * - Compliance with applicable data protection regulations (GDPR, CCPA, etc.)
 * - Implementation of proper access controls and audit logging
 * - Secure data retention and deletion policies
 * - Customer consent management for data collection and processing
 *
 * @package Soneso\StellarSDK\SEP\StandardKYCFields
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-09 v1.17.0 Specification
 */
class StandardKYCFields
{
    /**
     * @var NaturalPersonKYCFields|null KYC fields for natural persons (individuals)
     */
    public ?NaturalPersonKYCFields $naturalPersonKYCFields = null;

    /**
     * @var OrganizationKYCFields|null KYC fields for organizations (companies, entities)
     */
    public ?OrganizationKYCFields $organizationKYCFields = null;
}