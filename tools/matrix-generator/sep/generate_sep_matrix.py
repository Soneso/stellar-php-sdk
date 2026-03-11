#!/usr/bin/env python3
"""
Stellar PHP SDK SEP Compatibility Matrix Generator

Analyzes the Stellar PHP SDK source code and SEP specifications to generate
a compatibility matrix showing which SEP features are implemented.

Usage:
    python3 generate_sep_matrix.py [--output OUTPUT_DIR] [--sep SEP_NUMBER]

Requirements:
    Python 3.10+, stdlib only (no third-party packages needed)
"""

from __future__ import annotations

import argparse
import re
import sys
from dataclasses import dataclass, field
from datetime import datetime, timezone
from enum import Enum
from pathlib import Path
from typing import Optional


# ---------------------------------------------------------------------------
# Version detection
# ---------------------------------------------------------------------------

def get_sdk_version(sdk_root: Path) -> str:
    """Read VERSION_NR from StellarSDK.php."""
    sdk_file = sdk_root / "Soneso" / "StellarSDK" / "StellarSDK.php"
    if not sdk_file.exists():
        return "unknown"
    content = sdk_file.read_text(encoding="utf-8")
    m = re.search(r'VERSION_NR\s*=\s*["\']([^"\']+)["\']', content)
    return m.group(1) if m else "unknown"


# ---------------------------------------------------------------------------
# Data classes
# ---------------------------------------------------------------------------

class SupportStatus(Enum):
    SUPPORTED = "supported"
    NOT_SUPPORTED = "not_supported"
    PARTIAL = "partial"
    NOT_APPLICABLE = "not_applicable"
    UNKNOWN = "unknown"


@dataclass
class SEPField:
    """Represents a single field/requirement from a SEP specification."""
    name: str
    description: str
    status: SupportStatus = SupportStatus.UNKNOWN
    notes: str = ""
    sdk_class: str = ""


@dataclass
class SEPSection:
    """Represents a section within a SEP specification."""
    name: str
    description: str
    fields: list[SEPField] = field(default_factory=list)


@dataclass
class SEPInfo:
    """Metadata about a SEP."""
    number: int
    title: str
    url: str
    status: str = "Active"


@dataclass
class CompatibilityMatrix:
    """Complete compatibility matrix for a SEP."""
    sep_info: SEPInfo
    sections: list[SEPSection] = field(default_factory=list)
    overall_status: SupportStatus = SupportStatus.UNKNOWN
    sdk_version: str = ""
    generated_at: str = ""
    notes: str = ""


# ---------------------------------------------------------------------------
# SDK Analyzer  (PHP-specific)
# ---------------------------------------------------------------------------

class SDKAnalyzer:
    """Analyzes the PHP SDK source code for SEP-related classes and members."""

    def __init__(self, sdk_root: Path):
        self.sdk_root = sdk_root
        self.stellarsdk_path = sdk_root / "Soneso" / "StellarSDK"
        self._class_path_cache: dict[str, Optional[Path]] = {}
        self._members_cache: dict[str, dict[str, str]] = {}

    def search_files(self, pattern: str, file_extension: str = "php") -> set[Path]:
        """Return all files whose content matches *pattern*."""
        matched: set[Path] = set()
        regex = re.compile(pattern)
        for php_file in self.stellarsdk_path.rglob(f"*.{file_extension}"):
            try:
                if regex.search(php_file.read_text(encoding="utf-8")):
                    matched.add(php_file)
            except OSError:
                pass
        return matched

    def find_class(self, name: str) -> Optional[Path]:
        """Return the path of the file defining PHP class *name*, or None."""
        if name in self._class_path_cache:
            return self._class_path_cache[name]
        pattern = rf"class\s+{re.escape(name)}\b"
        regex = re.compile(pattern)
        for php_file in self.stellarsdk_path.rglob("*.php"):
            try:
                if regex.search(php_file.read_text(encoding="utf-8")):
                    self._class_path_cache[name] = php_file
                    return php_file
            except OSError:
                pass
        self._class_path_cache[name] = None
        return None

    def class_exists(self, name: str) -> bool:
        return self.find_class(name) is not None

    def extract_public_members(self, file_path: Path) -> dict[str, str]:
        """
        Return a mapping of member_name -> kind ('property' or 'method')
        for all public properties and methods in *file_path*.
        """
        members: dict[str, str] = {}
        try:
            content = file_path.read_text(encoding="utf-8")
        except OSError:
            return members

        # Public properties:  public [?Type] $name
        for m in re.finditer(r"public\s+(?:\??\w+\s+)?\$(\w+)", content):
            prop = m.group(1)
            if prop not in ("this",):
                members[prop] = "property"

        # Public methods:  public [static] function name(
        for m in re.finditer(r"public\s+(?:static\s+)?function\s+(\w+)\s*\(", content):
            members[m.group(1)] = "method"

        # Class constants:  const NAME = ...  or  public const NAME = ...
        for m in re.finditer(r"(?:public\s+)?const\s+(\w+)\s*=", content):
            members[m.group(1)] = "const"

        return members

    def get_class_members(self, class_name: str) -> dict[str, str]:
        """Return public members of *class_name*, or empty dict if not found."""
        if class_name in self._members_cache:
            return self._members_cache[class_name]
        path = self.find_class(class_name)
        if path is None:
            self._members_cache[class_name] = {}
            return {}
        members = self.extract_public_members(path)
        self._members_cache[class_name] = members
        return members

    def has_member(self, class_name: str, member_name: str) -> bool:
        members = self.get_class_members(class_name)
        return member_name in members

    def has_property(self, class_name: str, prop_name: str) -> bool:
        members = self.get_class_members(class_name)
        return members.get(prop_name) == "property"

    def has_method(self, class_name: str, method_name: str) -> bool:
        members = self.get_class_members(class_name)
        return members.get(method_name) == "method"

    def count_classes_in_dir(self, relative_dir: str) -> int:
        """Count PHP class definitions inside a sub-directory of the SDK."""
        dir_path = self.stellarsdk_path / relative_dir
        if not dir_path.exists():
            return 0
        count = 0
        for php_file in dir_path.rglob("*.php"):
            try:
                if re.search(r"\bclass\s+\w+", php_file.read_text(encoding="utf-8")):
                    count += 1
            except OSError:
                pass
        return count

    def list_classes_in_dir(self, relative_dir: str) -> list[str]:
        """Return names of PHP classes defined inside a sub-directory."""
        dir_path = self.stellarsdk_path / relative_dir
        if not dir_path.exists():
            return []
        names: list[str] = []
        for php_file in dir_path.rglob("*.php"):
            try:
                for m in re.finditer(r"\bclass\s+(\w+)", php_file.read_text(encoding="utf-8")):
                    names.append(m.group(1))
            except OSError:
                pass
        return names


# ---------------------------------------------------------------------------
# Base class for all SEP analyzers
# ---------------------------------------------------------------------------

class SEPAnalyzerBase:
    """Abstract base for SEP-specific analyzers."""

    sep_number: int = 0
    sep_title: str = ""
    sep_url: str = ""

    def __init__(self, sdk_analyzer: SDKAnalyzer):
        self.sdk = sdk_analyzer

    def analyze(self) -> CompatibilityMatrix:
        raise NotImplementedError

    def _make_matrix(self) -> CompatibilityMatrix:
        return CompatibilityMatrix(
            sep_info=SEPInfo(
                number=self.sep_number,
                title=self.sep_title,
                url=self.sep_url,
            ),
            sdk_version=get_sdk_version(self.sdk.sdk_root),
            generated_at=datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC"),
        )

    def _overall(self, sections: list[SEPSection]) -> SupportStatus:
        """Compute overall status from section fields."""
        all_fields = [f for s in sections for f in s.fields]
        if not all_fields:
            return SupportStatus.UNKNOWN
        applicable = [
            f for f in all_fields if f.status != SupportStatus.NOT_APPLICABLE
        ]
        if not applicable:
            return SupportStatus.NOT_APPLICABLE
        if all(f.status == SupportStatus.SUPPORTED for f in applicable):
            return SupportStatus.SUPPORTED
        if all(f.status == SupportStatus.NOT_SUPPORTED for f in applicable):
            return SupportStatus.NOT_SUPPORTED
        return SupportStatus.PARTIAL

    def _field(
        self,
        name: str,
        description: str,
        supported: bool,
        notes: str = "",
        sdk_class: str = "",
    ) -> SEPField:
        return SEPField(
            name=name,
            description=description,
            status=SupportStatus.SUPPORTED if supported else SupportStatus.NOT_SUPPORTED,
            notes=notes,
            sdk_class=sdk_class,
        )

    def _na(self, name: str, description: str, notes: str = "") -> SEPField:
        return SEPField(
            name=name,
            description=description,
            status=SupportStatus.NOT_APPLICABLE,
            notes=notes,
        )

    def _check_properties(
        self,
        class_name: str,
        field_map: list[tuple[str, str, str, bool]],
    ) -> list[SEPField]:
        """Check a list of spec fields against a PHP class.

        field_map items: (spec_name, php_property, description, required)
        """
        members = self.sdk.get_class_members(class_name)
        fields: list[SEPField] = []
        for spec_name, php_prop, desc, required in field_map:
            found = php_prop in members
            note = f"{'Required. ' if required else ''}{class_name}.${php_prop}"
            fields.append(self._field(spec_name, desc, found, sdk_class=note if found else ""))
        return fields


# ===========================================================================
# SEP-01: Stellar TOML
# ===========================================================================

class SEP01Analyzer(SEPAnalyzerBase):
    sep_number = 1
    sep_title = "Stellar Info File"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        toml_exists = self.sdk.class_exists("StellarToml")
        general_exists = self.sdk.class_exists("GeneralInformation")
        docs_exists = self.sdk.class_exists("Documentation")
        poc_exists = self.sdk.class_exists("PointOfContact")
        currency_exists = self.sdk.class_exists("Currency")
        validator_exists = self.sdk.class_exists("Validator")

        # --- Fetch section ---
        fetch_section = SEPSection(
            name="Fetching",
            description="Fetching stellar.toml files",
        )
        fetch_section.fields.append(self._field(
            "StellarToml.fromDomain",
            "Fetch stellar.toml from a domain",
            self.sdk.has_method("StellarToml", "fromDomain"),
            sdk_class="StellarToml",
        ))
        fetch_section.fields.append(self._field(
            "StellarToml.currencyFromUrl",
            "Parse a Currency from a linked TOML URL",
            self.sdk.has_method("StellarToml", "currencyFromUrl"),
            sdk_class="StellarToml",
        ))

        # --- General Information ---
        general_section = SEPSection(
            name="General Information",
            description="GeneralInformation class fields",
        )
        general_fields = [
            ("version", "TOML file version"),
            ("networkPassphrase", "Network passphrase"),
            ("federationServer", "Federation server URL"),
            ("authServer", "Authentication server URL"),
            ("transferServer", "Transfer server URL"),
            ("transferServerSep24", "SEP-24 transfer server URL"),
            ("kYCServer", "KYC server URL"),
            ("webAuthEndpoint", "Web auth endpoint"),
            ("webAuthForContractsEndpoint", "Web auth for contracts endpoint"),
            ("signingKey", "Signing key"),
            ("horizonUrl", "Horizon URL"),
            ("accounts", "List of accounts"),
            ("uriRequestSigningKey", "URI request signing key"),
            ("directPaymentServer", "SEP-31 direct payment server"),
            ("anchorQuoteServer", "SEP-38 anchor quote server"),
            ("webAuthContractId", "SEP-45 web auth contract ID"),
        ]
        for prop, desc in general_fields:
            general_section.fields.append(self._field(
                f"GeneralInformation.{prop}", desc,
                general_exists and self.sdk.has_member("GeneralInformation", prop),
                sdk_class="GeneralInformation",
            ))

        # --- Documentation ---
        docs_section = SEPSection(
            name="Documentation",
            description="Documentation class fields",
        )
        doc_fields = [
            ("orgName", "Organization name"),
            ("orgDBA", "Organization DBA"),
            ("orgUrl", "Organization URL"),
            ("orgLogo", "Organization logo URL"),
            ("orgDescription", "Organization description"),
            ("orgPhysicalAddress", "Physical address"),
            ("orgPhysicalAddressAttestation", "Physical address attestation"),
            ("orgPhoneNumber", "Phone number"),
            ("orgPhoneNumberAttestation", "Phone number attestation"),
            ("orgKeybase", "Keybase handle"),
            ("orgTwitter", "Twitter handle"),
            ("orgGithub", "GitHub handle"),
            ("orgOfficialEmail", "Official email"),
            ("orgSupportEmail", "Support email"),
            ("orgLicensingAuthority", "Licensing authority"),
            ("orgLicenseType", "License type"),
            ("orgLicenseNumber", "License number"),
        ]
        for prop, desc in doc_fields:
            docs_section.fields.append(self._field(
                f"Documentation.{prop}", desc,
                docs_exists and self.sdk.has_member("Documentation", prop),
                sdk_class="Documentation",
            ))

        # --- Principals ---
        poc_section = SEPSection(
            name="Point of Contact",
            description="PointOfContact class fields",
        )
        poc_fields = [
            ("name", "Contact name"),
            ("email", "Contact email"),
            ("keybase", "Keybase handle"),
            ("telegram", "Telegram handle"),
            ("twitter", "Twitter handle"),
            ("github", "GitHub handle"),
            ("idPhotoHash", "ID photo hash"),
            ("verificationPhotoHash", "Verification photo hash"),
        ]
        for prop, desc in poc_fields:
            poc_section.fields.append(self._field(
                f"PointOfContact.{prop}", desc,
                poc_exists and self.sdk.has_member("PointOfContact", prop),
                sdk_class="PointOfContact",
            ))

        # --- Currency ---
        currency_section = SEPSection(
            name="Currency",
            description="Currency class fields",
        )
        currency_fields = [
            ("code", "Asset code"),
            ("codeTemplate", "Asset code template"),
            ("issuer", "Asset issuer"),
            ("status", "Asset status"),
            ("displayDecimals", "Display decimals"),
            ("name", "Asset name"),
            ("desc", "Asset description"),
            ("conditions", "Asset conditions"),
            ("image", "Asset image URL"),
            ("fixedNumber", "Fixed number of assets"),
            ("maxNumber", "Maximum number of assets"),
            ("isUnlimited", "Is asset supply unlimited"),
            ("isAssetAnchored", "Is asset anchored"),
            ("anchorAssetType", "Anchor asset type"),
            ("anchorAsset", "Anchor asset"),
            ("attestationOfReserve", "Reserve attestation URL"),
            ("redemptionInstructions", "Redemption instructions"),
            ("collateralAddresses", "Collateral addresses"),
            ("collateralAddressMessages", "Collateral address messages"),
            ("collateralAddressSignatures", "Collateral address signatures"),
            ("regulated", "Is regulated asset"),
            ("approvalServer", "Approval server URL"),
            ("approvalCriteria", "Approval criteria"),
            ("contract", "Token contract ID"),
        ]
        for prop, desc in currency_fields:
            currency_section.fields.append(self._field(
                f"Currency.{prop}", desc,
                currency_exists and self.sdk.has_member("Currency", prop),
                sdk_class="Currency",
            ))

        # --- Validator ---
        validator_section = SEPSection(
            name="Validator",
            description="Validator class fields",
        )
        validator_fields = [
            ("alias", "Validator alias"),
            ("displayName", "Validator display name"),
            ("publicKey", "Validator public key"),
            ("host", "Validator host"),
            ("history", "Validator history URL"),
        ]
        for prop, desc in validator_fields:
            validator_section.fields.append(self._field(
                f"Validator.{prop}", desc,
                validator_exists and self.sdk.has_member("Validator", prop),
                sdk_class="Validator",
            ))

        sections = [
            fetch_section, general_section, docs_section,
            poc_section, currency_section, validator_section,
        ]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-02: Federation Protocol
