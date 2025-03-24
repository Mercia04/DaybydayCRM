<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentApiController extends Controller
{
    /**
     * Récupérer la liste de tous les paiements
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $payments = Payment::with('invoice')->get();
        
        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Liste des paiements récupérée avec succès'
        ]);
    }

    /**
     * Récupérer les détails d'un paiement spécifique
     *
     * @param string $externalId
     * @return JsonResponse
     */
    public function show(string $externalId): JsonResponse
    {
        $payment = Payment::with('invoice')->where('external_id', $externalId)->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Détails du paiement récupérés avec succès'
        ]);
    }
}
