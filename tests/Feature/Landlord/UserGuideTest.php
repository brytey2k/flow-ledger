<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Landlord\DocumentationController as LandlordDocumentationController;
use App\Http\Controllers\Web\Tenant\DocumentationController as TenantDocumentationController;
use App\Support\UserGuide;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

uses(TestCase::class);

test('guests can read every guide chapter and follow its links', function () {
    $base = 'http://' . config('app.central_url') . '/docs';
    $index = $this->get($base)->assertOk()->assertSeeInOrder(['Getting Started', 'Branches']);
    $anchors = [];
    $links = [];

    foreach (File::files(resource_path('docs')) as $file) {
        $slug = $file->getFilenameWithoutExtension();
        $index->assertSee($base . '/' . $slug);
        $response = $this->get($base . '/' . $slug . '/')->assertOk()->assertDontSee('onThisPage:');
        $dom = new DOMDocument();
        $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//article/h2/@id') as $id) {
            $anchors[$slug][] = $id->nodeValue;
            $response->assertSee('href="#' . $id->nodeValue . '"', false);
        }

        Assert::assertArrayHasKey($slug, $anchors);
        foreach ($xpath->query('//article//a/@href') as $href) {
            $links[] = [$slug, $href->nodeValue];
        }
    }

    // The index's task cards link into chapters too.
    preg_match_all('#href="' . preg_quote($base, '#') . '/([a-z0-9-]+)\#([a-z0-9-]+)"#', $index->getContent(), $taskLinks, PREG_SET_ORDER);
    Assert::assertNotEmpty($taskLinks);
    foreach ($taskLinks as [, $slug, $anchor]) {
        $links[] = [$slug, '/docs/' . $slug . '#' . $anchor];
    }

    foreach ($links as [$slug, $href]) {
        if (! str_starts_with($href, '#') && ! str_starts_with($href, '/docs/')) {
            continue;
        }

        $target = trim(substr(parse_url($href, PHP_URL_PATH) ?? '', 6), '/') ?: $slug;
        Assert::assertArrayHasKey($target, $anchors, $href);
        $fragment = parse_url($href, PHP_URL_FRAGMENT);
        if ($fragment !== null) {
            Assert::assertContains($fragment, $anchors[$target], $href);
        }
    }

    $this->get($base . '/missing-chapter')->assertNotFound();
});

test('every screenshot a chapter shows exists, every screenshot is shown, and browsers may keep them', function () {
    $base = 'http://' . config('app.central_url') . '/docs';
    $shown = [];

    foreach (File::files(resource_path('docs')) as $file) {
        preg_match_all('/!\[[^\]]+\]\(images\/([^ )]+)/', $file->getContents(), $images);
        foreach ($images[1] as $image) {
            Assert::assertFileExists(resource_path('docs/images/' . $image), $file->getFilename());
            $shown[] = $image;
        }
    }

    $files = File::isDirectory(resource_path('docs/images')) ? File::files(resource_path('docs/images')) : [];
    foreach ($files as $image) {
        Assert::assertContains($image->getFilename(), $shown, 'Unused screenshot ' . $image->getFilename());
    }

    if ($files !== []) {
        $this->get($base . '/images/' . $files[0]->getFilename())->assertOk()
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }
    $this->get($base . '/images/missing-shot.webp')->assertNotFound();
});