# ===========================================================================

class SEP02Analyzer(SEPAnalyzerBase):
    sep_number = 2
    sep_title = "Federation Protocol"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        fed_exists = self.sdk.class_exists("Federation")
        resp_exists = self.sdk.class_exists("FederationResponse")

        lookup_section = SEPSection(
            name="Lookup",
            description="Federation address lookup methods",
        )
        lookup_section.fields.extend([
            self._field("Federation.resolveStellarAddress",
                        "Resolve stellar address (name*domain) to account ID",
                        fed_exists and self.sdk.has_method("Federation", "resolveStellarAddress"),
                        sdk_class="Federation"),
            self._field("Federation.resolveStellarAccountId",
                        "Resolve account ID to federation info",
                        fed_exists and self.sdk.has_method("Federation", "resolveStellarAccountId"),
                        sdk_class="Federation"),
            self._field("Federation.resolveStellarTransactionId",
                        "Resolve transaction ID to federation info",
                        fed_exists and self.sdk.has_method("Federation", "resolveStellarTransactionId"),
                        sdk_class="Federation"),
            self._field("Federation.resolveForward",
                        "Forward-type federation lookup",
                        fed_exists and self.sdk.has_method("Federation", "resolveForward"),
                        sdk_class="Federation"),
        ])

        response_section = SEPSection(
            name="Response",
            description="FederationResponse accessor methods",
        )
        response_fields = [
            ("getStellarAddress", "Get stellar address"),
            ("getAccountId", "Get account ID"),
            ("getMemoType", "Get memo type"),
            ("getMemo", "Get memo value"),
        ]
        for method, desc in response_fields:
            response_section.fields.append(self._field(
                f"FederationResponse.{method}", desc,
                resp_exists and self.sdk.has_method("FederationResponse", method),
                sdk_class="FederationResponse",
            ))

        sections = [lookup_section, response_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-05: Key Derivation Methods for Stellar Keys
# ===========================================================================

class SEP05Analyzer(SEPAnalyzerBase):
    sep_number = 5
    sep_title = "Key Derivation Methods for Stellar Keys"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        mnemonic_exists = self.sdk.class_exists("Mnemonic")
        hdnode_exists = self.sdk.class_exists("HDNode")

        # --- BIP-39 Mnemonic ---
        mnemonic_section = SEPSection(
            name="BIP-39 Mnemonic Features",
            description="Mnemonic phrase generation, validation, and seed derivation",
        )
        mnemonic_section.fields.extend([
            self._field("generate12WordsMnemonic",
                        "Generate 12-word BIP-39 mnemonic phrase",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "generate12WordsMnemonic"),
                        sdk_class="Mnemonic.generate12WordsMnemonic()"),
            self._field("generate15WordsMnemonic",
                        "Generate 15-word BIP-39 mnemonic phrase",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "generate15WordsMnemonic"),
                        sdk_class="Mnemonic.generate15WordsMnemonic()"),
            self._field("generate24WordsMnemonic",
                        "Generate 24-word BIP-39 mnemonic phrase",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "generate24WordsMnemonic"),
                        sdk_class="Mnemonic.generate24WordsMnemonic()"),
            self._field("mnemonicFromWords",
                        "Validate BIP-39 mnemonic phrase (word list and checksum)",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "mnemonicFromWords"),
                        sdk_class="Mnemonic.mnemonicFromWords()"),
            self._field("generateSeed",
                        "Convert BIP-39 mnemonic to seed using PBKDF2",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "generateSeed"),
                        sdk_class="Mnemonic.generateSeed()"),
            self._field("passphrase_support",
                        "Support optional BIP-39 passphrase (25th word)",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "generateSeed"),
                        sdk_class="Mnemonic.generateSeed($passphrase)"),
        ])

        # --- BIP-32 HD Key Derivation ---
        hd_section = SEPSection(
            name="BIP-32 Key Derivation",
            description="Hierarchical Deterministic key derivation using Ed25519",
        )
        hd_section.fields.extend([
            self._field("master_key_generation",
                        "Generate master key from seed (Ed25519 curve)",
                        hdnode_exists and self.sdk.has_method("HDNode", "newMasterNode"),
                        sdk_class="HDNode.newMasterNode()"),
            self._field("child_key_derivation",
                        "Derive child keys from parent keys",
                        hdnode_exists and self.sdk.has_method("HDNode", "derive"),
                        sdk_class="HDNode.derive()"),
            self._field("path_derivation",
                        "Derive key at BIP-44 path string",
                        hdnode_exists and self.sdk.has_method("HDNode", "derivePath"),
                        sdk_class="HDNode.derivePath()"),
            self._field("stellar_derivation_path",
                        "Stellar BIP-44 path m/44'/148'/account'",
                        mnemonic_exists and self.sdk.has_method("Mnemonic", "m44148keyHex"),
                        sdk_class="Mnemonic.m44148keyHex()"),
        ])

        # --- Language Support ---
        wordlist_section = SEPSection(
            name="Language Support",
            description="BIP-39 word list languages",
        )
        wordlist_dir = (self.sdk.sdk_root / "Soneso" / "StellarSDK"
                        / "SEP" / "Derivation" / "wordlists")
        languages = [
            ("english", "English"),
            ("chinese_simplified", "Chinese Simplified"),
            ("chinese_traditional", "Chinese Traditional"),
            ("french", "French"),
            ("italian", "Italian"),
            ("japanese", "Japanese"),
            ("korean", "Korean"),
            ("spanish", "Spanish"),
            ("malay", "Malay"),
        ]
        for lang_file, lang_name in languages:
            exists = (wordlist_dir / f"{lang_file}.txt").is_file()
            wordlist_section.fields.append(self._field(
                lang_file, f"{lang_name} BIP-39 word list",
                exists,
                sdk_class=f"{lang_file}.txt",
            ))

        sections = [mnemonic_section, hd_section, wordlist_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-06: Anchor/Client Interoperability
# ===========================================================================

class SEP06Analyzer(SEPAnalyzerBase):
    sep_number = 6
    sep_title = "Deposit and Withdrawal API"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Deposit Endpoints --
        svc = "TransferServerService"
        svc_members = self.sdk.get_class_members(svc)

        dep_ep = SEPSection(name="Deposit Endpoints", description="Deposit API endpoints")
        dep_ep.fields = [
            self._field("deposit", "GET /deposit", "deposit" in svc_members, sdk_class=f"{svc}.deposit()"),
            self._field("deposit_exchange", "GET /deposit-exchange", "depositExchange" in svc_members, sdk_class=f"{svc}.depositExchange()"),
        ]
        sections.append(dep_ep)

        # -- Deposit Request Parameters --
        dep_params = SEPSection(name="Deposit Request Parameters", description="Parameters for GET /deposit")
        dep_params.fields = self._check_properties("DepositRequest", [
            ("asset_code",                  "assetCode",                "Code of the on-chain asset the user wants to receive", True),
            ("account",                     "account",                  "Stellar account ID of the user", True),
            ("memo_type",                   "memoType",                 "Type of memo to attach to transaction", False),
            ("memo",                        "memo",                     "Value of memo to attach to transaction", False),
            ("email_address",               "emailAddress",             "Email address of the user", False),
            ("type",                        "type",                     "Type of deposit method", False),
            ("wallet_name",                 "walletName",               "Name of the wallet", False),
            ("wallet_url",                  "walletUrl",                "URL of the wallet", False),
            ("lang",                        "lang",                     "Language code (ISO 639-1)", False),
            ("on_change_callback",          "onChangeCallback",         "Callback URL for status changes", False),
            ("amount",                      "amount",                   "Amount of asset to receive", False),
            ("country_code",                "countryCode",              "Country code (ISO 3166-1 alpha-3)", False),
            ("claimable_balance_supported", "claimableBalanceSupported", "Whether client supports claimable balances", False),
            ("customer_id",                 "customerId",               "SEP-12 customer ID", False),
            ("location_id",                 "locationId",               "Physical location ID for cash pickup", False),
        ])
        sections.append(dep_params)

        # -- Deposit Response Fields --
        dep_resp = SEPSection(name="Deposit Response Fields", description="Fields returned by GET /deposit")
        dep_resp.fields = self._check_properties("DepositResponse", [
            ("how",         "how",        "Instructions for how to deposit", True),
            ("id",          "id",         "Persistent transaction identifier", False),
            ("eta",         "eta",        "Estimated seconds until deposit completes", False),
            ("min_amount",  "minAmount",  "Minimum deposit amount", False),
            ("max_amount",  "maxAmount",  "Maximum deposit amount", False),
            ("fee_fixed",   "feeFixed",   "Fixed fee for deposit", False),
            ("fee_percent", "feePercent", "Percentage fee for deposit", False),
            ("extra_info",  "extraInfo",  "Additional information about the deposit", False),
        ])
        sections.append(dep_resp)

        # -- Withdraw Endpoints --
        with_ep = SEPSection(name="Withdraw Endpoints", description="Withdrawal API endpoints")
        with_ep.fields = [
            self._field("withdraw", "GET /withdraw", "withdraw" in svc_members, sdk_class=f"{svc}.withdraw()"),
            self._field("withdraw_exchange", "GET /withdraw-exchange", "withdrawExchange" in svc_members, sdk_class=f"{svc}.withdrawExchange()"),
        ]
        sections.append(with_ep)

        # -- Withdraw Request Parameters --
        with_params = SEPSection(name="Withdraw Request Parameters", description="Parameters for GET /withdraw")
        with_params.fields = self._check_properties("WithdrawRequest", [
            ("asset_code",       "assetCode",       "Code of the on-chain asset to send", True),
            ("type",             "type",             "Type of withdrawal method", True),
            ("dest",             "dest",             "Destination for withdrawal", False),
            ("dest_extra",       "destExtra",        "Extra info for destination", False),
            ("account",          "account",          "Stellar account ID of the user", False),
            ("memo",             "memo",             "Memo to identify the user", False),
            ("memo_type",        "memoType",         "Type of memo", False),
            ("wallet_name",      "walletName",       "Name of the wallet", False),
            ("wallet_url",       "walletUrl",        "URL of the wallet", False),
            ("lang",             "lang",             "Language code (ISO 639-1)", False),
            ("on_change_callback", "onChangeCallback", "Callback URL for status changes", False),
            ("amount",           "amount",           "Amount of asset to send", False),
            ("country_code",     "countryCode",      "Country code (ISO 3166-1 alpha-3)", False),
            ("refund_memo",      "refundMemo",       "Memo for refund transaction", False),
            ("refund_memo_type", "refundMemoType",   "Type of refund memo", False),
            ("customer_id",      "customerId",       "SEP-12 customer ID", False),
            ("location_id",      "locationId",       "Physical location ID for cash pickup", False),
        ])
        sections.append(with_params)

        # -- Withdraw Response Fields --
        with_resp = SEPSection(name="Withdraw Response Fields", description="Fields returned by GET /withdraw")
        with_resp.fields = self._check_properties("WithdrawResponse", [
            ("account_id",  "accountId",  "Stellar account to send assets to", True),
            ("memo_type",   "memoType",   "Type of memo to attach", False),
            ("memo",        "memo",       "Value of memo to attach", False),
            ("id",          "id",         "Persistent transaction identifier", True),
            ("eta",         "eta",        "Estimated seconds until withdrawal completes", False),
            ("min_amount",  "minAmount",  "Minimum withdrawal amount", False),
            ("max_amount",  "maxAmount",  "Maximum withdrawal amount", False),
            ("fee_fixed",   "feeFixed",   "Fixed fee for withdrawal", False),
            ("fee_percent", "feePercent", "Percentage fee for withdrawal", False),
            ("extra_info",  "extraInfo",  "Additional information about the withdrawal", False),
        ])
        sections.append(with_resp)

        # -- Info Endpoint --
        info_ep = SEPSection(name="Info Endpoint", description="Anchor capabilities and asset information")
        info_ep.fields = [
            self._field("info_endpoint", "GET /info", "info" in svc_members, sdk_class=f"{svc}.info()"),
        ]
        sections.append(info_ep)

        # -- Info Response Fields --
        info_resp = SEPSection(name="Info Response Fields", description="Fields returned by GET /info")
        info_resp.fields = self._check_properties("InfoResponse", [
            ("deposit",           "depositAssets",          "Map of asset codes to deposit information", True),
            ("withdraw",          "withdrawAssets",         "Map of asset codes to withdraw information", True),
            ("deposit-exchange",  "depositExchangeAssets",  "Map of asset codes to deposit-exchange information", False),
            ("withdraw-exchange", "withdrawExchangeAssets", "Map of asset codes to withdraw-exchange information", False),
            ("fee",               "feeInfo",                "Fee endpoint information", False),
            ("transactions",      "transactionsInfo",       "Transaction history endpoint information", False),
            ("transaction",       "transactionInfo",        "Single transaction endpoint information", False),
            ("features",          "featureFlags",           "Feature flags supported by the anchor", False),
        ])
        sections.append(info_resp)

        # -- Fee Endpoint --
        fee_ep = SEPSection(name="Fee Endpoint", description="Fee calculation (deprecated)")
        fee_ep.fields = [
            self._field("fee_endpoint", "GET /fee", "fee" in svc_members, sdk_class=f"{svc}.fee()"),
        ]
        sections.append(fee_ep)

        # -- Transaction Endpoints --
        tx_ep = SEPSection(name="Transaction Endpoints", description="Transaction query and update endpoints")
        tx_ep.fields = [
            self._field("transactions", "GET /transactions", "transactions" in svc_members, sdk_class=f"{svc}.transactions()"),
            self._field("transaction", "GET /transaction", "transaction" in svc_members, sdk_class=f"{svc}.transaction()"),
            self._field("patch_transaction", "PATCH /transaction", "patchTransaction" in svc_members, sdk_class=f"{svc}.patchTransaction()"),
        ]
        sections.append(tx_ep)

        # -- Transaction Fields --
        tx_fields = SEPSection(name="Transaction Fields", description="Fields in the transaction object")
        tx_fields.fields = self._check_properties("AnchorTransaction", [
            ("id",                       "id",                      "Unique transaction identifier", True),
            ("kind",                     "kind",                    "Kind of transaction (deposit, withdrawal, etc.)", True),
            ("status",                   "status",                  "Current status of the transaction", True),
            ("started_at",               "startedAt",               "When transaction was created (ISO 8601)", True),
            ("status_eta",               "statusEta",               "Estimated seconds until status changes", False),
            ("more_info_url",            "moreInfoUrl",             "URL with more transaction info", False),
            ("amount_in",                "amountIn",                "Amount received by anchor", False),
            ("amount_in_asset",          "amountInAsset",           "Asset of amount_in", False),
            ("amount_out",               "amountOut",               "Amount sent by anchor to user", False),
            ("amount_out_asset",         "amountOutAsset",          "Asset of amount_out", False),
            ("amount_fee",               "amountFee",               "Total fee charged", False),
            ("amount_fee_asset",         "amountFeeAsset",          "Asset of amount_fee", False),
            ("fee_details",              "feeDetails",              "Detailed fee breakdown", False),
            ("quote_id",                 "quoteId",                 "SEP-38 quote ID used", False),
            ("from",                     "from",                    "Stellar account that initiated the transaction", False),
            ("to",                       "to",                      "Stellar account receiving the transaction", False),
            ("deposit_memo",             "depositMemo",             "Memo for deposit transaction", False),
            ("deposit_memo_type",        "depositMemoType",         "Type of deposit memo", False),
            ("withdraw_anchor_account",  "withdrawAnchorAccount",   "Anchor's Stellar account for withdrawal", False),
            ("withdraw_memo",            "withdrawMemo",            "Memo for withdrawal transaction", False),
            ("withdraw_memo_type",       "withdrawMemoType",        "Type of withdrawal memo", False),
            ("updated_at",               "updatedAt",               "When transaction was last updated", False),
            ("completed_at",             "completedAt",             "When transaction completed (ISO 8601)", False),
            ("user_action_required_by",  "userActionRequiredBy",    "Deadline for user action", False),
            ("stellar_transaction_id",   "stellarTransactionId",    "Hash of the Stellar transaction", False),
            ("external_transaction_id",  "externalTransactionId",   "Identifier from external system", False),
            ("message",                  "message",                 "Human-readable message about transaction", False),
            ("refunded",                 "refunded",                "Whether transaction was refunded", False),
            ("refunds",                  "refunds",                 "Refund information if applicable", False),
            ("required_info_message",    "requiredInfoMessage",     "Message about required info updates", False),
            ("required_info_updates",    "requiredInfoUpdates",     "Fields needing updates from user", False),
            ("instructions",             "instructions",            "Deposit instructions for the user", False),
            ("claimable_balance_id",     "claimableBalanceId",      "Claimable balance ID if applicable", False),
        ])
        sections.append(tx_fields)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-07: URI Scheme
# ===========================================================================

class SEP07Analyzer(SEPAnalyzerBase):
    sep_number = 7
    sep_title = "URI Scheme to facilitate delegated signing"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        uri_exists = self.sdk.class_exists("URIScheme")
        members = self.sdk.get_class_members("URIScheme") if uri_exists else set()

        def _has(name: str) -> bool:
            return name in members

        # --- URI Operations ---
        ops_section = SEPSection(
            name="URI Operations",
            description="Generate and submit SEP-7 URIs",
        )
        ops_section.fields.extend([
            self._field("tx", "Transaction signing operation (web+stellar:tx)",
                        _has("generateSignTransactionURI"),
                        sdk_class="URIScheme.generateSignTransactionURI()"),
            self._field("pay", "Payment request operation (web+stellar:pay)",
                        _has("generatePayOperationURI"),
                        sdk_class="URIScheme.generatePayOperationURI()"),
        ])

        # --- TX Operation Parameters ---
        tx_section = SEPSection(
            name="TX Operation Parameters",
            description="Parameters for the tx (sign transaction) operation",
        )
        tx_params = [
            ("xdr", "xdrParameterName", "Base64 encoded TransactionEnvelope XDR", True),
            ("replace", "replaceParameterName", "Field replacement using Txrep (SEP-11) format", False),
            ("callback", "callbackParameterName", "URL for transaction submission callback", False),
            ("pubkey", "publicKeyParameterName", "Public key specifying which key should sign", False),
            ("chain", "chainParameterName", "Nested SEP-7 URL for transaction chaining", False),
        ]
        for spec_name, const_name, desc, required in tx_params:
            note = f"{'Required. ' if required else ''}URIScheme::{const_name}"
            tx_section.fields.append(self._field(
                spec_name, desc, _has(const_name), sdk_class=note if _has(const_name) else "",
            ))

        # --- PAY Operation Parameters ---
        pay_section = SEPSection(
            name="PAY Operation Parameters",
            description="Parameters for the pay (payment request) operation",
        )
        pay_params = [
            ("destination", "destinationParameterName", "Stellar account ID or payment address", True),
            ("amount", "amountParameterName", "Amount to send", False),
            ("asset_code", "assetCodeParameterName", "Asset code (e.g. USD, BTC)", False),
            ("asset_issuer", "assetIssuerParameterName", "Stellar account ID of asset issuer", False),
            ("memo", "memoParameterName", "Memo value to attach to transaction", False),
            ("memo_type", "memoTypeParameterName", "Type of memo (MEMO_TEXT, MEMO_ID, MEMO_HASH, MEMO_RETURN)", False),
        ]
        for spec_name, const_name, desc, required in pay_params:
            note = f"{'Required. ' if required else ''}URIScheme::{const_name}"
            pay_section.fields.append(self._field(
                spec_name, desc, _has(const_name), sdk_class=note if _has(const_name) else "",
            ))

        # --- Common Parameters ---
        common_section = SEPSection(
            name="Common Parameters",
            description="Parameters shared by tx and pay operations",
        )
        common_params = [
            ("msg", "messageParameterName", "User-facing message (max 300 chars)", False),
            ("network_passphrase", "networkPassphraseParameterName", "Network passphrase for the transaction", False),
            ("origin_domain", "originDomainParameterName", "FQDN of originating service", False),
            ("signature", "signatureParameterName", "Signature of the URI for verification", False),
        ]
        for spec_name, const_name, desc, required in common_params:
            note = f"URIScheme::{const_name}"
            common_section.fields.append(self._field(
                spec_name, desc, _has(const_name), sdk_class=note if _has(const_name) else "",
            ))

        # --- Signature Features ---
        sig_section = SEPSection(
            name="Signature Features",
            description="URI signing and verification",
        )
        sig_section.fields.extend([
            self._field("sign_uri", "Sign a SEP-7 URI with a keypair",
                        _has("signURI"),
                        sdk_class="URIScheme.signURI()"),
            self._field("verify_signed_uri", "Verify URI signature via origin domain TOML",
                        _has("checkUIRSchemeIsValid"),
                        sdk_class="URIScheme.checkUIRSchemeIsValid()"),
            self._field("sign_and_submit", "Sign and submit a URI-initiated transaction",
                        _has("signAndSubmitTransaction"),
                        sdk_class="URIScheme.signAndSubmitTransaction()"),
        ])

        sections = [ops_section, tx_section, pay_section, common_section, sig_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-08: Regulated Assets
# ===========================================================================

class SEP08Analyzer(SEPAnalyzerBase):
    sep_number = 8
    sep_title = "Regulated Assets"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Methods --
        svc = "RegulatedAssetsService"
        svc_members = self.sdk.get_class_members(svc)

        svc_section = SEPSection(name="Service Methods", description="RegulatedAssetsService core methods")
        svc_section.fields = [
            self._field("postTransaction", "Submit a transaction to the approval server",
                        "postTransaction" in svc_members, sdk_class=f"{svc}.postTransaction()"),
            self._field("postAction", "Follow up an action_required response with POST",
                        "postAction" in svc_members, sdk_class=f"{svc}.postAction()"),
            self._field("authorizationRequired", "Check if a regulated asset requires authorization",
                        "authorizationRequired" in svc_members, sdk_class=f"{svc}.authorizationRequired()"),
            self._field("fromDomain", "Factory: create service from stellar.toml domain",
                        "fromDomain" in svc_members, sdk_class=f"{svc}::fromDomain()"),
        ]
        sections.append(svc_section)

        # -- Regulated Asset Fields --
        asset_section = SEPSection(name="Regulated Asset Fields", description="RegulatedAsset class properties")
        asset_section.fields = self._check_properties("RegulatedAsset", [
            ("approval_server",   "approvalServer",   "URL of the approval server for this asset", True),
            ("approval_criteria", "approvalCriteria",  "Human-readable criteria for approval", False),
        ])
        sections.append(asset_section)

        # -- Success Response Fields --
        success_section = SEPSection(name="Success Response Fields", description="POST /tx_approve → status=success")
        success_section.fields = self._check_properties("SEP08PostTransactionSuccess", [
            ("tx",      "tx",      "Signed transaction envelope XDR (base64)", True),
            ("message", "message", "Human-readable information for the user", False),
        ])
        sections.append(success_section)

        # -- Revised Response Fields --
        revised_section = SEPSection(name="Revised Response Fields", description="POST /tx_approve → status=revised")
        revised_section.fields = self._check_properties("SEP08PostTransactionRevised", [
            ("tx",      "tx",      "Revised and signed transaction envelope XDR (base64)", True),
            ("message", "message", "Explanation of the modifications made", True),
        ])
        sections.append(revised_section)

        # -- Pending Response Fields --
        pending_section = SEPSection(name="Pending Response Fields", description="POST /tx_approve → status=pending")
        pending_section.fields = self._check_properties("SEP08PostTransactionPending", [
            ("timeout", "timeout", "Milliseconds to wait before resubmitting", True),
            ("message", "message", "Human-readable information for the user", False),
        ])
        sections.append(pending_section)

        # -- Action Required Response Fields --
        action_req_section = SEPSection(name="Action Required Response Fields", description="POST /tx_approve → status=action_required")
        action_req_section.fields = self._check_properties("SEP08PostTransactionActionRequired", [
            ("message",       "message",      "Information regarding the action required", True),
            ("action_url",    "actionUrl",    "URL for the user to complete the action", True),
            ("action_method", "actionMethod", "GET or POST request method for action_url", False),
            ("action_fields", "actionFields", "SEP-9 fields the client may provide", False),
        ])
        sections.append(action_req_section)

        # -- Rejected Response Fields --
        rejected_section = SEPSection(name="Rejected Response Fields", description="POST /tx_approve → status=rejected")
        rejected_section.fields = self._check_properties("SEP08PostTransactionRejected", [
            ("error", "error", "Explanation of why the transaction was rejected", True),
        ])
        sections.append(rejected_section)

        # -- Action Done Response --
        done_section = SEPSection(name="Action Done Response", description="POST action_url → result=no_further_action_required")
        done_section.fields = [
            self._field("SEP08PostActionDone", "Action done response class",
                        self.sdk.class_exists("SEP08PostActionDone"), sdk_class="SEP08PostActionDone"),
        ]
        sections.append(done_section)

        # -- Action Next URL Response Fields --
        next_url_section = SEPSection(name="Action Next URL Response Fields", description="POST action_url → result=follow_next_url")
        next_url_section.fields = self._check_properties("SEP08PostActionNextUrl", [
            ("next_url", "nextUrl",  "URL for the user to complete additional action", True),
            ("message",  "message",  "Information regarding the further action required", False),
        ])
        sections.append(next_url_section)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-09: Standard KYC Fields
# ===========================================================================

class SEP09Analyzer(SEPAnalyzerBase):
    sep_number = 9
    sep_title = "Standard KYC Fields"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        natural_exists = self.sdk.class_exists("NaturalPersonKYCFields")
        org_exists = self.sdk.class_exists("OrganizationKYCFields")
        financial_exists = self.sdk.class_exists("FinancialAccountKYCFields")
        card_exists = self.sdk.class_exists("CardKYCFields")

        natural_section = SEPSection(
            name="Natural Person Fields",
            description="Natural person KYC field coverage",
        )
        natural_fields = [
            ("lastName", "Last name"),
            ("firstName", "First name"),
            ("additionalName", "Additional name"),
            ("addressCountryCode", "Address country code"),
            ("stateOrProvince", "State or province"),
            ("city", "City"),
            ("postalCode", "Postal code"),
            ("address", "Street address"),
            ("mobileNumber", "Mobile number"),
            ("mobileNumberFormat", "Mobile number format"),
            ("emailAddress", "Email address"),
            ("birthDate", "Birth date"),
            ("birthPlace", "Birth place"),
            ("birthCountryCode", "Birth country code"),
            ("taxId", "Tax ID"),
            ("taxIdName", "Tax ID name"),
            ("occupation", "Occupation"),
            ("employerName", "Employer name"),
            ("employerAddress", "Employer address"),
            ("languageCode", "Language code"),
            ("idType", "ID type"),
            ("idCountryCode", "ID country code"),
            ("idIssueDate", "ID issue date"),
            ("idExpirationDate", "ID expiration date"),
            ("idNumber", "ID number"),
            ("photoIdFront", "Photo ID front"),
            ("photoIdBack", "Photo ID back"),
            ("notaryApprovalOfPhotoId", "Notary approval"),
            ("ipAddress", "IP address"),
            ("photoProofResidence", "Photo proof of residence"),
            ("sex", "Sex"),
            ("proofOfIncome", "Proof of income"),
            ("proofOfLiveness", "Proof of liveness"),
            ("referralId", "Referral ID"),
        ]
        for prop, desc in natural_fields:
            natural_section.fields.append(self._field(
                f"NaturalPersonKYCFields.{prop}", desc,
                natural_exists and self.sdk.has_member("NaturalPersonKYCFields", prop),
                sdk_class="NaturalPersonKYCFields",
            ))

        org_section = SEPSection(
            name="Organization Fields",
            description="Organization KYC field coverage",
        )
        org_fields = [
            ("name", "Organization name"),
            ("VATNumber", "VAT number"),
            ("registrationNumber", "Registration number"),
            ("registrationDate", "Registration date"),
            ("registeredAddress", "Registered address"),
            ("numberOfShareholders", "Number of shareholders"),
            ("shareholderName", "Shareholder name"),
            ("addressCountryCode", "Address country code"),
            ("stateOrProvince", "State or province"),
            ("city", "City"),
            ("postalCode", "Postal code"),
            ("directorName", "Director name"),
            ("website", "Website"),
            ("email", "Email"),
            ("phone", "Phone"),
            ("photoIncorporationDoc", "Photo incorporation doc"),
            ("photoProofAddress", "Photo proof of address"),
        ]
        for prop, desc in org_fields:
            org_section.fields.append(self._field(
                f"OrganizationKYCFields.{prop}", desc,
                org_exists and self.sdk.has_member("OrganizationKYCFields", prop),
                sdk_class="OrganizationKYCFields",
            ))

        financial_section = SEPSection(
            name="Financial Account Fields",
            description="Financial account KYC field coverage",
        )
        financial_fields = [
            ("bankName", "Bank name"),
            ("bankAccountType", "Bank account type"),
            ("bankAccountNumber", "Bank account number"),
            ("bankNumber", "Bank number"),
            ("bankPhoneNumber", "Bank phone number"),
            ("bankBranchNumber", "Bank branch number"),
            ("externalTransferMemo", "External transfer memo"),
            ("clabeNumber", "CLABE number"),
            ("cbuNumber", "CBU number"),
            ("cbuAlias", "CBU alias"),
            ("mobileMoneyNumber", "Mobile money number"),
            ("mobileMoneyProvider", "Mobile money provider"),
            ("cryptoAddress", "Crypto address"),
            ("cryptoMemo", "Crypto memo"),
        ]
        for prop, desc in financial_fields:
            financial_section.fields.append(self._field(
                f"FinancialAccountKYCFields.{prop}", desc,
                financial_exists and self.sdk.has_member("FinancialAccountKYCFields", prop),
                sdk_class="FinancialAccountKYCFields",
            ))

        card_section = SEPSection(
            name="Card Fields",
            description="Card KYC field coverage",
        )
        card_fields = [
            ("number", "Card number"),
            ("expirationDate", "Expiration date"),
            ("cvc", "CVC"),
            ("holderName", "Holder name"),
            ("network", "Network"),
            ("postalCode", "Postal code"),
            ("countryCode", "Country code"),
            ("stateOrProvince", "State or province"),
            ("city", "City"),
            ("address", "Address"),
            ("token", "Token"),
        ]
        for prop, desc in card_fields:
            card_section.fields.append(self._field(
                f"CardKYCFields.{prop}", desc,
                card_exists and self.sdk.has_member("CardKYCFields", prop),
                sdk_class="CardKYCFields",
            ))

        sections = [natural_section, org_section, financial_section, card_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-10: Stellar Web Authentication
# ===========================================================================

class SEP10Analyzer(SEPAnalyzerBase):
    sep_number = 10
    sep_title = "Stellar Web Authentication"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        auth_exists = self.sdk.class_exists("WebAuth")

        # --- Authentication Flow ---
        flow_section = SEPSection(
            name="Authentication Flow",
            description="Web authentication client methods",
        )
        flow_section.fields.extend([
            self._field("fromDomain",
                        "Construct WebAuth from domain (discovers auth endpoint via stellar.toml)",
                        auth_exists and self.sdk.has_method("WebAuth", "fromDomain"),
                        sdk_class="WebAuth.fromDomain()"),
            self._field("jwtToken",
                        "Complete challenge/sign/submit flow returning JWT token",
                        auth_exists and self.sdk.has_method("WebAuth", "jwtToken"),
                        sdk_class="WebAuth.jwtToken()"),
            self._field("setGracePeriod",
                        "Configure acceptable clock drift for timebounds validation",
                        auth_exists and self.sdk.has_method("WebAuth", "setGracePeriod"),
                        sdk_class="WebAuth.setGracePeriod()"),
        ])

        # --- Challenge Features ---
        # jwtToken() parameters indicate feature support
        features_section = SEPSection(
            name="Challenge Features",
            description="SEP-10 challenge transaction features supported via jwtToken() parameters",
        )
        features_section.fields.extend([
            self._field("memo_support",
                        "Memo support for shared/omnibus accounts",
                        auth_exists and self.sdk.has_method("WebAuth", "jwtToken"),
                        sdk_class="WebAuth.jwtToken($memo)"),
            self._field("home_domain",
                        "Home domain parameter for multi-tenant auth servers",
                        auth_exists and self.sdk.has_method("WebAuth", "jwtToken"),
                        sdk_class="WebAuth.jwtToken($homeDomain)"),
            self._field("client_domain",
                        "Client domain support for wallet identification",
                        auth_exists and self.sdk.has_method("WebAuth", "jwtToken"),
                        sdk_class="WebAuth.jwtToken($clientDomain)"),
            self._field("client_domain_signing",
                        "Client domain signing via keypair or callback",
                        auth_exists and self.sdk.has_method("WebAuth", "jwtToken"),
                        sdk_class="WebAuth.jwtToken($clientDomainKeyPair, $clientDomainSigningCallback)"),
        ])

        # --- Challenge Validation ---
        # Each validation error class indicates the SDK validates that aspect
        validation_section = SEPSection(
            name="Challenge Validation",
            description="Challenge transaction validation checks (each error class = one validation)",
        )
        validations = [
            ("home_domain_validation", "ChallengeValidationErrorInvalidHomeDomain",
             "Validate home domain in challenge matches server"),
            ("web_auth_domain_validation", "ChallengeValidationErrorInvalidWebAuthDomain",
             "Validate web_auth_domain operation"),
            ("source_account_validation", "ChallengeValidationErrorInvalidSourceAccount",
             "Validate challenge source account is server signing key"),
            ("signature_verification", "ChallengeValidationErrorInvalidSignature",
             "Verify signatures on challenge transaction"),
            ("timebounds_validation", "ChallengeValidationErrorInvalidTimeBounds",
             "Validate challenge is within valid time window"),
            ("sequence_number_validation", "ChallengeValidationErrorInvalidSeqNr",
             "Validate challenge has sequence number 0"),
            ("operation_type_validation", "ChallengeValidationErrorInvalidOperationType",
             "Validate challenge uses ManageData operations"),
            ("memo_type_validation", "ChallengeValidationErrorInvalidMemoType",
             "Validate memo type if present"),
            ("memo_value_validation", "ChallengeValidationErrorInvalidMemoValue",
             "Validate memo value if present"),
            ("memo_muxed_conflict", "ChallengeValidationErrorMemoAndMuxedAccount",
             "Reject memo when muxed account is used"),
        ]
        for spec_name, class_name, desc in validations:
            exists = self.sdk.class_exists(class_name)
            validation_section.fields.append(self._field(
                spec_name, desc, exists, sdk_class=class_name,
            ))

        # --- Response Models ---
        response_section = SEPSection(
            name="Response Models",
            description="Challenge and token response handling",
        )
        response_section.fields.extend([
            self._field("ChallengeResponse",
                        "Challenge transaction response from GET /auth",
                        self.sdk.class_exists("ChallengeResponse"),
                        sdk_class="ChallengeResponse"),
            self._field("SubmitCompletedChallengeResponse",
                        "JWT token response from POST /auth",
                        self.sdk.class_exists("SubmitCompletedChallengeResponse"),
                        sdk_class="SubmitCompletedChallengeResponse"),
        ])

        sections = [flow_section, features_section, validation_section, response_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-11: Txrep — Transaction Representation
# ===========================================================================

class SEP11Analyzer(SEPAnalyzerBase):
    sep_number = 11
    sep_title = "Txrep: human-readable low-level representation of Stellar transactions"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        txrep_exists = self.sdk.class_exists("TxRep")

        txrep_section = SEPSection(
            name="TxRep",
            description="Transaction serialization/deserialization",
        )
        txrep_section.fields.extend([
            self._field("TxRep (class)", "TxRep class exists", txrep_exists, sdk_class="TxRep"),
            self._field("TxRep.fromTransactionEnvelopeXdrBase64",
                        "Convert XDR Base64 envelope to txrep string",
                        txrep_exists and self.sdk.has_method("TxRep", "fromTransactionEnvelopeXdrBase64"),
                        sdk_class="TxRep"),
            self._field("TxRep.transactionEnvelopeXdrBase64FromTxRep",
                        "Convert txrep string to XDR Base64 envelope",
                        txrep_exists and self.sdk.has_method("TxRep", "transactionEnvelopeXdrBase64FromTxRep"),
                        sdk_class="TxRep"),
        ])

        sections = [txrep_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-12: KYC API
# ===========================================================================

class SEP12Analyzer(SEPAnalyzerBase):
    sep_number = 12
    sep_title = "KYC API"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Endpoints --
        svc = "KYCService"
        svc_members = self.sdk.get_class_members(svc)

        ep_section = SEPSection(name="Service Endpoints", description="KYCService API methods")
        ep_section.fields = [
            self._field("GET /customer", "Get customer KYC information",
                        "getCustomerInfo" in svc_members, sdk_class=f"{svc}.getCustomerInfo()"),
            self._field("PUT /customer", "Submit customer KYC information",
                        "putCustomerInfo" in svc_members, sdk_class=f"{svc}.putCustomerInfo()"),
            self._field("PUT /customer/callback", "Register a callback URL",
                        "putCustomerCallback" in svc_members, sdk_class=f"{svc}.putCustomerCallback()"),
            self._field("PUT /customer/verification", "Submit customer verification data",
                        "putCustomerVerification" in svc_members, sdk_class=f"{svc}.putCustomerVerification()"),
            self._field("DELETE /customer/:account", "Delete customer data",
                        "deleteCustomer" in svc_members, sdk_class=f"{svc}.deleteCustomer()"),
            self._field("POST /customer/files", "Upload a customer file",
                        "postCustomerFile" in svc_members, sdk_class=f"{svc}.postCustomerFile()"),
            self._field("GET /customer/files", "List customer files",
                        "getCustomerFiles" in svc_members, sdk_class=f"{svc}.getCustomerFiles()"),
        ]
        sections.append(ep_section)

        # -- GET /customer Request Parameters --
        get_req = SEPSection(name="GET /customer Request Parameters", description="Parameters for GET /customer")
        get_req.fields = self._check_properties("GetCustomerInfoRequest", [
            ("id",             "id",            "Anchor-assigned customer ID", False),
            ("account",        "account",       "Stellar account ID (deprecated)", False),
            ("memo",           "memo",          "Memo identifying customer on shared account", False),
            ("memo_type",      "memoType",      "Type of memo (deprecated)", False),
            ("type",           "type",          "Type of action the customer is being KYC'd for", False),
            ("transaction_id", "transactionId", "Transaction ID when info depends on transaction", False),
            ("lang",           "lang",          "Language code (ISO 639-1)", False),
        ])
        sections.append(get_req)

        # -- GET /customer Response Fields --
        get_resp = SEPSection(name="GET /customer Response Fields", description="Fields returned by GET /customer")
        get_resp.fields = self._check_properties("GetCustomerInfoResponse", [
            ("id",              "id",             "Customer ID", False),
            ("status",          "status",         "KYC verification status", True),
            ("fields",          "fields",         "Fields the anchor has not yet received", False),
            ("provided_fields", "providedFields", "Fields the anchor has received", False),
            ("message",         "message",        "Human-readable status message", False),
        ])
        sections.append(get_resp)

        # -- Field Object Fields --
        field_obj = SEPSection(name="Field Object Fields", description="Properties of each required field entry")
        field_obj.fields = self._check_properties("GetCustomerInfoField", [
            ("type",        "type",        "Data type of the field value", True),
            ("description", "description", "Human-readable field description", False),
            ("choices",     "choices",     "Array of valid values", False),
            ("optional",    "optional",    "Whether the field is required", False),
        ])
        sections.append(field_obj)

        # -- Provided Field Object Fields --
        provided_obj = SEPSection(name="Provided Field Object Fields", description="Properties of each provided field entry")
        provided_obj.fields = self._check_properties("GetCustomerInfoProvidedField", [
            ("type",        "type",        "Data type of the field value", True),
            ("description", "description", "Human-readable field description", False),
            ("choices",     "choices",     "Array of valid values", False),
            ("optional",    "optional",    "Whether the field is required", False),
            ("status",      "status",      "Verification status of the field", False),
            ("error",       "error",       "Reason for REJECTED status", False),
        ])
        sections.append(provided_obj)

        # -- PUT /customer Request Parameters --
        put_req = SEPSection(name="PUT /customer Request Parameters", description="Parameters for PUT /customer")
        put_req.fields = self._check_properties("PutCustomerInfoRequest", [
            ("id",             "id",            "Customer ID from previous PUT response", False),
            ("account",        "account",       "Stellar account ID (deprecated)", False),
            ("memo",           "memo",          "Memo identifying customer on shared account", False),
            ("memo_type",      "memoType",      "Type of memo (deprecated)", False),
            ("type",           "type",          "Type of action the customer is being KYC'd for", False),
            ("transaction_id", "transactionId", "Transaction ID when info depends on transaction", False),
            ("kyc_fields",     "KYCFields",     "Standard SEP-9 KYC fields", False),
            ("custom_fields",  "customFields",  "Custom key-value fields", False),
            ("custom_files",   "customFiles",   "Custom file uploads", False),
        ])
        sections.append(put_req)

        # -- PUT /customer Response Fields --
        # Note: PutCustomerInfoResponse.$id is private, accessible via getId() method
        put_resp = SEPSection(name="PUT /customer Response Fields", description="Fields returned by PUT /customer")
        put_resp_members = self.sdk.get_class_members("PutCustomerInfoResponse")
        put_resp.fields = [
            self._field("id", "Customer identifier", "getId" in put_resp_members,
                        sdk_class="PutCustomerInfoResponse.getId()"),
        ]
        sections.append(put_resp)

        # -- PUT /customer/callback Request Parameters --
        cb_req = SEPSection(name="PUT /customer/callback Request Parameters", description="Parameters for PUT /customer/callback")
        cb_req.fields = self._check_properties("PutCustomerCallbackRequest", [
            ("url",       "url",      "Callback URL for status change notifications", False),
            ("id",        "id",       "Customer ID", False),
            ("account",   "account",  "Stellar account ID", False),
            ("memo",      "memo",     "Memo identifying customer", False),
            ("memo_type", "memoType", "Type of memo (deprecated)", False),
        ])
        sections.append(cb_req)

        # -- PUT /customer/verification Request Parameters --
        ver_req = SEPSection(name="PUT /customer/verification Request Parameters", description="Parameters for PUT /customer/verification")
        ver_req.fields = self._check_properties("PutCustomerVerificationRequest", [
            ("id",                  "id",                 "Customer ID", False),
            ("verification_fields", "verificationFields", "SEP-9 fields with _verification suffix", False),
        ])
        sections.append(ver_req)

        # -- POST /customer/files Response Fields --
        file_resp = SEPSection(name="POST /customer/files Response Fields", description="Fields returned by POST /customer/files")
        file_resp.fields = self._check_properties("CustomerFileResponse", [
            ("file_id",      "fileId",      "Unique file identifier", True),
            ("content_type", "contentType", "MIME type of the file", True),
            ("size",         "size",        "File size in bytes", True),
            ("expires_at",   "expiresAt",   "Expiration date if not referenced", False),
            ("customer_id",  "customerId",  "Associated customer ID", False),
        ])
        sections.append(file_resp)

        # -- GET /customer/files Response Fields --
        files_resp = SEPSection(name="GET /customer/files Response Fields", description="Fields returned by GET /customer/files")
        files_resp.fields = self._check_properties("GetCustomerFilesResponse", [
            ("files", "files", "Array of file objects", True),
        ])
        sections.append(files_resp)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-24: Hosted Deposit and Withdrawal
# ===========================================================================

class SEP24Analyzer(SEPAnalyzerBase):
    sep_number = 24
    sep_title = "Hosted Deposit and Withdrawal"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Endpoints --
        svc = "InteractiveService"
        svc_members = self.sdk.get_class_members(svc)

        ep_section = SEPSection(name="Service Endpoints", description="InteractiveService API methods")
        ep_section.fields = [
            self._field("GET /info", "Get asset info and anchor capabilities",
                        "info" in svc_members, sdk_class=f"{svc}.info()"),
            self._field("POST /transactions/deposit/interactive", "Initiate interactive deposit",
                        "deposit" in svc_members, sdk_class=f"{svc}.deposit()"),
            self._field("POST /transactions/withdraw/interactive", "Initiate interactive withdrawal",
                        "withdraw" in svc_members, sdk_class=f"{svc}.withdraw()"),
            self._field("GET /transaction", "Get a single transaction",
                        "transaction" in svc_members, sdk_class=f"{svc}.transaction()"),
            self._field("GET /transactions", "Get transaction history",
                        "transactions" in svc_members, sdk_class=f"{svc}.transactions()"),
            self._field("GET /fee", "Get fee estimate",
                        "fee" in svc_members, sdk_class=f"{svc}.fee()"),
        ]
        sections.append(ep_section)

        # -- Deposit Request Parameters --
        dep_params = SEPSection(name="Deposit Request Parameters", description="Parameters for POST /transactions/deposit/interactive")
        dep_params.fields = self._check_properties("SEP24DepositRequest", [
            ("asset_code",                  "assetCode",                "Code of the on-chain asset to receive", True),
            ("asset_issuer",                "assetIssuer",              "Issuer of the stellar asset", False),
            ("source_asset",                "sourceAsset",              "Asset user wants to send (SEP-38 format)", False),
            ("amount",                      "amount",                   "Amount of asset requested to deposit", False),
            ("quote_id",                    "quoteId",                  "SEP-38 quote ID", False),
            ("account",                     "account",                  "Destination Stellar account", False),
            ("memo",                        "memo",                     "Memo to attach to transaction", False),
            ("memo_type",                   "memoType",                 "Type of memo", False),
            ("wallet_name",                 "walletName",               "Wallet name (deprecated)", False),
            ("wallet_url",                  "walletUrl",                "Wallet URL (deprecated)", False),
            ("lang",                        "lang",                     "Language code (RFC 4646)", False),
            ("claimable_balance_supported", "claimableBalanceSupported", "Client supports claimable balances", False),
            ("customer_id",                 "customerId",               "Off-chain customer ID", False),
            ("kyc_fields",                  "kycFields",                "SEP-9 KYC fields", False),
            ("custom_fields",               "customFields",             "Custom SEP-9 fields", False),
            ("custom_files",                "customFiles",              "Custom SEP-9 file uploads", False),
        ])
        sections.append(dep_params)

        # -- Withdraw Request Parameters --
        wd_params = SEPSection(name="Withdraw Request Parameters", description="Parameters for POST /transactions/withdraw/interactive")
        wd_params.fields = self._check_properties("SEP24WithdrawRequest", [
            ("asset_code",        "assetCode",        "Code of the asset to withdraw", True),
            ("asset_issuer",      "assetIssuer",      "Issuer of the stellar asset", False),
            ("destination_asset", "destinationAsset",  "Asset user wants to receive (SEP-38 format)", False),
            ("amount",            "amount",            "Amount of asset requested to withdraw", False),
            ("quote_id",          "quoteId",           "SEP-38 quote ID", False),
            ("account",           "account",           "Source Stellar account", False),
            ("memo",              "memo",              "Memo (deprecated)", False),
            ("memo_type",         "memoType",          "Type of memo (deprecated)", False),
            ("wallet_name",       "walletName",        "Wallet name (deprecated)", False),
            ("wallet_url",        "walletUrl",         "Wallet URL (deprecated)", False),
            ("lang",              "lang",              "Language code (RFC 4646)", False),
            ("refund_memo",       "refundMemo",        "Memo for refund payments", False),
            ("refund_memo_type",  "refundMemoType",    "Type of refund memo", False),
            ("customer_id",       "customerId",        "Off-chain customer ID", False),
            ("kyc_fields",        "kycFields",         "SEP-9 KYC fields", False),
            ("custom_fields",     "customFields",      "Custom SEP-9 fields", False),
            ("custom_files",      "customFiles",       "Custom SEP-9 file uploads", False),
        ])
        sections.append(wd_params)

        # -- Interactive Response Fields --
        interactive_resp = SEPSection(name="Interactive Response Fields", description="Fields returned by POST deposit/withdraw")
        interactive_resp.fields = self._check_properties("SEP24InteractiveResponse", [
            ("type", "type", "Always 'interactive_customer_info_needed'", True),
            ("url",  "url",  "URL for the interactive flow", True),
            ("id",   "id",   "Anchor's internal transaction ID", True),
        ])
        sections.append(interactive_resp)

        # -- Info Response Fields --
        info_resp = SEPSection(name="Info Response Fields", description="Fields returned by GET /info")
        info_resp.fields = self._check_properties("SEP24InfoResponse", [
            ("deposit",  "depositAssets",  "Supported deposit assets", False),
            ("withdraw", "withdrawAssets",  "Supported withdrawal assets", False),
            ("fee",      "feeEndpointInfo", "Fee endpoint information", False),
            ("features", "featureFlags",    "Additional feature flags", False),
        ])
        sections.append(info_resp)

        # -- Transactions Request Parameters --
        txs_req = SEPSection(name="Transactions Request Parameters", description="Parameters for GET /transactions")
        txs_req.fields = self._check_properties("SEP24TransactionsRequest", [
            ("asset_code",    "assetCode",   "Code of the asset of interest", True),
            ("no_older_than", "noOlderThan", "Filter transactions by start date", False),
            ("limit",         "limit",       "Maximum number of transactions", False),
            ("kind",          "kind",        "Filter by deposit or withdrawal", False),
            ("paging_id",     "pagingId",    "Pagination cursor", False),
            ("lang",          "lang",        "Language code (RFC 4646)", False),
        ])
        sections.append(txs_req)

        # -- Transaction Fields --
        tx_fields = SEPSection(name="Transaction Fields", description="Fields in the SEP-24 transaction object")
        tx_fields.fields = self._check_properties("SEP24Transaction", [
            ("id",                       "id",                      "Unique transaction ID", True),
            ("kind",                     "kind",                    "Transaction kind (deposit/withdrawal)", True),
            ("status",                   "status",                  "Processing status", True),
            ("status_eta",               "statusEta",               "Estimated seconds until status change", False),
            ("kyc_verified",             "kycVerified",             "Whether KYC is verified for this transaction", False),
            ("more_info_url",            "moreInfoUrl",             "URL with transaction details", False),
            ("amount_in",                "amountIn",                "Amount received by anchor", False),
            ("amount_in_asset",          "amountInAsset",           "Asset received (SEP-38 format)", False),
            ("amount_out",               "amountOut",               "Amount sent to user", False),
            ("amount_out_asset",         "amountOutAsset",          "Asset delivered (SEP-38 format)", False),
            ("amount_fee",               "amountFee",               "Fee charged by anchor", False),
            ("amount_fee_asset",         "amountFeeAsset",          "Fee asset (SEP-38 format)", False),
            ("quote_id",                 "quoteId",                 "SEP-38 quote ID", False),
            ("started_at",               "startedAt",               "Transaction start time", True),
            ("completed_at",             "completedAt",             "Transaction completion time", False),
            ("updated_at",               "updatedAt",               "Last status update time", False),
            ("user_action_required_by",  "userActionRequiredBy",    "Deadline for user action", False),
            ("stellar_transaction_id",   "stellarTransactionId",    "Stellar network transaction ID", False),
            ("external_transaction_id",  "externalTransactionId",   "External network transaction ID", False),
            ("message",                  "message",                 "Human-readable status message", False),
            ("refunded",                 "refunded",                "Whether fully refunded (deprecated)", False),
            ("refunds",                  "refunds",                 "Refund details object", False),
            ("from",                     "from",                    "Source address", False),
            ("to",                       "to",                      "Destination address", False),
            ("deposit_memo",             "depositMemo",             "Memo for deposit transfer", False),
            ("deposit_memo_type",        "depositMemoType",         "Type of deposit memo", False),
            ("claimable_balance_id",     "claimableBalanceId",      "Claimable balance ID (deposits)", False),
            ("withdraw_anchor_account",  "withdrawAnchorAccount",   "Anchor account for withdrawal", False),
            ("withdraw_memo",            "withdrawMemo",            "Memo for withdrawal transfer", False),
            ("withdraw_memo_type",       "withdrawMemoType",        "Type of withdrawal memo", False),
        ])
        sections.append(tx_fields)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-30: Account Recovery
# ===========================================================================

class SEP30Analyzer(SEPAnalyzerBase):
    sep_number = 30
    sep_title = "Account Recovery: multi-party recovery of Stellar accounts"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Endpoints --
        svc = "RecoveryService"
        svc_members = self.sdk.get_class_members(svc)

        ep_section = SEPSection(name="Service Endpoints", description="RecoveryService API methods")
        ep_section.fields = [
            self._field("POST /accounts/:address", "Register an account for recovery",
                        "registerAccount" in svc_members, sdk_class=f"{svc}.registerAccount()"),
            self._field("PUT /accounts/:address", "Update account recovery identities",
                        "updateIdentitiesForAccount" in svc_members, sdk_class=f"{svc}.updateIdentitiesForAccount()"),
            self._field("POST /accounts/:address/sign/:signing_address", "Sign a transaction",
                        "signTransaction" in svc_members, sdk_class=f"{svc}.signTransaction()"),
            self._field("GET /accounts/:address", "Get recovery info for an account",
                        "accountDetails" in svc_members, sdk_class=f"{svc}.accountDetails()"),
            self._field("DELETE /accounts/:address", "Delete account recovery info",
                        "deleteAccount" in svc_members, sdk_class=f"{svc}.deleteAccount()"),
            self._field("GET /accounts", "List recoverable accounts",
                        "accounts" in svc_members, sdk_class=f"{svc}.accounts()"),
        ]
        sections.append(ep_section)

        # -- Request Fields --
        req_section = SEPSection(name="Request Fields", description="SEP30Request properties")
        req_section.fields = self._check_properties("SEP30Request", [
            ("identities", "identities", "Array of identity objects", True),
        ])
        sections.append(req_section)

        # -- Request Identity Fields --
        req_id_section = SEPSection(name="Request Identity Fields", description="SEP30RequestIdentity properties")
        req_id_section.fields = self._check_properties("SEP30RequestIdentity", [
            ("role",         "role",        "Role of the identity (owner, sender, receiver)", True),
            ("auth_methods", "authMethods", "Array of authentication methods", True),
        ])
        sections.append(req_id_section)

        # -- Auth Method Fields --
        auth_section = SEPSection(name="Auth Method Fields", description="SEP30AuthMethod properties")
        auth_section.fields = self._check_properties("SEP30AuthMethod", [
            ("type",  "type",  "Authentication method type (stellar_address, phone_number, email)", True),
            ("value", "value", "Authentication method value", True),
        ])
        sections.append(auth_section)

        # -- Account Response Fields --
        acct_resp = SEPSection(name="Account Response Fields", description="SEP30AccountResponse properties")
        acct_resp.fields = self._check_properties("SEP30AccountResponse", [
            ("address",    "address",    "Stellar account address", True),
            ("identities", "identities", "Array of response identity objects", True),
            ("signers",    "signers",    "Array of recovery signer objects", True),
        ])
        sections.append(acct_resp)

        # -- Response Identity Fields --
        resp_id = SEPSection(name="Response Identity Fields", description="SEP30ResponseIdentity properties")
        resp_id.fields = self._check_properties("SEP30ResponseIdentity", [
            ("role",          "role",          "Role of the identity", False),
            ("authenticated", "authenticated", "Whether the identity is authenticated", False),
        ])
        sections.append(resp_id)

        # -- Response Signer Fields --
        resp_signer = SEPSection(name="Response Signer Fields", description="SEP30ResponseSigner properties")
        resp_signer.fields = self._check_properties("SEP30ResponseSigner", [
            ("key", "key", "Public key of the recovery signer", True),
        ])
        sections.append(resp_signer)

        # -- Signature Response Fields --
        sig_resp = SEPSection(name="Signature Response Fields", description="SEP30SignatureResponse properties")
        sig_resp.fields = self._check_properties("SEP30SignatureResponse", [
            ("signature",          "signature",         "Base64-encoded signature", True),
            ("network_passphrase", "networkPassphrase", "Network passphrase used for signing", True),
        ])
        sections.append(sig_resp)

        # -- Accounts Response Fields --
        accts_resp = SEPSection(name="Accounts List Response Fields", description="SEP30AccountsResponse properties")
        accts_resp.fields = self._check_properties("SEP30AccountsResponse", [
            ("accounts", "accounts", "Array of account response objects", True),
        ])
        sections.append(accts_resp)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-31: Cross-Border Payments (new in PHP SDK)
# ===========================================================================

class SEP31Analyzer(SEPAnalyzerBase):
    sep_number = 31
    sep_title = "Cross-Border Payments API"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Endpoints --
        svc = "CrossBorderPaymentsService"
        svc_members = self.sdk.get_class_members(svc)

        ep_section = SEPSection(name="Service Endpoints", description="CrossBorderPaymentsService API methods")
        ep_section.fields = [
            self._field("GET /info", "Get supported assets and quote requirements",
                        "info" in svc_members, sdk_class=f"{svc}.info()"),
            self._field("POST /transactions", "Create a cross-border payment",
                        "postTransactions" in svc_members, sdk_class=f"{svc}.postTransactions()"),
            self._field("GET /transactions/:id", "Get transaction status",
                        "getTransaction" in svc_members, sdk_class=f"{svc}.getTransaction()"),
            self._field("PATCH /transactions/:id", "Update a pending transaction",
                        "patchTransaction" in svc_members, sdk_class=f"{svc}.patchTransaction()"),
            self._field("PUT /transactions/:id/callback", "Register transaction callback",
                        "putTransactionCallback" in svc_members, sdk_class=f"{svc}.putTransactionCallback()"),
        ]
        sections.append(ep_section)

        # -- Info Response Fields --
        info_resp = SEPSection(name="Info Response Fields", description="SEP31InfoResponse properties")
        info_resp.fields = self._check_properties("SEP31InfoResponse", [
            ("receive", "receiveAssets", "Supported receive assets", True),
        ])
        sections.append(info_resp)

        # -- Receive Asset Info Fields --
        asset_info = SEPSection(name="Receive Asset Info Fields", description="SEP31ReceiveAssetInfo properties")
        asset_info.fields = self._check_properties("SEP31ReceiveAssetInfo", [
            ("sep12",             "sep12Info",        "SEP-12 type requirements", False),
            ("min_amount",        "minAmount",        "Minimum amount", False),
            ("max_amount",        "maxAmount",        "Maximum amount", False),
            ("fee_fixed",         "feeFixed",         "Fixed fee", False),
            ("fee_percent",       "feePercent",       "Percentage fee", False),
            ("sender_sep12_type", "senderSep12Type",  "Sender SEP-12 type (deprecated)", False),
            ("receiver_sep12_type", "receiverSep12Type", "Receiver SEP-12 type (deprecated)", False),
            ("fields",            "fields",           "Required fields (deprecated)", False),
            ("quotes_supported",  "quotesSupported",  "Whether quotes are supported", False),
            ("quotes_required",   "quotesRequired",   "Whether quotes are required", False),
            ("funding_methods",   "fundingMethods",   "Accepted funding methods", False),
        ])
        sections.append(asset_info)

        # -- SEP-12 Types Info Fields --
        sep12_types = SEPSection(name="SEP-12 Types Info Fields", description="SEP12TypesInfo properties")
        sep12_types.fields = self._check_properties("SEP12TypesInfo", [
            ("sender",   "senderTypes",   "Required sender SEP-12 types", True),
            ("receiver", "receiverTypes",  "Required receiver SEP-12 types", True),
        ])
        sections.append(sep12_types)

        # -- POST /transactions Request Fields --
        post_req = SEPSection(name="POST /transactions Request Fields", description="SEP31PostTransactionsRequest properties")
        post_req.fields = self._check_properties("SEP31PostTransactionsRequest", [
            ("amount",            "amount",           "Amount to send", True),
            ("asset_code",        "assetCode",        "Code of the asset to send", True),
            ("asset_issuer",      "assetIssuer",      "Issuer of the asset", False),
            ("destination_asset", "destinationAsset",  "Asset user receives (SEP-38 format)", False),
            ("quote_id",          "quoteId",           "SEP-38 quote ID", False),
            ("sender_id",         "senderId",          "SEP-12 sender customer ID", False),
            ("receiver_id",       "receiverId",        "SEP-12 receiver customer ID", False),
            ("fields",            "fields",            "Transaction fields (deprecated)", False),
            ("lang",              "lang",              "Language code", False),
            ("refund_memo",       "refundMemo",        "Memo for refund payments", False),
            ("refund_memo_type",  "refundMemoType",    "Type of refund memo", False),
            ("funding_method",    "fundingMethod",     "Funding method", False),
        ])
        sections.append(post_req)

        # -- POST /transactions Response Fields --
        post_resp = SEPSection(name="POST /transactions Response Fields", description="SEP31PostTransactionsResponse properties")
        post_resp.fields = self._check_properties("SEP31PostTransactionsResponse", [
            ("id",                  "id",               "Transaction ID", True),
            ("stellar_account_id",  "stellarAccountId",  "Anchor's Stellar account", False),
            ("stellar_memo_type",   "stellarMemoType",   "Type of memo for payment", False),
            ("stellar_memo",        "stellarMemo",       "Memo for payment", False),
        ])
        sections.append(post_resp)

        # -- GET /transactions/:id Response Fields --
        tx_resp = SEPSection(name="Transaction Response Fields", description="SEP31TransactionResponse properties")
        tx_resp.fields = self._check_properties("SEP31TransactionResponse", [
            ("id",                       "id",                     "Transaction ID", True),
            ("status",                   "status",                 "Processing status", True),
            ("status_eta",               "statusEta",              "Estimated seconds until status change", False),
            ("status_message",           "statusMessage",          "Human-readable status message", False),
            ("amount_in",                "amountIn",               "Amount received by anchor", False),
            ("amount_in_asset",          "amountInAsset",          "Asset received (SEP-38 format)", False),
            ("amount_out",               "amountOut",              "Amount sent to receiver", False),
            ("amount_out_asset",         "amountOutAsset",         "Asset delivered (SEP-38 format)", False),
            ("amount_fee",               "amountFee",              "Fee amount (deprecated)", False),
            ("amount_fee_asset",         "amountFeeAsset",         "Fee asset (deprecated)", False),
            ("fee_details",              "feeDetails",             "Detailed fee breakdown", False),
            ("quote_id",                 "quoteId",                "SEP-38 quote ID", False),
            ("stellar_account_id",       "stellarAccountId",       "Anchor's Stellar account", False),
            ("stellar_memo_type",        "stellarMemoType",        "Type of memo for payment", False),
            ("stellar_memo",             "stellarMemo",            "Memo for payment", False),
            ("started_at",               "startedAt",              "Transaction start time", False),
            ("updated_at",               "updatedAt",              "Last status update time", False),
            ("completed_at",             "completedAt",            "Transaction completion time", False),
            ("stellar_transaction_id",   "stellarTransactionId",   "Stellar network transaction ID", False),
            ("external_transaction_id",  "externalTransactionId",  "External transaction ID", False),
            ("refunded",                 "refunded",               "Whether refunded (deprecated)", False),
            ("refunds",                  "refunds",                "Refund details", False),
            ("required_info_message",    "requiredInfoMessage",    "Message about required info", False),
            ("required_info_updates",    "requiredInfoUpdates",    "Fields that need updating", False),
        ])
        sections.append(tx_resp)

        # -- Refunds Fields --
        refunds_section = SEPSection(name="Refunds Fields", description="SEP31Refunds properties")
        refunds_section.fields = self._check_properties("SEP31Refunds", [
            ("amount_refunded", "amountRefunded", "Total amount refunded", True),
            ("amount_fee",      "amountFee",      "Total fee for refunds", True),
            ("payments",        "payments",       "Array of refund payments", True),
        ])
        sections.append(refunds_section)

        # -- Refund Payment Fields --
        refund_payment = SEPSection(name="Refund Payment Fields", description="SEP31RefundPayment properties")
        refund_payment.fields = self._check_properties("SEP31RefundPayment", [
            ("id",     "id",     "Payment ID", True),
            ("amount", "amount", "Refund amount", True),
            ("fee",    "fee",    "Refund fee", True),
        ])
        sections.append(refund_payment)

        # -- Fee Details Fields --
        fee_details = SEPSection(name="Fee Details Fields", description="SEP31FeeDetails properties")
        fee_details.fields = self._check_properties("SEP31FeeDetails", [
            ("total",   "total",   "Total fee amount", True),
            ("asset",   "asset",   "Fee asset", True),
            ("details", "details", "Fee breakdown array", False),
        ])
        sections.append(fee_details)

        # -- Fee Details Breakdown Fields --
        fee_breakdown = SEPSection(name="Fee Details Breakdown Fields", description="SEP31FeeDetailsDetails properties")
        fee_breakdown.fields = self._check_properties("SEP31FeeDetailsDetails", [
            ("name",        "name",        "Fee component name", True),
            ("amount",      "amount",      "Fee component amount", True),
            ("description", "description", "Fee component description", False),
        ])
        sections.append(fee_breakdown)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-38: Anchor RFQ API
# ===========================================================================

class SEP38Analyzer(SEPAnalyzerBase):
    sep_number = 38
    sep_title = "Anchor RFQ API"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()
        sections: list[SEPSection] = []

        # -- Service Endpoints --
        svc = "QuoteService"
        svc_members = self.sdk.get_class_members(svc)

        ep_section = SEPSection(name="Service Endpoints", description="QuoteService API methods")
        ep_section.fields = [
            self._field("GET /info", "Get supported assets",
                        "info" in svc_members, sdk_class=f"{svc}.info()"),
            self._field("GET /prices", "Get asset prices",
                        "prices" in svc_members, sdk_class=f"{svc}.prices()"),
            self._field("GET /price", "Get price for a specific asset pair",
                        "price" in svc_members, sdk_class=f"{svc}.price()"),
            self._field("POST /quote", "Request a firm quote",
                        "postQuote" in svc_members, sdk_class=f"{svc}.postQuote()"),
            self._field("GET /quote/:id", "Get a previously requested quote",
                        "getQuote" in svc_members, sdk_class=f"{svc}.getQuote()"),
        ]
        sections.append(ep_section)

        # -- Info Response Fields --
        info_resp = SEPSection(name="Info Response Fields", description="SEP38InfoResponse properties")
        info_resp.fields = self._check_properties("SEP38InfoResponse", [
            ("assets", "assets", "Array of supported assets", True),
        ])
        sections.append(info_resp)

        # -- Asset Fields --
        asset_section = SEPSection(name="Asset Fields", description="SEP38Asset properties")
        asset_section.fields = self._check_properties("SEP38Asset", [
            ("asset",                 "asset",              "Asset identifier (SEP-38 format)", True),
            ("sell_delivery_methods", "sellDeliveryMethods", "Sell delivery methods", False),
            ("buy_delivery_methods",  "buyDeliveryMethods",  "Buy delivery methods", False),
            ("country_codes",         "countryCodes",        "Supported country codes", False),
        ])
        sections.append(asset_section)

        # -- Prices Response Fields --
        prices_resp = SEPSection(name="Prices Response Fields", description="SEP38PricesResponse properties")
        prices_resp.fields = self._check_properties("SEP38PricesResponse", [
            ("buy_assets",  "buyAssets",  "Array of buy asset prices", False),
            ("sell_assets", "sellAssets", "Array of sell asset prices", False),
        ])
        sections.append(prices_resp)

        # -- Buy Asset Fields --
        buy_asset = SEPSection(name="Buy Asset Fields", description="SEP38BuyAsset properties")
        buy_asset.fields = self._check_properties("SEP38BuyAsset", [
            ("asset",    "asset",    "Asset identifier", True),
            ("price",    "price",    "Indicative price", True),
            ("decimals", "decimals", "Significant decimals", True),
        ])
        sections.append(buy_asset)

        # -- Sell Asset Fields --
        sell_asset = SEPSection(name="Sell Asset Fields", description="SEP38SellAsset properties")
        sell_asset.fields = self._check_properties("SEP38SellAsset", [
            ("asset",    "asset",    "Asset identifier", True),
            ("price",    "price",    "Indicative price", True),
            ("decimals", "decimals", "Significant decimals", True),
        ])
        sections.append(sell_asset)

        # -- Price Response Fields --
        price_resp = SEPSection(name="Price Response Fields", description="SEP38PriceResponse properties")
        price_resp.fields = self._check_properties("SEP38PriceResponse", [
            ("total_price", "totalPrice",  "Total price including fees", True),
            ("price",       "price",       "Price without fees", True),
            ("sell_amount", "sellAmount",  "Amount to sell", True),
            ("buy_amount",  "buyAmount",   "Amount to buy", True),
            ("fee",         "fee",         "Fee object", True),
        ])
        sections.append(price_resp)

        # -- POST /quote Request Fields --
        quote_req = SEPSection(name="POST /quote Request Fields", description="SEP38PostQuoteRequest properties")
        quote_req.fields = self._check_properties("SEP38PostQuoteRequest", [
            ("context",              "context",             "Context for the quote (sep6, sep24, sep31)", True),
            ("sell_asset",           "sellAsset",           "Asset to sell (SEP-38 format)", True),
            ("buy_asset",            "buyAsset",            "Asset to buy (SEP-38 format)", True),
            ("sell_amount",          "sellAmount",          "Amount to sell", False),
            ("buy_amount",           "buyAmount",           "Amount to buy", False),
            ("expire_after",         "expireAfter",         "Requested quote expiration time", False),
            ("sell_delivery_method", "sellDeliveryMethod",  "Sell delivery method", False),
            ("buy_delivery_method",  "buyDeliveryMethod",   "Buy delivery method", False),
            ("country_code",         "countryCode",         "Country code for delivery", False),
        ])
        sections.append(quote_req)

        # -- Quote Response Fields --
        quote_resp = SEPSection(name="Quote Response Fields", description="SEP38QuoteResponse properties")
        quote_resp.fields = self._check_properties("SEP38QuoteResponse", [
            ("id",                   "id",                  "Quote ID", True),
            ("expires_at",           "expiresAt",           "Quote expiration time", True),
            ("total_price",          "totalPrice",          "Total price including fees", True),
            ("price",                "price",               "Price without fees", True),
            ("sell_asset",           "sellAsset",           "Asset to sell", True),
            ("sell_amount",          "sellAmount",          "Amount to sell", True),
            ("buy_asset",            "buyAsset",            "Asset to buy", True),
            ("buy_amount",           "buyAmount",           "Amount to buy", True),
            ("fee",                  "fee",                 "Fee object", True),
            ("sell_delivery_method", "sellDeliveryMethod",  "Sell delivery method", False),
            ("buy_delivery_method",  "buyDeliveryMethod",   "Buy delivery method", False),
        ])
        sections.append(quote_resp)

        # -- Fee Fields --
        fee_section = SEPSection(name="Fee Fields", description="SEP38Fee properties")
        fee_section.fields = self._check_properties("SEP38Fee", [
            ("total",   "total",   "Total fee amount", True),
            ("asset",   "asset",   "Fee asset", True),
            ("details", "details", "Fee breakdown array", False),
        ])
        sections.append(fee_section)

        # -- Fee Details Fields --
        fee_details = SEPSection(name="Fee Details Fields", description="SEP38FeeDetails properties")
        fee_details.fields = self._check_properties("SEP38FeeDetails", [
            ("name",        "name",        "Fee component name", True),
            ("amount",      "amount",      "Fee component amount", True),
            ("description", "description", "Fee component description", False),
        ])
        sections.append(fee_details)

        # -- Delivery Method Fields --
        sell_dm = SEPSection(name="Sell Delivery Method Fields", description="SEP38SellDeliveryMethod properties")
        sell_dm.fields = self._check_properties("SEP38SellDeliveryMethod", [
            ("name",        "name",        "Delivery method identifier", True),
            ("description", "description", "Human-readable description", True),
        ])
        sections.append(sell_dm)

        buy_dm = SEPSection(name="Buy Delivery Method Fields", description="SEP38BuyDeliveryMethod properties")
        buy_dm.fields = self._check_properties("SEP38BuyDeliveryMethod", [
            ("name",        "name",        "Delivery method identifier", True),
            ("description", "description", "Human-readable description", True),
        ])
        sections.append(buy_dm)

        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-45: Web Authentication for Smart Contracts
# ===========================================================================

class SEP45Analyzer(SEPAnalyzerBase):
    sep_number = 45
    sep_title = "Stellar Web Authentication for Contract Accounts"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        svc = "WebAuthForContracts"
        svc_members = self.sdk.get_class_members(svc)

        # -- Authentication Flow --
        auth_section = SEPSection(
            name="Authentication Flow",
            description="Contract web authentication client methods",
        )
        auth_section.fields = [
            self._field("fromDomain", "Discover endpoints from stellar.toml",
                        "fromDomain" in svc_members,
                        sdk_class="WebAuthForContracts.fromDomain()"),
            self._field("jwtToken", "Complete challenge/sign/submit flow returning JWT",
                        "jwtToken" in svc_members,
                        sdk_class="WebAuthForContracts.jwtToken()"),
            self._field("getChallenge", "Fetch authorization entries from server",
                        "getChallenge" in svc_members,
                        sdk_class="WebAuthForContracts.getChallenge()"),
            self._field("sendSignedChallenge", "Submit signed entries to receive JWT",
                        "sendSignedChallenge" in svc_members,
                        sdk_class="WebAuthForContracts.sendSignedChallenge()"),
            self._field("setUseFormUrlEncoded", "Support form-urlencoded POST format",
                        "setUseFormUrlEncoded" in svc_members,
                        sdk_class="WebAuthForContracts.setUseFormUrlEncoded()"),
        ]

        # -- Challenge Features (derived from jwtToken parameters) --
        challenge_section = SEPSection(
            name="Challenge Features",
            description="SEP-45 challenge features supported via jwtToken() parameters",
        )
        # Read jwtToken source to verify parameters
        svc_path = self.sdk.find_class(svc)
        svc_src = svc_path.read_text(encoding="utf-8") if svc_path else ""
        challenge_section.fields = [
            self._field("client_domain", "Optional client_domain parameter",
                        "clientDomain" in svc_src,
                        sdk_class="WebAuthForContracts.jwtToken($clientDomain)"),
            self._field("client_domain_signing", "Client domain keypair or callback signing",
                        "clientDomainKeyPair" in svc_src and "clientDomainSigningCallback" in svc_src,
                        sdk_class="WebAuthForContracts.jwtToken($clientDomainKeyPair, $clientDomainSigningCallback)"),
            self._field("multi_signer_support", "Multiple signers for multi-sig contracts",
                        "array $signers" in svc_src,
                        sdk_class="WebAuthForContracts.jwtToken($signers)"),
            self._field("signature_expiration_ledger", "Signature expiration ledger for replay protection",
                        "signatureExpirationLedger" in svc_src,
                        sdk_class="WebAuthForContracts.jwtToken($signatureExpirationLedger)"),
            self._field("decodeAuthorizationEntries", "Decode base64 XDR authorization entries",
                        "decodeAuthorizationEntries" in svc_members,
                        sdk_class="WebAuthForContracts.decodeAuthorizationEntries()"),
            self._field("signAuthorizationEntries", "Sign client authorization entries",
                        "signAuthorizationEntries" in svc_members,
                        sdk_class="WebAuthForContracts.signAuthorizationEntries()"),
        ]

        # -- Challenge Validation (each error class = one validation check) --
        validation_section = SEPSection(
            name="Challenge Validation",
            description="Challenge validation checks (each error class = one validation)",
        )
        validations = [
            ("ContractChallengeValidationErrorInvalidContractAddress", "contract_address_validation",
             "Validate contract address matches WEB_AUTH_CONTRACT_ID"),
            ("ContractChallengeValidationErrorInvalidFunctionName", "function_name_validation",
             "Validate function name is web_auth_verify"),
            ("ContractChallengeValidationErrorInvalidServerSignature", "server_signature_validation",
             "Verify server signature on authorization entry"),
            ("ContractChallengeValidationErrorInvalidHomeDomain", "home_domain_validation",
             "Validate home_domain argument matches expected domain"),
            ("ContractChallengeValidationErrorInvalidWebAuthDomain", "web_auth_domain_validation",
             "Validate web_auth_domain argument matches server domain"),
            ("ContractChallengeValidationErrorInvalidAccount", "account_validation",
             "Validate account argument matches client contract account"),
            ("ContractChallengeValidationErrorInvalidNonce", "nonce_validation",
             "Validate nonce consistency across authorization entries"),
            ("ContractChallengeValidationErrorInvalidArgs", "args_validation",
             "Validate authorization entry arguments structure"),
            ("ContractChallengeValidationErrorInvalidNetworkPassphrase", "network_passphrase_validation",
             "Validate network passphrase if provided by server"),
            ("ContractChallengeValidationErrorSubInvocationsFound", "sub_invocations_check",
             "Reject authorization entries with sub-invocations"),
            ("ContractChallengeValidationErrorMissingServerEntry", "server_entry_validation",
             "Validate server authorization entry exists"),
            ("ContractChallengeValidationErrorMissingClientEntry", "client_entry_validation",
             "Validate client authorization entry exists"),
        ]
        validation_section.fields = [
            self._field(name, desc, self.sdk.class_exists(cls), sdk_class=cls)
            for cls, name, desc in validations
        ]

        # -- Response Models --
        response_section = SEPSection(
            name="Response Models",
            description="Challenge and token response handling",
        )
        response_section.fields = [
            self._field("ContractChallengeResponse", "Challenge response model",
                        self.sdk.class_exists("ContractChallengeResponse"),
                        sdk_class="ContractChallengeResponse"),
            self._field("SubmitContractChallengeResponse", "JWT token response model",
                        self.sdk.class_exists("SubmitContractChallengeResponse"),
                        sdk_class="SubmitContractChallengeResponse"),
        ]

        sections = [auth_section, challenge_section, validation_section, response_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-46: Contract Metadata
# ===========================================================================

class SEP46Analyzer(SEPAnalyzerBase):
    sep_number = 46
    sep_title = "Contract Meta"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0046.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        parser_exists = self.sdk.class_exists("SorobanContractParser")
        info_exists = self.sdk.class_exists("SorobanContractInfo")

        meta_section = SEPSection(
            name="Contract Metadata",
            description="Parsing and reading SEP-46 contract metadata",
        )
        meta_section.fields.extend([
            self._field("SorobanContractParser (class)",
                        "Contract WASM parser class",
                        parser_exists, sdk_class="SorobanContractParser"),
            self._field("SorobanContractParser.parseContractByteCode",
                        "Parse WASM bytecode to extract metadata",
                        parser_exists and self.sdk.has_method("SorobanContractParser", "parseContractByteCode"),
                        sdk_class="SorobanContractParser"),
            self._field("SorobanContractInfo.metaEntries",
                        "Access contract meta entries (key-value pairs)",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "metaEntries"),
                        sdk_class="SorobanContractInfo"),
            self._field("SorobanContractInfo.supportedSeps",
                        "Read supported SEPs from metadata",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "supportedSeps"),
                        sdk_class="SorobanContractInfo"),
            self._field("SorobanContractInfo.envMetaProtocol",
                        "Environment metadata protocol version",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "envMetaProtocol"),
                        sdk_class="SorobanContractInfo"),
            self._field("SorobanContractInfo.envMetaPreRelease",
                        "Environment metadata pre-release version",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "envMetaPreRelease"),
                        sdk_class="SorobanContractInfo"),
        ])

        sections = [meta_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-47: Contract Interface Discovery
# ===========================================================================

class SEP47Analyzer(SEPAnalyzerBase):
    sep_number = 47
    sep_title = "Contract Interface Discovery"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        parser_exists = self.sdk.class_exists("SorobanContractParser")
        info_exists = self.sdk.class_exists("SorobanContractInfo")

        discovery_section = SEPSection(
            name="SEP Discovery",
            description="Discovering which SEPs a contract implements via metadata",
        )
        discovery_section.fields = [
            self._field("SorobanContractParser.parseContractByteCode",
                        "Parse WASM bytecode to extract metadata including supported SEPs",
                        parser_exists and self.sdk.has_method("SorobanContractParser", "parseContractByteCode"),
                        sdk_class="SorobanContractParser.parseContractByteCode()"),
            self._field("SorobanContractInfo.supportedSeps",
                        "List of SEP numbers the contract declares support for",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "supportedSeps"),
                        sdk_class="SorobanContractInfo.$supportedSeps"),
            self._field("SorobanContractInfo.metaEntries",
                        "Raw meta entries containing sep key-value declarations",
                        info_exists and self.sdk.has_member("SorobanContractInfo", "metaEntries"),
                        sdk_class="SorobanContractInfo.$metaEntries"),
        ]

        sections = [discovery_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-48: Contract Interface Specification
# ===========================================================================

class SEP48Analyzer(SEPAnalyzerBase):
    sep_number = 48
    sep_title = "Contract Interface Specification"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        info_members = self.sdk.get_class_members("SorobanContractInfo")
        spec_members = self.sdk.get_class_members("ContractSpec")
        parser_members = self.sdk.get_class_members("SorobanContractParser")

        # -- Wasm Section Parsing --
        parsing_section = SEPSection(
            name="Wasm Parsing",
            description="Parse contract spec from Wasm bytecode",
        )
        parsing_section.fields = [
            self._field("parseContractByteCode", "Parse Wasm bytecode to extract spec entries",
                        "parseContractByteCode" in parser_members,
                        sdk_class="SorobanContractParser.parseContractByteCode()"),
            self._field("specEntries", "Decoded SCSpecEntry array from contractspecv0 section",
                        "specEntries" in info_members,
                        sdk_class="SorobanContractInfo.$specEntries"),
            self._field("envInterfaceVersion", "Environment interface version from contractenvmetav0 section",
                        "envMetaProtocol" in info_members,
                        sdk_class="SorobanContractInfo.$envMetaProtocol"),
        ]

        # -- Entry Types (SC_SPEC_ENTRY_*) --
        entry_section = SEPSection(
            name="Entry Types",
            description="Spec entry type parsing (SC_SPEC_ENTRY_*)",
        )
        entry_section.fields = [
            self._field("function_specs", "Parse SC_SPEC_ENTRY_FUNCTION_V0 entries",
                        "funcs" in info_members,
                        sdk_class="SorobanContractInfo.$funcs"),
            self._field("struct_specs", "Parse SC_SPEC_ENTRY_UDT_STRUCT_V0 entries",
                        "udtStructs" in info_members,
                        sdk_class="SorobanContractInfo.$udtStructs"),
            self._field("union_specs", "Parse SC_SPEC_ENTRY_UDT_UNION_V0 entries",
                        "udtUnions" in info_members,
                        sdk_class="SorobanContractInfo.$udtUnions"),
            self._field("enum_specs", "Parse SC_SPEC_ENTRY_UDT_ENUM_V0 entries",
                        "udtEnums" in info_members,
                        sdk_class="SorobanContractInfo.$udtEnums"),
            self._field("error_enum_specs", "Parse SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0 entries",
                        "udtErrorEnums" in info_members,
                        sdk_class="SorobanContractInfo.$udtErrorEnums"),
            self._field("event_specs", "Parse SC_SPEC_ENTRY_EVENT_V0 entries",
                        "events" in info_members,
                        sdk_class="SorobanContractInfo.$events"),
        ]

        # -- Type System & Value Conversion --
        type_section = SEPSection(
            name="Type System",
            description="Native-to-XDR value conversion using spec type definitions",
        )
        type_section.fields = [
            self._field("nativeToXdrSCVal", "Convert native values to XDR using SCSpecTypeDef",
                        "nativeToXdrSCVal" in spec_members,
                        sdk_class="ContractSpec.nativeToXdrSCVal()"),
            self._field("funcArgsToXdrSCValues", "Convert function arguments to XDR values by name",
                        "funcArgsToXdrSCValues" in spec_members,
                        sdk_class="ContractSpec.funcArgsToXdrSCValues()"),
            self._field("getFunc", "Look up function spec by name",
                        "getFunc" in spec_members,
                        sdk_class="ContractSpec.getFunc()"),
            self._field("findEntry", "Look up any spec entry by name",
                        "findEntry" in spec_members,
                        sdk_class="ContractSpec.findEntry()"),
        ]

        sections = [parsing_section, entry_section, type_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# SEP-53: Message Signing
# ===========================================================================

class SEP53Analyzer(SEPAnalyzerBase):
    sep_number = 53
    sep_title = "Sign and Verify Messages"
    sep_url = "https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0053.md"

    def analyze(self) -> CompatibilityMatrix:
        matrix = self._make_matrix()

        kp_members = self.sdk.get_class_members("KeyPair")

        # Read KeyPair source to verify payload construction details
        kp_path = self.sdk.find_class("KeyPair")
        kp_src = kp_path.read_text(encoding="utf-8") if kp_path else ""

        # -- Message Signing --
        signing_section = SEPSection(
            name="Message Signing",
            description="SEP-53 sign and verify methods on KeyPair",
        )
        signing_section.fields = [
            self._field("signMessage", "Sign message with SEP-53 payload construction",
                        "signMessage" in kp_members,
                        sdk_class="KeyPair.signMessage()"),
            self._field("verifyMessage", "Verify SEP-53 message signature",
                        "verifyMessage" in kp_members,
                        sdk_class="KeyPair.verifyMessage()"),
        ]

        # -- Payload Construction --
        payload_section = SEPSection(
            name="Payload Construction",
            description="SEP-53 message hashing and prefix",
        )
        payload_section.fields = [
            self._field("payload_prefix", 'Use "Stellar Signed Message:\\n" prefix',
                        'Stellar Signed Message:\\n' in kp_src,
                        sdk_class="KeyPair.calculateMessageHash()"),
            self._field("sha256_hashing", "Hash prefixed payload with SHA-256",
                        "Hash::generate" in kp_src and "calculateMessageHash" in kp_src,
                        sdk_class="KeyPair.calculateMessageHash()"),
        ]

        sections = [signing_section, payload_section]
        matrix.sections = sections
        matrix.overall_status = self._overall(sections)
        return matrix


# ===========================================================================
# Analyzer Factory
# ===========================================================================

class SEPAnalyzerFactory:
    """Maps SEP numbers to analyzer classes."""

    _ANALYZERS: dict[int, type[SEPAnalyzerBase]] = {
        1: SEP01Analyzer,
        2: SEP02Analyzer,
        5: SEP05Analyzer,
        6: SEP06Analyzer,
        7: SEP07Analyzer,
        8: SEP08Analyzer,
        9: SEP09Analyzer,
        10: SEP10Analyzer,
        11: SEP11Analyzer,
        12: SEP12Analyzer,
        24: SEP24Analyzer,
        30: SEP30Analyzer,
        31: SEP31Analyzer,
        38: SEP38Analyzer,
        45: SEP45Analyzer,
        46: SEP46Analyzer,
        47: SEP47Analyzer,
        48: SEP48Analyzer,
        53: SEP53Analyzer,
    }

    @classmethod
    def get_analyzer(
        cls,
        sep_number: int,
        sdk_analyzer: SDKAnalyzer,
    ) -> Optional[SEPAnalyzerBase]:
        analyzer_class = cls._ANALYZERS.get(sep_number)
        if analyzer_class is None:
            return None
        return analyzer_class(sdk_analyzer)

    @classmethod
    def supported_seps(cls) -> list[int]:
        return sorted(cls._ANALYZERS.keys())


# ===========================================================================
# Markdown Renderer
# ===========================================================================

class MatrixRenderer:
    """Renders a CompatibilityMatrix (or list of them) to Markdown."""

    STATUS_ICONS = {
        SupportStatus.SUPPORTED: "✅",
        SupportStatus.NOT_SUPPORTED: "❌",
        SupportStatus.PARTIAL: "⚠️",
        SupportStatus.NOT_APPLICABLE: "N/A",
        SupportStatus.UNKNOWN: "❓",
    }

    STATUS_LABELS = {
        SupportStatus.SUPPORTED: "Supported",
        SupportStatus.NOT_SUPPORTED: "Not Supported",
        SupportStatus.PARTIAL: "Partial",
        SupportStatus.NOT_APPLICABLE: "Not Applicable",
        SupportStatus.UNKNOWN: "Unknown",
    }

    def render_matrix(self, matrix: CompatibilityMatrix) -> str:
        lines: list[str] = []
        sep = matrix.sep_info
        icon = self.STATUS_ICONS[matrix.overall_status]
        label = self.STATUS_LABELS[matrix.overall_status]

        lines.append(f"# SEP-{sep.number:02d}: {sep.title}")
        lines.append("")
        lines.append(f"**Status:** {icon} {label}  ")
        lines.append(f"**SDK Version:** {matrix.sdk_version}  ")
        lines.append(f"**Generated:** {matrix.generated_at}  ")
        lines.append(f"**Spec:** [{sep.url}]({sep.url})")
        lines.append("")

        if matrix.notes:
            lines.append(f"> {matrix.notes}")
            lines.append("")

        # -- Overall coverage --
        all_fields = [f for s in matrix.sections for f in s.fields
                      if f.status != SupportStatus.NOT_APPLICABLE]
        total = len(all_fields)
        implemented = sum(1 for f in all_fields if f.status == SupportStatus.SUPPORTED)

        if total > 0:
            pct = implemented / total * 100
            lines.append("## Overall Coverage")
            lines.append("")
            lines.append(f"**Total Coverage:** {pct:.1f}% ({implemented}/{total} fields)")
            lines.append("")
            lines.append(f"- ✅ **Implemented:** {implemented}/{total}")
            not_impl = total - implemented
            lines.append(f"- ❌ **Not Implemented:** {not_impl}/{total}")
            lines.append("")

            # Per-section table
            lines.append("## Coverage by Section")
            lines.append("")
            lines.append("| Section | Coverage | Implemented | Total |")
            lines.append("|---------|----------|-------------|-------|")
            for section in matrix.sections:
                s_fields = [f for f in section.fields
                            if f.status != SupportStatus.NOT_APPLICABLE]
                s_total = len(s_fields)
                s_impl = sum(1 for f in s_fields if f.status == SupportStatus.SUPPORTED)
                s_pct = (s_impl / s_total * 100) if s_total > 0 else 0
                lines.append(f"| {section.name} | {s_pct:.1f}% | {s_impl} | {s_total} |")
            lines.append("")

        # -- Detailed sections --
        for section in matrix.sections:
            lines.append(f"## {section.name}")
            if section.description:
                lines.append("")
                lines.append(section.description)
            lines.append("")
            lines.append("| Feature | Status | Notes |")
            lines.append("|---------|--------|-------|")
            for f in section.fields:
                icon = self.STATUS_ICONS[f.status]
                label = self.STATUS_LABELS[f.status]
                notes = f.notes or ""
                if f.sdk_class:
                    notes = f"`{f.sdk_class}`" + (f" — {notes}" if notes else "")
                feature = f"`{f.name}`" if not f.name.startswith("`") else f.name
                lines.append(f"| {feature} | {icon} {label} | {notes} |")
            lines.append("")

        return "\n".join(lines)


# ===========================================================================
# Main Generator
# ===========================================================================

class SEPMatrixGenerator:
    """Orchestrates the full SEP matrix generation."""

    def __init__(self, sdk_root: Path, output_dir: Path):
        self.sdk_root = sdk_root
        self.output_dir = output_dir
        self.sdk_analyzer = SDKAnalyzer(sdk_root)
        self.renderer = MatrixRenderer()
        self.factory = SEPAnalyzerFactory()

    def generate(self, sep_numbers: Optional[list[int]] = None) -> list[CompatibilityMatrix]:
        targets = sep_numbers or self.factory.supported_seps()
        matrices: list[CompatibilityMatrix] = []

        for sep_num in targets:
            analyzer = self.factory.get_analyzer(sep_num, self.sdk_analyzer)
            if analyzer is None:
                print(f"  [SKIP] SEP-{sep_num}: no analyzer registered", file=sys.stderr)
                continue
            print(f"  [RUN]  Analyzing SEP-{sep_num}: {analyzer.sep_title} ...", file=sys.stderr)
            try:
                matrix = analyzer.analyze()
                matrices.append(matrix)
                print(
                    f"         -> {matrix.overall_status.value}  "
                    f"({sum(len(s.fields) for s in matrix.sections)} fields)",
                    file=sys.stderr,
                )
            except Exception as exc:
                print(f"  [ERR]  SEP-{sep_num} failed: {exc}", file=sys.stderr)

        return matrices

    def write_outputs(self, matrices: list[CompatibilityMatrix]) -> None:
        self.output_dir.mkdir(parents=True, exist_ok=True)

        # Individual SEP files
        for matrix in matrices:
            filename = f"SEP-{matrix.sep_info.number:04d}_COMPATIBILITY_MATRIX.md"
            out_path = self.output_dir / filename
            out_path.write_text(self.renderer.render_matrix(matrix), encoding="utf-8")
            print(f"  [WRITE] {out_path}", file=sys.stderr)



# ===========================================================================
# CLI entry-point
# ===========================================================================

def main() -> None:
    parser = argparse.ArgumentParser(
        description=(
            "Generate Stellar PHP SDK SEP compatibility matrices.\n\n"
            "Analyzes the PHP SDK source code and produces Markdown files "
            "showing which SEP features are supported."
        ),
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        "--sdk-root",
        type=Path,
        default=Path(__file__).resolve().parents[3],
        help="Path to the root of the Stellar PHP SDK repository (default: auto-detected)",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path(__file__).resolve().parents[3] / "compatibility" / "sep",
        help="Directory to write output Markdown files (default: compatibility/sep/)",
    )
    parser.add_argument(
        "--sep",
        type=int,
        nargs="+",
        metavar="N",
        help="Only generate matrix for the given SEP number(s) (e.g. --sep 1 10 24)",
    )
    parser.add_argument(
        "--list",
        action="store_true",
        help="List all supported SEP numbers and exit",
    )

    args = parser.parse_args()

    if args.list:
        seps = SEPAnalyzerFactory.supported_seps()
        print("Supported SEPs:", ", ".join(str(n) for n in seps))
        return

    print(f"Stellar PHP SDK SEP Compatibility Matrix Generator", file=sys.stderr)
    print(f"SDK root : {args.sdk_root}", file=sys.stderr)
    print(f"Output   : {args.output}", file=sys.stderr)
    print(f"Version  : {get_sdk_version(args.sdk_root)}", file=sys.stderr)
    print("", file=sys.stderr)

    generator = SEPMatrixGenerator(sdk_root=args.sdk_root, output_dir=args.output)
    matrices = generator.generate(sep_numbers=args.sep)

    if matrices:
        print("", file=sys.stderr)
        print("Writing output files ...", file=sys.stderr)
        generator.write_outputs(matrices)
        print("", file=sys.stderr)
        print(f"Done. {len(matrices)} matrices written to {args.output}", file=sys.stderr)
    else:
        print("No matrices generated.", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
