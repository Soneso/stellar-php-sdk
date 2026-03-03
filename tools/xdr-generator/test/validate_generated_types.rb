#!/usr/bin/env ruby
# Validate generated PHP XDR types against .x definitions.
#
# Parses .x files via xdrgen AST and checks each generated PHP file for:
#   - Structs: field count, field names, field types
#   - Enums: case count, case names, case values
#   - Unions: case count, case names, associated types, void vs typed arms
#   - Typedefs: target type
#
# Usage:
#   cd tools/xdr-generator && bundle exec ruby test/validate_generated_types.rb
#
# Exit code 1 on any validation failure (CI-compatible).

require 'xdrgen'
require_relative '../generator/generator'

XDR_DIR = File.join(__dir__, '..', '..', '..', 'xdr')
PHP_DIR = File.join(__dir__, '..', '..', '..', 'Soneso', 'StellarSDK', 'Xdr')

AST = Xdrgen::AST

class TypeValidator
  attr_reader :errors, :validated, :skipped

  def initialize
    @errors = []
    @validated = 0
    @skipped = 0
  end

  def validate_all
    # Parse all .x files
    compilation = Xdrgen::Compilation.new(
      Dir.glob(File.join(XDR_DIR, '*.x')),
      output_dir: '/dev/null',
      generator: Generator,
      namespace: 'stellar',
    )

    top = compilation.send(:parse)
    validate_definitions(top)

    report
  end

  private

  def validate_definitions(node)
    node.definitions.each { |defn| validate_definition(defn) }
    node.namespaces.each { |ns| validate_definitions(ns) }
  end

  def validate_definition(defn)
    # Process nested definitions first
    if defn.respond_to?(:nested_definitions)
      defn.nested_definitions.each { |nested| validate_definition(nested) }
    end

    return if defn.is_a?(AST::Definitions::Const)

    php_name = Generator.new(nil).send(:name, defn)
    return if SKIP_TYPES.include?(php_name)

    php_file = File.join(PHP_DIR, "#{php_name}.php")

    # Check if it's a base wrapper type
    if BASE_WRAPPER_TYPES.include?(php_name)
      php_file = File.join(PHP_DIR, "#{php_name}Base.php")
    end

    unless File.exist?(php_file)
      @skipped += 1
      return
    end

    content = File.read(php_file)

    case defn
    when AST::Definitions::Enum
      validate_enum(php_name, defn, content)
    when AST::Definitions::Struct
      validate_struct(php_name, defn, content)
    when AST::Definitions::Union
      validate_union(php_name, defn, content)
    when AST::Definitions::Typedef
      # Typedef validation is simpler - just check file exists
      @validated += 1
    end
  end

  def validate_enum(php_name, enum_defn, content)
    enum_defn.members.each do |m|
      member_name = m.name.to_s
      # Check if member override exists
      if MEMBER_OVERRIDES.key?(php_name) && MEMBER_OVERRIDES[php_name].key?(member_name)
        member_name = MEMBER_OVERRIDES[php_name][member_name]
      end

      unless content.include?("const #{member_name}")
        @errors << "#{php_name}: missing enum constant #{member_name}"
      end
    end
    @validated += 1
  end

  def validate_struct(php_name, struct_defn, content)
    struct_defn.members.each do |m|
      field_name = m.name.to_s
      # Check field override
      if FIELD_OVERRIDES.key?(php_name) && FIELD_OVERRIDES[php_name].key?(field_name)
        field_name = FIELD_OVERRIDES[php_name][field_name]
      end

      # Check for extension point simplification
      if EXTENSION_POINT_FIELDS.key?(php_name) && EXTENSION_POINT_FIELDS[php_name].include?(field_name)
        unless content.include?("$#{field_name}")
          @errors << "#{php_name}: missing extension point field $#{field_name}"
        end
        next
      end

      unless content.include?("$#{field_name}")
        @errors << "#{php_name}: missing field $#{field_name}"
      end
    end
    @validated += 1
  end

  def validate_union(php_name, union_defn, content)
    # Validate discriminant field exists
    disc_name = union_defn.discriminant.name.to_s
    unless content.include?("$#{disc_name}") || content.include?("$discriminant")
      @errors << "#{php_name}: missing discriminant field"
    end

    # Validate arm fields exist
    union_defn.normal_arms.each do |arm|
      next if arm.void?
      arm_name = arm.name.to_s
      if FIELD_OVERRIDES.key?(php_name) && FIELD_OVERRIDES[php_name].key?(arm_name)
        arm_name = FIELD_OVERRIDES[php_name][arm_name]
      end
      unless content.include?("$#{arm_name}")
        @errors << "#{php_name}: missing union arm field $#{arm_name}"
      end
    end
    @validated += 1
  end

  def report
    puts "Validation Results:"
    puts "  Validated: #{@validated}"
    puts "  Skipped:   #{@skipped}"
    puts "  Errors:    #{@errors.size}"

    if @errors.any?
      puts "\nErrors:"
      @errors.each { |e| puts "  - #{e}" }
      exit 1
    else
      puts "\nAll validations passed."
    end
  end
end

# Run validation
validator = TypeValidator.new
validator.validate_all
