<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HistoryController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$user = Auth::user();

		$query = $user->history()
			->with(['song.artists', 'song.album'])
			->orderBy('played_at', 'desc');

		// Lọc theo khoảng thời gian
		if ($request->has('start_date') && $request->has('end_date')) {
			$query->whereBetween('played_at', [
				$request->start_date,
				$request->end_date
			]);
		}

		// Phân trang
		$perPage = $request->per_page ?? 20;
		$history = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $history
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'song_id' => 'required|exists:songs,id',
			'progress' => 'sometimes|integer|min:0'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$song = Song::find($request->song_id);

		$history = History::create([
			'user_id' => Auth::id(),
			'song_id' => $song->id,
			'progress' => $request->progress ?? 0,
			'played_at' => now()
		]);

		return response()->json([
			'success' => true,
			'data' => $history->load('song')
		], 201);
	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		$history = History::where('user_id', Auth::id())->find($id);

		if (!$history) {
			return response()->json([
				'success' => false,
				'message' => 'History item not found'
			], 404);
		}

		$history->delete();

		return response()->json([
			'success' => true,
			'message' => 'History item deleted'
		]);
	}

	// Xóa toàn bộ lịch sử
	public function clear()
	{
		Auth::user()->history()->delete();

		return response()->json([
			'success' => true,
			'message' => 'All history cleared'
		]);
	}

	// Cập nhật tiến độ nghe
	public function updateProgress(Request $request, $id)
	{
		$history = History::where('user_id', Auth::id())->find($id);

		if (!$history) {
			return response()->json([
				'success' => false,
				'message' => 'History item not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'progress' => 'required|integer|min:0|max:' . $history->song->duration
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$history->update([
			'progress' => $request->progress,
			'played_at' => now()
		]);

		return response()->json([
			'success' => true,
			'data' => $history
		]);
	}
}
