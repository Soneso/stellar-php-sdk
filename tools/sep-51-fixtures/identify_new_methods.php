<?php declare(strict_types=1);

// Identify methods that are "new" in the working tree relative to a diff base
// for a given PHP file. A method is "new" iff the working-tree version of the
// file declares `function <name>(...)` AND the diff-base version does not.
//
// stdout: one line per new method, formatted as
//     <file>:<startLine>-<endLine>:<methodName>
//
// Used by tools/sep-51-fixtures/coverage_gate.sh subgate (b) to scope the line
// coverage check to net-new methods only.
//
// Usage: php tools/sep-51-fixtures/identify_new_methods.php <file> [<diff_base>]
//
// Exit 0 on success. Exit 2 on usage error. Exit 0 silently when the file
// does not exist on either side or is not tracked.

if ($argc < 2) {
    fwrite(STDERR, "usage: identify_new_methods.php <file> [<diff_base>]\n");
    exit(2);
}

$file = $argv[1];
$diffBase = $argv[2] ?? 'main';

if (!is_file($file)) {
    exit(0);
}

function git_show(string $ref, string $path): ?string {
    $cmd = sprintf('git show %s:%s 2>/dev/null', escapeshellarg($ref), escapeshellarg($path));
    $output = shell_exec($cmd);
    if ($output === null || $output === false) {
        return null;
    }
    return $output;
}

$head = git_show('HEAD', $file);
if ($head === null) {
    // File untracked at HEAD; fall back to the working-tree contents (treats
    // every method as new).
    $head = file_get_contents($file);
}
$base = git_show($diffBase, $file);
if ($base === null) {
    // File absent at diff base; every method in HEAD is new.
    $base = '';
}

/**
 * Extract method declarations as map<methodName, [startLine, endLine]>.
 * Brace-counter walks the file line by line. Robust against single-line method
 * bodies and trailing braces inside strings/comments only to the same extent
 * as the rest of the codebase already tolerates (we are scanning generated /
 * hand-written PHP that follows PSR-12 style).
 */
function extract_methods(string $php): array {
    $lines = preg_split('/\r\n|\r|\n/', $php);
    $methods = [];
    $stack = []; // stack of [name, startLine, openBraces]
    $inMethod = false;
    $braceCount = 0;
    $currentName = null;
    $currentStart = 0;

    foreach ($lines as $idx => $line) {
        $lineNo = $idx + 1;
        // Strip line-comments outside strings to reduce false-positive opens.
        $stripped = preg_replace('@//.*$@', '', $line);
        $stripped = $stripped ?? $line;

        if (!$inMethod) {
            if (preg_match('/\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $stripped, $m)) {
                $currentName = $m[1];
                $currentStart = $lineNo;
                $inMethod = true;
                $opens = substr_count($stripped, '{');
                $closes = substr_count($stripped, '}');
                $braceCount = $opens - $closes;
                if ($braceCount === 0 && $opens >= 1 && $closes >= 1) {
                    // Single-line method body: `public function foo(): T { return ...; }`
                    // Opens and closes on the same line balance to zero.
                    $methods[$currentName] = [$currentStart, $lineNo];
                    $inMethod = false;
                    $currentName = null;
                } elseif ($braceCount === 0 && $opens === 0 && $closes === 0
                    && preg_match('/;\s*$/', trim($stripped))) {
                    // Abstract / interface declaration with trailing semicolon — skip.
                    $inMethod = false;
                    $currentName = null;
                }
                // Otherwise: $braceCount > 0 means a multi-line body has begun;
                // the loop's else branch will close it when braceCount drops to 0.
            }
        } else {
            $braceCount += substr_count($stripped, '{') - substr_count($stripped, '}');
            if ($braceCount <= 0) {
                $methods[$currentName] = [$currentStart, $lineNo];
                $inMethod = false;
                $currentName = null;
                $braceCount = 0;
            }
        }
    }
    return $methods;
}

$headMethods = extract_methods($head);
$baseMethods = extract_methods($base);

foreach ($headMethods as $name => [$startLine, $endLine]) {
    if (isset($baseMethods[$name])) {
        continue;
    }
    printf("%s:%d-%d:%s\n", $file, $startLine, $endLine, $name);
}

exit(0);
