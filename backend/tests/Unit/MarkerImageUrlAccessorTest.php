<?php

namespace Tests\Unit;

use App\Models\MarkerImage;
use Tests\TestCase;

class MarkerImageUrlAccessorTest extends TestCase
{
    public function test_public_disk_returns_relative_storage_url_for_path_value(): void
    {
        config(['filesystems.default' => 'public']);

        $image = new MarkerImage(['image_url' => 'markers/test.jpg']);

        $this->assertSame('/storage/markers/test.jpg', $image->image_url);
    }

    public function test_public_disk_normalizes_localhost_absolute_url_to_relative_storage_url(): void
    {
        config(['filesystems.default' => 'public']);

        $image = new MarkerImage([
            'image_url' => 'http://localhost:8080/storage/markers/test.jpg',
        ]);

        $this->assertSame('/storage/markers/test.jpg', $image->image_url);
    }
}
