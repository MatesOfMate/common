<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Tests\Truncator;

use MatesOfMate\Common\Truncator\MessageTruncator;
use PHPUnit\Framework\TestCase;

class MessageTruncatorTest extends TestCase
{
    public function testTruncateWithNoPrefixes(): void
    {
        $truncator = new MessageTruncator();

        $message = 'This is a test message';
        $result = $truncator->truncate($message);

        $this->assertSame($message, $result);
    }

    public function testTruncateRemovesMatchingPrefix(): void
    {
        $truncator = new MessageTruncator(['Parameter ', 'Method ']);

        $message = 'Parameter $user of method UserService::create()';
        $result = $truncator->truncate($message);

        $this->assertSame('$user of method UserService::create()', $result);
    }

    public function testTruncateRemovesFirstMatchingPrefixOnly(): void
    {
        $truncator = new MessageTruncator(['Parameter ', 'Method ']);

        $message = 'Method UserService::create() Parameter $user';
        $result = $truncator->truncate($message);

        $this->assertSame('UserService::create() Parameter $user', $result);
    }

    public function testTruncateDoesNotRemoveNonMatchingPrefix(): void
    {
        $truncator = new MessageTruncator(['Parameter ', 'Method ']);

        $message = 'Property $name is not defined';
        $result = $truncator->truncate($message);

        $this->assertSame($message, $result);
    }

    public function testTruncateShortensFQCN(): void
    {
        $truncator = new MessageTruncator();

        $message = 'App\Very\Long\Namespace\ClassName method called';
        $result = $truncator->truncate($message);

        $this->assertSame('App\ClassName method called', $result);
    }

    public function testTruncateShortensFQCNWithSingleNamespace(): void
    {
        $truncator = new MessageTruncator();

        $message = 'App\Service\UserService::create()';
        $result = $truncator->truncate($message);

        $this->assertSame('App\UserService::create()', $result);
    }

    public function testTruncateShortensFQCNWithMultipleClasses(): void
    {
        $truncator = new MessageTruncator();

        $message = 'App\Service\UserService expects App\Entity\User but App\Value\UserValue given';
        $result = $truncator->truncate($message);

        $this->assertSame('App\UserService expects App\User but App\UserValue given', $result);
    }

    public function testTruncateDoesNotModifyShortClassName(): void
    {
        $truncator = new MessageTruncator();

        $message = 'UserService expects User but null given';
        $result = $truncator->truncate($message);

        $this->assertSame($message, $result);
    }

    public function testTruncateAddsEllipsisWhenExceedingMaxLength(): void
    {
        $truncator = new MessageTruncator();

        $message = str_repeat('A', 250);
        $result = $truncator->truncate($message, 200);

        $this->assertSame(200, \strlen($result));
        $this->assertStringEndsWith('...', $result);
        $this->assertSame(str_repeat('A', 197).'...', $result);
    }

    public function testTruncateDoesNotAddEllipsisWhenAtMaxLength(): void
    {
        $truncator = new MessageTruncator();

        $message = str_repeat('A', 200);
        $result = $truncator->truncate($message, 200);

        $this->assertSame($message, $result);
        $this->assertStringEndsNotWith('...', $result);
    }

    public function testTruncateDoesNotAddEllipsisWhenBelowMaxLength(): void
    {
        $truncator = new MessageTruncator();

        $message = str_repeat('A', 100);
        $result = $truncator->truncate($message, 200);

        $this->assertSame($message, $result);
    }

    public function testTruncateWithCustomMaxLength(): void
    {
        $truncator = new MessageTruncator();

        $message = str_repeat('A', 150);
        $result = $truncator->truncate($message, 50);

        $this->assertSame(50, \strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateAppliesPrefixRemovalBeforeLengthCheck(): void
    {
        $truncator = new MessageTruncator(['Parameter ']);

        $message = 'Parameter '.str_repeat('A', 50);
        $result = $truncator->truncate($message, 60);

        $this->assertSame(str_repeat('A', 50), $result);
        $this->assertStringEndsNotWith('...', $result);
    }

    public function testTruncateAppliesClassNameShorteningBeforeLengthCheck(): void
    {
        $truncator = new MessageTruncator();

        $message = 'App\Very\Long\Namespace\ClassName expects User';
        $result = $truncator->truncate($message, 100);

        $this->assertSame('App\ClassName expects User', $result);
        $this->assertStringEndsNotWith('...', $result);
    }

    public function testTruncateHandlesEmptyMessage(): void
    {
        $truncator = new MessageTruncator(['Parameter ']);

        $result = $truncator->truncate('');

        $this->assertSame('', $result);
    }

    public function testTruncateHandlesMessageWithOnlyPrefix(): void
    {
        $truncator = new MessageTruncator(['Parameter ']);

        $result = $truncator->truncate('Parameter ');

        $this->assertSame('', $result);
    }

    public function testTruncateHandlesMultibyteCharacters(): void
    {
        $truncator = new MessageTruncator();

        $message = 'Error: 日本語のメッセージ';
        $result = $truncator->truncate($message, 20);

        $this->assertLessThanOrEqual(20, \strlen($result));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateWithAllTransformations(): void
    {
        $truncator = new MessageTruncator(['Parameter ', 'Method ']);

        $message = 'Parameter #1 $user of method App\Service\UserService::create() expects App\Entity\User but null given';
        $result = $truncator->truncate($message, 80);

        $this->assertLessThanOrEqual(80, \strlen($result));
        $this->assertStringNotContainsString('Parameter ', $result);

        $this->assertStringContainsString('App\UserService', $result);
        $this->assertStringContainsString('App\User', $result);
    }

    public function testTruncateWithEmptyPrefixArray(): void
    {
        $truncator = new MessageTruncator([]);

        $message = 'Parameter $user is required';
        $result = $truncator->truncate($message);

        $this->assertSame($message, $result);
    }

    public function testTruncateDoesNotRemovePrefixInMiddleOfMessage(): void
    {
        $truncator = new MessageTruncator(['Parameter ']);

        $message = 'The Parameter $user is invalid';
        $result = $truncator->truncate($message);

        // Should not remove 'Parameter ' because it's not at the start
        $this->assertSame($message, $result);
    }

    public function testTruncatePrefixMatchingIsCaseSensitive(): void
    {
        $truncator = new MessageTruncator(['Parameter ']);

        $message = 'parameter $user is required';
        $result = $truncator->truncate($message);

        // Should not remove 'parameter ' (lowercase) when prefix is 'Parameter ' (uppercase)
        $this->assertSame($message, $result);
    }

    public function testTruncateWithComplexNamespacePattern(): void
    {
        $truncator = new MessageTruncator();

        $message = 'MatesOfMate\PHPUnitExtension\Parser\JunitParser::parse() called';
        $result = $truncator->truncate($message);

        // Regex only removes one level at a time: MatesOfMate\PHPUnitExtension\Parser\ -> MatesOfMate\PHPUnitExtension\
        $this->assertSame('MatesOfMate\PHPUnitExtension\JunitParser::parse() called', $result);
    }

    public function testTruncateWithNestedNamespaces(): void
    {
        $truncator = new MessageTruncator();

        $message = 'Symfony\Component\Process\Process and Symfony\Component\Finder\Finder used';
        $result = $truncator->truncate($message);

        $this->assertSame('Symfony\Process and Symfony\Finder used', $result);
    }
}
