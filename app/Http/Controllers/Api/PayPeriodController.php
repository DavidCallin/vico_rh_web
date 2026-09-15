<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayPeriodResource;
use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayPeriodController extends Controller
{
    /**
     * Liste des périodes de paie
     */
    public function index(Request $request)
    {
        $query = PayPeriod::with('closedByUser');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $periods = $query->orderBy('year', 'desc')
                        ->orderBy('month', 'desc')
                        ->paginate(12);

        return PayPeriodResource::collection($periods);
    }

    /**
     * Afficher une période
     */
    public function show(PayPeriod $payPeriod)
    {
        $payPeriod->load(['closedByUser', 'payslips.employee']);
        return new PayPeriodResource($payPeriod);
    }

    /**
     * Ouvrir une nouvelle période de paie
     */
    public function open(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        // Vérifier qu'elle n'existe pas déjà
        $exists = PayPeriod::where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette période de paie existe déjà.',
            ], 409);
        }

        $period = PayPeriod::create([
            'month' => $request->month,
            'year' => $request->year,
            'status' => 'open',
            'opening_date' => now(),
        ]);

        return new PayPeriodResource($period);
    }

    /**
     * Clôturer une période de paie
     */
    public function close(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'closed') {
            return response()->json([
                'message' => 'Cette période est déjà clôturée.',
            ], 400);
        }

        if ($payPeriod->status === 'archived') {
            return response()->json([
                'message' => 'Cette période est archivée et ne peut plus être modifiée.',
            ], 400);
        }

        $payPeriod->update([
            'status' => 'closed',
            'closing_date' => now(),
            'closure_type' => 'normal',
            'closed_by' => Auth::id(),
        ]);

        return new PayPeriodResource($payPeriod->load('closedByUser'));
    }
}