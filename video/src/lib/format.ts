import { useVideoConfig } from 'remotion';

/**
 * Every composition renders in landscape (1920 × 1080) or portrait
 * (1080 × 1920, for TikTok and Reels) from the same scenes; components
 * read the format from the composition they are rendered in.
 */
export const LANDSCAPE = { width: 1920, height: 1080 };
export const PORTRAIT = { width: 1080, height: 1920 };

/**
 * Portrait bands, top to bottom: the chapter header, the app window, then
 * the captions. The bottom of the frame stays clear of the TikTok UI.
 */
export const PORTRAIT_BANDS = { headerTop: 150, stageTop: 330, stageBottom: 1330, captionTop: 1370 };

export const useIsPortrait = (): boolean => {
    const { width, height } = useVideoConfig();

    return height > width;
};
