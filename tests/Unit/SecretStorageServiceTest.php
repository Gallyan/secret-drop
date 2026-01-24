<?php

namespace Tests\Unit;

use App\Services\SecretStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecretStorageServiceTest extends TestCase
{
    private SecretStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(SecretStorageService::class);
    }

    protected function tearDown(): void
    {
        // Clean up any test files
        Storage::disk('secrets')->deleteDirectory('.');
        parent::tearDown();
    }

    public function testStoreCreatesFileOnDisk(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'test_token_'.uniqid();

        $path = $this->storage->store($token, $file);

        $this->assertEquals($token, $path);
        $this->assertTrue($this->storage->exists($path));
    }

    public function testExistsReturnsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent_file'));
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 50);
        $token = 'exists_test_'.uniqid();

        $this->storage->store($token, $file);

        $this->assertTrue($this->storage->exists($token));
    }

    public function testSizeReturnsFileSize(): void
    {
        $content = str_repeat('x', 1024);
        $file = UploadedFile::fake()->createWithContent('test.bin', $content);
        $token = 'size_test_'.uniqid();

        $this->storage->store($token, $file);

        $size = $this->storage->size($token);
        $this->assertEquals(1024, $size);
    }

    public function testDeleteRemovesFile(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'delete_test_'.uniqid();

        $this->storage->store($token, $file);
        $this->assertTrue($this->storage->exists($token));

        $result = $this->storage->delete($token);

        $this->assertTrue($result);
        $this->assertFalse($this->storage->exists($token));
    }

    public function testDeleteReturnsFalseForNonExistentFile(): void
    {
        $result = $this->storage->delete('nonexistent_file');

        $this->assertFalse($result);
    }

    public function testDownloadReturnsStreamedResponse(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'download_test_'.uniqid();

        $this->storage->store($token, $file);

        $response = $this->storage->download($token);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    public function testReadStreamReturnsResource(): void
    {
        $file = UploadedFile::fake()->create('test.bin', 100);
        $token = 'stream_test_'.uniqid();

        $this->storage->store($token, $file);

        $stream = $this->storage->readStream($token);

        $this->assertIsResource($stream);
        fclose($stream);
    }

    public function testDiskReturnsFilesystemInstance(): void
    {
        $disk = $this->storage->disk();

        $this->assertInstanceOf(\Illuminate\Contracts\Filesystem\Filesystem::class, $disk);
    }
}
