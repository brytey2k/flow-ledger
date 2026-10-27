import { Html5Audio, interpolate, staticFile, useVideoConfig } from 'remotion';
import { promo } from '../data';

const FADE_IN_FRAMES = 10;
const FADE_OUT_FRAMES = 45;
const DUCK_FRAMES = 8;

/**
 * A frame window in which narration plays.
 */
export type VoiceWindow = { from: number; to: number };

/**
 * 1 while narration plays and 0 between lines, easing over DUCK_FRAMES at
 * each edge.
 */
const voiceLevel = (frame: number, windows: VoiceWindow[]): number =>
    windows.reduce(
        (level, window) =>
            Math.max(
                level,
                interpolate(frame, [window.from - DUCK_FRAMES, window.from, window.to, window.to + DUCK_FRAMES], [0, 1, 1, 0], {
                    extrapolateLeft: 'clamp',
                    extrapolateRight: 'clamp',
                }),
            ),
        0,
    );

/**
 * Background music for a whole composition: fades in and out, and dips
 * to `duckedVolume` under each narration window so the voice stays clear.
 */
export const MusicBed = ({ volume, duckedVolume, voiceWindows }: { volume: number; duckedVolume: number; voiceWindows: VoiceWindow[] }) => {
    const { durationInFrames } = useVideoConfig();

    return (
        <Html5Audio
            src={staticFile(promo.music.file)}
            volume={(frame) =>
                interpolate(voiceLevel(frame, voiceWindows), [0, 1], [volume, duckedVolume]) *
                interpolate(frame, [0, FADE_IN_FRAMES, durationInFrames - FADE_OUT_FRAMES, durationInFrames], [0, 1, 1, 0], {
                    extrapolateLeft: 'clamp',
                    extrapolateRight: 'clamp',
                })
            }
        />
    );
};
