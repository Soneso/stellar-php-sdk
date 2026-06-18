<?php

declare(strict_types=1);

/**
 * Stellar PHP SDK API Reference Generator
 *
 * Generates a compact markdown file listing all public method signatures
 * for the Stellar PHP SDK classes using reflection.
 *
 * Usage (from the repository root): php tools/skill-generator/generate_api_reference.php
 */

// Configuration — paths derived from script location
// Repo root is one level up from this script's directory
define('REPO_ROOT', dirname(__DIR__, 2));
define('SDK_PATH', REPO_ROOT . '/Soneso/StellarSDK/');
define('OUTPUT_PATH', REPO_ROOT . '/skills/stellar-php-sdk/references/api_reference.md');
define('AUTOLOAD_PATH', REPO_ROOT . '/vendor/autoload.php');

// Namespaces to skip
const SKIP_NAMESPACES = [
    'Soneso\\StellarSDK\\Xdr\\',
];

// Require composer autoloader
if (!is_file(AUTOLOAD_PATH)) {
    fwrite(STDERR, "Composer autoloader not found at " . AUTOLOAD_PATH . "\n");
    fwrite(STDERR, "Run `composer install` from the repository root first.\n");
    exit(1);
}
require_once AUTOLOAD_PATH;

// Stats
$stats = [
    'classes' => 0,
    'methods' => 0,
    'skipped' => 0,
    'errors' => 0,
];

// Store classes by group
$groups = [
    'core' => [],
    'crypto' => [],
    'requests' => [],
    'responses' => [],
    'soroban' => [],
    'sep' => [],
    'util' => [],
    'exceptions' => [],
];

/**
 * Recursively find all PHP files in a directory
 */
function findPhpFiles(string $directory): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * Extract class name from file path
 */
function extractClassName(string $filePath): ?string {
    $content = file_get_contents($filePath);

    // Extract namespace
    if (!preg_match('/namespace\s+([\w\\\\]+);/', $content, $nsMatch)) {
        return null;
    }

    // Extract class/interface/trait name (anchored to line start to skip PHPDoc comments)
    if (!preg_match('/^(?:abstract\s+|final\s+|readonly\s+)*(class|interface|trait|enum)\s+(\w+)/m', $content, $classMatch)) {
        return null;
    }

    return $nsMatch[1] . '\\' . $classMatch[2];
}

/**
 * Check if namespace should be skipped
 */
function shouldSkipNamespace(string $className): bool {
    foreach (SKIP_NAMESPACES as $skipNs) {
        if (str_starts_with($className, $skipNs)) {
            return true;
        }
    }
    return false;
}

/**
 * Determine which group a class belongs to
 */
function determineGroup(string $className): string {
    if (str_contains($className, '\\SEP\\')) {
        return 'sep';
    }
    if (str_contains($className, '\\Crypto\\')) {
        return 'crypto';
    }
    if (str_contains($className, '\\Requests\\')) {
        return 'requests';
    }
    if (str_contains($className, '\\Responses\\')) {
        return 'responses';
    }
    if (str_contains($className, '\\Soroban\\')) {
        return 'soroban';
    }
    if (str_contains($className, '\\Util\\')) {
        return 'util';
    }
    if (str_contains($className, '\\Exceptions\\')) {
        return 'exceptions';
    }
    return 'core';
}

/**
 * Get short class name from fully qualified name
 */
function getShortName(string $className): string {
    $parts = explode('\\', $className);
    return end($parts);
}

/**
 * Format a type hint to use short names
 */
function formatType(?ReflectionType $type): string {
    if ($type === null) {
        return '';
    }

    if ($type instanceof ReflectionUnionType) {
        $types = array_map(function($t) {
            return formatSingleType($t);
        }, $type->getTypes());
        return implode('|', $types);
    }

    return formatSingleType($type);
}

/**
 * Format a single type (handling nullable)
 */
function formatSingleType(ReflectionNamedType|ReflectionIntersectionType $type): string {
    if ($type instanceof ReflectionIntersectionType) {
        $types = array_map(fn($t) => getShortName($t->getName()), $type->getTypes());
        return implode('&', $types);
    }

    $name = $type->getName();

    // Use short name for classes
    if (!$type->isBuiltin()) {
        $name = getShortName($name);
    }

    // Handle nullable
    if ($type->allowsNull() && $name !== 'mixed' && $name !== 'null') {
        return '?' . $name;
    }

    return $name;
}

