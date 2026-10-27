import type { ComponentType } from 'react';
import { Fragment } from 'react';
import { AbsoluteFill, Html5Audio, Sequence, Series, staticFile } from 'remotion';
import { linearTiming, TransitionSeries } from '@remotion/transitions';
import { fade } from '@remotion/transitions/fade';
import type { Chapter } from './components/ChapterCard';
import { MusicBed } from './components/MusicBed';
import { PortraitOverlay } from './components/PortraitOverlay';
import { FPS } from './lib/motion';
import type { Segment } from './Series';
import { APPROVE_CLICK, ApproveRequest, FULLY_APPROVED } from './scenes/ApproveRequest';
import { CreateAdvance, ITEMS_START } from './scenes/CreateAdvance';
import { DISBURSED, DisburseAdvance, REFERENCE_CLICK } from './scenes/DisburseAdvance';
import { PromoCta, PromoHook, PromoReveal } from './scenes/promo/PromoCards';
import { CfoReports, OUTSTANDING_PAGE } from './scenes/reports/CfoReports';
import { AUDIT_PAGE, ItReports } from './scenes/reports/ItReports';
import { DIFFERENCE_AT, RetireAdvance } from './scenes/RetireAdvance';
import voiceover from './voiceover.json';

const TRANSITION_FRAMES = 8;
const VOICE_LEAD_FRAMES = 6;
const VOICE_TAIL_FRAMES = 8;
const MUSIC_VOLUME = 0.32;
const MUSIC_DUCKED_VOLUME = 0.12;

const voiceClips: Record<string, { seconds: number }> = voiceover;

/**
 * One beat of the promo: a card or a clip from a walkthrough scene, the
 * headline shown above an app clip, and whether its narration is also
 * captioned (cards already show their words).
 */
type Beat = { id: string; component: ComponentType; durationInFrames: number; headline?: Chapter; captions: boolean };

/**
 * A walkthrough scene played from `from`, so the promo can cut straight
 * to the moment that matters.
 */
const clip =
    (Scene: ComponentType, from: number): ComponentType =>
    () => (
        <Sequence from={-from} layout="none">
            <Scene />
        </Sequence>
    );

const REPORT_CLIP_FRAMES = 72;

const ReportsClip = () => {
    const Outstanding = clip(CfoReports, OUTSTANDING_PAGE - 6);
    const AuditTrail = clip(ItReports, AUDIT_PAGE - 6);

    return (
        <Series>
            <Series.Sequence durationInFrames={REPORT_CLIP_FRAMES}>
                <Outstanding />
            </Series.Sequence>
            <Series.Sequence durationInFrames={REPORT_CLIP_FRAMES}>
                <AuditTrail />
            </Series.Sequence>
        </Series>
    );
};

const headline = (part: string, title: string): Chapter => ({ part, title, body: '' });

const beats: Beat[] = [
    { id: 'Promo1-Hook', component: PromoHook, durationInFrames: 96, captions: false },
    { id: 'Promo2-Reveal', component: PromoReveal, durationInFrames: 84, captions: false },
    {
        id: 'Promo3-Request',
        component: clip(CreateAdvance, ITEMS_START - 12),
        durationInFrames: 100,
        headline: headline('Request', 'Funds requested in seconds'),
        captions: true,
    },
    {
        id: 'Promo4-Approve',
        component: clip(ApproveRequest, APPROVE_CLICK - 24),
        durationInFrames: FULLY_APPROVED - APPROVE_CLICK + 54,
        headline: headline('Approve · your own stages', 'Your workflow, your rules'),
        captions: true,
    },
    {
        id: 'Promo5-Disburse',
        component: clip(DisburseAdvance, REFERENCE_CLICK - 8),
        durationInFrames: DISBURSED - REFERENCE_CLICK + 44,
        headline: headline('Pay out', 'Every payout in the cashbook'),
        captions: true,
    },
    {
        id: 'Promo6-Retire',
        component: clip(RetireAdvance, DIFFERENCE_AT - 50),
        durationInFrames: 96,
        headline: headline('Retire', 'Every cedi accounted for'),
        captions: true,
    },
    {
        id: 'Promo7-Reports',
        component: ReportsClip,
        durationInFrames: REPORT_CLIP_FRAMES * 2,
        headline: headline('Report', 'Live reports, full audit trail'),
        captions: true,
    },
    { id: 'Promo8-Cta', component: PromoCta, durationInFrames: 140, captions: false },
];

/**
 * Lays the beats back to back, overlapping each by the crossfade. A beat
 * is held longer when its voice clip would otherwise be cut off.
 */
const layout = (): (Segment & { captions: boolean })[] => {
    let start = 0;

    return beats.map((beat) => {
        const voiceFrames = voiceClips[beat.id] ? Math.ceil(voiceClips[beat.id].seconds * FPS) : 0;
        const durationInFrames = Math.max(
            beat.durationInFrames,
            voiceFrames ? VOICE_LEAD_FRAMES + voiceFrames + VOICE_TAIL_FRAMES + TRANSITION_FRAMES : 0,
        );
        const Beat = beat.component;
        const segment = {
            key: beat.id,
            start,
            durationInFrames,
            render: () => <Beat />,
            voice: beat.id,
            chapter: beat.headline,
            captions: beat.captions,
        };
        start += durationInFrames - TRANSITION_FRAMES;

        return segment;
    });
};

export const PROMO_DURATION = (() => {
    const last = layout().at(-1);

    return last ? last.start + last.durationInFrames : 0;
})();

/**
 * Thirty-second portrait promo for TikTok and Reels: a hook, the product,
 * five quick cuts from the walkthrough, and a call to action, over a
 * music bed. Narration plays per beat once it has been generated; until
 * then the captions are timed by estimate so the draft can be reviewed.
 */
export const Promo = () => {
    const segments = layout();

    return (
        <AbsoluteFill>
            <TransitionSeries>
                {segments.map((segment, index) => (
                    <Fragment key={segment.key}>
                        {index > 0 && (
                            <TransitionSeries.Transition
                                presentation={fade()}
                                timing={linearTiming({ durationInFrames: TRANSITION_FRAMES })}
                            />
                        )}
                        <TransitionSeries.Sequence durationInFrames={segment.durationInFrames}>
                            {segment.render()}
                        </TransitionSeries.Sequence>
                    </Fragment>
                ))}
            </TransitionSeries>
            {segments.map(
                (segment) =>
                    voiceClips[segment.key] && (
                        <Sequence key={`${segment.key}-voice`} from={segment.start + VOICE_LEAD_FRAMES} layout="none">
                            <Html5Audio src={staticFile(`voiceover/${segment.key}.mp3`)} />
                        </Sequence>
                    ),
            )}
            <MusicBed
                volume={MUSIC_VOLUME}
                duckedVolume={MUSIC_DUCKED_VOLUME}
                voiceWindows={segments.flatMap((segment) =>
                    voiceClips[segment.key]
                        ? [
                              {
                                  from: segment.start + VOICE_LEAD_FRAMES,
                                  to: segment.start + VOICE_LEAD_FRAMES + voiceClips[segment.key].seconds * FPS,
                              },
                          ]
                        : [],
                )}
            />
            <PortraitOverlay
                segments={segments.map((segment) => (segment.captions ? segment : { ...segment, voice: undefined }))}
                voiceLeadFrames={VOICE_LEAD_FRAMES}
            />
        </AbsoluteFill>
    );
};
