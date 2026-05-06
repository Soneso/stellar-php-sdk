require_relative 'generator/round_trip_emitter'

puts "Emitting SEP-51 round-trip tests..."

RoundTripEmitter.run

puts "Done!"
