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

    // Validation
    'redirect_source_invalid' => 'Die Quelle muss mit einem Schrägstrich beginnen oder ein in # eingeschlossener regulärer Ausdruck sein.',
    'redirect_source_invalid_regex' => 'Die Quelle ist kein gültiger regulärer Ausdruck.',
    'redirect_source_not_unique' => 'Eine Weiterleitung mit dieser Quelle existiert bereits für diese Website.',
    'redirect_destination_invalid' => 'Das Ziel muss ein Pfad sein, der mit einem Schrägstrich beginnt (z. B. /about), oder eine vollständige URL inklusive Schema (z. B. https://example.com).',
    'redirect_destination_unpublished' => 'Das Ziel muss ein veröffentlichter Eintrag sein, sonst würde die Weiterleitung zu einem 404 führen.',

    // Redirects
    'redirects' => 'Weiterleitungen',
    'redirects_description' => 'URL-Weiterleitungen für deine Websites verwalten.',
    'redirect_create_title' => 'Weiterleitung erstellen',
    'redirect_create_description' => 'Lege deine erste Weiterleitung an, um loszulegen.',
    'redirect_edit_title' => 'Weiterleitung bearbeiten',
    'test_redirect' => 'Weiterleitung testen',
    'save_and_enable' => 'Speichern & aktivieren',
    'save_and_disable' => 'Speichern & deaktivieren',

    // Flash messages
    'redirect_enabled' => 'Weiterleitung aktiviert',
    'redirect_disabled' => 'Weiterleitung deaktiviert',
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

    // Alerts
    'alert_indexing_disabled' => 'Dieser :type wird nicht in Suchergebnissen erscheinen.',

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
    'permission_view_redirects' => 'Weiterleitungen anzeigen',
    'permission_view_redirects_description' => 'Gewährt Zugriff auf die Anzeige von Weiterleitungen. Untergeordnete Berechtigungen ermöglichen das Bearbeiten, Erstellen und Löschen.',
    'permission_edit_redirects' => 'Weiterleitungen bearbeiten',
    'permission_create_redirects' => 'Weiterleitungen erstellen',
    'permission_delete_redirects' => 'Weiterleitungen löschen',
    'permission_edit_redirects_description' => 'Gewährt die Möglichkeit, bestehende Weiterleitungen zu bearbeiten.',
    'permission_create_redirects_description' => 'Gewährt die Möglichkeit, neue Weiterleitungen zu erstellen.',
    'permission_delete_redirects_description' => 'Gewährt die Möglichkeit, Weiterleitungen zu löschen.',

    // Pro
    'pro_features' => 'Upgrade auf Pro',
    'pro_features_instructions' => '<a href="https://statamic.com/addons/aerni/advanced-seo" target="_blank" class="underline">Pro holen</a>, um diese und weitere Funktionen freizuschalten.',
    'pro_feature_sitemaps' => 'Sitemaps',
    'pro_feature_multi_site' => 'Multi-Site',
    'pro_feature_ai' => 'KI-Copywriting',
    'pro_feature_social_images' => 'Social Images',
    'pro_feature_permissions' => 'Berechtigungen',
    'pro_feature_graphql' => 'GraphQL',
    'pro_feature_eloquent' => 'Eloquent Driver',
    'pro_feature_custom_routes' => 'Benutzerdefinierte Routen',
    'pro_feature_custom_tokens' => 'Benutzerdefinierte Tokens',
];