/**
 * Format parameter with type and default value
 */
function formatParameter(ReflectionParameter $param): string {
    $parts = [];

    // Type hint
    if ($param->hasType()) {
        $parts[] = formatType($param->getType());
    }

    // Parameter name
    $name = '$' . $param->getName();
    if ($param->isVariadic()) {
        $name = '...' . $name;
    }
    $parts[] = $name;

    // Default value
    if ($param->isOptional() && !$param->isVariadic()) {
        try {
            if ($param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
                if ($default === null) {
                    $parts[count($parts) - 1] .= ' = null';
                } elseif (is_bool($default)) {
                    $parts[count($parts) - 1] .= ' = ' . ($default ? 'true' : 'false');
                } elseif (is_string($default)) {
                    $parts[count($parts) - 1] .= ' = \'' . addslashes($default) . '\'';
                } elseif (is_array($default)) {
                    $parts[count($parts) - 1] .= ' = []';
                } else {
                    $parts[count($parts) - 1] .= ' = ' . var_export($default, true);
                }
            }
        } catch (ReflectionException $e) {
            // Skip default value if it can't be determined
        }
    }

    return implode(' ', $parts);
}

/**
 * Extract class info using reflection
 */
function extractClassInfo(string $className): ?array {
    try {
        $reflection = new ReflectionClass($className);

        // Skip abstract classes and interfaces in some cases
        $classType = '';
        if ($reflection->isInterface()) {
            $classType = 'interface';
        } elseif ($reflection->isTrait()) {
            $classType = 'trait';
        } elseif ($reflection->isAbstract()) {
            $classType = 'abstract';
        }

        // Get parent and interfaces
        $parent = $reflection->getParentClass();
        $parentName = null;
        if ($parent && !in_array($parent->getName(), ['Exception', 'RuntimeException', 'InvalidArgumentException'])) {
            $parentName = getShortName($parent->getName());
        }

        $interfaces = [];
        foreach ($reflection->getInterfaces() as $interface) {
            $interfaceName = $interface->getName();
            // Skip common PHP interfaces
            if (!in_array($interfaceName, ['Throwable', 'Stringable', 'JsonSerializable', 'ArrayAccess', 'Iterator', 'IteratorAggregate', 'Countable'])) {
                $interfaces[] = getShortName($interfaceName);
            }
        }

        // Extract public constants (defined in this class only)
        $constants = [];
        foreach ($reflection->getReflectionConstants() as $constant) {
            if (!$constant->isPublic()) {
                continue;
            }
            if ($constant->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            $value = $constant->getValue();
            if (is_string($value)) {
                $constants[] = "const {$constant->getName()} = '{$value}'";
            } elseif (is_bool($value)) {
                $constants[] = "const {$constant->getName()} = " . ($value ? 'true' : 'false');
            } elseif (is_int($value) || is_float($value)) {
                $constants[] = "const {$constant->getName()} = {$value}";
            } elseif (is_null($value)) {
                $constants[] = "const {$constant->getName()} = null";
            } else {
                $constants[] = "const {$constant->getName()}";
            }
        }

        // Extract public properties (defined in this class only)
        $properties = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            $prop = '';
            if ($property->isStatic()) {
                $prop .= 'static ';
            }
            if ($property->hasType()) {
                $prop .= formatType($property->getType()) . ' ';
            }
            $prop .= '$' . $property->getName();
            $properties[] = $prop;
        }

        // Extract methods
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods except __construct
            if (str_starts_with($method->getName(), '__') && $method->getName() !== '__construct') {
                continue;
            }

            // Only include methods defined in this class (not inherited)
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $signature = '';

            // Static
            if ($method->isStatic()) {
                $signature .= 'static ';
            }

            // Method name
            $signature .= $method->getName() . '(';

            // Parameters
            $params = array_map('formatParameter', $method->getParameters());
            $signature .= implode(', ', $params);

            $signature .= ')';

            // Return type
            if ($method->hasReturnType()) {
                $signature .= ': ' . formatType($method->getReturnType());
            }

            $methods[] = $signature;
        }

        return [
            'short_name' => getShortName($className),
            'type' => $classType,
            'parent' => $parentName,
            'interfaces' => $interfaces,
            'constants' => $constants,
            'properties' => $properties,
            'methods' => $methods,
        ];

    } catch (ReflectionException $e) {
        fwrite(STDERR, "Warning: Could not reflect class {$className}: {$e->getMessage()}\n");
        return null;
    }
}

