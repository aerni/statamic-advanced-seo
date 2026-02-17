import { inject, provide } from 'vue';

const SocialPreviewKey = Symbol('SocialPreview');

export function provideSocialPreview(context) {
    provide(SocialPreviewKey, context);
}

export function useSocialPreview() {
    return inject(SocialPreviewKey);
}
