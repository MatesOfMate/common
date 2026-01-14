<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\Common\Truncator;

/**
 * Truncates messages for token-efficient output.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
interface MessageTruncatorInterface
{
    public function truncate(string $message, int $maxLength = 200): string;
}
