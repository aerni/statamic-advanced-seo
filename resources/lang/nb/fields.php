<?php

use Aerni\AdvancedSeo\Facades\SocialImage;

return [

    'seo_section_title_description' => [
        'instructions' => 'Konfigurer tittel og beskrivelse for denne :type.',
        'default_instructions' => 'Konfigurer tittel og beskrivelse for :type.',
    ],

    'seo_title' => [
        'instructions' => 'Sett metatittel for denne :type.',
        'default_instructions' => 'Sett standard metatittel for :type.',
    ],

    'seo_description' => [
        'instructions' => 'Sett metabeskrivelse for denne :type.',
        'default_instructions' => 'Sett standard metabeskrivelse for :type.',
    ],

    'seo_section_social_images_generator' => [
        'instructions' => 'Konfigurer generatorinnstillingene for denne :type.',
        'default_instructions' => 'Konfigurer standard generatorinnstillinger for :type.',
    ],

    'seo_generate_social_images' => [
        'instructions' => 'Aktiver for å bruke bildegeneratoren for denne :type.',
        'default_instructions' => 'Aktiver for å bruke bildegeneratoren for :type som standard.',
    ],

    'seo_social_images_theme' => [
        'instructions' => 'Velg temaet som skal brukes for genererte bilder for denne :type.',
        'default_instructions' => 'Velg standardtemaet som skal brukes for genererte bilder for :type.',
    ],

    'seo_section_og' => [
        'instructions' => 'Konfigurer Open Graph-innstillinger for denne :type.',
        'default_instructions' => 'Konfigurer standard Open Graph-innstillinger for :type.',
    ],

    'seo_og_title' => [
        'instructions' => 'Sett Open Graph-tittel for denne :type.',
        'default_instructions' => 'Sett standard Open Graph-tittel for :type.',
    ],

    'seo_og_description' => [
        'instructions' => 'Sett Open Graph-beskrivelse for denne :type.',
        'default_instructions' => 'Sett standard Open Graph-beskrivelse for :type.',
    ],

    'seo_og_image' => [
        'instructions' => 'Legg til et Open Graph-bilde for denne :type. Det vil bli beskjært til ' . SocialImage::sizeString('open_graph') . '.',
        'default_instructions' => 'Legg til et Open Graph-bilde for :type. Det vil bli beskjært til ' . SocialImage::sizeString('open_graph') . '.',
    ],

    'seo_section_twitter' => [
        'instructions' => 'Konfigurer Twitter-innstillingene for denne :type.',
        'default_instructions' => 'Konfigurer standard Twitter-innstillinger for :type.',
    ],

    'seo_twitter_card' => [
        'instructions' => 'Velg type kort som skal brukes når denne :type deles.',
        'default_instructions' => 'Velg standardkort som skal brukes når denne :type deles.',
    ],

    'seo_twitter_title' => [
        'instructions' => 'Sett Twitter-tittelen for denne :type.',
        'default_instructions' => 'Sett standard Twitter-tittel for :type.',
    ],

    'seo_twitter_description' => [
        'instructions' => 'Sett Twitter-beskrivelsen for denne :type',
        'default_instructions' => 'Sett standard Twitter-beskrivelse for :type',
    ],

    'seo_twitter_summary_image' => [
        'instructions' => 'Legg til et Twitter-bilde for denne :type. Det blir beskjært til ' . SocialImage::sizeString('twitter_summary') . '.',
        'default_instructions' => 'Legg til et standard Twitter-bilde for denne :type. Det blir beskjært til ' . SocialImage::sizeString('twitter_summary') . '.',
    ],

    'seo_twitter_summary_large_image' => [
        'instructions' => 'Legg til et Twitter-bilde for denne :type. Det blir beskjært til ' . SocialImage::sizeString('twitter_summary_large_image') . '.',
        'default_instructions' => 'Legg til et standard Twitter-bilde for denne :type. Det blir beskjært til ' . SocialImage::sizeString('twitter_summary_large_image') . '.',
    ],

    'seo_section_canonical_url' => [
        'instructions' => 'Konfigurer de kanoniske URL-innstillingene for denne :type.',
        'default_instructions' => 'Konfigurer standard kanoniske URL-innstillinger for :type.',
    ],

    'seo_canonical_type' => [
        'instructions' => 'Hvor skal den kanoniske nettadressen til denne :type peke?',
        'default_instructions' => 'Hvor skal den standard kanoniske nettadressen til :type peke?',
    ],

    'seo_canonical_entry' => [
        'instructions' => 'Velg oppføringen med det originale innholdet.',
        'default_instructions' => 'Velg oppføringen med det originale innholdet.',
    ],

    'seo_canonical_custom' => [
        'instructions' => 'En fullstendig kvalifisert URL som begynner med https://.',
        'default_instructions' => 'En fullstendig kvalifisert URL som begynner med https://.',
    ],

    'seo_section_indexing' => [
        'instructions' => 'Konfigurer indekseringsinnstillingene for denne :type.',
        'default_instructions' => 'Konfigurer standard indekseringsinnstillinger for :type.',
    ],

    'seo_noindex' => [
        'instructions' => 'Hindre at denne :type blir indeksert av søkemotorer.',
        'default_instructions' => 'Hindre at din :type blir indeksert av søkemotorer.',
    ],

    'seo_nofollow' => [
        'instructions' => 'Hindre søkeroboter fra å følge koblinger på denne :type -side.',
        'default_instructions' => 'Hindre søkeroboter fra å følge koblinger på dine :type -sider.',
    ],

    'seo_section_sitemap' => [
        'instructions' => 'Konfigurer nettstedkartinnstillingene for denne :type.',
        'default_instructions' => 'Konfigurer standard nettstedkartinnstillinger for :type.',
    ],

    'seo_sitemap_enabled' => [
        'instructions' => 'Velg å legge til eller fjerne denne :type fra nettstedskartet.',
        'default_instructions' => 'Velg å legge til eller fjerne :type fra nettstedskartet.',
    ],

    'seo_sitemap_priority' => [
        'instructions' => 'Velg områdekartprioritet for denne :type. 1.0 er det viktigste.',
        'default_instructions' => 'Velg standard områdekartprioritet for din :type. 1.0 er det viktigste.',
    ],

    'seo_sitemap_change_frequency' => [
        'instructions' => 'Velg frekvensen som søkemotorer skal gjennomsøke denne :type med.',
        'default_instructions' => 'Velg standardfrekvensen for søkemotorer som skal gjennomsøke :type.',
    ],

    'seo_section_json_ld' => [
        'instructions' => 'Legg til tilpasset [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for denne :type.',
        'default_instructions' => 'Legg til standard tilpasset [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for :type.',
    ],

    'seo_json_ld' => [
        'instructions' => 'De strukturerte dataene av denne :type. Dette vil bli pakket inn i den riktige skriptkoden.',
        'default_instructions' => 'Standard strukturerte data for din :type. Dette vil bli pakket inn i den riktige skriptkoden..',
    ],

];
