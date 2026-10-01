<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Item;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'latest' => Item::catalog()->with('club:id,name,city')->latest()->limit(6)->get(),
            'stats' => [
                'clubs' => Club::count(),
                'items' => Item::catalog()->count(),
            ],
        ]);
    }

    public function imprint(): Response
    {
        return Inertia::render('Imprint');
    }
}
