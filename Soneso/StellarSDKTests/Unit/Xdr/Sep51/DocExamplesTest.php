<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Sandboxed runner for fenced PHP code blocks in docs/sep/sep-51.md.
 *
 * Each fenced ```php block in the documentation is parsed, validated against
 * a static blocklist regex, and executed in an isolated child PHP process
 * with disable_functions and open_basedir constraining the runtime surface.
 *
 * Each block executes twice; the two stdout streams must match (determinism
 * check). Blocks may opt out of the determinism check via an HTML
 * <!-- nondeterministic --> comment paired with an <!-- expected-pattern: ... -->
 * regex assertion.
 *
 * The runner enforces the SEP-0051 documentation contract: every example in
 * the user-facing doc must be executable and produce the documented output.
 */
class DocExamplesTest extends TestCase
{
    private const PER_BLOCK_TIMEOUT_SECONDS = 60;
    private const SANDBOX_DIR = '/tmp/sep51-doc-block-sandbox';

    /**
     * Repository root, resolved at setUp via dirname(__DIR__, 5):
     *   __DIR__ = .../Soneso/StellarSDKTests/Unit/Xdr/Sep51
     *   level 1 = .../Soneso/StellarSDKTests/Unit/Xdr
     *   level 2 = .../Soneso/StellarSDKTests/Unit
     *   level 3 = .../Soneso/StellarSDKTests
     *   level 4 = .../Soneso
     *   level 5 = .../              <- repo root, contains composer.json
     */
    private string $repoRoot;
    private string $sandbox;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 5);
        if (!is_string($this->repoRoot) || $this->repoRoot === '' || !file_exists($this->repoRoot . '/composer.json')) {
            $this->fail(
                'DocExamplesTest $repoRoot resolution failed: expected composer.json under '
                . $this->repoRoot . '. Has the test file been relocated? Update the dirname '
                . 'depth to match the new location.'
            );
        }

        $this->sandbox = self::SANDBOX_DIR;

        // Ensure a clean sandbox before each test method. If a previous run
        // crashed without tearDown, the residual state must be cleared so this
        // run starts from a known-good shape.
        if (is_dir($this->sandbox) || is_link($this->sandbox)) {
            $this->cleanupSandbox();
        }

        if (!@mkdir($this->sandbox, 0700, true) && !is_dir($this->sandbox)) {
            $this->fail('Failed to create sandbox dir: ' . $this->sandbox);
        }
        @chmod($this->sandbox, 0700);

        $linkPath = $this->sandbox . '/repo';
        if (!is_link($linkPath)) {
            // Whole-repo symlink so the child PHP process can resolve PSR-4
            // paths off vendor/composer/. Symlinking the repo (not copying)
            // keeps the layout intact for Composer's autoloader.
            if (!@symlink($this->repoRoot, $linkPath)) {
                $this->fail('Failed to create repo symlink under sandbox: ' . $linkPath);
            }
        }

        $sandboxRef = $this->sandbox;
        register_shutdown_function(static function () use ($sandboxRef): void {
            // Defence-in-depth cleanup for fatal-error paths that bypass the
            // normal PHPUnit tearDown (e.g. parse error in this test file
            // itself, or a fatal in a hook). Ignored if directory already
            // gone.
            if (!is_dir($sandboxRef) && !is_link($sandboxRef)) {
                return;
            }
            $linkPath = $sandboxRef . '/repo';
            if (is_link($linkPath)) {
                @unlink($linkPath);
            }
            // Inline the iterator walk to avoid pulling in the full method
            // surface from this static closure.
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($sandboxRef, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $entry) {
                    /** @var \SplFileInfo $entry */
                    if ($entry->isLink() || $entry->isFile()) {
                        @unlink($entry->getPathname());
                    } elseif ($entry->isDir()) {
                        @rmdir($entry->getPathname());
                    }
                }
            } catch (\Throwable $ignored) {
                // Best-effort; the OS will reap on reboot if iteration breaks.
            }
            @rmdir($sandboxRef);
        });
    }

    protected function tearDown(): void
    {
        $this->cleanupSandbox();
    }

    /**
     * Cleanup order is pinned to prevent traversing the live tree via the
     * `repo` symlink:
     *   (1) is_link + unlink the symlink first.
     *   (2) walk the sandbox via RecursiveDirectoryIterator+SKIP_DOTS+CHILD_FIRST.
     *   (3) rmdir the sandbox last.
     *
     * Forbidden: shell-out via exec / system / `rm -rf`. `rm` follows symlinks
     * differently across BSD/GNU and would compromise the live tree if step
     * (1) silently failed.
     */
    private function cleanupSandbox(): void
    {
        if (!is_dir($this->sandbox) && !is_link($this->sandbox)) {
            return;
        }

        // (1) Symlink first; is_link must precede unlink (is_dir / file_exists
        // would return true for the symlink target, not the link itself).
        $linkPath = $this->sandbox . '/repo';
        if (is_link($linkPath)) {
            @unlink($linkPath);
        }

        // (2) Walk and remove remaining sandbox entries bottom-up.
        if (is_dir($this->sandbox)) {
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->sandbox, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $entry) {
                    /** @var \SplFileInfo $entry */
                    if ($entry->isLink() || $entry->isFile()) {
                        @unlink($entry->getPathname());
                    } elseif ($entry->isDir()) {
                        @rmdir($entry->getPathname());
                    }
                }
            } catch (\UnexpectedValueException $ignored) {
                // RecursiveDirectoryIterator raises if a subdir disappeared
                // mid-walk (e.g. the symlink unlinked above had children). We
                // tolerate; the final rmdir below will surface any real leak.
            }
        }

        // (3) Remove the sandbox dir last.
        if (is_dir($this->sandbox)) {
            @rmdir($this->sandbox);
        }
    }

    /**
     * @return iterable<string, array{0: int, 1: string, 2: ?string, 3: ?string, 4: bool}>
     *   key       => "block-NN at line LL"
     *   payload   => [blockIndex, sourceCode, expectedString|null, expectedPattern|null, nondeterministic]
     */
    public static function blockProvider(): iterable
    {
        $repoRoot = dirname(__DIR__, 5);
        $docPath = $repoRoot . '/docs/sep/sep-51.md';
        if (!file_exists($docPath)) {
            // Yield a single sentinel that fails loudly during dataset access.
            yield 'doc-missing' => [-1, '', null, null, false];
            return;
        }
        $contents = file_get_contents($docPath);
        if ($contents === false) {
            yield 'doc-unreadable' => [-1, '', null, null, false];
            return;
        }

        $blocks = self::extractFencedPhpBlocks($contents);
        foreach ($blocks as $idx => $block) {
            $key = sprintf(
                'block-%02d at line %d',
                $idx + 1,
                $block['lineNumber']
            );
            yield $key => [
                $idx + 1,
                $block['source'],
                $block['expected'],
                $block['expectedPattern'],
                $block['nondeterministic'],
            ];
        }
    }

    /**
     * Extract every fenced ```php block from the document, with the post-fence
     * HTML comment annotations (expected, expected-pattern, nondeterministic).
     *
     * @param string $markdown
     * @return list<array{source: string, lineNumber: int, expected: ?string, expectedPattern: ?string, nondeterministic: bool}>
     */
    private static function extractFencedPhpBlocks(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $count = count($lines);
        $blocks = [];

        $i = 0;
        while ($i < $count) {
            $line = $lines[$i];
            if (preg_match('/^```php\s*$/', $line)) {
                $startLine = $i + 1; // 1-based
                $i++;
                $bodyLines = [];
                while ($i < $count && !preg_match('/^```\s*$/', $lines[$i])) {
                    $bodyLines[] = $lines[$i];
                    $i++;
                }
                // Skip the closing ```
                $i++;

                // Look at the next few non-blank lines for HTML annotations.
                $expected = null;
                $expectedPattern = null;
                $nondet = false;
                $look = $i;
                while ($look < $count) {
                    $next = $lines[$look];
                    $trim = trim($next);
                    if ($trim === '') {
                        $look++;
                        continue;
                    }
                    // Multi-line expected: <!-- expected: ... --> spanning
                    // several lines until the closing -->.
                    if (preg_match('/^<!--\s*expected:\s*(.*)$/s', $next, $m)) {
                        $captured = $m[1];
                        $closed = false;
                        $endIdx = strpos($captured, '-->');
                        if ($endIdx !== false) {
                            $expected = rtrim(substr($captured, 0, $endIdx), "\r\n");
                            $closed = true;
                            $look++;
                        } else {
                            $accum = $captured;
                            $look++;
                            while ($look < $count) {
                                $candidate = $lines[$look];
                                $endIdx = strpos($candidate, '-->');
                                if ($endIdx !== false) {
                                    $accum .= "\n" . substr($candidate, 0, $endIdx);
                                    $closed = true;
                                    $look++;
                                    break;
                                }
                                $accum .= "\n" . $candidate;
                                $look++;
                            }
                            $expected = rtrim($accum, "\r\n");
                        }
                        if (!$closed) {
                            // Malformed annotation; treat as no expected.
                            $expected = null;
                        }
                        continue;
                    }
                    if (preg_match('/^<!--\s*expected-pattern:\s*(.*?)\s*-->\s*$/', $next, $m)) {
                        $expectedPattern = $m[1];
                        $look++;
                        continue;
                    }
                    if (preg_match('/^<!--\s*nondeterministic\s*-->\s*$/', $next)) {
                        $nondet = true;
                        $look++;
                        continue;
                    }
                    // First non-annotation line ends the trailing-comment scan.
                    break;
                }

                $blocks[] = [
                    'source' => implode("\n", $bodyLines),
                    'lineNumber' => $startLine,
                    'expected' => $expected,
                    'expectedPattern' => $expectedPattern,
                    'nondeterministic' => $nondet,
                ];
                continue;
            }
            $i++;
        }

        return $blocks;
    }

    /**
     * Read the static blocklist regex committed alongside this test.
     */
    public static function loadBlocklistRegex(): string
    {
        $repoRoot = dirname(__DIR__, 5);
        $path = $repoRoot . '/tools/sep-51-fixtures/doc_block_blocklist.regex';
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Cannot read blocklist regex at ' . $path);
        }
        $body = rtrim($raw, "\r\n");
        if ($body === '') {
            throw new \RuntimeException('Blocklist regex is empty at ' . $path);
        }
        return '/' . $body . '/';
    }

    /**
     * @dataProvider blockProvider
     */
    public function testFencedBlockExecutes(
        int $blockIndex,
        string $source,
        ?string $expected,
        ?string $expectedPattern,
        bool $nondeterministic
    ): void {
        if ($blockIndex < 0) {
            $this->fail(
                'Doc not found or unreadable at docs/sep/sep-51.md. The DocExamplesTest '
                . 'data provider could not extract any fenced blocks.'
            );
        }

        $blocklist = self::loadBlocklistRegex();
        if (preg_match($blocklist, $source, $m)) {
            $this->fail(sprintf(
                'Doc block %d in docs/sep/sep-51.md matches the SEP-51 doc-block blocklist: '
                . 'forbidden token "%s" found. Rewrite the example so it does not call any of the '
                . 'sandbox-disallowed functions, or mark the fence as `php-novalidate` to opt out '
                . 'of execution.',
                $blockIndex,
                $m[0]
            ));
        }

        $sha = hash('sha256', $source);
        $blockPath = $this->sandbox . '/block-' . $sha . '.php';
        // file_put_contents is intentionally avoided in the doc-block source
        // (it is on the sandbox blocklist); writing the block to the sandbox
        // happens in the host process here, before isolation begins.
        if (@file_put_contents($blockPath, $source) === false) {
            $this->fail('Failed to write block source to ' . $blockPath);
        }

        // Run twice — second run validates determinism unless the block
        // is explicitly marked nondeterministic.
        [$exit1, $stdout1, $stderr1] = $this->runIsolated($blockPath);
        [$exit2, $stdout2, $stderr2] = $this->runIsolated($blockPath);

        $location = sprintf('docs/sep/sep-51.md (block %d)', $blockIndex);

        if ($exit1 !== 0) {
            $this->fail(sprintf(
                'Doc block %d failed to execute: exit code %d. Stderr:%s%s%sStdout:%s%s',
                $blockIndex,
                $exit1,
                PHP_EOL,
                $stderr1,
                PHP_EOL,
                PHP_EOL,
                $stdout1
            ));
        }

        if ($expected !== null) {
            $this->assertSame(
                $expected,
                rtrim($stdout1, "\n"),
                'Doc block ' . $blockIndex . ' stdout did not match <!-- expected: ... --> '
                . 'in ' . $location
            );
        } elseif ($expectedPattern !== null) {
            $this->assertMatchesRegularExpression(
                '/' . $expectedPattern . '/',
                $stdout1,
                'Doc block ' . $blockIndex . ' stdout did not match <!-- expected-pattern: ... --> '
                . 'in ' . $location
            );
        } else {
            $this->assertSame(
                '',
                $stderr1,
                'Doc block ' . $blockIndex . ' wrote to stderr but had no expected/expected-pattern '
                . 'annotation in ' . $location
            );
        }

        if (!$nondeterministic) {
            $this->assertSame(
                $stdout1,
                $stdout2,
                'Doc block ' . $blockIndex . ' produced different stdout on the second run; '
                . 'mark the fence with <!-- nondeterministic --> + <!-- expected-pattern: ... --> '
                . 'if this is intentional. Block at ' . $location
            );
        }
    }

    /**
     * Spawn the child PHP process with the isolation flags from the SEP-51
     * doc-execution sandbox spec and run the given block file.
     *
     * @param string $blockPath Absolute path to the block-<sha>.php file.
     * @return array{0: int, 1: string, 2: string} [exit, stdout, stderr]
     */
    private function runIsolated(string $blockPath): array
    {
        $autoload = $this->sandbox . '/repo/vendor/autoload.php';

        // disable_functions list. Crypto primitives (random_bytes / random_int /
        // microtime / time / mt_rand / uniqid) are intentionally NOT disabled —
        // Stellar examples may need them. The determinism check guards against
        // unintentional non-determinism.
        $disableFns = 'exec,system,passthru,shell_exec,proc_open,popen,curl_exec,curl_multi_exec,'
            . 'file_put_contents,fopen,fwrite,fputs,fputcsv,unlink,rmdir,mkdir,rename,copy,'
            . 'chmod,chown,symlink,link,touch,stream_socket_client,fsockopen,gethostbyname,'
            . 'dns_get_record,extract,eval,create_function,assert,parse_ini_file,parse_ini_string,'
            . 'phpinfo,getenv,putenv,ini_set,pcntl_exec,pcntl_fork,pcntl_signal,posix_kill,'
            . 'posix_setuid,mail,header,setcookie,socket_create,socket_connect,'
            . 'stream_socket_server,dl,proc_close,proc_get_status,proc_terminate,'
            . 'date_create_from_format';

        $argv = [
            'php', '-n',
            '-d', 'disable_functions=' . $disableFns,
            '-d', 'open_basedir=' . $this->sandbox . ':' . $this->repoRoot,
            '-d', 'allow_url_fopen=0',
            '-d', 'allow_url_include=0',
            '-d', 'auto_prepend_file=' . $autoload,
            $blockPath,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($argv, $descriptors, $pipes);
        if (!is_resource($proc)) {
            $this->fail('proc_open failed for ' . $blockPath);
        }

        // No stdin input.
        fclose($pipes[0]);

        // Non-blocking reads with a wall-clock deadline.
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $deadline = microtime(true) + self::PER_BLOCK_TIMEOUT_SECONDS;
        $stdout = '';
        $stderr = '';

        while (true) {
            $status = proc_get_status($proc);
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                proc_terminate($proc, 9);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
                $this->fail(sprintf(
                    'Doc block %s exceeded %d-second timeout; killed via proc_terminate.',
                    basename($blockPath),
                    self::PER_BLOCK_TIMEOUT_SECONDS
                ));
            }

            // Drain any available output.
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);

            if (!$status['running']) {
                break;
            }

            // Sleep briefly to avoid busy-spin.
            usleep(10_000);
        }

        // Final drain after the process exited.
        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);
        if ($exit === -1) {
            // proc_close returns -1 if the status was already collected by
            // proc_get_status above; fall back to that status's exitcode.
            $exit = $status['exitcode'] ?? -1;
        }

        return [(int) $exit, $stdout, $stderr];
    }
}
