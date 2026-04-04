<?php

return [

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

    'seo_section_social_appearance' => [
        'display' => 'Social-Media-Darstellung',
        'instructions' => 'Steuere, wie dieser :type beim Teilen in sozialen Medien aussieht.',
        'default_instructions' => 'Lege Standards fest, wie deine :type beim Teilen in sozialen Medien aussehen.',
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

    'seo_og_image' => [
        'display' => 'Social-Media-Bild',
        'instructions' => 'Empfohlene Größe: 1200x630px. Wird bei Bedarf automatisch angepasst.',
        'default_instructions' => 'Das Standard-Social-Media-Bild für deine :type. Empfohlene Größe: 1200x630px.',
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

    'seo_section_canonical_url' => [
        'display' => 'Kanonische URL',
        'instructions' => 'Lege die kanonische URL für diesen :type fest.',
        'default_instructions' => 'Lege die Standard-kanonische URL für deine :type fest.',
    ],

    'seo_canonical_type' => [
        'display' => 'Kanonische URL',
        'instructions' => 'Wohin die kanonische URL verweisen soll.',
        'default_instructions' => 'Wohin die Standard-kanonische URL verweisen soll.',
        'current' => 'Aktueller :type',
        'other' => 'Anderer Eintrag',
        'custom' => 'Benutzerdefinierte URL',
    ],

    'seo_canonical_entry' => [
        'display' => 'Eintrag',
        'instructions' => 'Der Eintrag mit dem Originalinhalt.',
        'default_instructions' => 'Der Eintrag mit dem Originalinhalt.',
    ],

    'seo_canonical_custom' => [
        'display' => 'URL',
        'instructions' => 'Eine vollständig qualifizierte [aktive URL](https://laravel.com/docs/master/validation#rule-active-url).',
        'default_instructions' => 'Eine vollständig qualifizierte [aktive URL](https://laravel.com/docs/master/validation#rule-active-url).',
    ],

    'seo_section_indexing' => [
        'display' => 'Indexierung',
        'instructions' => 'Steuere die Suchmaschinen-Indexierung für diesen :type.',
        'default_instructions' => 'Lege das Standard-Indexierungsverhalten für deine :type fest.',
    ],

    'seo_noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Verhindere, dass Suchmaschinen diesen :type indexieren.',
        'default_instructions' => 'Verhindere, dass Suchmaschinen deine :type indexieren.',
    ],

    'seo_nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Verhindere, dass Crawler Links auf diesem :type folgen.',
        'default_instructions' => 'Verhindere, dass Crawler Links auf deinen :type folgen.',
    ],

    'seo_section_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Konfiguriere Sitemap-Einstellungen für diesen :type.',
        'default_instructions' => 'Lege Standard-Sitemap-Einstellungen für deine :type fest.',
    ],

    'seo_sitemap_enabled' => [
        'display' => 'Aktiviert',
        'instructions' => 'Diesen :type in die Sitemap aufnehmen.',
        'default_instructions' => 'Deine :type in die Sitemap aufnehmen.',
    ],

    'seo_sitemap_priority' => [
        'display' => 'Priorität',
        'instructions' => 'Sitemap-Priorität. 1.0 ist am wichtigsten.',
        'default_instructions' => 'Standard-Sitemap-Priorität. 1.0 ist am wichtigsten.',
    ],

    'seo_sitemap_change_frequency' => [
        'display' => 'Änderungshäufigkeit',
        'instructions' => 'Wie oft Suchmaschinen diesen :type crawlen sollen.',
        'default_instructions' => 'Wie oft Suchmaschinen deine :type crawlen sollen.',
        'always' => 'Immer',
        'hourly' => 'Stündlich',
        'daily' => 'Täglich',
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
        'never' => 'Nie',
    ],

    'seo_section_structured_data' => [
        'display' => 'Strukturierte Daten',
        'instructions' => 'Füge benutzerdefinierte [strukturierte Daten](https://developers.google.com/search/docs/guides/intro-structured-data) für diesen :type hinzu.',
        'default_instructions' => 'Lege Standard-[strukturierte Daten](https://developers.google.com/search/docs/guides/intro-structured-data) für deine :type fest.',
    ],

    'seo_json_ld' => [
        'display' => 'JSON-LD-Schema',
        'instructions' => 'Benutzerdefinierte strukturierte Daten. Werden automatisch in ein Script-Tag eingebettet.',
        'default_instructions' => 'Standard-strukturierte Daten für deine :type. Werden automatisch in ein Script-Tag eingebettet.',
    ],

    'seo_search_preview' => [
        'display' => 'Suchvorschau',
    ],

    'seo_social_preview' => [
        'display' => 'Social-Media-Vorschau',
    ],

    'section_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Eine datenschutzfreundliche Alternative zu Google Analytics. [Mehr erfahren](https://usefathom.com)',
    ],

    'use_fathom' => [
        'display' => 'Fathom',
        'instructions' => 'Das Fathom-Tracking-Skript hinzufügen.',
    ],

    'fathom_id' => [
        'display' => 'Website-ID',
        'instructions' => 'Deine Fathom-Website-ID.',
    ],

    'fathom_spa' => [
        'display' => 'SPA-Modus',
        'instructions' => 'Für Single-Page-Applikationen aktivieren.',
    ],

    'section_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Eine datenschutzfreundliche Alternative zu Google Analytics. [Mehr erfahren](https://www.cloudflare.com/web-analytics)',
    ],

    'use_cloudflare_web_analytics' => [
        'display' => 'Cloudflare Web Analytics',
        'instructions' => 'Das Cloudflare Web Analytics-Tracking-Skript hinzufügen.',
    ],

    'cloudflare_web_analytics' => [
        'display' => 'Beacon-Token',
        'instructions' => 'Dein Cloudflare-Beacon-Token.',
    ],

    'section_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Tracking-Skripte über [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager) verwalten.',
    ],

    'use_google_tag_manager' => [
        'display' => 'Google Tag Manager',
        'instructions' => 'Die Google Tag Manager-Skripte hinzufügen.',
    ],

    'google_tag_manager' => [
        'display' => 'Container-ID',
        'instructions' => 'Deine GTM-Container-ID.',
    ],

    'section_favicon' => [
        'display' => 'Favicon',
        'instructions' => 'Das Symbol, das in Browser-Tabs angezeigt wird.',
    ],

    'favicon_svg' => [
        'display' => 'Favicon (SVG)',
        'instructions' => 'Ein SVG-Favicon hochladen.',
    ],

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

    'section_structured_data' => [
        'display' => 'Strukturierte Daten',
        'instructions' => 'Hilf Suchmaschinen, deine Website mit [strukturierten Daten](https://developers.google.com/search/docs/guides/intro-structured-data) zu verstehen.',
    ],

    'site_json_ld_type' => [
        'display' => 'Inhaltstyp',
        'instructions' => 'Was diese Website repräsentiert.',
        'none' => 'Keiner',
        'organization' => 'Organisation',
        'person' => 'Person',
        'custom' => 'Benutzerdefiniert',
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
        'instructions' => 'Strukturierte Daten, die auf jeder Seite hinzugefügt werden. Werden automatisch in ein Script-Tag eingebettet.',
    ],

    'use_breadcrumbs' => [
        'display' => 'Breadcrumbs',
        'instructions' => '[Breadcrumb](https://developers.google.com/search/docs/data-types/breadcrumb)-strukturierte Daten hinzufügen.',
    ],

    'section_indexing' => [
        'display' => 'Indexierung',
        'instructions' => 'Website-weite Indexierungseinstellungen. Diese überschreiben Eintrags- und Begriffseinstellungen.',
    ],

    'noindex' => [
        'display' => 'Noindex',
        'instructions' => 'Verhindere, dass Suchmaschinen deine gesamte Website indexieren.',
    ],

    'nofollow' => [
        'display' => 'Nofollow',
        'instructions' => 'Verhindere, dass Crawler Links auf deiner Website folgen.',
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

    'section_social_appearance' => [
        'display' => 'Social-Media-Darstellung',
        'instructions' => 'Website-weite Social-Media-Einstellungen.',
    ],

    'og_image' => [
        'display' => 'Social-Media-Bild',
        'instructions' => 'Fallback-Bild, wenn keines im Inhalt gesetzt ist. Empfohlene Größe: 1200x630px.',
    ],

    'twitter_card' => [
        'display' => 'X (Twitter) Card',
        'instructions' => 'Kartengröße beim Teilen von Inhalten dieses :type.',
        'summary' => 'Klein',
        'summary_large_image' => 'Groß',
    ],

    'twitter_handle' => [
        'display' => 'X (Twitter) Benutzername',
        'instructions' => 'Dein X (Twitter) Benutzername.',
    ],

    'config_enabled' => [
        'display' => 'SEO aktivieren',
        'instructions' => 'SEO-Daten für diesen :type verarbeiten und ausgeben.',
    ],

    'config_editable' => [
        'display' => 'Bearbeitung aktivieren',
        'instructions' => 'Bearbeitung von SEO-Feldern auf einzelnen :content erlauben.',
    ],

    'config_sitemaps' => [
        'display' => 'Sitemaps',
    ],

    'config_sitemap' => [
        'display' => 'Sitemap',
        'instructions' => 'Aktiviert die Sitemap für diesen :type.',
    ],

    'config_social_images_generator' => [
        'display' => 'Social-Media-Bilder-Generator',
        'instructions' => 'Aktiviert den Social-Media-Bilder-Generator für diesen :type.',
    ],

    'config_social_images_themes' => [
        'display' => 'Themes',
        'instructions' => 'Wähle die verfügbaren Social-Media-Bild-Themes für diesen :type.',
    ],

    'section_ai' => [
        'display' => 'KI',
    ],

    'config_ai' => [
        'display' => 'KI aktivieren',
        'instructions' => 'Aktiviert KI-Funktionen für diese/n :type.',
    ],

    'config_ai_instructions' => [
        'display' => 'Copywriting-Anweisungen',
        'instructions' => 'Spezifische Anweisungen für die Erstellung von Titeln und Beschreibungen für diese/n :type.',
        'placeholder' => 'Beispiel: Die :content dieser/s :type sind Produkte. Hebe Funktionen und Preise hervor.',
    ],

    'ai_instructions' => [
        'display' => 'Copywriting-Anweisungen',
        'instructions' => 'Allgemeine Anweisungen für die Erstellung von Titeln und Beschreibungen über alle Collections und Taxonomien hinweg.',
        'placeholder' => 'Beispiel: Verwende einen freundlichen, umgangssprachlichen Ton. Sage immer „nachhaltig" statt „umweltfreundlich".',
    ],

];
