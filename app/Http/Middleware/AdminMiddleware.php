<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
	 */
	public function handle(Request $request, Closure $next, $ability): Response
	{
		if ($request->user()->account_type !== 'admin' || !$request->user()->tokenCan($ability)) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthorized'
			], 403);
		}

		return $next($request);
	}
}
