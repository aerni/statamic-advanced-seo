<?php

namespace Aerni\AdvancedSeo\Enums;

enum Scope: string
{
    /**
     * Config scope: Editing SeoSetConfig
     */
    case Config = 'config';

    /**
     * Localization scope: Editing SeoSetLocalization
     */
    case Localization = 'localization';

    /**
     * Content scope: Editing individual entries and terms
     */
    case Content = 'content';
}
