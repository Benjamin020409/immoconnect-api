<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        Configuration::instance(env('CLOUDINARY_URL'));
        $this->cloudinary = new Cloudinary();
    }

    public function upload(string $filePath, string $folder = 'properties'): array
    {
        $result = $this->cloudinary->uploadApi()->upload($filePath, [
            'folder' => $folder,
        ]);

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }
}