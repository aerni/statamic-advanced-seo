<?php

return [

    // Navigation
    'site' => 'Site',
    'site_description' => 'Configure site-wide settings.',
    'collections' => 'Collections',
    'collections_description' => 'Configure collections and define default values used by entries.',
    'taxonomies' => 'Taxonomies',
    'taxonomies_description' => 'Configure taxonomies and define default values used by terms.',

    // Content type labels
    'entry' => 'Entry',
    'entries' => 'Entries',
    'term' => 'Term',
    'terms' => 'Terms',
    'reset_to_default' => 'Reset to default',

    // Validation
    'redirect_source_invalid' => 'The source must start with a forward slash, or be a regular expression wrapped in #.',
    'redirect_source_invalid_regex' => 'The source is not a valid regular expression.',
    'redirect_source_not_unique' => 'A redirect with this source already exists for this site.',

    // Flash messages
    'redirect_created' => 'Redirect created',
    'redirect_saved' => 'Redirect saved',
    'redirect_deleted' => 'Redirect deleted',
    'seo_set_localization_saved' => 'SEO defaults saved',
    'seo_set_localization_deleted' => 'SEO defaults deleted',
    'seo_set_config_saved' => 'SEO config saved',
    'seo_set_config_deleted' => 'SEO config deleted',
    'disable_confirmation' => 'Are you sure you want to disable this item? All SEO data will be deleted.',

    // Redirects
    'redirects' => 'Redirects',
    'redirects_description' => 'Manage URL redirects for your sites.',
    'redirect_create_title' => 'Create Redirect',
    'redirect_create_description' => 'Get started by creating your first redirect.',
    'redirect_edit_title' => 'Edit Redirect',

    // UI
    'origins' => 'Origins',
    'origins_instructions' => 'Choose to inherit values from selected origins.',
    'origins_circular_dependency' => 'Circular site origin dependencies are not allowed.',
    'configure_title' => 'Configure :title',
    'no_items_for_site' => 'No :title configured for the selected site.',
    'enabled' => 'Enabled',
    'enable' => 'Enable',
    'disable' => 'Disable',
    'disable_title' => 'Disable :title',
    'no_results' => 'No results',
    'from_domain' => 'From :domain',
    'social_image_updates_on_save' => 'The image updates on save.',
    'social_image_generates_on_first_save' => 'The image generates on first save.',
    'token_group_fields' => 'Fields',
    'token_group_common' => 'Common',
    'token_separator' => 'Separator',
    'token_site_name' => 'Site Name',
    'token_add' => 'Add Token',
    'token_picker_placeholder' => 'Type / to add a token',
    'token_suggestion_placeholder' => 'Type to search …',

    // Alerts
    'alert_indexing_disabled' => 'This :type will not appear in search results.',

    // AI
    'ai_generate' => 'Generate with AI',
    'ai_insufficient_content' => 'Add at least :characters more characters of content to generate with AI.',
    'ai_generation_failed' => 'AI generation failed. Please try again.',

    // Permissions
    'permission_configure_seo' => 'Configure SEO (Full Access)',
    'permission_configure_seo_description' => 'Grants all permissions including the ability to edit settings, defaults, and content',
    'permission_edit_defaults' => 'Edit Defaults',
    'permission_edit_defaults_description' => 'Grants ability to edit collection and taxonomy defaults, and access the SEO tab on entries and terms',
    'permission_edit_content' => 'Edit Content',
    'permission_edit_content_description' => 'Grants access to the SEO tab on entries and terms',
    'permission_view_redirects' => 'View Redirects',
    'permission_view_redirects_description' => 'Grants access to view redirects. Child permissions grant editing, creating, and deleting.',
    'permission_edit_redirects' => 'Edit Redirects',
    'permission_create_redirects' => 'Create Redirects',
    'permission_delete_redirects' => 'Delete Redirects',
    'permission_edit_redirects_description' => 'Grants the ability to edit existing redirects.',
    'permission_create_redirects_description' => 'Grants the ability to create new redirects.',
    'permission_delete_redirects_description' => 'Grants the ability to delete redirects.',

    // Pro
    'pro_features' => 'Upgrade to Pro',
    'pro_features_instructions' => '<a href="https://statamic.com/addons/aerni/advanced-seo" target="_blank" class="underline">Get Pro</a> to unlock these features and more.',
    'pro_feature_sitemaps' => 'Sitemaps',
    'pro_feature_multi_site' => 'Multi-Site',
    'pro_feature_ai' => 'AI Copywriting',
    'pro_feature_social_images' => 'Social Images',
    'pro_feature_permissions' => 'Permissions',
    'pro_feature_graphql' => 'GraphQL',
    'pro_feature_eloquent' => 'Eloquent Driver',
    'pro_feature_custom_routes' => 'Custom Routes',
    'pro_feature_custom_tokens' => 'Custom Tokens',
];
