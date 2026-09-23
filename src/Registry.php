<?php

namespace QuebecStudioMods\ConsentKit\Core;

use DateTimeImmutable;

/**
 * What a consent register is made of, whatever keeps it.
 *
 * The integrations hold the storage; these are the parts that must read the
 * same everywhere — the settings it answers to, the columns it shows, and the
 * date beyond which a record has outlived what it attests.
 */
final class Registry
{
    /**
     * Off by default: keeping a register is something a site announces in its
     * privacy policy, not something an update starts doing.
     */
    public const DEFAULTS = [
        'registry' => false,
        'registryUser' => true,
        'registryRequestContext' => false,
        'registryGrace' => 12,
    ];

    /** Columns a register can be sorted on. */
    public const SORTABLE = ['decidedAt', 'siteHandle', 'action', 'outcome'];

    /**
     * The date before which records have outlived the consent they attest:
     * the life of the consent cookie, plus the grace months on top. Null when
     * the grace is zero, which keeps everything until a purge by hand.
     */
    public static function cutoff(int $cookieMaxAge, int $graceMonths, ?DateTimeImmutable $now = null): ?string
    {
        if ($graceMonths <= 0) {
            return null;
        }

        return ($now ?? new DateTimeImmutable())
            ->modify("-$cookieMaxAge seconds")
            ->modify("-$graceMonths months")
            ->format('Y-m-d H:i:s');
    }
}
