<?php

namespace App\Http\Controllers;

use App\Helpers\CloudinaryHelper;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArtistController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Artist::query()->with(['songs.artists']);

		// Filter by name
		if ($request->has('name')) {
			$query->where('name', 'ilike', '%' . $request->name . '%');
		}

		// Include songs count
		$query->withCount('songs');

		// Pagination
		$perPage = $request->has('per_page') ? (int)$request->per_page : 15;
		$artists = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $artists
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:100|unique:artists',
			'biography' => 'nullable|string',
			'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Upload image to Cloudinary
		$image = $request->file('image');
		$imageUrl = CloudinaryHelper::uploadImage($image->getRealPath());

		$artistData = [
			'name' => $request->name,
			'biography' => $request->biography,
			'image_url' => $imageUrl,
		];

		$artist = Artist::create($artistData);

		return response()->json([
			'success' => true,
			'data' => $artist
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		$artist = Artist::with(['songs.artists', 'songs.album', 'albums'])
			->withCount(['songs', 'albums'])
			->find($id);

		if (!$artist) {
			return response()->json([
				'success' => false,
				'message' => 'Artist not found'
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => $artist
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		$artist = Artist::find($id);

		if (!$artist) {
			return response()->json([
				'success' => false,
				'message' => 'Artist not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'name' => 'sometimes|string|max:100|unique:artists,name,' . $artist->id,
			'biography' => 'nullable|string',
			'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $request->only(['name', 'biography']);

		// Nếu có ảnh mới
		if ($request->hasFile('image')) {
			$image = $request->file('image');
			$imageUrl = CloudinaryHelper::uploadImage($image->getRealPath());
			$data['image_url'] = $imageUrl;
		}

		$artist->update($data);

		return response()->json([
			'success' => true,
			'data' => $artist->fresh()
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$artist = Artist::find($id);

		if (!$artist) {
			return response()->json([
				'success' => false,
				'message' => 'Artist not found'
			], 404);
		}

		// Check if artist has songs or albums
		if ($artist->songs()->count() > 0 || $artist->albums()->count() > 0) {
			return response()->json([
				'success' => false,
				'message' => 'Cannot delete artist with existing songs or albums'
			], 400);
		}

		$artist->delete();

		return response()->json([
			'success' => true,
			'message' => 'Artist deleted successfully'
		]);
	}
}
