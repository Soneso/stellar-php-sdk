#!/usr/bin/env python3
"""Derive phase-2/3/4-owned.txt regex files from the live BASE_WRAPPER_TYPES,
CAT_A_INLINE_TARGETS, and shape classification of every Soneso/StellarSDK/Xdr/Xdr*.php.

Each output file contains one PCRE alternation per line, anchored on the form
    ^Soneso/StellarSDK/Xdr/Xdr<TypeName>(Base)?\\.php$

Phase 2 owns: non-Cat-B enums + scalar-typedef wrapper classes.
Phase 3 owns: non-Cat-B structs + unions + EXTENSION_POINT_FIELDS holders.
Phase 4 owns: every Cat-B *Base.php; every Cat-A inline-emission target;
              XdrDataValue.php; override registries.

Heuristics for shape classification (limitations documented inline):
  enum  - file declares `public int $value;` and at least one `const NAME = <int>;`
          and no `switch (...->type->...)` / `switch (...->discriminant->...)` shape.
  union - file declares a `switch (` on a discriminant property in either encode()
          or decode(), AND a public typed `XdrXxxType` (or similar discriminant)
          property; OR `switch (\\$xdr->readInteger32()` in decode for int-cased.
  struct - all other class files under Soneso/StellarSDK/Xdr/Xdr*.php (default).

Files that cannot be classified default to phase-4-owned.txt; the Phase 4
reviewer catches misclassification because Phase 4 already covers the broadest
set (Cat-B + Cat-A + override registries).

Usage:
    python3 tools/sep-51-fixtures/compute_phase_owned.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path
from typing import Iterable


REPO_ROOT = Path(__file__).resolve().parents[2]
XDR_DIR = REPO_ROOT / "Soneso" / "StellarSDK" / "Xdr"
TYPE_OVERRIDES = REPO_ROOT / "tools" / "xdr-generator" / "generator" / "type_overrides.rb"
CAT_A_RB = REPO_ROOT / "tools" / "xdr-generator" / "generator" / "cat_a_inline_targets.rb"

PHASE2_OUT = REPO_ROOT / "tools" / "sep-51-fixtures" / "phase-2-owned.txt"
PHASE3_OUT = REPO_ROOT / "tools" / "sep-51-fixtures" / "phase-3-owned.txt"
PHASE4_OUT = REPO_ROOT / "tools" / "sep-51-fixtures" / "phase-4-owned.txt"

ENUM_VALUE_RE = re.compile(r"public\s+int\s+\$value\b")
ENUM_CONST_RE = re.compile(r"^\s*const\s+[A-Z][A-Z0-9_]*\s*=\s*-?\d", re.MULTILINE)
UNION_SWITCH_RE = re.compile(
    r"switch\s*\(\s*\$\w+->(?:type|discriminant)->getValue\(\)\s*\)"
)
INT_CASED_SWITCH_RE = re.compile(
    r"switch\s*\(\s*\$\w+->readInteger32\(\)\s*\)|switch\s*\(\s*\$\w+\)\s*\{?\s*case\s+0\s*:"
)


def parse_ruby_array_const(path: Path, const_name: str) -> list[str]:
    """Extract %w[ ... ] entries for a constant assignment in a .rb file."""
    text = path.read_text(encoding="utf-8")
    pattern = re.compile(
        rf"{re.escape(const_name)}\s*=\s*%w\[(.*?)\]\.(?:sort\.)?freeze",
        re.DOTALL,
    )
    match = pattern.search(text)
    if not match:
        raise SystemExit(f"could not parse {const_name} in {path}")
    body = match.group(1)
    return [w for w in body.split() if w]


def parse_extension_point_fields(path: Path) -> set[str]:
    text = path.read_text(encoding="utf-8")
    block = re.search(
        r"EXTENSION_POINT_FIELDS\s*=\s*\{(.*?)\}\.freeze",
        text,
        re.DOTALL,
    )
    if not block:
        return set()
    keys = re.findall(r'"([^"]+)"\s*=>', block.group(1))
    return set(keys)


def list_xdr_classes() -> list[Path]:
    return sorted(p for p in XDR_DIR.glob("Xdr*.php") if p.is_file())


def classify_shape(php_path: Path) -> str:
    """Return one of 'enum', 'union', 'struct'.

    Order matters: enum detection runs first because XDR enum classes also contain
    switch statements (in their `decode(int $value)` and `decode($name)` factories)
    that would otherwise match union heuristics. The enum signal is the
    combination of `public int $value;` AND a class-body `const NAME = <int>;`.
    """
    try:
        text = php_path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        return "struct"
    if ENUM_VALUE_RE.search(text) and ENUM_CONST_RE.search(text):
        return "enum"
    if UNION_SWITCH_RE.search(text):
        return "union"
    if INT_CASED_SWITCH_RE.search(text):
        # Int-cased unions and a few struct decoders match this; favour union to bias
        # toward Phase 3 ownership which already covers int-cased unions.
        return "union"
    return "struct"


def type_name(path: Path) -> str:
    """Strip the trailing 'Base' suffix when present, return the underlying type name."""
    stem = path.stem  # e.g. 'XdrAccountIDBase' or 'XdrSCVal'
    if stem.endswith("Base"):
        return stem[:-4]
    return stem


def regex_for(path: Path) -> str:
    rel = path.relative_to(REPO_ROOT).as_posix()
    return f"^{re.escape(rel)}$"


def write_owned(out: Path, paths: Iterable[Path]) -> None:
    lines = sorted(regex_for(p) for p in paths)
    out.write_text("\n".join(lines) + ("\n" if lines else ""), encoding="utf-8")


def main() -> int:
    base_wrappers = set(parse_ruby_array_const(TYPE_OVERRIDES, "BASE_WRAPPER_TYPES"))
    cat_a = set(parse_ruby_array_const(CAT_A_RB, "CAT_A_INLINE_TARGETS"))
    ext_point_holders = parse_extension_point_fields(TYPE_OVERRIDES)
    skip_types = set(parse_ruby_array_const(TYPE_OVERRIDES, "SKIP_TYPES"))

    phase2: list[Path] = []
    phase3: list[Path] = []
    phase4: list[Path] = []

    for php in list_xdr_classes():
        # Skip non-XDR PHP files that happen to share the prefix.
        stem = php.stem
        if stem == "TxRepHelper":
            continue

        tname = type_name(php)
        is_base = stem.endswith("Base")
        wrapper_for_base = stem[:-4] if is_base else None

        # Phase 4 ownership precedence:
        # 1. Cat-B *Base.php files.
        # 2. Cat-A inline targets (the wrapper, since it's hand-written).
        # 3. SKIP_TYPES (XdrDataValue).
        if is_base and wrapper_for_base in base_wrappers:
            phase4.append(php)
            continue
        # The wrapper file (XdrXxx.php) for a Cat-B type is hand-written; ownership
        # of the wrapper is Phase 4 because the wrapper-introduces-new-constants
        # audit and any registered template-body override are Phase 4 deliverables.
        if not is_base and stem in base_wrappers:
            phase4.append(php)
            continue
        if stem in cat_a:
            phase4.append(php)
            continue
        if stem in skip_types:
            phase4.append(php)
            continue

        # Phase 3 ownership: EXTENSION_POINT_FIELDS holders are explicitly Phase 3.
        if stem in ext_point_holders:
            phase3.append(php)
            continue

        # Phase 2 ownership: enums (and scalar typedef wrappers — heuristically same shape).
        # Phase 3 ownership: structs and unions.
        shape = classify_shape(php)
        if shape == "enum":
            phase2.append(php)
        elif shape in ("struct", "union"):
            phase3.append(php)
        else:
            # Unclassifiable — fall through to Phase 4 per the documented default.
            phase4.append(php)

    write_owned(PHASE2_OUT, phase2)
    write_owned(PHASE3_OUT, phase3)
    write_owned(PHASE4_OUT, phase4)

    print(f"phase-2-owned.txt: {len(phase2)} entries -> {PHASE2_OUT.relative_to(REPO_ROOT)}")
    print(f"phase-3-owned.txt: {len(phase3)} entries -> {PHASE3_OUT.relative_to(REPO_ROOT)}")
    print(f"phase-4-owned.txt: {len(phase4)} entries -> {PHASE4_OUT.relative_to(REPO_ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
