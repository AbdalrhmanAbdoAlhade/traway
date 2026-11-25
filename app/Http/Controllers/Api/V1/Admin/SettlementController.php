<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(private SettlementService $settlements)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'           => ['nullable', 'string', 'in:pending,processing,paid,failed,cancelled'],
            'organization_id'  => ['nullable', 'integer', 'exists:organizations,id'],
            'period_start'     => ['nullable', 'date'],
            'period_end'       => ['nullable', 'date'],
            'per_page'         => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Settlement::query()->with('organization:id,name,type');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['organization_id'])) {
            $query->where('organization_id', $validated['organization_id']);
        }
        if (! empty($validated['period_start'])) {
            $query->where('period_start', '>=', $validated['period_start']);
        }
        if (! empty($validated['period_end'])) {
            $query->where('period_end', '<=', $validated['period_end']);
        }

        $paginator = $query->orderByDesc('period_end')->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($paginator);
    }

    public function show(Settlement $settlement): JsonResponse
    {
        $settlement->load([
            'organization:id,name,type',
            'items.ledgerEntry',
        ]);

        return response()->json(['data' => $settlement]);
    }

    public function currentPeriod(): JsonResponse
    {
        $period = $this->settlements->currentPeriod();

        return response()->json([
            'data' => [
                'period_start' => $period['start']->toDateString(),
                'period_end'   => $period['end']->toDateString(),
                'label'        => $period['label'],
            ],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'period_start'    => ['required', 'date'],
            'period_end'      => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $start = Carbon::parse($validated['period_start']);
        $end   = Carbon::parse($validated['period_end']);

        if (! empty($validated['organization_id'])) {
            $settlement = $this->settlements->generateForOrganization(
                (int) $validated['organization_id'],
                $start,
                $end
            );

            return response()->json(['data' => $settlement], 201);
        }

        $results = $this->settlements->generateForAllOrganizations($start, $end);

        return response()->json([
            'generated' => $results->whereInstanceOf(Settlement::class)->count(),
            'failed'    => $results->whereNotInstanceOf(Settlement::class)->count(),
            'data'      => $results->values(),
        ], 201);
    }

    public function markPaid(Request $request, Settlement $settlement): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement = $this->settlements->markPaid($settlement, $validated['notes'] ?? null);

        return response()->json(['data' => $settlement]);
    }

    public function cancel(Settlement $settlement): JsonResponse
    {
        $settlement = $this->settlements->cancel($settlement);

        return response()->json(['data' => $settlement]);
    }
}