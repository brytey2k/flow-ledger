/**
 * ElevenLabs voice-over for the walkthrough videos.
 *
 *   npm run voiceover -- design            Design three narrator voices from
 *                                          NARRATOR.description; previews go
 *                                          to voice-previews/.
 *   npm run voiceover -- create <number>   Save preview <number> as a
 *                                          permanent voice in voice.json.
 *   npm run voiceover -- generate [--force] [--speed=<0.7–1.2>] [id ...]
 *                                          One MP3 per narration line in
 *                                          public/voiceover/, with lengths in
 *                                          src/voiceover.json. Pass line ids
 *                                          to voice only those lines, and a
 *                                          speed to voice them faster or
 *                                          slower than normal.
 *
 * Reads the API key from ELEVENLABS_API_KEY; when the key is a network
 * secret instead, the proxy adds it to each request. generate skips lines
 * whose text is unchanged, so re-runs only spend credits on edited lines.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { narration } from '../src/narration.ts';

const API = 'https://api.elevenlabs.io/v1';
const TTS_MODEL_ID = 'eleven_multilingual_v2';
const PREVIEW_DIR = 'voice-previews';
const VOICE_FILE = 'voice.json';
const AUDIO_DIR = 'public/voiceover';
const MANIFEST = 'src/voiceover.json';

const NARRATOR = {
    name: 'FlowLedger Narrator',
    description:
        'A warm, confident Ghanaian woman in her thirties with a natural Accra accent, speaking clear Ghanaian English at a calm, measured pace, like a professional corporate narrator. Studio-quality recording.',
};

type Manifest = Record<string, { text: string; seconds: number }>;
type Previews = { generatedVoiceIds: string[]; text: string };

const request = async (path: string, body: unknown): Promise<Response> => {
    const response = await fetch(`${API}${path}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(process.env.ELEVENLABS_API_KEY ? { 'xi-api-key': process.env.ELEVENLABS_API_KEY } : {}),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        console.error(`ElevenLabs returned ${response.status} for ${path}: ${await response.text()}`);
        process.exit(1);
    }

    return response;
};

const readJson = <T>(file: string): T => JSON.parse(readFileSync(file, 'utf8')) as T;
const writeJson = (file: string, data: unknown): void => writeFileSync(file, `${JSON.stringify(data, null, 4)}\n`);

const durationInSeconds = (file: string): number =>
    Number(
        execFileSync('npx', ['remotion', 'ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', file])
            .toString()
            .trim(),
    );

const design = async (): Promise<void> => {
    const sample = [narration.WalkthroughIntro, narration['Step1-CreateAdvance']].join(' ');
    const response = await request('/text-to-voice/design?output_format=mp3_44100_128', {
        voice_description: NARRATOR.description,
        text: sample,
    });
    const { previews, text } = (await response.json()) as {
        previews: { generated_voice_id: string; audio_base_64: string }[];
        text: string;
    };

    mkdirSync(PREVIEW_DIR, { recursive: true });
    previews.forEach((preview, index) => {
        writeFileSync(`${PREVIEW_DIR}/preview-${index + 1}.mp3`, Buffer.from(preview.audio_base_64, 'base64'));
        console.log(`preview ${index + 1}: ${PREVIEW_DIR}/preview-${index + 1}.mp3`);
    });
    writeJson(`${PREVIEW_DIR}/previews.json`, {
        generatedVoiceIds: previews.map((preview) => preview.generated_voice_id),
        text,
    } satisfies Previews);
};

const create = async (choice: string | undefined): Promise<void> => {
    const { generatedVoiceIds } = readJson<Previews>(`${PREVIEW_DIR}/previews.json`);
    const generatedVoiceId = generatedVoiceIds[Number(choice) - 1];

    if (!generatedVoiceId) {
        console.error(`Pick a preview between 1 and ${generatedVoiceIds.length}: npm run voiceover -- create <number>`);
        process.exit(1);
    }

    const response = await request('/text-to-voice', {
        voice_name: NARRATOR.name,
        voice_description: NARRATOR.description,
        generated_voice_id: generatedVoiceId,
        played_not_selected_voice_ids: generatedVoiceIds.filter((id) => id !== generatedVoiceId),
    });
    const { voice_id: voiceId } = (await response.json()) as { voice_id: string };

    writeJson(VOICE_FILE, { voiceId, name: NARRATOR.name, description: NARRATOR.description, fromPreview: Number(choice) });
    console.log(`saved voice ${voiceId} to ${VOICE_FILE}`);
};

const generate = async (force: boolean, speed: number, only: string[]): Promise<void> => {
    if (!existsSync(VOICE_FILE)) {
        console.error(`No ${VOICE_FILE} yet: run design, then create <number>.`);
        process.exit(1);
    }

    const { voiceId } = readJson<{ voiceId: string }>(VOICE_FILE);
    const manifest = readJson<Manifest>(MANIFEST);
    const entries = Object.entries(narration);
    mkdirSync(AUDIO_DIR, { recursive: true });

    for (const [index, [id, text]] of entries.entries()) {
        if (only.length > 0 && !only.includes(id)) {
            continue;
        }

        const file = `${AUDIO_DIR}/${id}.mp3`;

        if (!force && manifest[id]?.text === text && existsSync(file)) {
            console.log(`skip  ${id} (unchanged)`);
            continue;
        }

        const response = await request(`/text-to-speech/${voiceId}?output_format=mp3_44100_128`, {
            text,
            model_id: TTS_MODEL_ID,
            previous_text: entries[index - 1]?.[1],
            next_text: entries[index + 1]?.[1],
            voice_settings: { stability: 0.5, similarity_boost: 0.75, style: 0, use_speaker_boost: true, speed },
        });

        writeFileSync(file, Buffer.from(await response.arrayBuffer()));
        manifest[id] = { text, seconds: durationInSeconds(file) };
        writeJson(MANIFEST, manifest);
        console.log(`voice ${id} (${manifest[id].seconds.toFixed(1)}s, ${text.length} characters)`);
    }
};

const [command, argument] = process.argv.slice(2);

if (command === 'design') {
    await design();
} else if (command === 'create') {
    await create(argument);
} else if (command === 'generate') {
    const speedArgument = process.argv.find((arg) => arg.startsWith('--speed='));
    await generate(
        process.argv.includes('--force'),
        speedArgument ? Number(speedArgument.slice('--speed='.length)) : 1,
        process.argv.slice(3).filter((arg) => !arg.startsWith('--')),
    );
} else {
    console.error('Usage: npm run voiceover -- design | create <number> | generate [--force] [--speed=<0.7–1.2>] [id ...]');
    process.exit(1);
}
