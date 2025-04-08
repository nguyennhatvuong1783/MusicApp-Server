<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'amount',
		'payment_date',
		'payment_method',
		'transaction_id',
		'status',
		'invoice_number'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
