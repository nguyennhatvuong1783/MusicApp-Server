<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PlaylistController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$user = auth()->user();

		$query = $user->playlists();

		// Filter by title
		if ($request->has('title')) {
			$query->where('title', 'like', '%' . $request->title . '%');
		}

		// Include songs count
		$query->withCount('songs');

		// Pagination
		$perPage = $request->has('per_page') ? (int)$request->per_page : 10;
		$playlists = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $playlists
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'title' => 'required|string|max:100',
			'description' => 'nullable|string',
			'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
			'is_public' => 'sometimes|boolean',
			'song_ids' => 'sometimes|array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = [
			'user_id' => auth()->id(),
			'title' => $request->title,
			'description' => $request->description,
			'is_public' => $request->boolean('is_public', true)
		];

		// Handle image upload
		if ($request->hasFile('image')) {
			$path = $request->file('image')->store('playlists', 'public');
			$data['image_url'] = $path;
		}

		$playlist = Playlist::create($data);

		// Attach songs if provided
		if ($request->has('song_ids')) {
			$playlist->songs()->attach($request->song_ids);
		}

		return response()->json([
			'success' => true,
			'data' => $playlist->load('songs')
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		$playlist = Playlist::with(['songs.artists', 'user'])
			->withCount('songs')
			->find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		// Check if playlist is private and doesn't belong to current user
		if (!$playlist->is_public && $playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to view this playlist'
			], 403);
		}

		return response()->json([
			'success' => true,
			'data' => $playlist
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		$playlist = Playlist::find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		// Check if current user owns the playlist
		if ($playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to update this playlist'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'title' => 'sometimes|string|max:100',
			'description' => 'nullable|string',
			'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
			'is_public' => 'sometimes|boolean',
			'song_ids' => 'sometimes|array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $request->only(['title', 'description', 'is_public']);

		// Handle image upload
		if ($request->hasFile('image')) {
			// Delete old image if exists
			if ($playlist->image_url) {
				Storage::disk('public')->delete($playlist->image_url);
			}

			$path = $request->file('image')->store('playlists', 'public');
			$data['image_url'] = $path;
		}

		$playlist->update($data);

		// Sync songs if provided
		if ($request->has('song_ids')) {
			$playlist->songs()->sync($request->song_ids);
		}

		return response()->json([
			'success' => true,
			'data' => $playlist->fresh(['songs', 'user'])
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$playlist = Playlist::find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		// Check if current user owns the playlist
		if ($playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to delete this playlist'
			], 403);
		}

		// Delete image if exists
		if ($playlist->image_url) {
			Storage::disk('public')->delete($playlist->image_url);
		}

		$playlist->delete();

		return response()->json([
			'success' => true,
			'message' => 'Playlist deleted successfully'
		]);
	}

	/**
	 * Add songs to playlist.
	 */
	public function addSongs(Request $request, $id)
	{
		$playlist = Playlist::find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		if ($playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to modify this playlist'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'song_ids' => 'required|array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Get current max position
		$maxPosition = $playlist->songs()->max('position') ?? 0;

		// Prepare data with positions
		$songsToAdd = [];
		foreach ($request->song_ids as $songId) {
			$songsToAdd[$songId] = ['position' => ++$maxPosition];
		}

		$playlist->songs()->attach($songsToAdd);

		return response()->json([
			'success' => true,
			'message' => 'Songs added to playlist successfully',
			'data' => $playlist->fresh('songs')
		]);
	}

	/**
	 * Remove songs from playlist.
	 */
	public function removeSongs(Request $request, $id)
	{
		$playlist = Playlist::find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		if ($playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to modify this playlist'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'song_ids' => 'required|array',
			'song_ids.*' => 'exists:songs,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$playlist->songs()->detach($request->song_ids);

		return response()->json([
			'success' => true,
			'message' => 'Songs removed from playlist successfully',
			'data' => $playlist->fresh('songs')
		]);
	}

	/**
	 * Reorder songs in playlist.
	 */
	public function reorderSongs(Request $request, $id)
	{
		$playlist = Playlist::find($id);

		if (!$playlist) {
			return response()->json([
				'success' => false,
				'message' => 'Playlist not found'
			], 404);
		}

		if ($playlist->user_id !== auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized to modify this playlist'
			], 403);
		}

		$validator = Validator::make($request->all(), [
			'song_order' => 'required|array',
			'song_order.*.song_id' => 'required|exists:songs,id',
			'song_order.*.position' => 'required|integer|min:1'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Update each song position
		foreach ($request->song_order as $order) {
			$playlist->songs()->updateExistingPivot($order['song_id'], [
				'position' => $order['position']
			]);
		}

		return response()->json([
			'success' => true,
			'message' => 'Playlist songs reordered successfully',
			'data' => $playlist->fresh('songs')
		]);
	}
}
