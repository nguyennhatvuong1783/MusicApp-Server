<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(Request $request)
	{
		$user = Auth::user();

		$query = $user->payments()
			->with(['subscription', 'paymentMethod'])
			->latest();

		// Lọc theo trạng thái
		if ($request->has('status')) {
			$query->where('status', $request->status);
		}

		// Phân trang
		$payments = $query->paginate($request->per_page ?? 15);

		return response()->json([
			'success' => true,
			'data' => $payments
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$user = Auth::user();

		$validator = Validator::make($request->all(), [
			'subscription_id' => 'required|exists:user_subscriptions,id',
			'payment_method_id' => 'required|exists:payment_methods,id',
			'amount' => 'required|numeric|min:0',
			'currency' => 'required|string|size:3'
		]);

		if ($validator->fails()) {
			return $this->validationError($validator);
		}

		// Xử lý thanh toán (giả lập)
		$paymentResult = $this->processPayment(
			$request->payment_method,
			$request->amount
		);

		// Tạo bản ghi thanh toán
		$payment = Payment::create([
			'user_id' => Auth::id(),
			'subscription_id' => $request->subscription_id,
			'amount' => $request->amount,
			'currency' => $request->currency,
			'payment_method' => $request->payment_method,
			'transaction_id' => $paymentResult['transaction_id'],
			'status' => $paymentResult['status'],
			'invoice_number' => 'INV-' . time()
		]);

		// Cập nhật trạng thái subscription
		if ($paymentResult['status'] === 'completed') {
			$user->update([
				'account_type' => 'premium',
			]);
		}

		return response()->json([
			'success' => $paymentResult['status'] === 'completed',
			'data' => $payment
		], 201);
	}

	// Xử lý webhook từ cổng thanh toán
	public function handleWebhook(Request $request)
	{
		$payload = $request->all();
		$signature = $request->header('X-Signature');

		if (!$this->verifyWebhookSignature($signature, $payload)) {
			return response()->json(['success' => false], 403);
		}

		// Xử lý các sự kiện khác nhau
		switch ($payload['event_type']) {
			case 'payment_succeeded':
				$this->handleSuccessfulPayment($payload);
				break;

			case 'payment_failed':
				$this->handleFailedPayment($payload);
				break;
		}

		return response()->json(['success' => true]);
	}

	// Lấy danh sách phương thức thanh toán
	public function getPaymentMethods()
	{
		$methods = Auth::user()->paymentMethods;
		return response()->json(['success' => true, 'data' => $methods]);
	}

	private function processPayment($paymentMethod, $amount)
	{
		// Giả lập xử lý thanh toán
		// Thực tế cần tích hợp với cổng thanh toán như Stripe, PayPal...
		return [
			'status' => 'completed',
			'transaction_id' => 'tx_' . uniqid()
		];
	}

	private function verifyWebhookSignature($signature, $payload)
	{
		// Xác thực chữ ký webhook
		// Thực tế cần implement theo documentation của cổng thanh toán
		return true;
	}

	private function handleSuccessfulPayment($payload)
	{
		$payment = Payment::where('transaction_id', $payload['transaction_id'])
			->firstOrFail();

		$payment->update(['status' => 'completed']);

		$subscription = $payment->subscription;
		$subscription->update([
			'status' => 'active',
			'end_date' => now()->addMonth()
		]);
	}

	private function handleFailedPayment($payload)
	{
		$payment = Payment::where('transaction_id', $payload['transaction_id'])
			->firstOrFail();

		$payment->update(['status' => 'failed']);

		$payment->subscription->update(['status' => 'expired']);
	}

	private function validationError($validator)
	{
		return response()->json([
			'success' => false,
			'errors' => $validator->errors()
		], 422);
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
		//
	}
}
