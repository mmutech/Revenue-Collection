<?php

namespace App\Http\Controllers;

use App\Models\BankLodgementDocument;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LodgementDocumentController extends Controller
{
    public function __invoke(BankLodgementDocument $document): StreamedResponse
    {
        $document->loadMissing('lodgement');
        Gate::authorize('view', $document->lodgement);

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->path), 404);

        AuditLogger::log(
            'lodgement_document.downloaded',
            $document,
            [],
            ['original_name' => $document->original_name],
            null,
            'Lodgement documents',
            auth()->user(),
        );

        return $disk->download(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }
}
