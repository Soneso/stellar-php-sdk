<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Exception;

/**
 * Exception thrown when RegulatedAssetsService cannot be initialized due to missing configuration.
 *
 * This exception is thrown by the RegulatedAssetsService constructor when:
 * - Network passphrase cannot be determined from parameters or stellar.toml NETWORK_PASSPHRASE
 * - Horizon URL cannot be determined from parameters, stellar.toml HORIZON_URL, or known network defaults
 *
 * The exception message indicates which configuration value could not be found.
 *
 * Handling Recommendations:
 * - Ensure stellar.toml contains valid NETWORK_PASSPHRASE and HORIZON_URL fields
 * - Provide explicit network and horizonUrl parameters when calling constructor
 * - Verify stellar.toml is properly formatted and accessible
 *
 * Common Causes:
 * - stellar.toml missing required NETWORK_PASSPHRASE field
 * - stellar.toml using custom network without providing HORIZON_URL
 * - stellar.toml file is malformed or incomplete
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see RegulatedAssetsService::__construct()
 * @see RegulatedAssetsService::fromDomain()
 */
class SEP08IncompleteInitData extends Exception
{

}