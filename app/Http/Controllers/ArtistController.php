<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArtistController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Artist::query();

		// Filter by name
		if ($request->has('name')) {
			$query->where('name', 'like', '%' . $request->name . '%');
		}

		// Filter by verified status
		if ($request->has('verified')) {
			$query->where('verified', $request->verified);
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
		// Only admin or user with artist account can create artist
		if (auth()->user()->account_type !== 'admin' && auth()->user()->account_type !== 'artist') {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:100|unique:artists',
			'biography' => 'nullable|string',
			'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
			'user_id' => 'sometimes|exists:users,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Upload image
		$imagePath = $request->file('image')->store('artists', 'public');

		$artistData = [
			'name' => $request->name,
			'biography' => $request->biography,
			'image_url' => $imagePath,
			'verified' => auth()->user()->account_type === 'admin' // Auto verify if created by admin
		];

		// Associate with user if provided or use current user
		if ($request->has('user_id')) {
			$user = User::find($request->user_id);
			if ($user->account_type !== 'artist') {
				return response()->json([
					'success' => false,
					'message' => 'User must have artist account type'
				], 400);
			}
			$artistData['user_id'] = $user->id;
		} else {
			if (auth()->user()->account_type === 'artist') {
				$artistData['user_id'] = auth()->id();
			}
		}

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
		$artist = Artist::with(['songs.album', 'albums'])
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

		// Only admin or artist owner can update
		if (
			auth()->user()->account_type !== 'admin' &&
			(!auth()->user()->artist || auth()->user()->artist->id !== $artist->id)
		) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'name' => 'sometimes|string|max:100|unique:artists,name,' . $artist->id,
			'biography' => 'nullable|string',
			'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
			'verified' => 'sometimes|boolean' // Only admin can update this
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $request->only(['name', 'biography']);

		// Only admin can update verified status
		if (auth()->user()->account_type === 'admin' && $request->has('verified')) {
			$data['verified'] = $request->verified;
		}

		// Handle image upload
		if ($request->hasFile('image')) {
			// Delete old image if exists
			if ($artist->image_url) {
				Storage::disk('public')->delete($artist->image_url);
			}

			$path = $request->file('image')->store('artists', 'public');
			$data['image_url'] = $path;
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

		// Only admin can delete artists
		if (auth()->user()->account_type !== 'admin') {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized'
			], 403);
		}

		// Check if artist has songs or albums
		if ($artist->songs()->count() > 0 || $artist->albums()->count() > 0) {
			return response()->json([
				'success' => false,
				'message' => 'Cannot delete artist with existing songs or albums'
			], 400);
		}

		// Delete image if exists
		if ($artist->image_url) {
			Storage::disk('public')->delete($artist->image_url);
		}

		$artist->delete();

		return response()->json([
			'success' => true,
			'message' => 'Artist deleted successfully'
		]);
	}
}
