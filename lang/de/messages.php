<?php

return [

    // Navigation
    'site' => 'Website',
    'site_description' => 'Website-weite Einstellungen konfigurieren.',
    'collections' => 'Sammlungen',
    'collections_description' => 'Sammlungen konfigurieren und Standardwerte für Einträge festlegen.',
    'taxonomies' => 'Taxonomien',
    'taxonomies_description' => 'Taxonomien konfigurieren und Standardwerte für Begriffe festlegen.',

    // Content type labels
    'entry' => 'Eintrag',
    'entries' => 'Einträge',
    'term' => 'Begriff',
    'terms' => 'Begriffe',
    'reset_to_default' => 'Auf Standard zurücksetzen',

    // Flash messages
    'seo_set_localization_saved' => 'SEO-Standards gespeichert',
    'seo_set_localization_deleted' => 'SEO-Standards gelöscht',
    'seo_set_config_saved' => 'SEO-Konfiguration gespeichert',
    'seo_set_config_deleted' => 'SEO-Konfiguration gelöscht',
    'disable_confirmation' => 'Möchtest du dieses Element wirklich deaktivieren? Alle SEO-Daten werden gelöscht.',

    // UI
    'origins' => 'Quellen',
    'origins_instructions' => 'Werte von ausgewählten Quellen übernehmen.',
    'origins_circular_dependency' => 'Zirkuläre Abhängigkeiten zwischen Website-Quellen sind nicht erlaubt.',
    'configure_title' => ':title konfigurieren',
    'no_items_for_site' => 'Keine :title für die ausgewählte Website konfiguriert.',
    'enabled' => 'Aktiviert',
    'enable' => 'Aktivieren',
    'disable' => 'Deaktivieren',
    'disable_title' => ':title deaktivieren',
    'no_results' => 'Keine Ergebnisse',
    'from_domain' => 'Von :domain',
    'social_image_updates_on_save' => 'Das Bild wird beim Speichern aktualisiert.',
    'social_image_generates_on_first_save' => 'Das Bild wird beim ersten Speichern generiert.',
    'token_group_fields' => 'Felder',
    'token_group_common' => 'Allgemein',
    'token_separator' => 'Trennzeichen',
    'token_site_name' => 'Website-Name',
    'token_add' => 'Token hinzufügen',
    'token_picker_placeholder' => 'Tippe /, um ein Token hinzuzufügen',
    'token_suggestion_placeholder' => 'Tippe, um zu suchen …',

    // Override
    'overridden_by_site_defaults_badge' => 'Überschrieben',
    'overridden_by_site_defaults_tooltip' => 'Dieser Wert wird von den Website-Einstellungen überschrieben und wird erst wirksam, wenn die Website-Einstellung entfernt wird.',

    // AI
    'ai_generate' => 'Mit KI generieren',
    'ai_insufficient_content' => 'Füge mindestens :characters weitere Zeichen hinzu, um mit KI zu generieren.',
    'ai_generation_failed' => 'KI-Generierung fehlgeschlagen. Bitte versuche es erneut.',

    // Permissions
    'permission_configure_seo' => 'SEO konfigurieren (Vollzugriff)',
    'permission_configure_seo_description' => 'Gewährt alle Berechtigungen, einschliesslich der Möglichkeit, Einstellungen, Standards und Inhalte zu bearbeiten',
    'permission_edit_defaults' => 'Standards bearbeiten',
    'permission_edit_defaults_description' => 'Ermöglicht das Bearbeiten von Sammlungs- und Taxonomie-Standards sowie den Zugriff auf den SEO-Tab bei Einträgen und Begriffen',
    'permission_edit_content' => 'Inhalte bearbeiten',
    'permission_edit_content_description' => 'Gewährt Zugriff auf den SEO-Tab bei Einträgen und Begriffen',

    // Pro
    'pro_features' => 'Upgrade auf Pro',
    'pro_features_instructions' => '<a href="https://statamic.com/addons/aerni/advanced-seo" target="_blank" class="underline">Pro holen</a>, um diese und weitere Funktionen freizuschalten.',
    'pro_feature_sitemaps' => 'Sitemaps',
    'pro_feature_multi_site' => 'Multi-Site',
    'pro_feature_ai' => 'KI-Inhaltsgenerierung',
    'pro_feature_social_images' => 'Social Images',
    'pro_feature_permissions' => 'Berechtigungen',
    'pro_feature_graphql' => 'GraphQL',
    'pro_feature_eloquent' => 'Eloquent Driver',
    'pro_feature_custom_routes' => 'Benutzerdefinierte Routen',
    'pro_feature_custom_tokens' => 'Benutzerdefinierte Tokens',
];
