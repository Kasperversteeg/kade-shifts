<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        return $user;
    }

    public function test_admin_can_upload_document_for_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/documents", [
            'file' => UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf'),
            'type' => 'contract_signed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'contract_signed',
            'original_filename' => 'contract.pdf',
            'uploaded_by' => $admin->id,
        ]);
    }

    public function test_user_can_upload_own_id_document(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/my-documents', [
            'file' => UploadedFile::fake()->create('id-front.jpg', 100, 'image/jpeg'),
            'type' => 'id_front',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'id_front',
            'original_filename' => 'id-front.jpg',
            'uploaded_by' => $user->id,
        ]);
    }

    public function test_user_cannot_upload_restricted_types(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/my-documents', [
            'file' => UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf'),
            'type' => 'contract_signed',
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseMissing('documents', [
            'documentable_id' => $user->id,
            'type' => 'contract_signed',
        ]);
    }

    public function test_user_cannot_upload_other_type(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/my-documents', [
            'file' => UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf'),
            'type' => 'other',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_user_cannot_download_another_users_document(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $document = Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user1->id,
            'type' => 'id_front',
            'original_filename' => 'id.jpg',
            'path' => 'documents/1/id.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $user1->id,
        ]);

        Storage::disk('local')->put('documents/1/id.jpg', 'fake-content');

        $response = $this->actingAs($user2)->get("/documents/{$document->id}/download");

        $response->assertStatus(403);
    }

    public function test_admin_can_download_any_document(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $document = Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'id_front',
            'original_filename' => 'id.jpg',
            'path' => 'documents/1/id.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('local')->put('documents/1/id.jpg', 'fake-content');

        $response = $this->actingAs($admin)->get("/documents/{$document->id}/download");

        $response->assertStatus(200);
    }

    public function test_user_can_download_own_document(): void
    {
        $user = $this->createUser();

        $document = Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'id_front',
            'original_filename' => 'id.jpg',
            'path' => 'documents/1/id.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('local')->put('documents/1/id.jpg', 'fake-content');

        $response = $this->actingAs($user)->get("/documents/{$document->id}/download");

        $response->assertStatus(200);
    }

    public function test_admin_can_delete_document(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $document = Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'contract_signed',
            'original_filename' => 'contract.pdf',
            'path' => 'documents/1/contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/documents/{$document->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_file_validation_rejects_oversized_files(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/documents", [
            'file' => UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf'),
            'type' => 'contract_signed',
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_file_validation_rejects_invalid_mime_types(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/documents", [
            'file' => UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload'),
            'type' => 'other',
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_regular_user_cannot_upload_for_another_user(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $response = $this->actingAs($user)->post("/admin/users/{$otherUser->id}/documents", [
            'file' => UploadedFile::fake()->create('id.jpg', 100, 'image/jpeg'),
            'type' => 'id_front',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_document(): void
    {
        $user = $this->createUser();

        $document = Document::create([
            'documentable_type' => User::class,
            'documentable_id' => $user->id,
            'type' => 'id_front',
            'original_filename' => 'id.jpg',
            'path' => 'documents/1/id.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/admin/documents/{$document->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'deleted_at' => null]);
    }
}
