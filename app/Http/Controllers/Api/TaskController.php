<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Récupérer toutes les tâches
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Récupérer toutes les tâches avec leurs relations
        $tasks = Task::with(['user', 'client', 'status', 'project'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Récupérer les détails d'une tâche spécifique
     *
     * @param string $external_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($external_id)
    {
        $task = Task::where('external_id', $external_id)
            ->with(['user', 'client', 'status', 'project', 'documents'])
            ->first();
            
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }
}
