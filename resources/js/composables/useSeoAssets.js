import { computed, getCurrentInstance, onUnmounted, ref, watch } from 'vue';
import { usePublishFields } from './usePublishFields.js';

const OG_IMAGE_HANDLE = 'seo_og_image';
const GENERATE_SOCIAL_IMAGES_HANDLE = 'seo_generate_social_images';
const SOCIAL_IMAGES_THEME_HANDLE = 'seo_social_images_theme';

/**
 * Composable for resolving SEO image assets from the publish form context.
 *
 * Image URLs are resolved by loading assets from Statamic's assets-fieldtype
 * endpoint, using the same approach as Statamic's AssetsFieldtype component.
 * Asset IDs from publishValues and cascade fallbacks are collected and loaded
 * via a single API call, then cached for subsequent lookups.
 */
export function useSeoAssets() {
    const { $axios } = getCurrentInstance().appContext.config.globalProperties;
    const {
        publishContainer,
        getFieldRawValue,
        getFieldValue,
        getFieldMeta,
    } = usePublishFields();

    /**
     * Cache of loaded asset data keyed by asset ID.
     * Each value is the full asset object from the API (with url, path, etc.).
     */
    const assetCache = ref({});

    /**
     * Incremented on each PATCH response to cache-bust the social image template iframe.
     */
    const cacheBustKey = ref(0);

    /**
     * Get the cascade default asset ID for a field from its meta.
     */
    function getFallbackAssetId(handle) {
        const fallback = getFieldMeta(handle)?.defaultValue;

        return (Array.isArray(fallback) && fallback.length > 0 && typeof fallback[0] === 'string')
            ? fallback[0]
            : null;
    }

    /**
     * Extract an asset ID from an unwrapped field value.
     *
     * Statamic's assets fieldtype preProcess returns arrays of asset IDs
     * in "container::path" format.
     *
     * @returns {string|null}
     */
    function extractAssetIdFromValue(value) {
        if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
            return value[0];
        }

        return null;
    }

    /**
     * OG image asset IDs that need to be loaded from both publish values and fallbacks.
     */
    const ogAssetIds = computed(() => {
        const ogImageValue = getFieldValue(OG_IMAGE_HANDLE);
        const ids = [
            extractAssetIdFromValue(ogImageValue),
            getFallbackAssetId(OG_IMAGE_HANDLE),
        ].filter(Boolean);

        return [...new Set(ids)];
    });

    /**
     * Load assets from the server that aren't already cached.
     * Uses the same endpoint as Statamic's AssetsFieldtype: POST /cp/assets-fieldtype
     */
    async function loadAssets(ids) {
        const missing = ids.filter(id => !assetCache.value[id]);

        if (missing.length === 0) return;

        try {
            const response = await $axios.post(
                cp_url('assets-fieldtype'),
                { assets: missing },
            );

            const newCache = { ...assetCache.value };

            response.data.forEach(asset => {
                newCache[asset.id] = asset;
            });

            assetCache.value = newCache;
        } catch (e) {
            console.error('Failed to load SEO preview assets:', e);
        }
    }

    // Watch for changes in asset IDs and load any missing ones.
    watch(ogAssetIds, (ids) => loadAssets(ids), { immediate: true });

    /**
     * Check if social image generation is enabled for this content.
     * For seo-wrapped fields, raw.value already contains the resolved default
     * (from PHP's childDefaultValue), so no meta lookup is needed.
     */
    function isGeneratorEnabled() {
        return getFieldValue(GENERATE_SOCIAL_IMAGES_HANDLE) === true;
    }

    function isPatchResponse(response) {
        return response.config.method === 'patch';
    }

    function shouldRefreshAssetCache(response) {
        return response.config.url?.includes('/assets/');
    }

    function refreshAssetCache() {
        assetCache.value = {};
        loadAssets(ogAssetIds.value);
    }

    function bumpPreviewCacheBustKey() {
        cacheBustKey.value++;
    }

    // Intercept responses to detect saves and asset edits.
    const interceptorId = $axios.interceptors.response.use((response) => {
        if (!isPatchResponse(response)) {
            return response;
        }

        // Re-fetch cached assets when any asset is saved (e.g. focal point edits).
        if (shouldRefreshAssetCache(response)) {
            refreshAssetCache();
        }

        // Cache-bust the social image template iframe after patches.
        bumpPreviewCacheBustKey();

        return response;
    });

    onUnmounted(() => {
        $axios.interceptors.response.eject(interceptorId);
    });

    /**
     * Get a cached asset by its ID.
     */
    function getAsset(assetId) {
        return assetCache.value[assetId] || null;
    }

    /**
     * Parse a Statamic focus value (e.g. "70-30-2") into focal point coordinates and zoom.
     *
     * @returns {{ x: number, y: number, z: number } | undefined}
     */
    function parseFocalPoint(asset) {
        const focus = asset?.values?.focus;

        if (!focus || typeof focus !== 'string') return undefined;

        const [x, y, z] = focus.split('-');

        if (!x || !y) return undefined;

        return {
            x: parseFloat(x),
            y: parseFloat(y),
            z: parseFloat(z) || 1,
        };
    }

    function resolveOgImage() {
        if (isGeneratorEnabled()) return null;

        const raw = getFieldRawValue(OG_IMAGE_HANDLE);
        const assetId = extractAssetIdFromValue(getFieldValue(OG_IMAGE_HANDLE));
        const fallbackId = getFallbackAssetId(OG_IMAGE_HANDLE);

        // Don't fall back to cascade default when the user explicitly cleared the value.
        const isCustom = raw && typeof raw === 'object' && !Array.isArray(raw) && raw.source === 'custom';
        const asset = getAsset(assetId) || (isCustom ? null : getAsset(fallbackId));

        if (!asset?.url) return null;

        return {
            url: asset.url,
            width: asset.width ?? null,
            height: asset.height ?? null,
            ...parseFocalPoint(asset),
        };
    }

    /**
     * Resolve the social image template URL with cache-busting.
     * Returns null when generation is disabled or there's no URL (new entries without an ID).
     * Reactively swaps the theme segment when the user changes the theme dropdown.
     */
    function resolveImageTemplateUrl() {
        if (!isGeneratorEnabled()) return null;

        const urlTemplate = publishContainer.meta.value?.seo_social_preview?.imageTemplateUrl;
        const theme = getFieldValue(SOCIAL_IMAGES_THEME_HANDLE);

        if (!urlTemplate || !theme) return null;

        return `${urlTemplate.replace('{theme}', theme)}?v=${cacheBustKey.value}`;
    }

    return {
        resolveOgImage,
        resolveImageTemplateUrl,
        isGeneratorEnabled,
    };
}
