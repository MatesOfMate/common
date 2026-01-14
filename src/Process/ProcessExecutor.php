<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Process;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Executes CLI tools with consistent PHP version.
 *
 * Reuses the current PHP binary to execute PHP scripts or runs system binaries directly.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ProcessExecutor implements ProcessExecutorInterface
{
    /**
     * @param array<int, string> $vendorPaths
     */
    public function __construct(
        private readonly array $vendorPaths = [],
    ) {
    }

    public function execute(string $binaryName, array $args = [], int $timeout = 300, bool $usePhpBinary = true): ProcessResult
    {
        $binaryPath = $this->findBinary($binaryName);
        if (null === $binaryPath) {
            throw new \RuntimeException(\sprintf('%s binary not found', $binaryName));
        }

        $command = $usePhpBinary ? [...$this->buildCommand($binaryPath), ...$args] : [$binaryPath, ...$args];

        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        return new ProcessResult(
            exitCode: $process->getExitCode() ?? 1,
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
        );
    }

    /**
     * @param array<int, string>|null $vendorPaths
     */
    private function findBinary(string $name, ?array $vendorPaths = null): ?string
    {
        $vendorPaths ??= $this->vendorPaths;

        foreach ($vendorPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        $finder = new ExecutableFinder();

        return $finder->find($name);
    }

    /**
     * @return array<int, string>
     */
    private function buildCommand(string $binaryPath): array
    {
        return [\PHP_BINARY, $binaryPath];
    }
}
