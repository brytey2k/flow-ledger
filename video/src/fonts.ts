import { loadFont } from '@remotion/fonts';
import geistSans from '@fontsource-variable/geist/files/geist-latin-wght-normal.woff2';
import geistMono from '@fontsource-variable/geist-mono/files/geist-mono-latin-wght-normal.woff2';

/**
 * Geist is bundled locally (the app loads it from Google Fonts) so renders
 * work offline and never wait on a CDN.
 */
export const loadFonts = (): void => {
    loadFont({ family: 'Geist', url: geistSans, weight: '100 900' });
    loadFont({ family: 'Geist Mono', url: geistMono, weight: '100 900' });
};
