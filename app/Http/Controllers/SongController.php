<?php

namespace App\Http\Controllers;

use App\Helpers\CloudinaryHelper;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SongController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Song::with(['artists', 'album', 'genres'])->orderBy('id');

		if ($request->has('search')) {
			$searchTerm = $request->search;
			$query->where(function ($q) use ($searchTerm) {
				$q->where('title', 'ilike', '%' . $searchTerm . '%')
					->orWhereHas('artists', function ($subQuery) use ($searchTerm) {
						$subQuery->where('name', 'ilike', '%' . $searchTerm . '%');
					});
			});
		}

		// Filter by title
		if ($request->has('title')) {
			$query->where('title', 'ilike', '%' . $request->title . '%');
		}

		// Filter by artist
		if ($request->has('artist')) {
			$query->whereHas('artists', function ($q) use ($request) {
				$q->where('name', 'like', '%' . $request->artist . '%');
			});
		}

		// Filter by genre
		if ($request->has('genre')) {
			$query->whereHas('genres', function ($q) use ($request) {
				$q->where('name', $request->genre);
			});
		}

		// Pagination
		$perPage = $request->has('per_page') ? (int)$request->per_page : 10;
		$songs = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $songs
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'title' => 'required|string|max:100',
			'album_id' => 'nullable|exists:albums,id',
			'duration' => 'required|integer',
			'file' => 'required|file|mimes:mp3,wav',
			'release_date' => 'nullable|date',
			'artist_ids' => 'required|array',
			'artist_ids.*' => 'exists:artists,id',
			'genre_ids' => 'required|array',
			'genre_ids.*' => 'exists:genres,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Upload file
		$file = $request->file('file');
		$filePath = CloudinaryHelper::uploadImage($file->getRealPath(), 'songs');

		$song = Song::create([
			'title' => $request->title,
			'album_id' => $request->album_id,
			'duration' => $request->duration,
			'file_url' => $filePath,
			'release_date' => $request->release_date,
		]);

		// Attach artists and genres
		$song->artists()->attach($request->artist_ids);
		$song->genres()->attach($request->genre_ids);

		return response()->json([
			'success' => true,
			'data' => $song->load(['artists', 'genres', 'album'])
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		$song = Song::with(['artists', 'album', 'genres'])->find($id);

		if (!$song) {
			return response()->json([
				'success' => false,
				'message' => 'Song not found'
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => $song
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		$song = Song::find($id);

		if (!$song) {
			return response()->json([
				'success' => false,
				'message' => 'Song not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'title' => 'sometimes|string|max:100',
			'album_id' => 'nullable|exists:albums,id',
			'duration' => 'sometimes|integer',
			'file' => 'sometimes|file|mimes:mp3,wav',
			'release_date' => 'nullable|date',
			'artist_ids' => 'sometimes|array',
			'artist_ids.*' => 'exists:artists,id',
			'genre_ids' => 'sometimes|array',
			'genre_ids.*' => 'exists:genres,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $request->only(['title', 'album_id', 'duration', 'release_date']);

		if ($request->hasFile('file')) {
			$file = $request->file('file');
			$data['file_url'] = CloudinaryHelper::uploadImage($file->getRealPath(), 'songs');
		}

		$song->update($data);

		// Sync artists and genres if provided
		if ($request->has('artist_ids')) {
			$song->artists()->sync($request->artist_ids);
		}

		if ($request->has('genre_ids')) {
			$song->genres()->sync($request->genre_ids);
		}

		return response()->json([
			'success' => true,
			'data' => $song->fresh(['artists', 'genres', 'album'])
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$song = Song::find($id);

		if (!$song) {
			return response()->json([
				'success' => false,
				'message' => 'Song not found'
			], 404);
		}

		$song->delete();

		return response()->json([
			'success' => true,
			'message' => 'Song deleted successfully'
		]);
	}
}