/**
 * Format class section for markdown
 */
function formatClassSection(array $classInfo): string {
    $output = '';

    // Class header
    $header = '## ';
    if ($classInfo['type']) {
        $header .= $classInfo['type'] . ' ';
    }
    $header .= $classInfo['short_name'];

    // Add parent/interfaces
    if ($classInfo['parent']) {
        $header .= ' extends ' . $classInfo['parent'];
    }
    if (!empty($classInfo['interfaces'])) {
        $header .= ' implements ' . implode(', ', $classInfo['interfaces']);
    }

    $output .= $header . "\n";

    // Constants
    foreach ($classInfo['constants'] as $constant) {
        $output .= $constant . "\n";
    }

    // Public properties
    foreach ($classInfo['properties'] as $property) {
        $output .= $property . "\n";
    }

    // Methods
    foreach ($classInfo['methods'] as $method) {
        $output .= $method . "\n";
    }

    $output .= "\n";

    return $output;
}

// Main execution
fwrite(STDERR, "Scanning PHP files in " . SDK_PATH . "...\n");

$files = findPhpFiles(SDK_PATH);
fwrite(STDERR, "Found " . count($files) . " PHP files\n");

fwrite(STDERR, "Extracting class information...\n");

foreach ($files as $file) {
    $className = extractClassName($file);

    if ($className === null) {
        continue;
    }

    // Skip certain namespaces
    if (shouldSkipNamespace($className)) {
        $stats['skipped']++;
        continue;
    }

    fwrite(STDERR, "Processing: {$className}\n");

    $classInfo = extractClassInfo($className);

    if ($classInfo === null) {
        $stats['errors']++;
        continue;
    }

    // Add to appropriate group
    $group = determineGroup($className);
    $groups[$group][] = $classInfo;

    $stats['classes']++;
    $stats['methods'] += count($classInfo['methods']);
}

// Sort classes within each group
foreach ($groups as &$group) {
    usort($group, fn($a, $b) => strcmp($a['short_name'], $b['short_name']));
}

fwrite(STDERR, "Generating markdown output...\n");

// Generate markdown
$markdown = "# PHP SDK API Reference (Signatures)\n\n";
$markdown .= "Compact method signature reference for `soneso/stellar-php-sdk`.\n";
$markdown .= "Generated by `generate_api_reference.php`. Do not edit manually.\n\n";
$markdown .= "**Stats:** {$stats['classes']} classes, {$stats['methods']} methods\n\n";

// Generate each group
$groupTitles = [
    'core' => 'Core Classes',
    'crypto' => 'Crypto',
    'requests' => 'Requests (Query Builders)',
    'responses' => 'Responses',
    'soroban' => 'Soroban',
    'sep' => 'SEP (Stellar Ecosystem Proposals)',
    'util' => 'Util',
    'exceptions' => 'Exceptions',
];

foreach ($groupTitles as $key => $title) {
    if (empty($groups[$key])) {
        continue;
    }

    $markdown .= "---\n";
    $markdown .= "## {$title}\n";
    $markdown .= "---\n\n";

    foreach ($groups[$key] as $classInfo) {
        $markdown .= formatClassSection($classInfo);
    }
}

// Ensure output directory exists
$outputDir = dirname(OUTPUT_PATH);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Write output
file_put_contents(OUTPUT_PATH, $markdown);

// Print stats
fwrite(STDERR, "\n=== Generation Complete ===\n");
fwrite(STDERR, "Classes processed: {$stats['classes']}\n");
fwrite(STDERR, "Methods extracted: {$stats['methods']}\n");
fwrite(STDERR, "Files skipped: {$stats['skipped']}\n");
fwrite(STDERR, "Errors: {$stats['errors']}\n");
fwrite(STDERR, "Output written to: " . OUTPUT_PATH . "\n");
fwrite(STDERR, "File size: " . number_format(filesize(OUTPUT_PATH)) . " bytes\n");

echo "API reference generated successfully!\n";
