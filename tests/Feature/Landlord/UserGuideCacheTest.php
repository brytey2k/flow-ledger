<?php

declare(strict_types=1);

use App\Support\UserGuide;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->guideDirectory = sys_get_temp_dir() . '/user-guide-' . bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->guideDirectory);
});

afterEach(fn() => File::deleteDirectory($this->guideDirectory));

function writeGuideChapter(string $directory, string $slug, string $title, int $order, int $modifiedAt, string $body = "## Start {#start}\n\nHello."): void
{
    $path = "{$directory}/{$slug}.md";
    file_put_contents($path, "---\ntitle: {$title}\ndescription: \"About {$title}\"\norder: {$order}\n---\n{$body}\n");
    touch($path, $modifiedAt);
}

// LR-21: every guide page view parsed every chapter.
test('chapters are parsed once and served from the cache until a file changes', function () {
    $modifiedAt = time() - 60;
    writeGuideChapter($this->guideDirectory, 'beta', 'Beta', 2, $modifiedAt);
    writeGuideChapter($this->guideDirectory, 'alpha', 'Alfa', 1, $modifiedAt);
    file_put_contents($this->guideDirectory . '/notes.txt', 'not a chapter');

    expect((new UserGuide($this->guideDirectory))->chapters()->map(fn(array $chapter): string => $chapter['title'])->all())
        ->toBe(['alpha' => 'Alfa', 'beta' => 'Beta']);

    // Same name, size and modified time: the cached parse is used and the file is not read again.
    writeGuideChapter($this->guideDirectory, 'alpha', 'Alph', 1, $modifiedAt);
    expect((new UserGuide($this->guideDirectory))->chapters()->get('alpha')['title'] ?? null)->toBe('Alfa');

    // A deploy (or an edit) changes the fingerprint, and the new content is parsed.
    writeGuideChapter($this->guideDirectory, 'alpha', 'Alph', 1, $modifiedAt + 30);
    expect((new UserGuide($this->guideDirectory))->chapters()->get('alpha')['title'] ?? null)->toBe('Alph');
});

test('a rendered chapter strips raw HTML and lists its sections', function () {
    writeGuideChapter($this->guideDirectory, 'alpha', 'Alpha', 1, time() - 60, "## First steps {#first-steps}\n\n<script>alert(1)</script>\n\n## Next {#next}\n\nDone.");
    $guide = new UserGuide($this->guideDirectory);

    $rendered = $guide->render('alpha', $guide->chapters()->get('alpha') ?? []);

    expect($rendered['sections'])->toBe(['first-steps' => 'First steps', 'next' => 'Next'])
        ->and($rendered['html'])->not->toContain('<script>');
});

test('a chapter without the required metadata is refused', function () {
    file_put_contents($this->guideDirectory . '/broken.md', "---\ntitle: Broken\n---\nBody\n");

    expect(fn() => (new UserGuide($this->guideDirectory))->chapters())->toThrow(UnexpectedValueException::class, 'broken.md');
});

test('a screenshot becomes a captioned figure with its size and a version that changes with the file', function () {
    File::ensureDirectoryExists($this->guideDirectory . '/images');
    $image = imagecreatetruecolor(40, 30);
    imagepng($image, $this->guideDirectory . '/images/new-invoice.png');
    writeGuideChapter($this->guideDirectory, 'alpha', 'Alpha', 1, time() - 60, implode("\n\n", [
        '## Start {#start}',
        '![The new invoice form](images/new-invoice.png "Ready to save")',
        'Inline ![no caption](images/missing.png) text.',
    ]));
    $guide = new UserGuide($this->guideDirectory);

    $html = $guide->render('alpha', $guide->chapters()->get('alpha') ?? [])['html'];
    $version = substr((string) hash_file('xxh128', $this->guideDirectory . '/images/new-invoice.png'), 0, 12);

    expect($html)
        ->toContain('<figure><img src="/docs/images/new-invoice.png?v=' . $version . '" loading="lazy" width="40" height="30" alt="The new invoice form" /><figcaption>Ready to save</figcaption></figure>')
        ->toContain('<img src="/docs/images/missing.png" alt="no caption" />');

    // A retaken screenshot changes the fingerprint, so the chapter is rendered again with the new version.
    imagepng(imagecreatetruecolor(41, 30), $this->guideDirectory . '/images/new-invoice.png');
    touch($this->guideDirectory . '/images/new-invoice.png', time() + 5);
    $guide = new UserGuide($this->guideDirectory);
    expect($guide->render('alpha', $guide->chapters()->get('alpha') ?? [])['html'])->toContain('width="41"');
});

test('only screenshot file names in the images folder resolve to a file', function () {
    File::ensureDirectoryExists($this->guideDirectory . '/images');
    file_put_contents($this->guideDirectory . '/images/shot.webp', 'x');
    file_put_contents($this->guideDirectory . '/secret.webp', 'x');
    $guide = new UserGuide($this->guideDirectory);

    expect($guide->imagePath('shot.webp'))->toBe($this->guideDirectory . '/images/shot.webp')
        ->and($guide->imagePath('missing.webp'))->toBeNull()
        ->and($guide->imagePath('../secret.webp'))->toBeNull()
        ->and($guide->imagePath('Shot.WEBP'))->toBeNull()
        ->and($guide->imagePath('shot.svg'))->toBeNull();
});

test('videos without a YouTube id are left out, and a chapter shows only its own videos', function () {
    File::ensureDirectoryExists($this->guideDirectory . '/videos/posters');
    file_put_contents($this->guideDirectory . '/videos/posters/tour.webp', 'x');
    file_put_contents($this->guideDirectory . '/videos/videos.yaml', implode("\n", [
        '- {key: tour, kind: walkthrough, title: Tour, description: All, youtube: abcdefghijk, seconds: 120, chapters: [alpha]}',
        '- {key: later, kind: part, title: Later, description: Soon, youtube: "", seconds: 30, chapters: [alpha]}',
        '- {key: cash, kind: part, title: Cash, description: Money, youtube: bcdefghijkl, seconds: 40, chapters: [beta]}',
    ]));
    $guide = new UserGuide($this->guideDirectory);

    expect($guide->videos()->keys()->all())->toBe(['tour', 'cash'])
        ->and($guide->videosFor('alpha')->keys()->all())->toBe(['tour'])
        ->and($guide->posterUrl('tour'))->toStartWith('/docs/videos/posters/tour.webp?v=')
        ->and($guide->posterUrl('cash'))->toBeNull()
        ->and($guide->posterPath('../tour.webp'))->toBeNull();
});

test('a video with an invalid entry is refused', function (string $entry) {
    File::ensureDirectoryExists($this->guideDirectory . '/videos');
    file_put_contents($this->guideDirectory . '/videos/videos.yaml', $entry);

    expect(fn() => (new UserGuide($this->guideDirectory))->videos())->toThrow(UnexpectedValueException::class);
})->with([
    'unknown kind' => '- {key: a, kind: trailer, title: A, description: A, youtube: "", seconds: 3, chapters: []}',
    'bad YouTube id' => '- {key: a, kind: part, title: A, description: A, youtube: "https://youtu.be/x", seconds: 3, chapters: []}',
    'no length' => '- {key: a, kind: part, title: A, description: A, youtube: "", seconds: 0, chapters: []}',
    'not a list' => 'key: a',
]);
