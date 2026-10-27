import { Img, useCurrentFrame } from 'remotion';
import { useIsPortrait } from '../lib/format';
import { enter, tween } from '../lib/motion';
import { Backdrop } from './Stage';
import { logos } from '../logos';

export type Chapter = { part: string; title: string; body: string };

const TEAL = 'oklch(0.72 0.13 168)';

/**
 * Progress rail across the bottom of a chapter card: one segment per
 * chapter, grouped under its part, with the current chapter filling in.
 */
const ProgressRail = ({ chapters, index, labels }: { chapters: Chapter[]; index: number; labels: boolean }) => {
    const frame = useCurrentFrame();
    const fill = tween(frame, 10, 24, 0, 1);
    const parts = chapters.reduce<{ part: string; indexes: number[] }[]>((groups, chapter, chapterIndex) => {
        const last = groups[groups.length - 1];

        if (last && last.part === chapter.part) {
            last.indexes.push(chapterIndex);

            return groups;
        }

        return [...groups, { part: chapter.part, indexes: [chapterIndex] }];
    }, []);

    return (
        <div className="flex w-full gap-6">
            {parts.map((group) => (
                <div key={group.part} className="flex flex-col gap-3" style={{ flexGrow: group.indexes.length, flexBasis: 0 }}>
                    {labels && (
                        <span
                            className="font-mono text-base uppercase tracking-widest"
                            style={{ color: group.indexes.includes(index) ? TEAL : 'var(--auth-panel-caption)' }}
                        >
                            {group.part}
                        </span>
                    )}
                    <div className="flex gap-2">
                        {group.indexes.map((chapterIndex) => (
                            <div key={chapterIndex} className="h-2 grow overflow-hidden rounded-full bg-white/10">
                                <div
                                    className="h-full rounded-full"
                                    style={{
                                        width: `${chapterIndex < index ? 100 : chapterIndex === index ? fill * 100 : 0}%`,
                                        background: chapterIndex === index ? TEAL : 'oklch(0.72 0.13 168 / 0.45)',
                                    }}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
};

/**
 * Portrait chapter card: the same content stacked, ending above the
 * caption band. The part names are left off the rail for want of width.
 */
const PortraitChapterCard = ({ chapters, index }: { chapters: Chapter[]; index: number }) => {
    const frame = useCurrentFrame();
    const chapter = chapters[index];
    const number = enter(frame, 2, 18);
    const title = enter(frame, 8, 20);
    const body = enter(frame, 14, 20);

    return (
        <Backdrop>
            <div className="flex size-full flex-col gap-14 px-20 pb-[600px] pt-40">
                <Img src={logos.dark} style={{ height: 44, alignSelf: 'flex-start' }} />
                <div className="flex grow flex-col justify-end gap-8">
                    <span
                        className="font-mono text-[200px] font-semibold leading-[0.8]"
                        style={{ color: TEAL, opacity: number, transform: `translateX(${(1 - number) * -60}px)` }}
                    >
                        {String(index + 1).padStart(2, '0')}
                    </span>
                    <span
                        className="font-mono text-3xl uppercase tracking-widest"
                        style={{ color: 'var(--auth-panel-caption)', opacity: number }}
                    >
                        {chapter.part}
                    </span>
                    <h2
                        className="text-[96px] font-semibold leading-none tracking-tight text-[var(--auth-panel-foreground)]"
                        style={{ opacity: title, transform: `translateY(${(1 - title) * 30}px)` }}
                    >
                        {chapter.title}
                    </h2>
                    <p
                        className="text-[40px] leading-snug text-[var(--auth-panel-muted)]"
                        style={{ opacity: body, transform: `translateY(${(1 - body) * 20}px)` }}
                    >
                        {chapter.body}
                    </p>
                </div>
                <ProgressRail chapters={chapters} index={index} labels={false} />
            </div>
        </Backdrop>
    );
};

/**
 * Full-frame card introducing the next chapter of a walkthrough.
 */
export const ChapterCard = ({ chapters, index }: { chapters: Chapter[]; index: number }) => {
    const frame = useCurrentFrame();
    const portrait = useIsPortrait();
    const chapter = chapters[index];
    const number = enter(frame, 2, 18);
    const title = enter(frame, 8, 20);
    const body = enter(frame, 14, 20);

    if (portrait) {
        return <PortraitChapterCard chapters={chapters} index={index} />;
    }

    return (
        <Backdrop>
            <div className="flex size-full flex-col justify-between px-40 pb-24 pt-20">
                <Img src={logos.dark} style={{ height: 40, alignSelf: 'flex-start' }} />
                <div className="flex items-end gap-16">
                    <span
                        className="font-mono text-[220px] font-semibold leading-[0.8]"
                        style={{ color: TEAL, opacity: number, transform: `translateX(${(1 - number) * -60}px)` }}
                    >
                        {String(index + 1).padStart(2, '0')}
                    </span>
                    <div className="flex max-w-[1150px] flex-col gap-6 pb-2">
                        <span
                            className="font-mono text-2xl uppercase tracking-widest"
                            style={{ color: 'var(--auth-panel-caption)', opacity: number }}
                        >
                            {chapter.part}
                        </span>
                        <h2
                            className="text-[96px] font-semibold leading-none tracking-tight text-[var(--auth-panel-foreground)]"
                            style={{ opacity: title, transform: `translateY(${(1 - title) * 30}px)` }}
                        >
                            {chapter.title}
                        </h2>
                        <p
                            className="text-[34px] leading-snug text-[var(--auth-panel-muted)]"
                            style={{ opacity: body, transform: `translateY(${(1 - body) * 20}px)` }}
                        >
                            {chapter.body}
                        </p>
                    </div>
                </div>
                <ProgressRail chapters={chapters} index={index} labels />
            </div>
        </Backdrop>
    );
};
