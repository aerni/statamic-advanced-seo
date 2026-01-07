<?php

namespace Aerni\AdvancedSeo\Enums;

enum Context: string
{
    /**
     * Config context: Editing collection/taxonomy config toggles.
     * Feature fields should always show (when globally enabled) to allow toggling.
     */
    case CONFIG = 'config';

    /**
     * Localization context: Editing collection/taxonomy/site defaults.
     * Feature fields only show when enabled in the config.
     */
    case LOCALIZATION = 'localization';

    /**
     * Content context: Editing individual entry/term SEO.
     * Feature fields only show when enabled in the collection/taxonomy config.
     */
    case CONTENT = 'content';
}
