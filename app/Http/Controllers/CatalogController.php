<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Club;
use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Item::catalog()
            ->with(['club:id,name,city', 'category:id,name'])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('club'), fn ($q, $id) => $q->where('club_id', $id))
            ->when($request->query('for'), fn ($q, $for) => match ($for) {
                'club' => $q->whereIn('lending_scope', ['clubs', 'both']),
                'private' => $q->whereIn('lending_scope', ['private', 'both']),
                default => $q,
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('catalog/Index', [
            'items' => $items,
            'filters' => $request->only(['q', 'category', 'club', 'for']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'clubs' => Club::whereHas('items', fn ($q) => $q->catalog())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Item $item): Response
    {
        abort_unless($item->active && $item->lending_scope->value !== 'none', 404);

        $user = $request->user();
        $item->load(['club', 'category']);

        $blocked = $item->loanRequests()
            ->whereIn('status', ['approved', 'picked_up'])
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'quantity']);

        return Inertia::render('catalog/Show', [
            'item' => $item,
            'scopeLabel' => $item->lending_scope->label(),
            'reservations' => $blocked,
            'canRequestAsClub' => $user?->club_id !== null && $user->club_id !== $item->club_id && $item->lending_scope->allowsClubs(),
            'canRequestAsPrivate' => $user === null && $item->lending_scope->allowsPrivate(),
            'ownItem' => $user?->club_id === $item->club_id && $user?->club_id !== null,
            'clubOnlyHint' => $user === null && ! $item->lending_scope->allowsPrivate(),
            'privateOnlyHint' => $user !== null && $user->club_id !== null && ! $item->lending_scope->allowsClubs(),
        ]);
    }
}
