# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Shared functionality library for MatesOfMate extensions providing reusable components for CLI tool execution, configuration detection, and token-efficient output formatting. This package follows **composition over inheritance** principles with minimal interfaces and concrete implementations designed for extension integration.

## Common Commands

### Development Workflow

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run specific test
vendor/bin/phpunit tests/Process/ProcessExecutorTest.php
vendor/bin/phpunit --filter testExecute

# Check code quality (validates composer.json, runs Rector, PHP CS Fixer, PHPStan)
composer lint

# Auto-fix code style and apply automated refactorings
composer fix
```

### Individual Quality Tools

```bash
# PHP CS Fixer (code style)
vendor/bin/php-cs-fixer fix --dry-run --diff  # Check only
vendor/bin/php-cs-fixer fix                   # Apply fixes

# PHPStan (static analysis at level 8)
vendor/bin/phpstan analyse

# Rector (automated refactoring to PHP 8.2)
vendor/bin/rector process --dry-run           # Preview changes
vendor/bin/rector process                     # Apply changes
```

## Architecture

### Component Structure

**Process Execution** (`src/Process/`):
- `ProcessExecutorInterface` - CLI tool execution contract
- `ProcessExecutor` - Concrete implementation with PHP binary reuse and vendor path detection
- `ProcessResult` - DTO for command execution results (exit code, output, error output)

**Configuration Detection** (`src/Config/`):
- `ConfigurationDetectorInterface` - Config file detection contract
- `ConfigurationDetector` - Auto-detects configuration files in project directories

**Message Truncation** (`src/Truncator/`):
- `MessageTruncatorInterface` - Token-efficient output contract
- `MessageTruncator` - Smart message shortening with common prefix removal and class name shortening

### Design Principles

**Composition Over Inheritance**:
- All classes designed for composition, not inheritance
- Extensions create internal instances with constructor configuration
- No abstract base classes or protected properties

**Minimal Interfaces**:
- Interfaces expose only essential public methods
- Helper methods (`findBinary()`, `buildCommand()`, `removeCommonPrefixes()`) are private implementation details
- Clear separation between public API and internal logic

**PHP Binary Reuse**:
- `ProcessExecutor` ensures PHP scripts run with same PHP version as current process
- Uses `\PHP_BINARY` constant for PHP tools (phpunit, phpstan, etc.)
- `usePhpBinary` parameter distinguishes PHP scripts from system binaries
- Default `true` for PHP tools, explicit `false` for system binaries (git, composer)

### Usage Patterns

**Process Executor Implementation**:
```php
use MatesOfMate\Common\Process\ProcessExecutor as CommonProcessExecutor;
use MatesOfMate\Common\Process\ProcessExecutorInterface;

class PhpunitProcessExecutor implements ProcessExecutorInterface
{
    private readonly CommonProcessExecutor $executor;

    public function __construct()
    {
        $cwd = getcwd();
        $vendorPaths = false !== $cwd ? [$cwd.'/vendor/bin/phpunit'] : [];
        $this->executor = new CommonProcessExecutor($vendorPaths);
    }

    public function execute(
        string $binaryName,
        array $args = [],
        int $timeout = 300,
        bool $usePhpBinary = true
    ): ProcessResult {
        return $this->executor->execute($binaryName, $args, $timeout, $usePhpBinary);
    }
}
```

**Configuration Detector Implementation**:
```php
use MatesOfMate\Common\Config\ConfigurationDetector as CommonConfigDetector;
use MatesOfMate\Common\Config\ConfigurationDetectorInterface;

class ConfigurationDetector implements ConfigurationDetectorInterface
{
    private readonly CommonConfigDetector $detector;

    public function __construct()
    {
        $this->detector = new CommonConfigDetector(['phpunit.xml', 'phpunit.xml.dist']);
    }

    public function detect(?string $projectRoot = null): ?string
    {
        return $this->detector->detect($projectRoot);
    }
}
```

**Message Truncator Implementation**:
```php
use MatesOfMate\Common\Truncator\MessageTruncator as CommonMessageTruncator;
use MatesOfMate\Common\Truncator\MessageTruncatorInterface;

class MessageTruncator implements MessageTruncatorInterface
{
    private readonly CommonMessageTruncator $truncator;

