require 'xdrgen'
require_relative 'generator/generator'

puts "Generating PHP XDR classes..."

Dir.chdir("../..")

Xdrgen::Compilation.new(
  Dir.glob("xdr/*.x"),
  output_dir: "Soneso/StellarSDK/Xdr/",
  generator: Generator,
  namespace: "stellar",
).compile

puts "Done!"
