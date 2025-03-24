<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Api\v1\Models\Token;
use Carbon\Carbon;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token non fourni'
            ], 401);
        }
        
        $tokenModel = Token::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        if (!$tokenModel) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 401);
        }
        
        // Ajouter l'utilisateur à la requête
        $request->user = $tokenModel->user;
        
        return $next($request);
    }
}
