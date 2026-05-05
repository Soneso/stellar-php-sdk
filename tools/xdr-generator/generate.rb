require 'xdrgen'
require_relative 'generator/generator'
require_relative 'generator/round_trip_emitter'

puts "Generating PHP XDR classes..."

Dir.chdir("../..")

Xdrgen::Compilation.new(
  Dir.glob("xdr/*.x"),
  output_dir: "Soneso/StellarSDK/Xdr/",
  generator: Generator,
  namespace: "stellar",
).compile

puts "Emitting SEP-51 round-trip tests..."
RoundTripEmitter.run

puts "Done!"
