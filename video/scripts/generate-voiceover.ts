/**
 * Generates the voice-over with ElevenLabs: one MP3 per narration line in
 * public/voiceover/, plus src/voiceover.json recording each clip's text and
 * length so the video can fit every chapter to its narration.
 *
 * Usage: npm run voiceover -- <voice-id> [--force]
 *
 * Reads the API key from ELEVENLABS_API_KEY. When the key is configured as
 * a network secret instead, the proxy adds it to the request. Lines whose
 * text has not changed since the last run are skipped, so re-running only
 * spends credits on edited lines; --force regenerates everything.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { narration } from '../src/narration.ts';

const MODEL_ID = 'eleven_multilingual_v2';
const AUDIO_DIR = 'public/voiceover';
const MANIFEST = 'src/voiceover.json';

type Manifest = Record<string, { text: string; seconds: number }>;

const voiceId = process.argv[2] ?? process.env.ELEVENLABS_VOICE_ID;
const force = process.argv.includes('--force');

if (!voiceId || voiceId.startsWith('--')) {
    console.error('Usage: npm run voiceover -- <voice-id> [--force]');
    process.exit(1);
}

const manifest: Manifest = JSON.parse(readFileSync(MANIFEST, 'utf8'));
const entries = Object.entries(narration);
mkdirSync(AUDIO_DIR, { recursive: true });

const durationInSeconds = (file: string): number =>
    Number(
        execFileSync('npx', ['remotion', 'ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', file])
            .toString()
            .trim(),
    );

for (const [index, [id, text]] of entries.entries()) {
    const file = `${AUDIO_DIR}/${id}.mp3`;

    if (!force && manifest[id]?.text === text && existsSync(file)) {
        console.log(`skip  ${id} (unchanged)`);
        continue;
    }

    const response = await fetch(`https://api.elevenlabs.io/v1/text-to-speech/${voiceId}?output_format=mp3_44100_128`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(process.env.ELEVENLABS_API_KEY ? { 'xi-api-key': process.env.ELEVENLABS_API_KEY } : {}),
        },
        body: JSON.stringify({
            text,
            model_id: MODEL_ID,
            previous_text: entries[index - 1]?.[1],
            next_text: entries[index + 1]?.[1],
            voice_settings: { stability: 0.5, similarity_boost: 0.75, style: 0, use_speaker_boost: true },
        }),
    });

    if (!response.ok) {
        console.error(`ElevenLabs returned ${response.status} for ${id}: ${await response.text()}`);
        process.exit(1);
    }

    writeFileSync(file, Buffer.from(await response.arrayBuffer()));
    manifest[id] = { text, seconds: durationInSeconds(file) };
    writeFileSync(MANIFEST, `${JSON.stringify(manifest, null, 4)}\n`);
    console.log(`voice ${id} (${manifest[id].seconds.toFixed(1)}s, ${text.length} characters)`);
}
