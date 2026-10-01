<?php

namespace Database\Seeders;

use App\Enums\LendingScope;
use App\Models\Category;
use App\Models\Club;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Zelte & Pavillons', 'Veranstaltungstechnik', 'Möbel & Bänke', 'Sport & Spiel', 'Küche & Grill', 'Werkzeug', 'Sonstiges'] as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        // Demo-Daten nur außerhalb von Produktion
        if (app()->isProduction()) {
            return;
        }

        $schuetzen = Club::factory()->create(['name' => 'Schützenverein Musterdorf e.V.', 'city' => 'Musterdorf', 'email' => 'schuetzen@example.com']);
        $feuerwehr = Club::factory()->create(['name' => 'Freiwillige Feuerwehr Beispielstadt', 'city' => 'Beispielstadt', 'email' => 'feuerwehr@example.com']);

        User::factory()->clubAdmin($schuetzen)->create(['name' => 'Sabine Schütz', 'email' => 'admin@schuetzen.test']);
        User::factory()->clubAdmin($feuerwehr)->create(['name' => 'Frank Feuer', 'email' => 'admin@feuerwehr.test']);

        $cat = fn (string $name) => Category::where('slug', Str::slug($name))->value('id');

        Item::factory()->create(['club_id' => $schuetzen->id, 'category_id' => $cat('Zelte & Pavillons'), 'name' => 'Festzelt 6x12 m', 'quantity' => 1, 'lending_scope' => LendingScope::Both, 'deposit_cents' => 15000, 'description' => 'Stabiles Festzelt mit Seitenwänden, Aufbau durch zwei Personen möglich.']);
        Item::factory()->create(['club_id' => $schuetzen->id, 'category_id' => $cat('Möbel & Bänke'), 'name' => 'Bierzeltgarnitur', 'quantity' => 20, 'lending_scope' => LendingScope::Both, 'deposit_cents' => 500, 'description' => 'Tisch + zwei Bänke.']);
        Item::factory()->create(['club_id' => $schuetzen->id, 'category_id' => $cat('Küche & Grill'), 'name' => 'Großer Gasgrill', 'quantity' => 1, 'lending_scope' => LendingScope::Clubs, 'description' => 'Nur an Vereine, ohne Gasflasche.']);
        Item::factory()->create(['club_id' => $feuerwehr->id, 'category_id' => $cat('Veranstaltungstechnik'), 'name' => 'PA-Anlage mit 2 Boxen', 'quantity' => 1, 'lending_scope' => LendingScope::Clubs, 'deposit_cents' => 10000, 'description' => 'Inklusive Mischpult und Mikrofonen.']);
        Item::factory()->create(['club_id' => $feuerwehr->id, 'category_id' => $cat('Sport & Spiel'), 'name' => 'Hüpfburg', 'quantity' => 1, 'lending_scope' => LendingScope::Private, 'deposit_cents' => 5000, 'description' => 'Für Kindergeburtstage und Hoffeste.']);
        Item::factory()->create(['club_id' => $feuerwehr->id, 'category_id' => $cat('Werkzeug'), 'name' => 'Kettensäge (intern)', 'quantity' => 2, 'lending_scope' => LendingScope::None, 'description' => 'Nur vereinsintern.']);
    }
}