    public function __construct()
    {
        $this->truncator = new CommonMessageTruncator([
            'Parameter ', 'Method ', 'Property ', 'Call to ',
        ]);
    }

    public function truncate(string $message, int $maxLength = 200): string
    {
        return $this->truncator->truncate($message, $maxLength);
    }
}
```

### Extension Integration

Extensions include common package via Composer path repositories:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../common",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "matesofmate/common": "^0.1"
    }
}
```

Service registration in extensions:
```php
// config/services.php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(PhpunitProcessExecutor::class);
    $services->set(ConfigurationDetector::class);
};
```

## Code Quality Standards

### PHP Requirements
- PHP 8.2+ minimum
- No `declare(strict_types=1)` by convention
- No final classes (extensibility)
- JSON encoding: Always use `\JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT`

### Quality Tools Configuration
- **PHPStan**: Level 8, maximum strictness with explicit types
- **PHP CS Fixer**: `@Symfony` + `@Symfony:risky` rulesets with ordered class elements
- **Rector**: PHP 8.2, code quality, dead code removal, early return, type declarations
- **PHPUnit**: Version 10.0+

### File Header Template

All PHP files must include:
```php
<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

### DocBlock Annotations

**@author annotation**: Required on all class-level DocBlocks:
```php
/**
 * Executes CLI tools with consistent PHP version.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ProcessExecutor implements ProcessExecutorInterface
```

**@internal annotation**: DO NOT use in common package classes or interfaces.

Common package provides **public API for extension authors**:
- Extensions are expected to use these classes directly
- Interfaces define contracts for composition
- Similar to Symfony Components - library APIs for developers

Use semantic versioning for breaking changes instead of @internal markers.

## Testing Philosophy

### Test Structure
- Tests mirror `src/` structure in `tests/`
- Extend `PHPUnit\Framework\TestCase`
- Test method names: `testExecute`, `testDetect`, `testTruncate`, etc.

### Key Testing Areas
- Process execution success and failure paths
- PHP binary detection and command building
- Configuration file detection with multiple candidates
- Message truncation with prefix preservation
- Edge cases (empty arrays, null values, missing binaries)

### Integration Testing
- ProcessExecutor with real vendor paths
- ConfigurationDetector with actual project structures
- Composition patterns in extension contexts

## Common Development Patterns

### Adding New Components

1. Create interface in appropriate namespace (Process, Config, Truncator)
2. Define minimal public API (1-3 methods maximum)
3. Implement concrete class with constructor configuration
4. Keep helper methods private
5. Add corresponding test in `tests/`
6. Update this CLAUDE.md with usage patterns

### Composition Best Practices

**Constructor Dependency Injection**:
```php
// ✅ Good - accepts configuration via constructor
public function __construct(
    private readonly array $vendorPaths = [],
) {}

// ❌ Bad - hardcoded or protected properties
protected array $vendorPaths = [];
```

**Private Implementation Details**:
```php
// ✅ Good - helper methods are private
private function findBinary(string $name): ?string {}
private function buildCommand(string $binaryPath): array {}

// ❌ Bad - exposing implementation details
public function findBinary(string $name): ?string {}
```

**Named Parameters for Clarity**:
```php
// ✅ Good - clear intent with named parameter
$result = $executor->execute('git', ['status'], usePhpBinary: false);

// ❌ Bad - unclear what false means
$result = $executor->execute('git', ['status'], 300, false);
```

### Backward Compatibility

- Treat all public methods as API contracts
- Use semantic versioning for breaking changes
- Add new optional parameters to maintain compatibility
- Document deprecations clearly in DocBlocks

## Commit Message Convention

Keep commit messages clean without AI attribution.

**Format:**
```
Short summary (50 chars or less)

- Conceptual change description
- Another concept or improvement
```

**Rules:**
- ❌ NO AI attribution (no "Co-Authored-By: Claude", etc.)
- ✅ Short, descriptive summary line
- ✅ Bullet list describing concepts/improvements
- ✅ Focus on the WHY and WHAT

**Good Example:**
```
Simplify ProcessExecutor interface

- Make findBinary() and buildCommand() private
- Accept binary name instead of full command
- Add usePhpBinary parameter for system binaries
```

**Bad Example:**
```
Update ProcessExecutor.php and tests

Co-Authored-By: Claude Code <noreply@anthropic.com>
```
