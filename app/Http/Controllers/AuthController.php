<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
	/**
	 * Đăng ký tài khoản mới
	 */
	public function register(Request $request)
	{
		// Validate dữ liệu gửi lên
		$validator = Validator::make($request->all(), [
			'username' => 'required|string|max:50|unique:users',
			'email' => 'required|string|email|max:100|unique:users',
			'password' => [
				'required',
				'confirmed',
				Password::min(8)
					->letters()
					->mixedCase()
					->numbers()
					->symbols()
			],
			'phone' => ['required', 'unique:users', 'phone:VN']
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Tạo user
		$user = User::create([
			'username' => $request->username,
			'email' => $request->email,
			'password' => Hash::make($request->password),
			'phone' => $request->phone,
			'account_type' => 'free', // Mặc định là tài khoản free
			'is_active' => true // Mặc định là tài khoản đang hoạt động
		]);

		// Xác định quyền hạn
		$abilities = ($user->account_type === 'admin') ? ['admin'] : ['user'];

		// Tạo token API
		$token = $user->createToken('auth_token', $abilities)->plainTextToken;
		$expiresAt = now()->addMinutes(60);

		return response()->json([
			'success' => true,
			'message' => 'User registered successfully',
			'data' => [
				'user' => $user,
				'access_token' => $token,
				'expires_at' => $expiresAt->toDateTimeString(),
				'token_type' => 'Bearer'
			]
		], 201);
	}

	/**
	 * Đăng nhập
	 */
	public function login(Request $request)
	{
		// Kiểm tra dữ liệu đầu vào
		$validator = Validator::make($request->all(), [
			'email' => 'required_without:username|email',
			'username' => 'required_without:email|string',
			'password' => 'required|string'
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Xác định trường dùng để đăng nhập (email hoặc username)
		$credentials = $request->only(['password']);
		$loginField = $request->email ? 'email' : 'username';
		$credentials[$loginField] = $request->$loginField;

		// Thử đăng nhập
		if (!Auth::attempt($credentials)) {
			return response()->json([
				'success' => false,
				'error' => 'Invalid login credentials'
			], 401);
		}

		// Lấy user đăng nhập
		$user = User::where($loginField, $request->$loginField)->firstOrFail();
		// Xác định quyền hạn
		$abilities = ($user->account_type === 'admin') ? ['admin'] : ['user'];
		// Tạo token
		$token = $user->createToken('auth_token', $abilities)->plainTextToken;
		$expiresAt = now()->addMinutes(60);

		return response()->json([
			'success' => true,
			'message' => 'Login successful',
			'data' => [
				'user' => $user,
				'access_token' => $token,
				'expires_at' => $expiresAt->toDateTimeString(),
				'token_type' => 'Bearer'
			]
		]);
	}

	/**
	 * Đăng xuất
	 */
	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();

		return response()->json([
			'success' => true,
			'message' => 'Logged out successfully'
		]);
	}

	/**
	 * Lấy thông tin user hiện tại
	 */
	public function me(Request $request)
	{
		return response()->json([
			'success' => true,
			'data' => $request->user()
		]);
	}

	/**
	 * Refresh token
	 */
	public function refresh(Request $request)
	{
		$user = $request->user();
		$user->currentAccessToken()->delete();
		$abilities = ($user->account_type === 'admin') ? ['admin'] : ['user'];
		$newToken = $user->createToken('auth_token', $abilities)->plainTextToken;
		$expiresAt = now()->addMinutes(60);

		return response()->json([
			'success' => true,
			'data' => [
				'access_token' => $newToken,
				'expires_at' => $expiresAt->toDateTimeString(),
				'token_type' => 'Bearer'
			]
		]);
	}
}
