<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * The public user guide, from resources/docs/*.md. A chapter's screenshots sit in resources/docs/images/ and are
 * written `![What it shows](images/name.webp "Caption")` on a line of their own. Parsing every chapter on each request
 * would cost an unauthenticated visitor's every page view a dozen file parses, so the chapter list, each rendered
 * chapter and the video list are cached. The cache key is a fingerprint of the files, images included (name, size,
 * modified time): a deploy, or an edit in development, changes it, and nothing is read until it does.
 *
 * The guide's videos are on YouTube and listed in resources/docs/videos/videos.yaml, each with a poster in
 * resources/docs/videos/posters/{key}.webp and the chapters that show it. A video without a YouTube id is not published.
 *
 * @phpstan-type Chapter array{title: string, description: string, order: int|float|string, body: string}
 * @phpstan-type RenderedChapter array{html: string, sections: array<string, string>}
 * @phpstan-type Video array{key: string, kind: string, title: string, description: string, youtube: string, seconds: int, chapters: list<string>}
 */
final class UserGuide
{
    private const int CACHE_SECONDS = 86400;

    /** A screenshot's file name in the guide's images folder. */
    public const string IMAGE_PATTERN = '[a-z0-9-]+\\.(?:webp|png|jpg)';

    /** A video poster's file name in the videos/posters folder. */
    public const string POSTER_PATTERN = '[a-z0-9-]+\\.webp';

    /** The kinds of video, in the order the video center shows them. */
    public const array VIDEO_KINDS = ['walkthrough', 'part', 'promo'];

    private readonly string $directory;

    private string|null $fingerprint = null;

    public function __construct(string|null $directory = null)
    {
        $this->directory = $directory ?? resource_path('docs');
    }

    /** @return Collection<string, Chapter> Chapters by slug, in guide order */
    public function chapters(): Collection
    {
        /** @var array<string, Chapter> $chapters */
        $chapters = Cache::remember($this->cacheKey('chapters'), self::CACHE_SECONDS, fn(): array => $this->parseChapters());

        return collect($chapters);
    }

    /**
     * Chapters containing every word of the query, in any order, best match first: the whole phrase in the title,
     * then words in the title, then in the description, then how often they appear in the text. Ties keep guide order.
     *
     * @param string $query
     *
     * @return Collection<string, Chapter>
     */
    public function search(string $query): Collection
    {
        $phrase = Str::lower(Str::squish($query));
        $words = array_values(array_unique(array_filter(explode(' ', $phrase), fn(string $word): bool => $word !== '')));

        if ($words === []) {
            return $this->chapters();
        }

        $scored = [];
        $position = 0;

        foreach ($this->chapters() as $slug => $chapter) {
            $title = Str::lower($chapter['title']);
            $description = Str::lower($chapter['description']);
            $body = Str::lower($chapter['body']);
            $score = str_contains($title, $phrase) ? 1000 : 0;

            foreach ($words as $word) {
                $inTitle = str_contains($title, $word);
                $inDescription = str_contains($description, $word);
                $inBody = substr_count($body, $word);

                if (! $inTitle && ! $inDescription && $inBody === 0) {
                    continue 2;
                }

                $score += ($inTitle ? 100 : 0) + ($inDescription ? 20 : 0) + min($inBody, 20);
            }

            $scored[$slug] = ['score' => $score, 'position' => $position++, 'chapter' => $chapter];
        }

        uasort($scored, fn(array $a, array $b): int => [$b['score'], $a['position']] <=> [$a['score'], $b['position']]);

        return collect($scored)->map(fn(array $match): array => $match['chapter']);
    }

