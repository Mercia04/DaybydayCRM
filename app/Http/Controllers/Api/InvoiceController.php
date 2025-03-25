<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * Get all invoices with details
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $invoices = Invoice::with([
            'client', 
            'invoiceLines', 
            'offer', 
            'payments',
            'source'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    public function show($externalId)
    {   
        $invoice = Invoice::with([
            'client',
            'invoiceLines',
            'offer',
            'payments',
            'source'
        ])->where('external_id', $externalId)->first();

        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }
}
