<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Fields
    |--------------------------------------------------------------------------
    |
    | Used by ContentSeoBlueprint and ContentSeoSetLocalizationBlueprint.
    |
    */

    'seo_section_search_appearance' => [
        'display' => 'Suchdarstellung',
        'instructions' => 'Steuere, wie dieser :type in Suchergebnissen erscheint.',
        'default_instructions' => 'Lege Standards fest, wie deine :type in Suchergebnissen erscheinen.',
    ],

    'seo_title' => [
        'display' => 'Meta-Titel',
        'instructions' => 'Wird als Titel in Suchergebnissen angezeigt.',
        'default_instructions' => 'Der Standard-Titel für deine :type in Suchergebnissen.',
    ],

    'seo_description' => [
        'display' => 'Meta-Beschreibung',
        'instructions' => 'Wird als Beschreibung in Suchergebnissen angezeigt.',
        'default_instructions' => 'Die Standard-Beschreibung für deine :type in Suchergebnissen.',
    ],

    'seo_search_preview' => [
        'display' => 'Suchvorschau',
    ],

    'seo_section_social_appearance' => [
        'display' => 'Social-Media-Darstellung',
        'instructions' => 'Steuere, wie dieser :type beim Teilen in sozialen Medien aussieht.',
        'default_instructions' => 'Lege Standards fest, wie deine :type beim Teilen in sozialen Medien aussehen.',
    ],

    'seo_og_title' => [
        'display' => 'Social-Media-Titel',
        'instructions' => 'Wird als Titel beim Teilen in sozialen Medien angezeigt.',
        'default_instructions' => 'Der Standard-Social-Media-Titel für deine :type.',
    ],

    'seo_og_description' => [
        'display' => 'Social-Media-Beschreibung',
        'instructions' => 'Wird als Beschreibung beim Teilen in sozialen Medien angezeigt.',
        'default_instructions' => 'Die Standard-Social-Media-Beschreibung für deine :type.',
    ],

    'seo_og_image' => [
        'display' => 'Social-Media-Bild',
        'instructions' => 'Empfohlene Größe: 1200x630px. Wird bei Bedarf automatisch angepasst.',
        'default_instructions' => 'Das Standard-Social-Media-Bild für deine :type. Empfohlene Größe: 1200x630px.',
    ],

    'seo_generate_social_images' => [
        'display' => 'Social-Media-Bilder generieren',
        'instructions' => 'Automatisch Social-Media-Bilder für diesen :type generieren.',
        'default_instructions' => 'Automatisch Social-Media-Bilder für deine :type generieren.',
    ],

    'seo_social_images_theme' => [
        'display' => 'Theme',
        'instructions' => 'Das Theme für generierte Social-Media-Bilder.',
        'default_instructions' => 'Das Standard-Theme für generierte Social-Media-Bilder.',
    ],

    'seo_social_preview' => [
        'display' => 'Social-Media-Vorschau',
    ],

    'seo_section_indexing' => [
        'display' => 'Indexierung',
        'instructions' => 'Steuere die Suchmaschinen-Indexierung für diesen :type.',
        'default_instructions' => 'Lege das Standard-Indexierungsverhalten für deine :type fest.',
    ],

    'seo_noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Diesen :type aus den Suchergebnissen ausschliessen.',
        'default_instructions' => 'Deine :type aus den Suchergebnissen ausschliessen.',
    ],

    'seo_nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Crawler anweisen, Links auf diesem :type nicht zu folgen.',
        'default_instructions' => 'Crawler anweisen, Links auf deinen :type nicht zu folgen.',
    ],

    'seo_section_robots' => [
        'display' => 'Robots',
        'instructions' => 'Zusätzliche Robots-Anweisungen für diesen :type.',
        'default_instructions' => 'Standard-Robots-Anweisungen für deine :type festlegen.',
    ],

    'seo_noarchive' => [
        'display' => 'Noarchive',
        'instructions' => 'Den Cache-Link in den Suchergebnissen ausblenden.',
        'default_instructions' => 'Den Cache-Link in den Suchergebnissen ausblenden.',
    ],

    'seo_nosnippet' => [
        'display' => 'Nosnippet',
        'instructions' => 'Den Textausschnitt in den Suchergebnissen ausblenden.',
        'default_instructions' => 'Den Textausschnitt in den Suchergebnissen ausblenden.',
    ],

    'seo_noimageindex' => [
        'display' => 'Noimageindex',
        'instructions' => 'Bilder auf diesem :type von der Bildersuche ausschliessen.',
        'default_instructions' => 'Bilder auf deinen :type von der Bildersuche ausschliessen.',
    ],

    'seo_canonical_type' => [
        'display' => 'Kanonische URL',
        'instructions' => 'Wohin die kanonische URL verweisen soll.',
        'current' => 'Aktuell',
        'entry' => 'Eintrag',
        'custom' => 'URL',
    ],

    'seo_canonical_entry' => [
        'display' => 'Eintrag',
        'instructions' => 'Der Eintrag mit dem Originalinhalt.',
    ],

    'seo_canonical_custom' => [
        'display' => 'URL',
        'instructions' => 'Eine vollständig qualifizierte [aktive URL](https://laravel.com/docs/master/validation#rule-active-url).',
    ],

    'seo_sitemap_enabled' => [
        'display' => 'Sitemap',
        'instructions' => 'Diesen :type in die Sitemap aufnehmen.',
        'default_instructions' => 'Deine :type in die Sitemap aufnehmen.',
    ],

    'seo_section_structured_data' => [
        'display' => 'Strukturierte Daten',
        'instructions' => 'Füge benutzerdefinierte [strukturierte Daten](https://developers.google.com/search/docs/guides/intro-structured-data) für diesen :type hinzu.',
        'default_instructions' => 'Lege Standard-[strukturierte Daten](https://developers.google.com/search/docs/guides/intro-structured-data) für deine :type fest.',
    ],

    'seo_json_ld' => [
        'display' => 'JSON-LD-Schema',
        'instructions' => 'Benutzerdefinierte strukturierte Daten. Werden automatisch in ein Skript-Tag eingebettet.',
        'default_instructions' => 'Standard-strukturierte Daten für deine :type. Werden automatisch in ein Skript-Tag eingebettet.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — General
    |--------------------------------------------------------------------------
    |
    | Used by SiteSeoSetLocalizationBlueprint.
    |
    */

    'section_titles' => [
        'display' => 'Titel',
        'instructions' => 'Konfiguriere, wie Seitentitel angezeigt werden.',
    ],

    'site_name' => [
        'display' => 'Website-Name',
        'instructions' => 'Wird an deine Meta-Titel angehängt.',
    ],

    'separator' => [
        'display' => 'Trennzeichen',
        'instructions' => 'Das Zeichen zwischen dem Seitentitel und dem Website-Namen.',
    ],

    'section_favicon' => [
        'display' => 'Favicon',
        'instructions' => 'Das Symbol, das in Browser-Tabs angezeigt wird.',
    ],

    'favicon_svg' => [
        'display' => 'Favicon (SVG)',
        'instructions' => 'Ein SVG-Favicon hochladen.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Search Appearance
    |--------------------------------------------------------------------------
    */

    'section_structured_data' => [
        'display' => 'Strukturierte Daten',
        'instructions' => 'Website-weite [strukturierte Daten](https://developers.google.com/search/docs/guides/intro-structured-data), die Suchmaschinen helfen, deine Website zu verstehen.',
    ],

    'site_json_ld_type' => [
        'display' => 'Inhaltstyp',
        'instructions' => 'Was diese Website repräsentiert.',
        'none' => 'Keiner',
        'organization' => 'Organisation',
        'person' => 'Person',
        'custom' => 'Benutzerdefiniert',
    ],

    'use_breadcrumbs' => [
        'display' => 'Breadcrumbs',
        'instructions' => '[Breadcrumb](https://developers.google.com/search/docs/data-types/breadcrumb)-strukturierte Daten hinzufügen.',
    ],

    'organization_name' => [
        'display' => 'Organisationsname',
        'instructions' => 'Der Name der Organisation.',
    ],

    'organization_logo' => [
        'display' => 'Organisationslogo',
        'instructions' => 'Mindestgröße: 112x112px.',
    ],

    'person_name' => [
        'display' => 'Personenname',
        'instructions' => 'Der Name der Person.',
    ],

    'site_json_ld' => [
        'display' => 'JSON-LD-Schema',
        'instructions' => 'Strukturierte Daten, die auf jeder Seite hinzugefügt werden. Werden automatisch in ein Skript-Tag eingebettet.',
    ],

    'section_indexing' => [
        'display' => 'Indexierung',
        'instructions' => 'Website-weite Indexierungseinstellungen, die Eintrags- und Begriffseinstellungen überschreiben.',
    ],

    'noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Deine gesamte Website aus den Suchergebnissen ausschliessen.',
    ],

    'section_verification' => [
        'display' => 'Website-Verifizierung',
        'instructions' => 'Bestätige die Inhaberschaft dieser Website.',
    ],

    'google_site_verification_code' => [
        'display' => 'Google-Verifizierungscode',
        'instructions' => 'Aus der [Google Search Console](https://search.google.com/search-console).',
    ],

    'bing_site_verification_code' => [
        'display' => 'Bing-Verifizierungscode',
        'instructions' => 'Aus den [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster).',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Social Appearance
    |--------------------------------------------------------------------------
    */

    'section_social_image' => [
        'display' => 'Social-Media-Bild',
        'instructions' => 'Wird als Standard verwendet, wenn Links in sozialen Medien geteilt werden.',
    ],

    'og_image' => [
        'display' => 'Social-Media-Bild',
        'instructions' => 'Fallback-Bild, wenn keines im Inhalt gesetzt ist. Empfohlene Größe: 1200x630px.',
    ],

    'section_twitter' => [
        'display' => 'X (Twitter)',
        'instructions' => 'Lege fest, wie Links zu deiner Website auf X dargestellt werden.',
    ],

    'twitter_card' => [
        'display' => 'X (Twitter) Card',
        'instructions' => 'Die Größe der Linkvorschau auf X.',
        'summary' => 'Klein',
        'summary_large_image' => 'Groß',
    ],

    'twitter_handle' => [
        'display' => 'X (Twitter) Benutzername',
        'instructions' => 'Der mit dieser Website verknüpfte X-Account.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — Analytics
    |--------------------------------------------------------------------------
    */

    'section_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Das Tracking-Skript von [Fathom Analytics](https://usefathom.com) einbinden.',
    ],

    'fathom_id' => [
        'display' => 'Website-ID',
        'instructions' => 'Aus deinem Fathom-Dashboard.',
    ],

    'fathom_spa' => [
        'display' => 'SPA-Modus',
        'instructions' => 'Aktivieren, wenn deine Website clientseitiges Routing verwendet.',
    ],

    'section_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Das Tracking-Skript von [Cloudflare Web Analytics](https://www.cloudflare.com/web-analytics) einbinden.',
    ],

    'cloudflare_beacon_token' => [
        'display' => 'Beacon-Token',
        'instructions' => 'Aus deinem Cloudflare-Analytics-Dashboard.',
    ],

    'section_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Das Container-Skript von [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager) einbinden.',
    ],

    'gtm_container_id' => [
        'display' => 'Container-ID',
        'instructions' => 'Aus deinem Google Tag Manager-Arbeitsbereich.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Fields — AI
    |--------------------------------------------------------------------------
    */

    'section_ai' => [
        'display' => 'KI',
        'instructions' => 'Konfiguriere KI-Funktionen für deine Website.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Config Fields
    |--------------------------------------------------------------------------
    |
    | Used by ContentSeoSetConfigBlueprint and SiteSeoSetConfigBlueprint.
    |
    */

    'config_enabled' => [
        'display' => 'SEO aktivieren',
        'instructions' => 'SEO-Daten für diesen :type verarbeiten und ausgeben.',
    ],

    'config_editable' => [
        'display' => 'Bearbeitung aktivieren',
        'instructions' => 'Bearbeitung von SEO-Feldern auf einzelnen :content erlauben.',
    ],

    'config_section_sitemaps' => [
        'display' => 'Sitemaps',
    ],

    'config_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Aktiviert die Sitemap für diesen :type.',
    ],

    'config_section_social_images' => [
        'display' => 'Social-Media-Bilder',
    ],

    'config_social_images_generator' => [
        'display' => 'Social-Media-Bilder-Generator',
        'instructions' => 'Aktiviert den Social-Media-Bilder-Generator für diesen :type.',
    ],

    'config_social_images_themes' => [
        'display' => 'Themes',
        'instructions' => 'Wähle die verfügbaren Social-Media-Bild-Themes für diesen :type.',
    ],

    'config_section_ai' => [
        'display' => 'KI',
    ],

    'config_ai' => [
        'display' => 'KI aktivieren',
        'instructions' => 'KI-Funktionen für diesen :type aktivieren.',
    ],

    'config_ai_instructions' => [
        'display' => 'Copywriting-Anweisungen',
        'instructions' => 'Spezifische Anweisungen für die Erstellung von Titeln und Beschreibungen für diesen :type.',
        'placeholder' => 'Beispiel: Die :content dieses :type sind Produkte. Hebe Funktionen und Preise hervor.',
    ],

    'config_ai_instructions_site' => [
        'display' => 'Copywriting-Anweisungen',
        'instructions' => 'Allgemeine Anweisungen für die Erstellung von Titeln und Beschreibungen über alle Sammlungen und Taxonomien hinweg.',
        'placeholder' => 'Beispiel: Verwende einen freundlichen, umgangssprachlichen Ton. Sage immer „nachhaltig" statt „umweltfreundlich".',
    ],

];
