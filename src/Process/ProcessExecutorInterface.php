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

/**
 * Executes CLI processes with consistent PHP version.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
interface ProcessExecutorInterface
{
    /**
     * @param array<int, string> $args
     */
    public function execute(string $binaryName, array $args = [], int $timeout = 300, bool $usePhpBinary = true): ProcessResult;
}
