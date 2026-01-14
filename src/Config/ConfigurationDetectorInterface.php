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
 * Detects configuration files in project directories.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
interface ConfigurationDetectorInterface
{
    public function detect(?string $projectRoot = null): ?string;
}
