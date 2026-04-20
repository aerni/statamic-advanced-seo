<?php

namespace Aerni\AdvancedSeo\Actions;

class HasCircularOrigins
{
    public static function handle(array $origins): bool
    {
        $originMap = array_filter($origins);

        foreach ($originMap as $site => $origin) {
            $visited = [$site];
            $current = $origin;

            while ($current !== null) {
                if (in_array($current, $visited)) {
                    return true;
                }

                $visited[] = $current;
                $current = $originMap[$current] ?? null;
            }
        }

        return false;
    }
}