    /**
     * The chapter's HTML (raw HTML in the Markdown is stripped, unsafe links refused, screenshots placed) and its h2
     * sections by anchor.
     *
     * @param string $slug
     * @param Chapter $chapter
     *
     * @return RenderedChapter
     */
    public function render(string $slug, array $chapter): array
    {
        /* @var RenderedChapter */
        return Cache::remember($this->cacheKey('chapter:' . $slug), self::CACHE_SECONDS, function () use ($chapter): array {
            $html = $this->placeImages(Str::markdown($chapter['body'], [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'attributes' => ['allow' => ['id']],
            ], [new AttributesExtension()]));

            preg_match_all('/<h2 id="([a-z0-9-]+)">(.*?)<\/h2>/s', $html, $matches, PREG_SET_ORDER);
            $sections = [];
            foreach ($matches as [, $id, $label]) {
                $sections[$id] = html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            return ['html' => $html, 'sections' => $sections];
        });
    }

    /**
     * Every video with a YouTube id, in the order of videos.yaml.
     *
     * @return Collection<string, Video> Videos by key
     */
    public function videos(): Collection
    {
        /** @var array<string, Video> $videos */
        $videos = Cache::remember($this->cacheKey('videos'), self::CACHE_SECONDS, fn(): array => $this->parseVideos());

        return collect(array_filter($videos, fn(array $video): bool => $video['youtube'] !== ''));
    }

    /**
     * The published videos a chapter shows at its top.
     *
     * @param string $slug
     *
     * @return Collection<string, Video>
     */
    public function videosFor(string $slug): Collection
    {
        /** @var Collection<string, Video> $videos */
        $videos = $this->videos()->filter(fn(array $video): bool => in_array($slug, $video['chapters'], true));

        return $videos;
    }

    /** The file of a video's poster, or null when there is no such poster. */
    public function posterPath(string $file): string|null
    {
        if (preg_match('#^' . self::POSTER_PATTERN . '$#', $file) !== 1) {
            return null;
        }

        $path = $this->directory . '/videos/posters/' . $file;

        return is_file($path) ? $path : null;
    }

    /** The poster's address, with a version that changes with the file so a new poster replaces the cached one. */
    public function posterUrl(string $key): string|null
    {
        $path = $this->posterPath($key . '.webp');

        return $path === null
            ? null
            : route('landlord.docs.poster', ['file' => $key . '.webp'], absolute: false) . '?v=' . $this->version($path);
    }

    /** The file of a screenshot, or null when there is no such image. */
    public function imagePath(string $file): string|null
    {
        if (preg_match('#^' . self::IMAGE_PATTERN . '$#', $file) !== 1) {
            return null;
        }

        $path = $this->directory . '/images/' . $file;

        return is_file($path) ? $path : null;
    }

    /**
     * Points each `images/...` screenshot at the guide's image route, with its size (so the page does not jump while it
     * loads) and a version that changes with the file (so a retaken screenshot replaces the cached one). An image alone
     * in its paragraph becomes a figure, its title the caption.
     *
     * @param string $html
     */
    private function placeImages(string $html): string
    {
        $html = (string) preg_replace_callback(
            '#<img src="images/(' . self::IMAGE_PATTERN . ')"#',
            function (array $match): string {
                $path = $this->imagePath($match[1]);
                $src = route('landlord.docs.image', ['file' => $match[1]], absolute: false);
                if ($path === null) {
                    return '<img src="' . e($src) . '"';
                }

                $size = getimagesize($path);
                $dimensions = $size === false ? '' : ' width="' . $size[0] . '" height="' . $size[1] . '"';

                return '<img src="' . e($src . '?v=' . $this->version($path)) . '" loading="lazy"' . $dimensions;
            },
            $html,
        );

        return (string) preg_replace_callback(
            '#<p>(<img [^>]*?)(?: title="([^"]*)")?( ?/?>)</p>#',
            static fn(array $match): string => '<figure>' . $match[1] . $match[3]
                . ($match[2] !== '' ? '<figcaption>' . $match[2] . '</figcaption>' : '') . '</figure>',
            $html,
        );
    }

    private function version(string $path): string
    {
        return substr(hash_file('xxh128', $path) ?: '', 0, 12);
    }

    /** @return array<string, Chapter> */
    private function parseChapters(): array
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
        $chapters = [];

        foreach ($this->files() as $file) {
            $document = $parser->parse($file->getContents());
            $metadata = $document->getFrontMatter();
            if (! is_array($metadata) || ! is_string($metadata['title'] ?? null)
                || ! is_string($metadata['description'] ?? null) || ! is_numeric($metadata['order'] ?? null)) {
                throw new UnexpectedValueException('Invalid guide metadata in ' . $file->getFilename());
            }

            $chapters[$file->getFilenameWithoutExtension()] = [
                'title' => $metadata['title'],
                'description' => $metadata['description'],
                'order' => $metadata['order'],
                'body' => $document->getContent(),
            ];
        }

        return collect($chapters)->sortBy('order')->all();
    }

    /** @return array<string, Video> */
    private function parseVideos(): array
    {
        $path = $this->directory . '/videos/videos.yaml';
        if (! is_file($path)) {
            return [];
        }

        $entries = Yaml::parseFile($path);
        if (! is_array($entries) || ! array_is_list($entries)) {
            throw new UnexpectedValueException('The guide videos file must be a list of videos.');
        }

        $videos = [];
        foreach ($entries as $position => $entry) {
            if (! is_array($entry)
                || ! is_string($entry['key'] ?? null) || preg_match('#^[a-z0-9-]+$#', $entry['key']) !== 1 || isset($videos[$entry['key']])
                || ! in_array($entry['kind'] ?? null, self::VIDEO_KINDS, true)
                || ! is_string($entry['title'] ?? null) || ! is_string($entry['description'] ?? null)
                || ! is_string($entry['youtube'] ?? null) || preg_match('#^(?:[A-Za-z0-9_-]{11})?$#', $entry['youtube']) !== 1
                || ! is_int($entry['seconds'] ?? null) || $entry['seconds'] < 1
                || ! is_array($entry['chapters'] ?? null) || ! array_is_list($entry['chapters'])
                || array_filter($entry['chapters'], fn(mixed $slug): bool => ! is_string($slug)) !== []) {
                throw new UnexpectedValueException('Invalid guide video at position ' . ($position + 1));
            }

            /** @var list<string> $chapters */
            $chapters = $entry['chapters'];
            $videos[$entry['key']] = [
                'key' => $entry['key'],
                'kind' => $entry['kind'],
                'title' => $entry['title'],
                'description' => $entry['description'],
                'youtube' => $entry['youtube'],
                'seconds' => $entry['seconds'],
                'chapters' => $chapters,
            ];
        }

        return $videos;
    }

    private function cacheKey(string $part): string
    {
        $this->fingerprint ??= hash('xxh128', implode('|', array_map(
            static fn(SplFileInfo $file): string => $file->getRelativePathname() . ':' . $file->getSize() . ':' . $file->getMTime(),
            File::allFiles($this->directory),
        )));

        return 'user-guide:' . hash('xxh128', $this->directory) . ':' . $this->fingerprint . ':' . $part;
    }

    /** @return list<SplFileInfo> */
    private function files(): array
    {
        return array_values(array_filter(
            File::files($this->directory),
            static fn(SplFileInfo $file): bool => $file->getExtension() === 'md',
        ));
    }
}
