<?php

namespace App\Http\Controllers;

use App\Helpers\CloudinaryHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
	/**
	 * Display a listing of the resource. (for admin only)
	 */
	public function index(Request $request)
	{
		$query = User::query();

		// Filter by username or email
		if ($request->has('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('username', 'like', '%' . $search . '%')
					->orWhere('email', 'like', '%' . $search . '%');
			});
		}

		// Filter by account type
		if ($request->has('account_type')) {
			$query->where('account_type', $request->account_type);
		}

		// Pagination
		$perPage = $request->has('per_page') ? (int)$request->per_page : 15;
		$users = $query->paginate($perPage);

		return response()->json([
			'success' => true,
			'data' => $users
		]);
	}

	/**
	 * Update user profile
	 */
	public function updateProfile(Request $request)
	{
		$user = $request->user();

		$validator = Validator::make($request->all(), [
			'username' => 'sometimes|string|max:50|unique:users,username,' . $user->id,
			'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
			'phone' => 'sometimes|string|max:10|unique:users,phone,' . $user->id,
			'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $request->only(['username', 'email', 'phone']);

		// Handle image upload
		if ($request->hasFile('image')) {
			// Upload image to Cloudinary
			$image = $request->file('image');
			$data['image_url'] = CloudinaryHelper::uploadImage($image->getRealPath());
		}

		$user->update($data);

		return response()->json([
			'success' => true,
			'data' => $user->fresh()
		]);
	}

	/**
	 * Change password
	 */
	public function changePassword(Request $request)
	{
		$user = $request->user();

		$validator = Validator::make($request->all(), [
			'current_password' => 'required|string',
			'new_password' => 'required|string|min:8|confirmed',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Verify current password
		if (!Hash::check($request->current_password, $user->password)) {
			return response()->json([
				'success' => false,
				'message' => 'Current password is incorrect'
			], 401);
		}

		// Update password
		$user->update([
			'password' => Hash::make($request->new_password)
		]);

		return response()->json([
			'success' => true,
			'message' => 'Password changed successfully'
		]);
	}

	/**
	 * Upgrade to premium account
	 */
	public function upgradeToPremium(Request $request)
	{
		$user = $request->user();

		if ($user->account_type === 'premium') {
			return response()->json([
				'success' => false,
				'message' => 'Account is already premium'
			], 400);
		}

		// Logic to process payment would go here
		// For demo, we'll just upgrade the account

		$user->update([
			'account_type' => 'premium'
		]);

		return response()->json([
			'success' => true,
			'message' => 'Account upgraded to premium successfully',
			'data' => $user
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		//
	}

	/**
	 * Display the specified resource. (admin only)
	 */
	public function show(string $id)
	{
		$user = User::find($id);

		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'User not found'
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => $user
		]);
	}

	/**
	 * Update the specified resource in storage. (admin only)
	 */
	public function update(Request $request, string $id)
	{
		$user = User::find($id);

		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'User not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'username' => 'sometimes|string|max:50|unique:users,username,' . $user->id,
			'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
			'phone' => 'sometimes|string|max:10|unique:users,phone,' . $user->id,
			'account_type' => 'sometimes|in:free,premium,artist,admin',
			'is_active' => 'sometimes|boolean',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$user->update($request->all());

		return response()->json([
			'success' => true,
			'data' => $user
		]);
	}

	/**
	 * Remove the specified resource from storage. (admin only)
	 */
	public function destroy(string $id)
	{
		$user = User::find($id);

		if (!$user) {
			return response()->json([
				'success' => false,
				'message' => 'User not found'
			], 404);
		}

		// Không cho phép xóa chính mình
		if ($user->id === auth()->id()) {
			return response()->json([
				'success' => false,
				'message' => 'You cannot delete yourself'
			], 400);
		}

		$user->delete();

		return response()->json([
			'success' => true,
			'message' => 'User deleted successfully'
		]);
	}
}
