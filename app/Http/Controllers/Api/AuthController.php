<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    /**
     * Authentifier un utilisateur et générer un token API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $countOffer = Offer::count();
            $totalPayments = Payment::count();
            $countProject = Project::count();
            $countInvoice = Invoice::count();
            $totalTasks = Task::count();


            $user->load('department', 'roles');
            
            // Créer un token avec Sanctum
            $token = $user->createToken('api-token');
            
            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => [
                    'token' => $token->plainTextToken,
                    'type' => 'Bearer',
                    'expiresAt' => now()->addDays(7) // Simuler une expiration pour compatibilité
                ],
                'data' => [
                    'total' => $countOffer,
                    'total_payments' => $totalPayments,
                    'total_projects' => $countProject,
                    'total_invoices' => $countInvoice,
                    'total_tasks' => $totalTasks
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Les identifiants fournis sont incorrects.'
        ], 401);
    }

    /**
     * Déconnexion (révocation du token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('department', 'roles');
        
        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Vérifier si un token est valide
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        return response()->json([
            'valid' => true
        ]);
    }
}
