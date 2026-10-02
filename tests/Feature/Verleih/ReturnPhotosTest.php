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
}
