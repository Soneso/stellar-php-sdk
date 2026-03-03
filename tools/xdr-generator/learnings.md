# XDR Generator Learnings

## PHP Patterns

### Encoding Infrastructure
- `XdrEncoder` — static utility: `integer32()`, `unsignedInteger64()`, `opaqueFixed()`, `opaqueVariable()`, `string()`, `boolean()`
- `XdrDecoder` — legacy static utility (not used by generated code)
- `XdrBuffer` — stateful reader, all generated `decode()` methods use this

### Type Patterns
- Enums: class with `private int $value` + constants + `encode(): string` + `static decode(XdrBuffer): self`
- Structs: class with public typed properties + constructor + encode/decode
- Unions: class with discriminant + nullable arm fields + switch-based encode/decode
- Typedefs: wrapper classes around primitives or other types

### XDR-to-PHP Type Map
| XDR | PHP | Encode | Decode |
|-----|-----|--------|--------|
| int | int | `XdrEncoder::integer32()` | `$xdr->readInteger32()` |
| unsigned int | int | `XdrEncoder::unsignedInteger32()` | `$xdr->readUnsignedInteger32()` |
| hyper | int | `XdrEncoder::integer64()` | `$xdr->readInteger64()` |
| unsigned hyper | int | `XdrEncoder::unsignedInteger64()` | `$xdr->readUnsignedInteger64()` |
| bool | bool | `XdrEncoder::boolean()` | `$xdr->readBoolean()` |
| string | string | `XdrEncoder::string()` | `$xdr->readString()` |
| opaque[N] | string | `XdrEncoder::opaqueFixed($v, N)` | `$xdr->readOpaqueFixed(N)` |
| opaque<> | string | `XdrEncoder::opaqueVariable()` | `$xdr->readOpaqueVariable()` |

### SDK-Specific Types
- `BigInteger` (phpseclib3): used for some int64 fields (amounts, balances)
- `XdrDataValueMandatory`: non-optional opaque data wrapper
- Full audit pending in Phase 2

## Batch Findings
_(Updated after each batch)_

## Cross-Boundary Fix Patterns
_(Updated as patterns emerge)_
