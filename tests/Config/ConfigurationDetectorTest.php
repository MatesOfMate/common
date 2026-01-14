<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Tests\Config;

use MatesOfMate\Common\Config\ConfigurationDetector;
use PHPUnit\Framework\TestCase;

class ConfigurationDetectorTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir().'/config-detector-test-'.uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
    }

    public function testDetectReturnsNullWhenConfigFilesArrayIsEmpty(): void
    {
        $detector = new ConfigurationDetector([]);

        $result = $detector->detect($this->testDir);

        $this->assertNull($result);
    }

    public function testDetectReturnsNullWhenNoConfigExists(): void
    {
        $detector = new ConfigurationDetector(['config.xml', 'config.xml.dist']);

        $result = $detector->detect($this->testDir);

        $this->assertNull($result);
    }

    public function testDetectFindsFirstConfigFile(): void
    {
        $configFile = $this->testDir.'/config.xml';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector(['config.xml', 'config.xml.dist']);

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile, $result);
    }

    public function testDetectFindsSecondConfigFileWhenFirstDoesNotExist(): void
    {
        $configFile = $this->testDir.'/config.xml.dist';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector(['config.xml', 'config.xml.dist']);

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile, $result);
    }

    public function testDetectPrioritizesFirstConfigFileInArray(): void
    {
        $configFile1 = $this->testDir.'/config.xml';
        $configFile2 = $this->testDir.'/config.xml.dist';
        $configFile3 = $this->testDir.'/config.dist.xml';
        file_put_contents($configFile1, '<?xml version="1.0"?>');
        file_put_contents($configFile2, '<?xml version="1.0"?>');
        file_put_contents($configFile3, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector([
            'config.xml',
            'config.xml.dist',
            'config.dist.xml',
        ]);

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile1, $result);
    }

    public function testDetectUsesGetcwdWhenProjectRootIsNull(): void
    {
        $originalCwd = getcwd();
        if (false !== $originalCwd) {
            chdir($this->testDir);
        }

        $configFile = $this->testDir.'/config.xml';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector(['config.xml']);

        $result = $detector->detect();

        if (false !== $originalCwd) {
            chdir($originalCwd);
        }

        $this->assertNotNull($result);
        $this->assertSame(realpath($configFile), realpath($result));
    }

    public function testDetectUsesExplicitProjectRootOverGetcwd(): void
    {
        $configFile = $this->testDir.'/config.xml';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector(['config.xml']);

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile, $result);
    }

    public function testDetectWithMultipleConfigFileVariants(): void
    {
        $detector = new ConfigurationDetector([
            'phpunit.xml',
            'phpunit.xml.dist',
            'phpunit.dist.xml',
        ]);

        $configFile = $this->testDir.'/phpunit.xml.dist';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile, $result);
    }

    public function testDetectWithNeonConfigFiles(): void
    {
        $detector = new ConfigurationDetector([
            'phpstan.neon',
            'phpstan.neon.dist',
            'phpstan.dist.neon',
        ]);

        $configFile = $this->testDir.'/phpstan.dist.neon';
        file_put_contents($configFile, 'parameters:');

        $result = $detector->detect($this->testDir);

        $this->assertSame($configFile, $result);
    }

    public function testDetectHandlesTrailingSlashInProjectRoot(): void
    {
        $configFile = $this->testDir.'/config.xml';
        file_put_contents($configFile, '<?xml version="1.0"?>');

        $detector = new ConfigurationDetector(['config.xml']);

        $result = $detector->detect($this->testDir.'/');

        $this->assertNotNull($result);
        $this->assertStringContainsString('config.xml', $result);
    }

    public function testDetectWithEmptyStringConfigFileNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config file names cannot be empty strings');

        new ConfigurationDetector(['', 'config.xml']);
    }

    public function testDetectWithOnlyEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config file names cannot be empty strings');

        new ConfigurationDetector(['']);
    }

    public function testDetectWithEmptyStringInMiddleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Config file names cannot be empty strings');

        new ConfigurationDetector(['config.xml', '', 'config.xml.dist']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
