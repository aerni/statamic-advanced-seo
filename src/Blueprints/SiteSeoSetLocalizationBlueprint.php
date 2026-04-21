<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Features\Cloudflare;
use Aerni\AdvancedSeo\Features\Fathom;
use Aerni\AdvancedSeo\Features\Favicons;
use Aerni\AdvancedSeo\Features\GoogleTagManager;
use Aerni\AdvancedSeo\Features\SiteVerification;
use Statamic\Facades\Site;

class SiteSeoSetLocalizationBlueprint extends BaseBlueprint
{
    use HasAssetField;

    protected function handle(): string
    {
        return 'site_localization';
    }

    protected function tabs(): array
    {
        return [
            'general' => [
                $this->titles(),
                $this->favicons(),
            ],
            'search_appearance' => [
                $this->structuredData(),
                $this->indexing(),
            ],
            'social_appearance' => [
                $this->socialImage(),
                $this->twitter(),
            ],
            'integrations' => [
                $this->ai(),
                $this->siteVerification(),
                $this->fathom(),
                $this->cloudflare(),
                $this->googleTagManager(),
            ],
        ];
    }

    protected function titles(): array
    {
        return [
            'display' => $this->trans('section_titles.display'),
            'instructions' => $this->trans('section_titles.instructions'),
            'fields' => [
                [
                    'handle' => 'site_name',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('site_name.display'),
                        'instructions' => $this->trans('site_name.instructions'),
                        'input_type' => 'text',
                        'default' => $this->lazy(fn (Context $context) => Site::get($context->site)?->name()),
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'separator',
                    'field' => [
                        'type' => 'select',
                        'display' => $this->trans('separator.display'),
                        'instructions' => $this->trans('separator.instructions'),
                        'options' => [
                            ' | ' => '|',
                            ' / ' => '/',
                            ' – ' => '–',
                            ' - ' => '-',
                            ' :: ' => '::',
                            ' > ' => '>',
                            ' ~ ' => '~',
                        ],
                        'default' => '|',
                        'clearable' => false,
                        'multiple' => false,
                        'searchable' => true,
                        'localizable' => true,
                        'taggable' => true,
                        'push_tags' => false,
                        'cast_booleans' => false,
                        'width' => 50,
                        'listable' => 'hidden',
                    ],
                ],
            ],
        ];
    }

    protected function structuredData(): array
    {
        return [
            'display' => $this->trans('section_structured_data.display'),
            'instructions' => $this->trans('section_structured_data.instructions'),
            'fields' => [
                [
                    'handle' => 'site_json_ld_type',
                    'field' => [
                        'type' => 'button_group',
                        'display' => $this->trans('site_json_ld_type.display'),
                        'instructions' => $this->trans('site_json_ld_type.instructions'),
                        'options' => [
                            'none' => $this->trans('site_json_ld_type.none'),
                            'organization' => $this->trans('site_json_ld_type.organization'),
                            'person' => $this->trans('site_json_ld_type.person'),
                            'custom' => $this->trans('site_json_ld_type.custom'),
                        ],
                        'default' => 'none',
                        'localizable' => true,
                        'listable' => false,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'use_breadcrumbs',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('use_breadcrumbs.display'),
                        'instructions' => $this->trans('use_breadcrumbs.instructions'),
                        'default' => true,
                        'localizable' => true,
                        'listable' => false,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'organization_name',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('organization_name.display'),
                        'instructions' => $this->trans('organization_name.instructions'),
                        'input_type' => 'text',
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                        'if' => [
                            'site_json_ld_type' => 'equals organization',
                        ],
                        'validate' => [
                            'required_if:site_json_ld_type,organization',
                        ],
                    ],
                ],
                [
                    'handle' => 'organization_logo',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('organization_logo.display'),
                        'instructions' => $this->trans('organization_logo.instructions'),
                        'width' => 50,
                        'folder' => null,
                        'validate' => [
                            'image',
                        ],
                        'if' => [
                            'site_json_ld_type' => 'equals organization',
                        ],
                    ]),
                ],
                [
                    'handle' => 'person_name',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('person_name.display'),
                        'instructions' => $this->trans('person_name.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'width' => 50,
                        'localizable' => true,
                        'if' => [
                            'site_json_ld_type' => 'equals person',
                        ],
                        'validate' => [
                            'required_if:site_json_ld_type,person',
                        ],
                    ],
                ],
                [
                    'handle' => 'site_json_ld',
                    'field' => [
                        'type' => 'json_ld',
                        'display' => $this->trans('site_json_ld.display'),
                        'instructions' => $this->trans('site_json_ld.instructions'),
                        'theme' => 'material',
                        'mode' => 'javascript',
                        'mode_selectable' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'if' => [
                            'site_json_ld_type' => 'equals custom',
                        ],
                        'validate' => [
                            'required_if:site_json_ld_type,custom',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function favicons(): array
    {
        return [
            'display' => $this->trans('section_favicon.display'),
            'instructions' => $this->trans('section_favicon.instructions'),
            'fields' => [
                [
                    'handle' => 'favicon_svg',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('favicon_svg.display'),
                        'instructions' => $this->trans('favicon_svg.instructions'),
                        'container' => config('advanced-seo.favicons.container', 'assets'),
                        'folder' => 'favicons',
                        'localizable' => true,
                        'feature' => Favicons::class,
                        'validate' => [
                            'image',
                            'mimes:svg',
                        ],
                    ]),
                ],
            ],
        ];
    }

    protected function socialImage(): array
    {
        return [
            'display' => $this->trans('section_social_image.display'),
            'instructions' => $this->trans('section_social_image.instructions'),
            'fields' => [
                [
                    'handle' => 'og_image',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('og_image.display'),
                        'instructions' => $this->trans('og_image.instructions'),
                        'validate' => [
                            'image',
                            'mimes:jpg,png',
                        ],
                    ]),
                ],
            ],
        ];
    }

