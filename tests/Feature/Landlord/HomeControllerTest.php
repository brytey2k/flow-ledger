<?php

declare(strict_types=1);

use App\Support\UserGuide;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Assert;

uses(Tests\LandlordTestCase::class);

/**
 * Points the user guide at a temporary directory with one chapter and the given videos, so the page can be tested with
 * and without a published promo whatever state resources/docs is in.
 *
 * @param list<string> $videos YAML flow mappings, one per video
 */
function useGuideWithVideos(array $videos): string
{
    $directory = sys_get_temp_dir() . '/landing-guide-' . bin2hex(random_bytes(6));
    File::ensureDirectoryExists($directory . '/videos/posters');
    File::put($directory . '/start.md', "---\ntitle: Start Here\ndescription: \"First steps\"\norder: 1\n---\n## Begin {#begin}\n\nHello.\n");
    File::put($directory . '/videos/posters/film.webp', 'poster');
    File::put($directory . '/videos/videos.yaml', implode("\n", $videos));
    app()->instance(UserGuide::class, new UserGuide($directory));

    return $directory;
}

/**
 * Every link on the page must lead somewhere: an anchor to an element on the page, a page of the site that answers,
 * or (for screenshots and logos) a file in public/.
 *
 * @param string $html
 */
function assertPageLinksResolve(string $html): void
{
    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    $host = parse_url(route('landlord.home'), PHP_URL_HOST);
    $ids = [];
    foreach ($xpath->query('//*[@id]/@id') as $id) {
        $ids[] = $id->nodeValue;
    }

    foreach ($xpath->query('//a/@href') as $href) {
        $url = $href->nodeValue;
        if (str_starts_with($url, '#')) {
            Assert::assertContains(substr($url, 1), $ids, $url);
        } elseif (str_starts_with($url, 'tel:')) {
            Assert::assertSame('tel:+233592209201', $url);
        } elseif (parse_url($url, PHP_URL_HOST) === $host) {
            test()->get(strtok($url, '#'))->assertOk();
        } else {
            Assert::assertStringStartsWith('https://wa.me/233592209201?text=', $url);
        }
    }

    foreach ($xpath->query('//img/@src') as $src) {
        $path = parse_url($src->nodeValue, PHP_URL_PATH);
        if (str_starts_with($path, '/docs/')) {
            test()->get($src->nodeValue)->assertOk();
        } else {
            Assert::assertFileExists(public_path(ltrim($path, '/')), $src->nodeValue);
        }
    }
}

test('guest can view the marketing home page with its sections and call to action', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertViewIs('landlord.home')
        ->assertSee('Every cedi advanced, accounted for.')
        ->assertSee('id="problem"', false)
        ->assertSee('id="how-it-works"', false)
        ->assertSee('id="controls"', false)
        ->assertSee('id="contact"', false)
        ->assertSeeInOrder([
            'Staff request an advance or claim an expense',
            'The right people approve, in the right order',
            'Finance pays out from the branch cashbook',
            'Staff account for every advance',
            'See where the money is, and where it is stuck',
        ])
        ->assertSee('Can one person request, approve and pay out the same advance?')
        ->assertSee('Book a demo')
        ->assertSee('href="#contact"', false)
        ->assertSee('Call +233 59 220 9201')
        ->assertSee('href="tel:+233592209201"', false)
        ->assertSee('https://wa.me/233592209201?text=', false)
        ->assertSee(route('landlord.docs'), false);
});

test('home page offers a demonstration instead of sign-up or the landlord login', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertDontSee(route('landlord.register'), false)
        ->assertDontSee(route('landlord.login'), false);
});

test('home page shows real product screenshots in light and dark', function () {
    $response = $this->get(route('landlord.home'))->assertOk();

    foreach (['dashboard', 'request-draft', 'approval-review', 'disbursements', 'retirement-form', 'outstanding-advances', 'timeline'] as $screen) {
        foreach (['light', 'dark'] as $theme) {
            $response->assertSee("assets/media/landing/{$screen}-{$theme}.webp", false);
            expect(public_path("assets/media/landing/{$screen}-{$theme}.webp"))->toBeFile();
        }
    }
});

test('home page leaves the film out until the promo is published', function () {
    $directory = useGuideWithVideos([
        '- {key: tour, kind: walkthrough, title: "The tour", description: "All of it.", youtube: "", seconds: 125, chapters: [start]}',
        '- {key: film, kind: promo, title: "The film", description: "Short.", youtube: "", seconds: 38, chapters: []}',
    ]);

    try {
        $response = $this->get(route('landlord.home'))
            ->assertOk()
            ->assertDontSee('id="video"', false)
            ->assertDontSee('href="#video"', false)
            ->assertDontSee('Watch the video')
            ->assertDontSee('youtube-nocookie.com', false)
            ->assertDontSee(route('landlord.docs.videos'), false)
            ->assertSee('See how it works');

        assertPageLinksResolve($response->getContent());
    } finally {
        File::deleteDirectory($directory);
    }
});

test('home page shows the published promo with links to the videos and the user guide', function () {
    $directory = useGuideWithVideos([
        '- {key: tour, kind: walkthrough, title: "The tour", description: "All of it.", youtube: "", seconds: 125, chapters: [start]}',
        '- {key: film, kind: promo, title: "The film", description: "Short.", youtube: "cdefghijklm", seconds: 38, chapters: []}',
    ]);

    try {
        $response = $this->get(route('landlord.home'))
            ->assertOk()
            ->assertSee('id="video"', false)
            ->assertSee('href="#video"', false)
            ->assertSee('Flow Ledger in 38 seconds.')
            ->assertSee('Watch the video')
            ->assertSee('Play video: The film (0:38)')
            ->assertSee('/docs/videos/posters/film.webp?v=', false)
            ->assertSee('youtube-nocookie.com/embed/cdefghijklm', false)
            ->assertSee('More videos')
            ->assertDontSee('Watch the full walkthrough')
            ->assertSeeInOrder(['id="video"', route('landlord.docs.videos'), route('landlord.docs')], false);

        assertPageLinksResolve($response->getContent());
    } finally {
        File::deleteDirectory($directory);
    }
});

test('the film links to the full walkthrough once it is published too', function () {
    $directory = useGuideWithVideos([
        '- {key: tour, kind: walkthrough, title: "The tour", description: "All of it.", youtube: "abcdefghijk", seconds: 125, chapters: [start]}',
        '- {key: film, kind: promo, title: "The film", description: "Short.", youtube: "cdefghijklm", seconds: 38, chapters: []}',
    ]);

    try {
        $this->get(route('landlord.home'))
            ->assertOk()
            ->assertSee('Watch the full walkthrough')
            ->assertDontSee('More videos');
    } finally {
        File::deleteDirectory($directory);
    }
});

test('home page renders translated content for the active locale', function () {
    $this->withSession(['locale' => 'fr'])
        ->get(route('landlord.home'))
        ->assertOk()
        ->assertSee(__('landing.hero.headline', [], 'fr'), false)
        ->assertSee(__('landing.contact.heading', [], 'fr'));
});

test('every landing translation exists in every supported locale', function () {
    $keys = array_keys(Arr::dot(require lang_path('en/landing.php')));

    foreach (array_keys(config('locales.supported')) as $locale) {
        expect(array_keys(Arr::dot(require lang_path("{$locale}/landing.php"))))->toEqualCanonicalizing($keys);
    }
});

test('home page sets html lang and dir attributes', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr">', false);
});
