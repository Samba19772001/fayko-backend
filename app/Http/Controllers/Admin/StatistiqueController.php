<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatistiqueController extends Controller
{
    public function index(Request $request)
    {
        $dateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->date_debut)->startOfDay()
            : now()->startOfMonth();
        $dateFin = $request->filled('date_fin')
            ? Carbon::parse($request->date_fin)->endOfDay()
            : now()->endOfDay();

        $contratsCrees = Contrat::whereBetween('created_at', [$dateDebut, $dateFin])->count();

        $contratsConclus = Contrat::whereNotNull('statut')
            ->where('frais_payes', true)
            ->whereBetween('conclu_at', [$dateDebut, $dateFin])
            ->count();

        $fraisCollectes = Paiement::where('statut', 'reussi')
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->sum('montant');

        $volumePrete = Contrat::whereNotNull('statut')
            ->where('frais_payes', true)
            ->whereBetween('conclu_at', [$dateDebut, $dateFin])
            ->sum('montant');

        // Historique glissant sur 12 mois, indépendant du filtre ci-dessus —
        // donne une vue d'ensemble rapide sans avoir à changer les dates.
        $historiqueMensuel = collect(range(0, 11))->map(function ($i) {
            $mois = now()->subMonths($i)->startOfMonth();
            $finMois = $mois->copy()->endOfMonth();

            return [
                'label' => ucfirst($mois->locale('fr')->translatedFormat('F Y')),
                'contrats_crees' => Contrat::whereBetween('created_at', [$mois, $finMois])->count(),
                'contrats_conclus' => Contrat::whereNotNull('statut')->where('frais_payes', true)
                    ->whereBetween('conclu_at', [$mois, $finMois])->count(),
                'frais_collectes' => Paiement::where('statut', 'reussi')
                    ->whereBetween('created_at', [$mois, $finMois])->sum('montant'),
            ];
        })->reverse()->values();

        return view('admin.statistiques', compact(
            'dateDebut', 'dateFin', 'contratsCrees', 'contratsConclus',
            'fraisCollectes', 'volumePrete', 'historiqueMensuel'
        ));
    }
}