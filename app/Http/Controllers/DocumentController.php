<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'type' => 'required|in:id_front,id_back,contract_signed,other',
        ]);

        $file = $request->file('file');
        $path = $file->store("documents/{$user->id}", 'local');

        $user->documents()->create([
            'type' => $request->type,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', __('Document uploaded successfully.'));
    }

    public function storeOwn(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'type' => 'required|in:id_front,id_back',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->store("documents/{$user->id}", 'local');

        $user->documents()->create([
            'type' => $request->type,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);

        return redirect()->back()->with('success', __('Document uploaded successfully.'));
    }

    public function download(Document $document)
    {
        $user = auth()->user();

        if (! $user->hasRole('admin') && $document->documentable_id !== $user->id) {
            abort(403);
        }

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()->back()->with('success', __('Document deleted successfully.'));
    }
}
