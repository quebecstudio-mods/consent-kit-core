<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * The site a resolution is about, reduced to what the core needs from it.
 */
final class SiteContext
{
    public function __construct(
        public readonly int $id,
        public readonly string $handle,
        public readonly string $language,
    ) {
    }
}
