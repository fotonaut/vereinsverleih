<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\LoanReturnPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnPhotoController extends Controller
{
    /** Für den verleihenden Verein (eingeloggt). */
    public function forClub(Request $request, LoanReturnPhoto $photo): StreamedResponse
    {
        Gate::authorize('decide', $photo->loanRequest);

        return $this->serve($photo);
    }

    /** Einzelnes Foto löschen (nur der verleihende Verein). Die Datei wird per Modell-Event mitgelöscht. */
    public function destroy(LoanReturnPhoto $photo): RedirectResponse
    {
        Gate::authorize('decide', $photo->loanRequest);
        $photo->delete();

        return back()->with('flash', 'Foto gelöscht.');
    }

    /** Für die Ausleihenden über den geheimen Link ihrer Anfrage. */
    public function forBorrower(string $token, LoanReturnPhoto $photo): StreamedResponse
    {
        $loan = LoanRequest::where('token', $token)->firstOrFail();
        abort_unless($photo->loan_request_id === $loan->id, 404);

        return $this->serve($photo);
    }

    private function serve(LoanReturnPhoto $photo): StreamedResponse
    {
        $disk = Storage::disk(LoanReturnPhoto::DISK);
        abort_unless($disk->exists($photo->path), 404);

        return $disk->response($photo->path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
