<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Tests\Process;

use MatesOfMate\Common\Process\ProcessExecutor;
use PHPUnit\Framework\TestCase;

class ProcessExecutorTest extends TestCase
{
    public function testExecuteWithPhpBinaryDefaultFindsSystemBinary(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--version'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }

    public function testExecuteWithoutPhpBinaryForSystemCommand(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('echo', ['test'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('test', $result->output);
    }

    public function testExecuteThrowsExceptionWhenBinaryNotFound(): void
    {
        $executor = new ProcessExecutor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('nonexistent-binary-12345 binary not found');

        $executor->execute('nonexistent-binary-12345', ['--help']);
    }

    public function testExecuteUsesVendorPathsWhenProvided(): void
    {
        $tempDir = sys_get_temp_dir().'/executor-test-'.uniqid();
        mkdir($tempDir);
        $fakeBinary = $tempDir.'/fake-binary';

        file_put_contents($fakeBinary, "#!/bin/sh\necho FAKE");
        chmod($fakeBinary, 0755);

        try {
            $executor = new ProcessExecutor([$fakeBinary]);

            $result = $executor->execute('fake-binary', [], usePhpBinary: false);

            $this->assertTrue($result->isSuccessful());
            $this->assertStringContainsString('FAKE', $result->output);
        } finally {
            unlink($fakeBinary);
            rmdir($tempDir);
        }
    }

    public function testExecutePrioritizesVendorPathsOverSystemPath(): void
    {
        $tempDir = sys_get_temp_dir().'/executor-test-'.uniqid();
        mkdir($tempDir);
        $fakeBinary = $tempDir.'/custom-tool';

        file_put_contents($fakeBinary, "#!/bin/sh\necho VENDOR_BINARY");
        chmod($fakeBinary, 0755);

        try {
            $executor = new ProcessExecutor([$fakeBinary]);

            $result = $executor->execute('custom-tool', [], usePhpBinary: false);

            $this->assertTrue($result->isSuccessful());
            $this->assertStringContainsString('VENDOR_BINARY', $result->output);
        } finally {
            unlink($fakeBinary);
            rmdir($tempDir);
        }
    }

    public function testExecuteReturnsNonZeroExitCodeOnFailure(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--invalid-option-xyz'], usePhpBinary: false);

        $this->assertFalse($result->isSuccessful());
        $this->assertNotSame(0, $result->exitCode);
    }

    public function testExecuteCapturesErrorOutput(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--invalid-option-xyz'], usePhpBinary: false);

        $this->assertFalse($result->isSuccessful());
        $this->assertNotEmpty($result->errorOutput);
    }

    public function testExecuteWithEmptyArgs(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', [], usePhpBinary: false);

        // Without args, php enters interactive mode and exits immediately with non-zero
        // So we just verify it returned a result
        $this->assertIsInt($result->exitCode);
    }

    public function testExecuteWithMultipleArgs(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['-r', 'echo "Hello World";'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('Hello World', $result->output);
    }

    public function testExecuteWithCustomTimeout(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--version'], timeout: 1, usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }

    public function testExecuteRespectsPhpBinaryParameter(): void
    {
        $tempDir = sys_get_temp_dir().'/executor-test-'.uniqid();
        mkdir($tempDir);
        $testScript = $tempDir.'/test.php';
        file_put_contents($testScript, '<?php echo "TEST_OUTPUT";');

        try {
            $executor = new ProcessExecutor([$testScript]);

            $result1 = $executor->execute('test.php', [], usePhpBinary: true);
            $this->assertTrue($result1->isSuccessful());
            $this->assertStringContainsString('TEST_OUTPUT', $result1->output);

            $shellScript = $tempDir.'/test.sh';
            file_put_contents($shellScript, "#!/bin/sh\necho SHELL_OUTPUT");
            chmod($shellScript, 0755);

            $executor2 = new ProcessExecutor([$shellScript]);

            $result2 = $executor2->execute('test.sh', [], usePhpBinary: false);
            $this->assertTrue($result2->isSuccessful());
            $this->assertStringContainsString('SHELL_OUTPUT', $result2->output);
        } finally {
            if (file_exists($testScript)) {
                unlink($testScript);
            }
            if (isset($shellScript) && file_exists($shellScript)) {
                unlink($shellScript);
            }
            rmdir($tempDir);
        }
    }

    public function testExecuteHandlesStdoutAndStderrSeparately(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute(
            'php',
            ['-r', 'echo "stdout"; fwrite(STDERR, "stderr");'],
            usePhpBinary: false
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('stdout', $result->output);
        $this->assertStringContainsString('stderr', $result->errorOutput);
    }

    public function testExecuteWithMultipleVendorPaths(): void
    {
        $tempDir = sys_get_temp_dir().'/executor-test-'.uniqid();
        mkdir($tempDir);
        $dir1 = $tempDir.'/vendor1';
        $dir2 = $tempDir.'/vendor2';
        mkdir($dir1);
        mkdir($dir2);

        $binary1 = $dir1.'/tool';
        $binary2 = $dir2.'/tool';

        file_put_contents($binary1, "#!/bin/sh\necho VENDOR1");
        chmod($binary1, 0755);
        file_put_contents($binary2, "#!/bin/sh\necho VENDOR2");
        chmod($binary2, 0755);

        try {
            $executor = new ProcessExecutor([$binary1, $binary2]);

            $result = $executor->execute('tool', [], usePhpBinary: false);

            $this->assertTrue($result->isSuccessful());
            $this->assertStringContainsString('VENDOR1', $result->output);
            $this->assertStringNotContainsString('VENDOR2', $result->output);
        } finally {
            unlink($binary1);
            unlink($binary2);
            rmdir($dir1);
            rmdir($dir2);
            rmdir($tempDir);
        }
    }

    public function testExecuteFallsBackToSystemPathWhenVendorPathsNotFound(): void
    {
        $executor = new ProcessExecutor([
            '/nonexistent/path/php',
            '/another/nonexistent/path/php',
        ]);

        $result = $executor->execute('php', ['--version'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }

    public function testExecuteWithLongRunningCommand(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute(
            'php',
            ['-r', 'sleep(1); echo "done";'],
            timeout: 5,
            usePhpBinary: false
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('done', $result->output);
    }

    public function testExecuteWithDefaultTimeout(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--version'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }

    public function testConstructorWithEmptyVendorPaths(): void
    {
        $executor = new ProcessExecutor([]);

        $result = $executor->execute('php', ['--version'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }

    public function testConstructorWithDefaultVendorPaths(): void
    {
        $executor = new ProcessExecutor();

        $result = $executor->execute('php', ['--version'], usePhpBinary: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->output);
    }
}
