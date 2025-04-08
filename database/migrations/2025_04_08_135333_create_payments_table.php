<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('payments', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
			$table->decimal('amount', 10, 2);
			$table->timestamp('payment_date');
			$table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer']);
			$table->string('transaction_id', 100)->nullable();
			$table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
			$table->string('invoice_number', 50)->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('payments');
	}
};
