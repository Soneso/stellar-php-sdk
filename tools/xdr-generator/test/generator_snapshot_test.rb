require 'minitest/autorun'
require 'xdrgen'
require_relative '../generator/generator'

class GeneratorSnapshotTest < Minitest::Test
  SNAPSHOT_DIR = File.join(__dir__, 'snapshots')
  OUTPUT_DIR = File.join(__dir__, '..', '..', '..', 'Soneso', 'StellarSDK', 'Xdr')
  XDR_DIR = File.join(__dir__, '..', '..', '..', 'xdr')

  # ------------------------------------------------------------------
  # Configuration sanity checks
  # ------------------------------------------------------------------

  def test_generator_loads
    assert defined?(Generator), "Generator class must be defined"
    assert Generator < Xdrgen::Generators::Base, "Generator must extend Xdrgen::Generators::Base"
  end

  def test_skip_types_populated
    assert SKIP_TYPES.is_a?(Array), "SKIP_TYPES must be an Array"
    assert SKIP_TYPES.size > 0, "SKIP_TYPES must not be empty during development"
  end

  def test_overrides_loaded
    assert defined?(NAME_OVERRIDES), "NAME_OVERRIDES must be defined"
    assert defined?(MEMBER_OVERRIDES), "MEMBER_OVERRIDES must be defined"
    assert defined?(FIELD_OVERRIDES), "FIELD_OVERRIDES must be defined"
    assert defined?(FIELD_TYPE_OVERRIDES), "FIELD_TYPE_OVERRIDES must be defined"
    assert defined?(TYPE_OVERRIDES), "TYPE_OVERRIDES must be defined"
    assert defined?(BASE_WRAPPER_TYPES), "BASE_WRAPPER_TYPES must be defined"
  end

  # ------------------------------------------------------------------
  # Snapshot comparison tests (added incrementally per batch)
  # ------------------------------------------------------------------

  # Example (uncomment when first snapshot is created):
  # def test_snapshot_xdr_asset_type
  #   assert_snapshot_match("XdrAssetType.php")
  # end

  private

  def assert_snapshot_match(filename)
    skip "Snapshots not yet created" unless Dir.exist?(SNAPSHOT_DIR) && !Dir.empty?(SNAPSHOT_DIR)

    generated = File.join(OUTPUT_DIR, filename)
    snapshot = File.join(SNAPSHOT_DIR, filename)

    assert File.exist?(generated), "Generated file not found: #{generated}"
    assert File.exist?(snapshot), "Snapshot file not found: #{snapshot}"

    generated_content = File.read(generated)
    snapshot_content = File.read(snapshot)

    assert_equal snapshot_content, generated_content,
      "Generated #{filename} does not match snapshot. " \
      "If the change is intentional, run: make xdr-generator-update-snapshots"
  end
end
