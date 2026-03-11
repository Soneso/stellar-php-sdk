#!/usr/bin/env ruby
# Regenerate snapshot baselines from the current generator output.
#
# Usage:
#   cd tools/xdr-generator && bundle exec ruby test/update_snapshots.rb
#
# After running, review the diff and commit the updated snapshots.

require 'fileutils'

SNAPSHOT_DIR = File.join(__dir__, 'snapshots')
OUTPUT_DIR = File.join(__dir__, '..', '..', '..', 'Soneso', 'StellarSDK', 'Xdr')

# List of snapshot files to maintain.
# Add new entries as generator coverage expands.
SNAPSHOT_FILES = [
  "XdrAssetType.php",                    # Simple enum
  "XdrPrice.php",                        # Simple struct (2 int fields)
  "XdrAsset.php",                        # Union with enum discriminant
  "XdrSCValBase.php",                    # Base wrapper union, self-referencing
  "XdrAccountEntryV1Ext.php",            # Union with integer discriminant
  "XdrLedgerEntryData.php",              # Large union, many struct-typed arms
  "XdrTransactionResultResult.php",      # Union with fee bump arms (regression guard)
  "XdrClaimableBalanceEntryExtV1.php",   # Struct with extension point field
].freeze

FileUtils.mkdir_p(SNAPSHOT_DIR)

updated = 0
SNAPSHOT_FILES.each do |filename|
  source = File.join(OUTPUT_DIR, filename)
  dest = File.join(SNAPSHOT_DIR, filename)

  unless File.exist?(source)
    puts "SKIP: #{filename} (not yet generated)"
    next
  end

  FileUtils.cp(source, dest)
  puts "UPDATED: #{filename}"
  updated += 1
end

puts "\n#{updated} snapshot(s) updated in #{SNAPSHOT_DIR}"
