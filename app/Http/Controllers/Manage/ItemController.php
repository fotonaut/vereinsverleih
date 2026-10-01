<?php

namespace App\Http\Controllers\Manage;

use App\Enums\LendingScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Services\WaitlistNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->club_id, 403);

        return Inertia::render('manage/items/Index', [
            'items' => Item::where('club_id', $request->user()->club_id)
                ->with('category:id,name')->orderBy('name')->get()
                ->map(fn (Item $i) => $i->setAttribute('scope_label', $i->lending_scope->label())),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Item::class);

        return Inertia::render('manage/items/Form', $this->formData(null));
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create($this->payload($request) + ['club_id' => $request->user()->club_id]);

        return to_route('manage.items.index')->with('flash', "„{$item->name}“ wurde angelegt.");
    }

    public function edit(Item $item): Response
    {
        Gate::authorize('manage', $item);

        return Inertia::render('manage/items/Form', $this->formData($item));
    }

    public function update(StoreItemRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('manage', $item);

        $payload = $this->payload($request);
        if (isset($payload['image_path']) && $item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->update($payload);

        // Mehr Bestand oder wieder aktiv: Warteliste prüfen
        app(WaitlistNotifier::class)->check($item->refresh());

        return to_route('manage.items.index')->with('flash', 'Änderungen gespeichert.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        Gate::authorize('manage', $item);

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();

        return to_route('manage.items.index')->with('flash', 'Gegenstand gelöscht.');
    }

    private function formData(?Item $item): array
    {
        return [
            'item' => $item ? $item->toArray() + ['deposit_euro' => $item->deposit_cents !== null ? $item->deposit_cents / 100 : null] : null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'scopes' => LendingScope::options(),
        ];
    }

    private function payload(StoreItemRequest $request): array
    {
        $data = $request->safe()->except(['image', 'deposit_euro']);
        $data['active'] = $request->boolean('active');
        $data['deposit_cents'] = $request->filled('deposit_euro') ? (int) round($request->input('deposit_euro') * 100) : null;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        return $data;
    }
}