test('guide search finds chapter content and handles no matches', function () {
    $base = 'http://' . config('app.central_url') . '/docs';

    $this->get($base . '?q=denomination')->assertOk()
        ->assertSee('Search results for')
        ->assertSee($base . '/cost-codes-currencies');
    $this->get($base . '?q=no-such-guide-topic-98765')->assertOk()
        ->assertSee('No chapters match');
    $this->get($base . '?q[]=unexpected')->assertOk()->assertSee('Start with a task');
    $this->get($base . '?q=%3Cscript%3Ealert(1)%3C%2Fscript%3E')->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('every video names real chapters and has a poster', function () {
    $guide = new UserGuide();
    $chapters = $guide->chapters();
    $entries = Symfony\Component\Yaml\Yaml::parseFile(resource_path('docs/videos/videos.yaml'));

    foreach ($entries as $entry) {
        Assert::assertNotNull($guide->posterPath($entry['key'] . '.webp'), 'Missing poster for ' . $entry['key']);
        foreach ($entry['chapters'] as $slug) {
            Assert::assertTrue($chapters->has($slug), "Video {$entry['key']} names unknown chapter {$slug}");
        }
    }

    $this->get('http://' . config('app.central_url') . '/docs/videos/posters/' . $entries[0]['key'] . '.webp')->assertOk()
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    $this->get('http://' . config('app.central_url') . '/docs/videos/posters/missing.webp')->assertNotFound();
});

test('published videos appear on the index, their chapters and the video center', function () {
    $directory = sys_get_temp_dir() . '/user-guide-videos-' . bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory . '/videos/posters');
    File::put($directory . '/start.md', "---\ntitle: Start Here\ndescription: \"First steps\"\norder: 1\n---\n## Begin {#begin}\n\nHello.\n");
    File::put($directory . '/money.md', "---\ntitle: Money\ndescription: \"Cash\"\norder: 2\n---\n## Cash {#cash}\n\nCount it.\n");
    File::put($directory . '/videos/posters/tour.webp', 'poster');
    File::put($directory . '/videos/videos.yaml', implode("\n", [
        '- {key: tour, kind: walkthrough, title: "The tour", description: "All of it.", youtube: "abcdefghijk", seconds: 125, chapters: [start]}',
        '- {key: cash, kind: part, title: "Cash part", description: "Money.", youtube: "bcdefghijkl", seconds: 40, chapters: [money]}',
        '- {key: soon, kind: part, title: "Not yet uploaded", description: "Later.", youtube: "", seconds: 30, chapters: [start]}',
        '- {key: film, kind: promo, title: "The film", description: "Short.", youtube: "cdefghijklm", seconds: 38, chapters: []}',
    ]));
    app()->instance(UserGuide::class, new UserGuide($directory));
    $base = 'http://' . config('app.central_url') . '/docs';

    try {
        $this->get($base)->assertOk()
            ->assertSee('See Flow Ledger in action')
            ->assertSee('Browse all 3 videos')
            ->assertSee('Play video: The tour (2:05)')
            ->assertDontSee('youtube-nocookie.com/embed/abcdefghijk&', false);

        $this->get($base . '/start')->assertOk()
            ->assertSee('Watch the walkthrough · 2:05')
            ->assertDontSee('Not yet uploaded');

        $this->get($base . '/videos')->assertOk()
            ->assertSeeInOrder(['The tour', 'Cash part', 'The film'])
            ->assertSee('id="video-cash"', false)
            ->assertSee($base . '/money')
            ->assertSee('youtube-nocookie.com/embed/bcdefghijkl', false)
            ->assertDontSee('Not yet uploaded');
    } finally {
        File::deleteDirectory($directory);
    }
});

test('the video center explains when no video is published yet', function () {
    $directory = sys_get_temp_dir() . '/user-guide-novideos-' . bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory);
    File::put($directory . '/start.md', "---\ntitle: Start Here\ndescription: \"First steps\"\norder: 1\n---\n## Begin {#begin}\n\nHello.\n");
    app()->instance(UserGuide::class, new UserGuide($directory));
    $base = 'http://' . config('app.central_url') . '/docs';

    try {
        $this->get($base . '/videos')->assertOk()->assertSee('No videos yet');
        $this->get($base)->assertOk()->assertDontSee('See Flow Ledger in action')->assertDontSee($base . '/videos"', false);
    } finally {
        File::deleteDirectory($directory);
    }
});

test('existing documentation entry points use the central guide', function () {
    $expected = rtrim(config('app.url'), '/') . '/docs';
    expect(config('app.docs_url'))->toBe($expected);
    expect(app(LandlordDocumentationController::class)->index()->getTargetUrl())->toBe($expected);
    expect(app(TenantDocumentationController::class)->index()->getTargetUrl())->toBe($expected);
});

test('guide retains attachment preview instructions', function () {
    $base = 'http://' . config('app.central_url') . '/docs/';
    foreach (['payment-requests', 'retirement-requests'] as $slug) {
        $this->get($base . $slug)->assertOk()
            ->assertSee('select the eye button beside a file to preview it without leaving')
            ->assertSee('PDF, JPG/JPEG, PNG, WebP, DOCX, XLS, and XLSX')
            ->assertSee('Download the original file');
    }
});
