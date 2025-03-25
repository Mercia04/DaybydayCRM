<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Récupérer la liste de tous les projets
     */
    public function index()
    {
        // Récupérer tous les projets avec leurs relations
        $projects = Project::with(['client', 'tasks', 'documents', 'user'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Récupérer les détails d'un projet spécifique
     */
    public function show($external_id)
    {
        // Vérifier si l'utilisateur est authentifié via l'API
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 401);
        }

        // Récupérer le projet avec ses relations
        $project = Project::with(['client', 'tasks', 'documents', 'user'])
            ->where('external_id', $external_id)
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }
}
