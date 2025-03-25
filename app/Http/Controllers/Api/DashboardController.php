<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Offer;
use App\Services\Invoice\InvoiceCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller    
{
    /**
     * Récupérer toutes les données du tableau de bord
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 'year');
        
        return response()->json([
            'revenue_data' => $this->getRevenueData($period),
            'project_status_data' => $this->getProjectStatusData(),
            'conversion_rate_data' => $this->getConversionRateData($period),
            'invoice_status_data' => $this->getInvoiceStatusData(),
            'totals' => $this->getTotals()
        ]);
    }
    
    /**
     * Récupérer uniquement les données de revenus
     */
    public function revenueData(Request $request)
    {
        $period = $request->input('period', 'year');
        return response()->json($this->getRevenueData($period));
    }
    
    /**
     * Récupérer uniquement les données de statut des projets
     */
    public function projectStatusData()
    {
        return response()->json($this->getProjectStatusData());
    }
    
    /**
     * Récupérer uniquement les données de taux de conversion
     */
    public function conversionRateData(Request $request)
    {
        $period = $request->input('period', 'year');
        return response()->json($this->getConversionRateData($period));
    }
    
    /**
     * Récupérer uniquement les données de statut des factures
     */
    public function invoiceStatusData()
    {
        return response()->json($this->getInvoiceStatusData());
    }
    
    /**
     * Récupérer les totaux pour les cartes du tableau de bord
     */
    public function totals()
    {
        return response()->json($this->getTotals());
    }
    
    /**
     * Calculer les données de revenus mensuels
     */
    private function getRevenueData($period)
    {
        $now = Carbon::now();
        $startDate = $this->getStartDateForPeriod($period);
        
        $payments = Payment::select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('payment_date', '>=', $startDate)
            ->where('payment_date', '<=', $now)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        $result = $this->initializeMonthlyData($startDate, $now);
        
        foreach ($payments as $payment) {
            $key = $payment->year . '-' . str_pad($payment->month, 2, '0', STR_PAD_LEFT);
            if (isset($result['data'][$key])) {
                $result['data'][$key] = round($payment->total_amount / 100, 2); // Convertir les centimes en euros
            }
        }
        
        return [
            'labels' => $result['labels'],
            'data' => array_values($result['data']),
            'period' => $period
        ];
    }
    
    /**
     * Calculer les données de statut des projets
     */
    private function getProjectStatusData()
    {
        $statuses = Project::select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->with('status')
            ->get();
        
        $labels = [];
        $data = [];
        $colors = [
            'rgba(255, 193, 7, 0.8)', // En cours
            'rgba(40, 167, 69, 0.8)',  // Terminés
            'rgba(23, 162, 184, 0.8)', // En attente
            'rgba(220, 53, 69, 0.8)'   // Annulés
        ];
        
        foreach ($statuses as $status) {
            $labels[] = $status->status->title ?? 'Inconnu';
            $data[] = $status->total;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors
        ];
    }
    
    /**
     * Calculer les données de taux de conversion des offres
     */
    private function getConversionRateData($period)
    {
        $now = Carbon::now();
        $startDate = $this->getStartDateForPeriod($period);
        
        // Nombre total d'offres par mois
        $totalOffers = Offer::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $now)
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });
        
        // Nombre d'offres acceptées par mois
        $acceptedOffers = Offer::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $now)
            ->where('status', 'accepted') // Ajustez selon votre modèle
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });
        
        $result = $this->initializeMonthlyData($startDate, $now);
        
        foreach ($result['data'] as $key => $value) {
            $total = isset($totalOffers[$key]) ? $totalOffers[$key]->total : 0;
            $accepted = isset($acceptedOffers[$key]) ? $acceptedOffers[$key]->total : 0;
            
            $result['data'][$key] = $total > 0 ? round(($accepted / $total) * 100, 1) : 0;
        }
        
        return [
            'labels' => $result['labels'],
            'data' => array_values($result['data']),
            'period' => $period
        ];
    }
    
    /**
     * Calculer les données de statut des factures
     */
    private function getInvoiceStatusData()
    {
        // Compter les factures par statut
        $invoices = Invoice::all();
        
        $statusCounts = [
            'Payées' => 0,
            'En attente' => 0,
            'En retard' => 0,
            'Annulées' => 0
        ];
        
        foreach ($invoices as $invoice) {
            $invoiceCalculator = new InvoiceCalculator($invoice);
            $amountDue = $invoiceCalculator->getAmountDue();
            
            // Convertir l'objet Money en valeur numérique
            $amountDueValue = $amountDue->getAmount();
            
            if ($invoice->status == 'cancelled') {
                $statusCounts['Annulées']++;
            } elseif ($amountDueValue <= 0) {
                $statusCounts['Payées']++;
            } elseif ($invoice->due_at && Carbon::parse($invoice->due_at)->isPast()) {
                $statusCounts['En retard']++;
            } else {
                $statusCounts['En attente']++;
            }
        }
        
        return [
            'labels' => array_keys($statusCounts),
            'data' => array_values($statusCounts),
            'colors' => [
                'rgba(40, 167, 69, 0.8)',  // Payées
                'rgba(255, 193, 7, 0.8)',  // En attente
                'rgba(220, 53, 69, 0.8)',  // En retard
                'rgba(108, 117, 125, 0.8)' // Annulées
            ]
        ];
    }
    
    /**
     * Récupérer les totaux pour les cartes du tableau de bord
     */
    private function getTotals()
    {
        return [
            'totalProjects' => Project::count(),
            'totalInvoices' => Invoice::count(),
            'totalOffers' => Offer::count(),
            'totalPayments' => Payment::count(),
            'totalTasks' => Task::count(),
            'totalRevenue' => Payment::sum('amount') / 100, // Convertir les centimes en euros
        ];
    }
    
    /**
     * Initialiser un tableau de données mensuelles vide
     */
    private function initializeMonthlyData($startDate, $endDate)
    {
        $labels = [];
        $data = [];
        
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            $key = $current->format('Y-m');
            $labels[] = $current->format('M');
            $data[$key] = 0;
            
            $current->addMonth();
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Déterminer la date de début en fonction de la période
     */
    private function getStartDateForPeriod($period)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'month':
                return $now->copy()->startOfMonth();
            case 'quarter':
                return $now->copy()->startOfQuarter();
            case 'year':
            default:
                return $now->copy()->startOfYear();
        }
    }
}
