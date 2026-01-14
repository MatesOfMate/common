# MatesOfMate Common Package

Shared functionality for MatesOfMate extensions.

## Components

### Process Execution (`src/Process/`)
- `ProcessExecutorInterface` - Interface for process execution
- `ProcessExecutor` - Concrete class for running CLI tools with PHP binary reuse capability
- `ProcessResult` - DTO for process execution results (exitCode, output, errorOutput)

### Configuration Detection (`src/Config/`)
- `ConfigurationDetectorInterface` - Interface for config file detection
- `ConfigurationDetector` - Concrete class for detecting configuration files in project root

### Message Truncation (`src/Truncator/`)
- `MessageTruncatorInterface` - Interface for message truncation
- `MessageTruncator` - Concrete class for token-efficient output with prefix removal and class name shortening

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer lint

# Fix code style
composer fix
```

## License

MIT
