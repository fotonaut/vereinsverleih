<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\LoanReturnPhoto;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReturnPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(LoanReturnPhoto::DISK);
        Notification::fake();
    }

    private function pickedUp(): array
    {
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id]);
        $loan = LoanRequest::factory()->status(LoanStatus::PickedUp)->create(['item_id' => $item->id, 'start_date' => today()->subDay(), 'end_date' => today()->addDay()]);

        return [$owner, $item, $loan];
    }

    private function returnWith(User $owner, LoanRequest $loan, array $photos): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", [
            'status' => 'returned', 'return_condition' => 'damaged', 'photos' => $photos,
        ]);
    }

    public function test_photos_are_stored_privately_resized_and_without_exif(): void
    {
        [$owner, , $loan] = $this->pickedUp();

        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('schaden.jpg', 3200, 2400), UploadedFile::fake()->image('b.png', 800, 600)])
            ->assertSessionHasNoErrors();

        $photos = $loan->returnPhotos()->get();
        $this->assertCount(2, $photos);
        foreach ($photos as $p) {
            $this->assertStringStartsWith('return-photos/', $p->path);
            Storage::disk(LoanReturnPhoto::DISK)->assertExists($p->path);
        }

        [$w, $h] = getimagesizefromstring(Storage::disk(LoanReturnPhoto::DISK)->get($photos[0]->path));
        $this->assertSame(1600, max($w, $h), 'wird auf 1600 px Kantenlänge verkleinert');
        $this->assertSame(1200, min($w, $h));
        $this->assertStringEndsWith('.jpg', $photos[1]->path, 'PNG wird als JPEG neu kodiert');
    }

    public function test_limits_and_file_types_are_enforced(): void
    {
        [$owner, , $loan] = $this->pickedUp();

        $five = array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 5));
        $this->returnWith($owner, $loan, $five)->assertSessionHasErrors('photos');
        $this->assertSame(LoanStatus::PickedUp, $loan->fresh()->status, 'bei Validierungsfehler bleibt alles unverändert');

        $this->returnWith($owner, $loan, [UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf')])->assertSessionHasErrors('photos.0');
        $this->returnWith($owner, $loan, [UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg')])->assertSessionHasErrors('photos.0');
        $this->assertSame(0, LoanReturnPhoto::count());
    }

    public function test_club_can_view_but_strangers_and_guests_cannot(): void
    {
        [$owner, , $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('a.jpg')]);
        $photo = $loan->returnPhotos()->firstOrFail();
        $stranger = User::factory()->clubAdmin()->create();

        $response = $this->actingAs($owner)->get("/verwaltung/rueckgabefotos/{$photo->id}")->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('content-type'));
        $this->assertStringContainsString('private', $response->headers->get('cache-control'));

        $this->actingAs($stranger)->get("/verwaltung/rueckgabefotos/{$photo->id}")->assertForbidden();
        auth()->logout();
        $this->get("/verwaltung/rueckgabefotos/{$photo->id}")->assertRedirect('/login');
    }

    public function test_borrower_sees_photos_via_the_secret_token_only(): void
    {
        [$owner, , $loan] = $this->pickedUp();
        $other = LoanRequest::factory()->create();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('a.jpg')]);
        $photo = $loan->returnPhotos()->firstOrFail();
        auth()->logout();

        $this->get("/anfragen/{$loan->token}")->assertInertia(fn (Assert $page) => $page
            ->has('loan.return.photos', 1)->where('loan.return.photos.0.url', route('requests.return-photo', [$loan->token, $photo])));

        $this->get("/anfragen/{$loan->token}/rueckgabefotos/{$photo->id}")->assertOk();
        $this->get("/anfragen/{$other->token}/rueckgabefotos/{$photo->id}")->assertNotFound();
        $this->get("/anfragen/falscher-token/rueckgabefotos/{$photo->id}")->assertNotFound();
    }

    public function test_photos_are_not_publicly_reachable(): void
    {
        [$owner, , $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('a.jpg')]);
        $photo = $loan->returnPhotos()->firstOrFail();

        $this->assertFalse(Storage::disk('public')->exists($photo->path));
        $this->assertSame('local', LoanReturnPhoto::DISK);
    }

    public function test_deleting_an_item_removes_the_photo_files(): void
    {
        [$owner, $item, $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('a.jpg')]);
        $path = $loan->returnPhotos()->firstOrFail()->path;
        Storage::disk(LoanReturnPhoto::DISK)->assertExists($path);

        $this->actingAs($owner)->delete("/verwaltung/gegenstaende/{$item->id}")->assertRedirect();

        Storage::disk(LoanReturnPhoto::DISK)->assertMissing($path);
        $this->assertSame(0, LoanReturnPhoto::count());
    }

    public function test_retention_job_deletes_old_photos_with_their_files(): void
    {
        [$owner, , $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('alt.jpg'), UploadedFile::fake()->image('neu.jpg')]);
        [$old, $new] = $loan->returnPhotos()->orderBy('id')->get()->all();
        $old->forceFill(['created_at' => now()->subDays(400)])->save();

        $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'prune-return-photos');
        $this->assertNotNull($event);
        $event->run(app());

        Storage::disk(LoanReturnPhoto::DISK)->assertMissing($old->path);
        Storage::disk(LoanReturnPhoto::DISK)->assertExists($new->path);
        $this->assertSame(1, LoanReturnPhoto::count());
    }

    public function test_incoming_and_calendar_pages_expose_photo_urls_only_for_the_club(): void
    {
        [$owner, $item, $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, [UploadedFile::fake()->image('a.jpg')]);

        $this->actingAs($owner)->get('/verwaltung/eingang')->assertInertia(fn (Assert $page) => $page
            ->has('loans.0.return_photos', 1)
            ->where('loans.0.return_photos.0.url', fn ($url) => str_contains($url, '/verwaltung/rueckgabefotos/')));
        $this->actingAs($owner)->get("/verwaltung/gegenstaende/{$item->id}/kalender")->assertInertia(fn (Assert $page) => $page
            ->has('returns.0.photos', 1));
    }

    private function returned(int $photos = 0): array
    {
        [$owner, $item, $loan] = $this->pickedUp();
        $this->returnWith($owner, $loan, array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, max($photos, 1))));
        if ($photos === 0) {
            $loan->returnPhotos->each->delete();
        }

        return [$owner, $item, $loan->fresh()];
    }

    public function test_photos_can_be_added_after_the_return(): void
    {
        [$owner, , $loan] = $this->returned(1);

        $this->actingAs($owner)->post("/verwaltung/eingang/{$loan->id}/fotos", [
            'photos' => [UploadedFile::fake()->image('spaeter.jpg', 2000, 1000), UploadedFile::fake()->image('spaeter2.png')],
        ])->assertSessionHasNoErrors()->assertSessionHas('flash');

        $this->assertSame(3, $loan->returnPhotos()->count());
        foreach ($loan->returnPhotos as $p) {
            Storage::disk(LoanReturnPhoto::DISK)->assertExists($p->path);
        }
    }

    public function test_total_is_capped_at_four_photos(): void
    {
        [$owner, , $loan] = $this->returned(3);

        $this->actingAs($owner)->post("/verwaltung/eingang/{$loan->id}/fotos", [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('photos');
        $this->assertSame(3, $loan->returnPhotos()->count());

        $this->actingAs($owner)->post("/verwaltung/eingang/{$loan->id}/fotos", ['photos' => [UploadedFile::fake()->image('c.jpg')]])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $loan->returnPhotos()->count());

        $this->actingAs($owner)->post("/verwaltung/eingang/{$loan->id}/fotos", ['photos' => [UploadedFile::fake()->image('d.jpg')]])
            ->assertSessionHasErrors('photos');
        $this->assertSame(4, $loan->returnPhotos()->count());
    }

    public function test_only_for_returned_loans_valid_images_and_the_owning_club(): void
    {
        [$owner, , $open] = $this->pickedUp();
        $this->actingAs($owner)->post("/verwaltung/eingang/{$open->id}/fotos", ['photos' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertSessionHasErrors('photos');

        [$owner2, , $returned] = $this->returned(0);
        $this->actingAs($owner2)->post("/verwaltung/eingang/{$returned->id}/fotos", ['photos' => []])->assertSessionHasErrors('photos');
        $this->actingAs($owner2)->post("/verwaltung/eingang/{$returned->id}/fotos", ['photos' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0');

        $stranger = User::factory()->clubAdmin()->create();
        $this->actingAs($stranger)->post("/verwaltung/eingang/{$returned->id}/fotos", ['photos' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
        $this->assertSame(0, LoanReturnPhoto::count());
    }

    public function test_club_can_delete_a_single_photo_with_its_file(): void
    {
        [$owner, , $loan] = $this->returned(2);
        [$first, $second] = $loan->returnPhotos()->orderBy('id')->get()->all();

        $this->actingAs($owner)->delete("/verwaltung/rueckgabefotos/{$first->id}")->assertSessionHas('flash');

        Storage::disk(LoanReturnPhoto::DISK)->assertMissing($first->path);
        Storage::disk(LoanReturnPhoto::DISK)->assertExists($second->path);
        $this->assertSame(1, $loan->returnPhotos()->count());
    }

    public function test_strangers_guests_and_borrowers_cannot_delete_photos(): void
    {
        [$owner, , $loan] = $this->returned(1);
        $photo = $loan->returnPhotos()->firstOrFail();
        $stranger = User::factory()->clubAdmin()->create();

        $this->actingAs($stranger)->delete("/verwaltung/rueckgabefotos/{$photo->id}")->assertForbidden();
        auth()->logout();
        $this->delete("/verwaltung/rueckgabefotos/{$photo->id}")->assertRedirect('/login');
        $this->delete("/anfragen/{$loan->token}/rueckgabefotos/{$photo->id}")->assertStatus(405);

        $this->assertSame(1, LoanReturnPhoto::count());
        Storage::disk(LoanReturnPhoto::DISK)->assertExists($photo->path);
    }
}
