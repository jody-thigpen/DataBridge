<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ComplianceDocumentType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ComplianceDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceDocumentController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::enum(ComplianceDocumentType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'require_acknowledgment' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg'],
        ]);

        $file = $request->file('document');
        $path = $file->store("compliance/{$organization->id}", 'local');

        ComplianceDocument::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'document_type' => $validated['document_type'],
            'description' => $validated['description'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'is_active' => true,
            'require_acknowledgment' => $request->boolean('require_acknowledgment', true),
            'sort_order' => $validated['sort_order'] ?? (($organization->complianceDocuments()->max('sort_order') ?? 0) + 10),
            'uploaded_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Compliance document uploaded.');
    }

    public function update(Request $request, Organization $organization, ComplianceDocument $complianceDocument): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);
        abort_unless($complianceDocument->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::enum(ComplianceDocumentType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'require_acknowledgment' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $complianceDocument->update([
            'name' => $validated['name'],
            'document_type' => $validated['document_type'],
            'description' => $validated['description'] ?? null,
            'require_acknowledgment' => $request->boolean('require_acknowledgment'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $complianceDocument->sort_order,
        ]);

        return back()->with('status', 'Compliance document updated.');
    }

    public function destroy(Request $request, Organization $organization, ComplianceDocument $complianceDocument): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);
        abort_unless($complianceDocument->organization_id === $organization->id, 404);

        $complianceDocument->deleteStoredFile();
        $complianceDocument->delete();

        return back()->with('status', 'Compliance document removed.');
    }

    public function download(Request $request, Organization $organization, ComplianceDocument $complianceDocument): StreamedResponse
    {
        abort_unless(
            $request->user()?->hasPermission(Permission::PlatformCatalogManage)
            || $request->user()?->hasPermission(Permission::PlatformOrganizationsManage)
            || $request->user()?->hasPermission(Permission::PlatformReportOrdersView),
            403,
        );
        abort_unless($complianceDocument->organization_id === $organization->id, 404);
        abort_unless(Storage::disk($complianceDocument->disk)->exists($complianceDocument->path), 404);

        return Storage::disk($complianceDocument->disk)->download(
            $complianceDocument->path,
            $complianceDocument->original_filename,
        );
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }
}
