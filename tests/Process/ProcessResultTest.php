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

use MatesOfMate\Common\Process\ProcessResult;
use PHPUnit\Framework\TestCase;

class ProcessResultTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $result = new ProcessResult(
            exitCode: 0,
            output: 'test output',
            errorOutput: 'test error',
        );

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('test output', $result->output);
        $this->assertSame('test error', $result->errorOutput);
    }

    public function testIsSuccessfulReturnsTrueForExitCodeZero(): void
    {
        $result = new ProcessResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseForNonZeroExitCode(): void
    {
        $result = new ProcessResult(
            exitCode: 1,
            output: '',
            errorOutput: 'Error occurred',
        );

        $this->assertFalse($result->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseForHighExitCode(): void
    {
        $result = new ProcessResult(
            exitCode: 127,
            output: '',
            errorOutput: 'Command not found',
        );

        $this->assertFalse($result->isSuccessful());
    }

    public function testPropertiesAreReadonly(): void
    {
        $result = new ProcessResult(
            exitCode: 0,
            output: 'output',
            errorOutput: 'error',
        );

        $reflection = new \ReflectionClass($result);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} is not readonly");
        }
    }

    public function testCanHandleEmptyOutput(): void
    {
        $result = new ProcessResult(
            exitCode: 0,
            output: '',
            errorOutput: '',
        );

        $this->assertSame('', $result->output);
        $this->assertSame('', $result->errorOutput);
        $this->assertTrue($result->isSuccessful());
    }

    public function testCanHandleMultilineOutput(): void
    {
        $output = "Line 1\nLine 2\nLine 3";
        $errorOutput = "Error 1\nError 2";

        $result = new ProcessResult(
            exitCode: 1,
            output: $output,
            errorOutput: $errorOutput,
        );

        $this->assertSame($output, $result->output);
        $this->assertSame($errorOutput, $result->errorOutput);
        $this->assertFalse($result->isSuccessful());
    }

    public function testCanHandleVeryLongOutput(): void
    {
        $longOutput = str_repeat('A', 10000);
        $longError = str_repeat('B', 10000);

        $result = new ProcessResult(
            exitCode: 2,
            output: $longOutput,
            errorOutput: $longError,
        );

        $this->assertSame($longOutput, $result->output);
        $this->assertSame($longError, $result->errorOutput);
        $this->assertSame(2, $result->exitCode);
    }

    public function testExitCodeCanBeNegative(): void
    {
        $result = new ProcessResult(
            exitCode: -1,
            output: '',
            errorOutput: 'Signal received',
        );

        $this->assertSame(-1, $result->exitCode);
        $this->assertFalse($result->isSuccessful());
    }
}
