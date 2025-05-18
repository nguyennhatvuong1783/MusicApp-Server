<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
	public function suggest(Request $request)
	{
		$text = $request->input('text');
		$response = Http::post('http://localhost:5000/suggest', ['text' => $text]);

		return response()->json($response->json());
	}
}
