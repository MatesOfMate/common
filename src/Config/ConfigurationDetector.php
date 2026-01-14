<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Config;

/**
 * Detects configuration files in a given project root.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class ConfigurationDetector implements ConfigurationDetectorInterface
{
    /**
     * @param array<int, string> $configFiles
     */
    public function __construct(
        private readonly array $configFiles = [],
    ) {
        foreach ($this->configFiles as $configFile) {
            if ('' === $configFile) {
                throw new \InvalidArgumentException('Config file names cannot be empty strings');
            }
        }
    }

    public function detect(?string $projectRoot = null): ?string
    {
        if ([] === $this->configFiles) {
            return null;
        }

        $projectRoot ??= getcwd();
        if (false === $projectRoot) {
            return null;
        }

        foreach ($this->configFiles as $configFile) {
            $path = $projectRoot.'/'.$configFile;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
