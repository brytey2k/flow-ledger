<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Support\UserGuide;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The marketing page. The promo film shows once it is published (has a YouTube id); until then the page leaves its
     * section out, along with every link to the video center.
     *
     * @param UserGuide $guide
     */
    public function index(UserGuide $guide): View
    {
        $videos = $guide->videos();
        $phone = Config::string('app.sales_phone');
        $digits = preg_replace('/\D+/', '', $phone);

        return view('landlord.home', [
            'guide' => $guide,
            'promo' => $videos->firstWhere('kind', 'promo'),
            'hasVideos' => $videos->isNotEmpty(),
            'hasWalkthrough' => $videos->contains('kind', 'walkthrough'),
            'salesPhone' => $phone,
            'salesPhoneLink' => 'tel:+' . $digits,
            'salesWhatsAppLink' => 'https://wa.me/' . $digits . '?text=' . rawurlencode(__('landing.contact.whatsapp_message')),
        ]);
    }
}
