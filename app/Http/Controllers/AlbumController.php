<?php

namespace App\Http\Controllers;

use App\Helpers\CloudinaryHelper;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AlbumController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Album::with(['artist', 'songs', 'genre'])
			->withCount('songs');

		// Lọc theo tiêu đề
		if ($request->has('title')) {
			$query->where('title', 'like', '%' . $request->title . '%');
		}

		// Lọc theo nghệ sĩ
		if ($request->has('artist_id')) {
			$query->where('artist_id', $request->artist_id);
		}

		// Phân trang
		$albums = $query->paginate($request->per_page ?? 10);

		return response()->json([
			'success' => true,
			'data' => $albums
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$user = Auth::user();

		// Validate dữ liệu
		$validator = Validator::make($request->all(), [
			'title' => 'required|string|max:100',
			'artist_id' => 'required|exists:artists,id',
			'genre_id' => 'required|exists:genres,id',
			'release_date' => 'nullable|date',
			'description' => 'nullable|string|max:500',
			'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
			'song_ids' => 'array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return $this->validationErrorResponse($validator);
		}

		// Upload image to Cloudinary
		$image = $request->file('image');
		$imageUrl = CloudinaryHelper::uploadImage($image->getRealPath());

		// Tạo album
		$album = Album::create([
			'title' => $request->title,
			'artist_id' => $request->artist_id,
			'image_url' => $imageUrl,
			'release_date' => $request->release_date,
			'genre_id' => $request->genre_id,
			'description' => $request->description
		]);

		// Thêm bài hát
		if ($request->song_ids) {
			$album->songs()->attach($request->song_ids);
		}

		return response()->json([
			'success' => true,
			'data' => $album->load('songs')
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		$album = Album::with(['artist', 'songs.artists', 'genre'])
			->withCount('songs')
			->find($id);

		if (!$album) {
			return $this->notFoundResponse('Album');
		}

		return response()->json([
			'success' => true,
			'data' => $album
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		$album = Album::find($id);

		// Kiểm tra tồn tại
		if (!$album) return $this->notFoundResponse('Album');

		// Validate
		$validator = Validator::make($request->all(), [
			'title' => 'sometimes|string|max:100',
			'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
			'artist_id' => 'sometimes|exists:artists,id',
			'genre_id' => 'sometimes|exists:genres,id',
			'release_date' => 'sometimes|date',
			'description' => 'sometimes|string|max:500',
			'song_ids' => 'array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return $this->validationErrorResponse($validator);
		}

		// Cập nhật dữ liệu
		$data = $request->only(['title', 'release_date', 'genre_id', 'description']);

		// Xử lý ảnh
		if ($request->hasFile('image')) {
			$image = $request->file('image');
			$data['image_url'] = CloudinaryHelper::uploadImage($image->getRealPath());
		}

		$album->update($data);

		// Đồng bộ bài hát
		if ($request->has('song_ids')) {
			$album->songs()->sync($request->song_ids);
		}

		return response()->json([
			'success' => true,
			'data' => $album->fresh(['songs', 'artist'])
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$album = Album::find($id);

		if (!$album) return $this->notFoundResponse('Album');

		$album->delete();

		return response()->json([
			'success' => true,
			'message' => 'Album deleted successfully'
		]);
	}

	// Helper: Trả lỗi 404
	private function notFoundResponse($item)
	{
		return response()->json([
			'success' => false,
			'message' => "$item not found"
		], 404);
	}

	// Helper: Trả lỗi validate
	private function validationErrorResponse($validator)
	{
		return response()->json([
			'success' => false,
			'errors' => $validator->errors()
		], 422);
	}
}
