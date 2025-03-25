<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Integration;
use App\Services\Invoice\GenerateInvoiceStatus;


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
        $payment = Payment::with('invoice')->where('id', $externalId)->first();
       
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
    
    /**
     * Mettre à jour le montant d'un paiement
     *
     * @param Request $request
     * @param string $externalId
     * @return JsonResponse
     */
    public function updateAmount(Request $request, string $externalId): JsonResponse
    {
        // Validation de la requête
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
        
        // Recherche du paiement
        $payment = Payment::where('id', $externalId)->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }
        
        try {
            // Mise à jour du montant (multiplié par 100 car stocké en centimes)
            $payment->amount = $request->amount * 100;
            $payment->save();
            
            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Montant du paiement mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du montant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un paiement
     *
     * @param string $externalId
     * @return JsonResponse
     */
    public function destroy(string $externalId): JsonResponse
    {
        // Recherche du paiement
        $payment = Payment::where('id', $externalId)->first();
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }
        
        try {
            // Vérifier si une intégration de facturation est configurée
            $api = Integration::initBillingIntegration();
            
            // Si une intégration existe et que le paiement y est lié, supprimer également dans l'intégration
            if ($api && $payment->integration_payment_id) {
                $api->deletePayment($payment);
            }
            
            // Supprimer le paiement
            $payment->delete();
            
            // Mettre à jour le statut de la facture associée si elle existe
            if ($payment->invoice) {
                app(GenerateInvoiceStatus::class, ['invoice' => $payment->invoice])->createStatus();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du paiement: ' . $e->getMessage()
            ], 500);
        }
    }
}
