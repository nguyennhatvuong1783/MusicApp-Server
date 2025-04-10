<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenreController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$query = Genre::query();

		// Filter by name
		if ($request->has('name')) {
			$query->where('name', 'like', '%' . $request->name . '%');
		}

		// Pagination
		$perPage = $request->has('per_page') ? (int)$request->per_page : 10;
		$genres = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $genres
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:50|unique:genres',
			'description' => 'nullable|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$genre = Genre::create([
			'name' => $request->name,
			'description' => $request->description,
		]);

		return response()->json([
			'success' => true,
			'data' => $genre
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		$genre = Genre::find($id);

		if (!$genre) {
			return response()->json([
				'success' => false,
				'message' => 'Genre not found'
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => $genre
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		$genre = Genre::find($id);

		if (!$genre) {
			return response()->json([
				'success' => false,
				'message' => 'Genre not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'name' => 'sometimes|string|max:50|unique:genres,name,' . $genre->id,
			'description' => 'nullable|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$genre->update($request->only(['name', 'description']));

		return response()->json([
			'success' => true,
			'data' => $genre
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$genre = Genre::find($id);

		if (!$genre) {
			return response()->json([
				'success' => false,
				'message' => 'Genre not found'
			], 404);
		}

		// Check if genre is being used by any songs
		if ($genre->songs()->count() > 0) {
			return response()->json([
				'success' => false,
				'message' => 'Cannot delete genre because it is associated with songs'
			], 400);
		}

		$genre->delete();

		return response()->json([
			'success' => true,
			'message' => 'Genre deleted successfully'
		]);
	}
}
