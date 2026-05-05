<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Meta-test: validates the DocExamplesTest sandbox itself.
 *
 * Four cases:
 *   1. A malicious block calling system('id') is rejected by the static blocklist.
 *   2. A pcntl_exec block is rejected (blocklist catches it; defence in depth
 *      against runtime escape since disable_functions also blocks it).
 *   3. A benign block executes successfully and stdout matches the expected.
 *   4. A non-deterministic block (random output) fails the second-run check.
 */
class DocExamplesTestSelfTest extends TestCase
{
    private const SANDBOX_DIR = '/tmp/sep51-doc-block-selftest-sandbox';
    private const PER_BLOCK_TIMEOUT_SECONDS = 30;

    private string $repoRoot;
    private string $sandbox;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 5);
        if (!is_string($this->repoRoot) || $this->repoRoot === ''
            || !file_exists($this->repoRoot . '/composer.json')) {
            $this->fail(
                'DocExamplesTestSelfTest $repoRoot resolution failed: expected composer.json under '
                . $this->repoRoot
            );
        }

        $this->sandbox = self::SANDBOX_DIR;
        if (is_dir($this->sandbox) || is_link($this->sandbox)) {
            $this->cleanupSandbox();
        }
        if (!@mkdir($this->sandbox, 0700, true) && !is_dir($this->sandbox)) {
            $this->fail('Failed to create selftest sandbox at ' . $this->sandbox);
        }
        @chmod($this->sandbox, 0700);

        $linkPath = $this->sandbox . '/repo';
        if (!is_link($linkPath)) {
            if (!@symlink($this->repoRoot, $linkPath)) {
                $this->fail('Failed to symlink repo into selftest sandbox');
            }
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupSandbox();
    }

    private function cleanupSandbox(): void
    {
        if (!is_dir($this->sandbox) && !is_link($this->sandbox)) {
            return;
        }
        $linkPath = $this->sandbox . '/repo';
        if (is_link($linkPath)) {
            @unlink($linkPath);
        }
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
                // Mid-walk disappearance; ignore.
            }
        }
        if (is_dir($this->sandbox)) {
            @rmdir($this->sandbox);
        }
    }

    /**
     * Case 1: a system('id') call is on the blocklist.
     */
    public function testMaliciousSystemBlockIsRejectedByBlocklist(): void
    {
        $source = "<?php\nsystem('id');\n";
        $blocklist = DocExamplesTest::loadBlocklistRegex();
        $this->assertSame(
            1,
            preg_match($blocklist, $source),
            'Blocklist must reject system() calls. Pattern: ' . $blocklist
        );
    }

    /**
     * Case 2: pcntl_exec is on the blocklist (and is also in disable_functions
     * as a runtime defence).
     */
    public function testPcntlExecBlockIsRejectedByBlocklist(): void
    {
        $source = "<?php\npcntl_exec('/bin/sh', ['-c', 'echo hi']);\n";
        $blocklist = DocExamplesTest::loadBlocklistRegex();
        $this->assertSame(
            1,
            preg_match($blocklist, $source),
            'Blocklist must reject pcntl_exec() calls. Pattern: ' . $blocklist
        );
    }

    /**
     * Case 3: a benign block executes and stdout matches expected.
     */
    public function testBenignBlockExecutesAndStdoutMatches(): void
    {
        $source = "<?php\necho 'hello sep51' . PHP_EOL;\n";

        $blocklist = DocExamplesTest::loadBlocklistRegex();
        $this->assertSame(
            0,
            preg_match($blocklist, $source),
            'Benign echo block must not match blocklist'
        );

        $sha = hash('sha256', $source);
        $blockPath = $this->sandbox . '/block-' . $sha . '.php';
        if (@file_put_contents($blockPath, $source) === false) {
            $this->fail('Failed to write benign block to ' . $blockPath);
        }

        [$exit, $stdout, $stderr] = $this->runIsolated($blockPath);
        $this->assertSame(0, $exit, 'Benign block exit code should be 0; stderr: ' . $stderr);
        $this->assertSame('hello sep51', rtrim($stdout, "\n"));
        $this->assertSame('', $stderr);
    }

    /**
     * Case 4: a non-deterministic block (random_int) produces different stdout
     * on the second run. The runner's determinism check must surface that
     * difference.
     *
     * We simulate the determinism check inline here: run the block twice via
     * the same isolation harness, then assert the two outputs differ. This is
     * the contract the production runner relies on; the production runner
     * fails the test when the two outputs differ. Here we assert the inverse:
     * the two outputs MUST differ for this kind of block, otherwise the
     * determinism check itself would be meaningless.
     */
    public function testNondeterministicBlockProducesDifferentOutput(): void
    {
        $source = "<?php\necho random_int(1, 1000000000) . PHP_EOL;\n";

        $blocklist = DocExamplesTest::loadBlocklistRegex();
        $this->assertSame(
            0,
            preg_match($blocklist, $source),
            'random_int block should not be on the blocklist'
        );

        $sha = hash('sha256', $source);
        $blockPath = $this->sandbox . '/block-' . $sha . '.php';
        if (@file_put_contents($blockPath, $source) === false) {
            $this->fail('Failed to write nondet block to ' . $blockPath);
        }

        [$e1, $stdout1, $stderr1] = $this->runIsolated($blockPath);
        [$e2, $stdout2, $stderr2] = $this->runIsolated($blockPath);

        $this->assertSame(0, $e1, 'first run exit; stderr: ' . $stderr1);
        $this->assertSame(0, $e2, 'second run exit; stderr: ' . $stderr2);
        $this->assertNotSame(
            $stdout1,
            $stdout2,
            'random_int output should differ across runs; if it does not, '
            . 'the determinism check is broken or PHP rng was seeded.'
        );
    }

    /**
     * Spawn the child PHP process with the same isolation flags as the
     * production runner.
     *
     * @return array{0: int, 1: string, 2: string} [exit, stdout, stderr]
     */
    private function runIsolated(string $blockPath): array
    {
        $autoload = $this->sandbox . '/repo/vendor/autoload.php';

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
        fclose($pipes[0]);

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
                    'Selftest block %s exceeded %d-second timeout',
                    basename($blockPath),
                    self::PER_BLOCK_TIMEOUT_SECONDS
                ));
            }

            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);

            if (!$status['running']) {
                break;
            }
            usleep(10_000);
        }

        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);
        if ($exit === -1) {
            $exit = $status['exitcode'] ?? -1;
        }

        return [(int) $exit, $stdout, $stderr];
    }
}
