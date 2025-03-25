<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Resources\OfferResource;
use App\Http\Resources\OfferCollection;

class OfferController extends Controller
{
    /**
     * Display a listing of the offers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $offers = Offer::with(['invoiceLines', 'invoice'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    /**
     * Display the specified offer.
     *
     * @param  string  $externalId
     * @return \Illuminate\Http\Response
     */
    public function show($externalId)
    {
        $offer = Offer::with(['invoiceLines', 'invoice'])
                      ->where('external_id', $externalId)
                      ->first();
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $offer
        ]);
    }
}
