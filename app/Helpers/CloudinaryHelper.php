<?php

namespace App\Helpers;

use Cloudinary\Cloudinary;

class CloudinaryHelper
{
	public static function uploadImage($filePath, $folder = 'images')
	{
		$cloudinary = new Cloudinary([
			'cloud' => [
				'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
				'api_key'    => env('CLOUDINARY_KEY'),
				'api_secret' => env('CLOUDINARY_SECRET'),
			]
		]);

		$result = $cloudinary->uploadApi()->upload($filePath, [
			'folder' => $folder,
			'resource_type' => 'auto'
		]);

		return $result['secure_url']; // Đường dẫn ảnh có thể dùng để hiển thị
	}
}
