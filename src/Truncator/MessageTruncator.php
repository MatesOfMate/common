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
 * Truncates and formats messages for token-efficient output.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
class MessageTruncator implements MessageTruncatorInterface
{
    /**
     * @param array<int, string> $prefixes
     */
    public function __construct(
        private readonly array $prefixes = [],
    ) {
    }

    public function truncate(string $message, int $maxLength = 200): string
    {
        $message = $this->removeCommonPrefixes($message, $this->prefixes);

        $message = $this->shortenClassName($message);

        // Truncate if still too long
        if (\strlen($message) > $maxLength) {
            return substr($message, 0, $maxLength - 3).'...';
        }

        return $message;
    }

    /**
     * @param array<int, string> $prefixes
     */
    private function removeCommonPrefixes(string $message, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($message, (string) $prefix)) {
                return substr($message, \strlen((string) $prefix));
            }
        }

        return $message;
    }

    private function shortenClassName(string $text): string
    {
        // Convert App\Very\Long\Namespace\ClassName to App\ClassName
        return (string) preg_replace('/([A-Z][a-z]+)\\\\([A-Z][a-z]+\\\\)+/', '$1\\', $text);
    }
}