    protected function twitter(): array
    {
        return [
            'display' => $this->trans('section_twitter.display'),
            'instructions' => $this->trans('section_twitter.instructions'),
            'fields' => [
                [
                    'handle' => 'twitter_card',
                    'field' => [
                        'type' => 'button_group',
                        'display' => $this->trans('twitter_card.display'),
                        'instructions' => $this->trans('twitter_card.instructions'),
                        'options' => [
                            'summary_large_image' => $this->trans('twitter_card.summary_large_image'),
                            'summary' => $this->trans('twitter_card.summary'),
                        ],
                        'default' => 'summary_large_image',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'twitter_handle',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('twitter_handle.display'),
                        'instructions' => $this->trans('twitter_handle.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'prepend' => '@',
                        'antlers' => false,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }

    protected function indexing(): array
    {
        return [
            'display' => $this->trans('section_indexing.display'),
            'instructions' => $this->trans('section_indexing.instructions'),
            'fields' => [
                [
                    'handle' => 'noindex',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('noindex.display'),
                        'instructions' => $this->trans('noindex.instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                    ],
                ],
            ],
        ];
    }

    protected function siteVerification(): array
    {
        return [
            'display' => $this->trans('section_verification.display'),
            'instructions' => $this->trans('section_verification.instructions'),
            'fields' => [
                [
                    'handle' => 'google_site_verification_code',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('google_site_verification_code.display'),
                        'instructions' => $this->trans('google_site_verification_code.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => SiteVerification::class,
                    ],
                ],
                [
                    'handle' => 'bing_site_verification_code',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('bing_site_verification_code.display'),
                        'instructions' => $this->trans('bing_site_verification_code.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => SiteVerification::class,
                    ],
                ],
            ],
        ];
    }

    protected function fathom(): array
    {
        return [
            'display' => $this->trans('section_fathom.display'),
            'instructions' => $this->trans('section_fathom.instructions'),
            'fields' => [
                [
                    'handle' => 'fathom_id',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('fathom_id.display'),
                        'instructions' => $this->trans('fathom_id.instructions'),
                        'input_type' => 'text',
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'antlers' => true,
                        'feature' => Fathom::class,
                    ],
                ],
                [
                    'handle' => 'fathom_spa',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('fathom_spa.display'),
                        'instructions' => $this->trans('fathom_spa.instructions'),
                        'icon' => 'toggle',
                        'listable' => 'hidden',
                        'default' => false,
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Fathom::class,
                    ],
                ],
            ],
        ];
    }

    protected function cloudflare(): array
    {
        return [
            'display' => $this->trans('section_cloudflare_web_analytics.display'),
            'instructions' => $this->trans('section_cloudflare_web_analytics.instructions'),
            'fields' => [
                [
                    'handle' => 'cloudflare_beacon_token',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('cloudflare_beacon_token.display'),
                        'instructions' => $this->trans('cloudflare_beacon_token.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Cloudflare::class,
                    ],
                ],
            ],
        ];
    }

    protected function googleTagManager(): array
    {
        return [
            'display' => $this->trans('section_google_tag_manager.display'),
            'instructions' => $this->trans('section_google_tag_manager.instructions'),
            'fields' => [
                [
                    'handle' => 'gtm_container_id',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('gtm_container_id.display'),
                        'instructions' => $this->trans('gtm_container_id.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => GoogleTagManager::class,
                    ],
                ],
            ],
        ];
    }

    protected function ai(): array
    {
        return [
            'display' => $this->trans('section_ai.display'),
            'instructions' => $this->trans('section_ai.instructions'),
            'fields' => [
                [
                    'handle' => 'ai_instructions',
                    'field' => [
                        'type' => 'textarea',
                        'display' => $this->trans('config_ai_instructions_site.display'),
                        'instructions' => $this->trans('config_ai_instructions_site.instructions'),
                        'placeholder' => $this->trans('config_ai_instructions_site.placeholder'),
                        'localizable' => true,
                        'feature' => Ai::class,
                    ],
                ],
            ],
        ];
    }
}
