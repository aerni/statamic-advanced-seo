import { computed, getCurrentInstance, onUnmounted, ref, watch } from 'vue';
import { injectPublishContext } from '@statamic/cms/ui';

/**
 * Field handles for image fields that may contain asset IDs.
 */
const IMAGE_HANDLES = [
    'seo_og_image',
];

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
    const publishContainer = injectPublishContext();

    /**
     * Cache of loaded asset data keyed by asset ID.
     * Each value is the full asset object from the API (with url, path, etc.).
     */
    const assetCache = ref({});

    /**
     * Get the raw value object from publishValues for a given field handle.
     */
    function getRawValue(handle) {
        if (!publishContainer?.values?.value) return undefined;
        return publishContainer.values.value[handle];
    }

    /**
     * Get the cascade default asset ID for a field from its meta.
     */
    function getFallbackId(handle) {
        const fallback = publishContainer?.meta?.value?.[handle]?.default;

        return (Array.isArray(fallback) && fallback.length > 0 && typeof fallback[0] === 'string')
            ? fallback[0]
            : null;
    }

    /**
     * Extract an asset ID from a publish value.
     *
     * Statamic's assets fieldtype preProcess returns arrays of asset IDs
     * in "container::path" format. For seo_source wrapped fields, the
     * value is { source, value: ["container::path"] }.
     *
     * @param {*} raw - The raw publish value
     * @returns {string|null}
     */
    function extractAssetId(raw) {
        if (!raw) return null;

        // seo_source wrapped: { source, value: ["container::path"] }
        if (typeof raw === 'object' && !Array.isArray(raw)) {
            const ids = raw.value;

            if (Array.isArray(ids) && ids.length > 0 && typeof ids[0] === 'string') {
                return ids[0];
            }

            return null;
        }

        // Direct asset array: ["container::path"]
        if (Array.isArray(raw) && raw.length > 0 && typeof raw[0] === 'string') {
            return raw[0];
        }

        return null;
    }

    /**
     * All asset IDs that need to be loaded, from both publish values and fallbacks.
     */
    const allAssetIds = computed(() => {
        const ids = new Set();

        for (const handle of IMAGE_HANDLES) {
            const id = extractAssetId(getRawValue(handle));
            if (id) ids.add(id);
        }

        for (const handle of IMAGE_HANDLES) {
            const fallback = getFallbackId(handle);
            if (fallback) ids.add(fallback);
        }

        return [...ids];
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
    watch(allAssetIds, (ids) => loadAssets(ids), { immediate: true });

    // Re-fetch cached assets when any asset is saved (e.g. focal point edits).
    // The asset editor PATCHes to /cp/assets/... which we detect here.
    const interceptorId = $axios.interceptors.response.use((response) => {
        if (response.config.method === 'patch' && response.config.url?.includes('/assets/')) {
            assetCache.value = {};
            loadAssets(allAssetIds.value);
        }

        return response;
    });

    onUnmounted(() => $axios.interceptors.response.eject(interceptorId));

    /**
     * Get a cached asset by its ID.
     */
    function getAsset(assetId) {
        if (!assetId) return null;

        return assetCache.value[assetId] || null;
    }

    /**
     * Convert a Statamic focus value (e.g. "70-30") to a CSS object-position value.
     */
    function focusToObjectPosition(asset) {
        const focus = asset?.values?.focus;

        if (!focus || typeof focus !== 'string') return undefined;

        const [x, y] = focus.split('-');

        return (x && y) ? `${x}% ${y}%` : undefined;
    }

    /**
     * Resolve an image from a seo_source wrapped asset field.
     *
     * @param {string} handle - The field handle
     * @param {string|null} fallbackId - Asset ID from the cascade fallback
     * @returns {{ url: string, objectPosition?: string } | null}
     */
    function resolveImage(handle, fallbackId = null) {
        const id = extractAssetId(getRawValue(handle));
        const asset = getAsset(id) || getAsset(fallbackId);

        if (!asset?.url) return null;

        return {
            url: asset.url,
            objectPosition: focusToObjectPosition(asset),
        };
    }

    /**
     * Check if social image generation is enabled.
     * Reads from reactive publishValues so this will update when toggle changes.
     */
    function isGenerationEnabled() {
        const raw = getRawValue('seo_generate_social_images');

        // seo_source wrapped: { source, value }
        if (typeof raw === 'object' && raw !== null && 'value' in raw) {
            return raw.value === true || raw.value === 'true';
        }

        return raw === true || raw === 'true';
    }

    /**
     * Get the generated social image URL from the seo_generated_og_image field meta.
     * Only returns URL if generation is enabled.
     */
    function getGeneratedImageUrl() {
        if (!isGenerationEnabled()) {
            return null;
        }

        return publishContainer?.meta?.value?.seo_generated_og_image?.image || null;
    }

    /**
     * Get the resolved OG image reactively.
     * Prioritizes generated image (when enabled) over manually uploaded image.
     */
    function resolveOgImage() {
        const generatedUrl = getGeneratedImageUrl();

        if (generatedUrl) {
            return { url: generatedUrl };
        }

        return resolveImage('seo_og_image', getFallbackId('seo_og_image'));
    }

    /**
     * Get the resolved Twitter image reactively.
     * Uses OG image since twitter-specific image fields have been removed.
     */
    function resolveTwitterImage() {
        return resolveOgImage();
    }

    return {
        resolveOgImage,
        resolveTwitterImage,
    };
}
