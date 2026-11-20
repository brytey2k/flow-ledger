<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Support\UserGuide;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The public user guide at /docs: the chapter index with search, each chapter, and the video center.
 *
 * @phpstan-import-type Video from UserGuide
 */
class UserGuideController
{
    public function __construct(private readonly UserGuide $guide) {}

    public function show(Request $request, string|null $slug = null): View
    {
        $chapters = $this->guide->chapters();

        if ($slug === null) {
            $input = $request->query('q', '');
            $query = is_string($input) ? Str::limit(trim($input), 100, '') : '';
            $matches = $query === '' ? $chapters : $this->guide->search($query);
            $videos = $this->guide->videos();

            return view('docs.index', [
                'chapters' => $matches,
                'query' => $query,
                'walkthrough' => $videos->firstWhere('kind', 'walkthrough'),
                'videoCount' => $videos->count(),
                'guide' => $this->guide,
            ]);
        }

        $chapter = $chapters->get($slug);
        abort_if($chapter === null, 404);

        ['html' => $html, 'sections' => $sections] = $this->guide->render($slug, $chapter);

        return view('docs.chapter', [
            'chapters' => $chapters,
            'chapter' => $chapter,
            'slug' => $slug,
            'html' => $html,
            'sections' => $sections,
            'videos' => $this->guide->videosFor($slug),
            'guide' => $this->guide,
        ]);
    }

    /** The video center: the full walkthrough, each of its parts, and the introduction film. */
    public function videos(): View
    {
        $videos = $this->guide->videos();

        return view('docs.videos', [
            'chapters' => $this->guide->chapters(),
            'walkthrough' => $videos->firstWhere('kind', 'walkthrough'),
            'parts' => $videos->where('kind', 'part'),
            'promos' => $videos->where('kind', 'promo'),
            'guide' => $this->guide,
        ]);
    }

    /** A chapter's screenshot. Its address carries a version that changes with the file, so browsers may keep it. */
    public function image(string $file): BinaryFileResponse
    {
        $path = $this->guide->imagePath($file);
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }

    /** A video's poster, versioned like a screenshot. */
    public function poster(string $file): BinaryFileResponse
    {
        $path = $this->guide->posterPath($file);
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
