<?php

namespace Aerni\AdvancedSeo\Enums;

enum Scope: string
{
    /**
     * Config scope: Editing SeoSetConfig
     */
    case CONFIG = 'config';

    /**
     * Localization scope: Editing SeoSetLocalization
     */
    case LOCALIZATION = 'localization';

    /**
     * Content scope: Editing individual entries and terms
     */
    case CONTENT = 'content';
}
