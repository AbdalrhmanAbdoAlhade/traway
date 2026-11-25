<?php

namespace App\Services\Finance;

use App\Exceptions\BusinessRuleException;
use App\Models\PartnerLedgerEntry;
use App\Models\Settlement;
use App\Models\SettlementItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /**
     * أنواع القيود التي تدخل التسوية.
     *
     * "payout" مستبعد عن قصد: هو سحب الشريك لفلوس اتسوّت قبل كده،
     * ومش دخل جديد — لو دخل، هيحصل ازدواج في الحساب.
     * "adjustment" بيتضمن لأن إشارته حرة (له/عليه).
     */
    private const SETTLEABLE_TYPES = [
        PartnerLedgerEntry::TYPE_SALE,
        PartnerLedgerEntry::TYPE_COMMISSION,
        PartnerLedgerEntry::TYPE_REFUND,
        PartnerLedgerEntry::TYPE_GATEWAY_FEE,
        PartnerLedgerEntry::TYPE_CHARGEBACK,
        PartnerLedgerEntry::TYPE_ADJUSTMENT,
    ];

    /**
     * نطاق الـ 15 يوم الحالي (1–15 أو 16–آخر الشهر).
     *
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    public function currentPeriod(?Carbon $reference = null): array
    {
        $ref = $reference ? $reference->copy() : now();

        if ($ref->day <= 15) {
            $start = $ref->copy()->startOfMonth();
            $end   = $ref->copy()->day(15)->endOfDay();
            $label = $start->format('Y-m') . ' (1–15)';
        } else {
            $start = $ref->copy()->day(16)->startOfDay();
            $end   = $ref->copy()->endOfMonth();
            $label = $start->format('Y-m') . ' (16–end)';
        }

        return ['start' => $start, 'end' => $end, 'label' => $label];
    }

    /**
     * توليد تسوية لمنظمة واحدة عن فترة محددة.
     *
     * - لو التسوية موجودة بالفعل بنفس (org + period) → تُرجع كما هي (idempotent).
     * - كل القيود المرشحة تُربط بـ settlement_id في نفس الـ transaction.
     * - القيود الـ append-only (model booted() يمنع update) → نستخدم mass update
     *   على query builder، وهو لا يطلق model events.
     */
    public function generateForOrganization(int $organizationId, Carbon $periodStart, Carbon $periodEnd): Settlement
    {
        if ($periodStart->gt($periodEnd)) {
            throw new BusinessRuleException('period_start must be before or equal to period_end.');
        }

        return DB::transaction(function () use ($organizationId, $periodStart, $periodEnd) {
            $existing = Settlement::query()
                ->where('organization_id', $organizationId)
                ->where('period_start', $periodStart->toDateString())
                ->where('period_end', $periodEnd->toDateString())
                ->first();

            if ($existing) {
                return $existing->load('items');
            }

            $entries = PartnerLedgerEntry::query()
                ->where('organization_id', $organizationId)
                ->whereNull('settlement_id')
                ->whereIn('type', self::SETTLEABLE_TYPES)
                ->whereBetween('created_at', [
                    $periodStart->copy()->startOfDay(),
                    $periodEnd->copy()->endOfDay(),
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                throw new BusinessRuleException(
                    'No settleable ledger entries for the given organization and period.'
                );
            }

            // amount_cents مُوقَّع:
            //   sale         > 0
            //   commission   < 0   (خصم المنصة)
            //   refund       < 0
            //   gateway_fee  < 0
            //   chargeback   < 0
            //   adjustment  حرة الإشارة
            //   payout       < 0   (مستبعد)
            $net        = (int) $entries->sum('amount_cents');
            $commission = (int) abs(
                $entries
                    ->where('type', PartnerLedgerEntry::TYPE_COMMISSION)
                    ->sum('amount_cents')
            );

            // gross هنا = "الصافي قبل خصم العمولة"،
            // بحيث gross - commission = net دائمًا.
            $gross = $net + $commission;

            $settlement = Settlement::create([
                'organization_id'         => $organizationId,
                'period_start'            => $periodStart->toDateString(),
                'period_end'              => $periodEnd->toDateString(),
                'status'                  => Settlement::STATUS_PENDING,
                'gross_amount_cents'      => $gross,
                'commission_amount_cents' => $commission,
                'net_amount_cents'        => $net,
                // جدول organizations لا يحمل currency بعد؛ مثبّتة مؤقتًا.
                // عند إضافة organizations.currency استبدل السطر بـ $org->currency.
                'currency'                => 'USD',
                'entries_count'           => $entries->count(),
            ]);

            foreach ($entries as $entry) {
                SettlementItem::create([
                    'settlement_id'           => $settlement->id,
                    'partner_ledger_entry_id' => $entry->id,
                    'amount_cents'            => $entry->amount_cents,
                    'entry_type'              => $entry->type, // snapshot
                ]);

                // PartnerLedgerEntry::updating يرمي LogicException،
                // لذلك نستخدم mass update على query builder (لا يطلق model events).
                PartnerLedgerEntry::query()
                    ->where('id', $entry->id)
                    ->update(['settlement_id' => $settlement->id]);
            }

            return $settlement->fresh('items');
        });
    }

    /**
     * توليد تسويات لجميع المنظمات التي لديها قيود قابلة للتسوية في الفترة.
     *
     * @return Collection<int, Settlement|array{organization_id:int, error:string}>
     */
    public function generateForAllOrganizations(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $orgIds = PartnerLedgerEntry::query()
            ->whereNull('settlement_id')
            ->whereIn('type', self::SETTLEABLE_TYPES)
            ->whereBetween('created_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->distinct()
            ->pluck('organization_id');

        $results = collect();

        foreach ($orgIds as $orgId) {
            try {
                $results->push($this->generateForOrganization((int) $orgId, $periodStart, $periodEnd));
            } catch (BusinessRuleException $e) {
                $results->push([
                    'organization_id' => (int) $orgId,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function markProcessing(Settlement $settlement): Settlement
    {
        if ($settlement->status !== Settlement::STATUS_PENDING) {
            throw new BusinessRuleException("Cannot process a settlement in status [{$settlement->status}].");
        }

        $settlement->update([
            'status'       => Settlement::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);

        return $settlement->fresh();
    }

    public function markPaid(Settlement $settlement, ?string $notes = null): Settlement
    {
        if (! in_array($settlement->status, [Settlement::STATUS_PENDING, Settlement::STATUS_PROCESSING], true)) {
            throw new BusinessRuleException("Cannot mark as paid from status [{$settlement->status}].");
        }

        $settlement->update([
            'status'  => Settlement::STATUS_PAID,
            'paid_at' => now(),
            'notes'   => $notes ?? $settlement->notes,
        ]);

        return $settlement->fresh();
    }

    public function cancel(Settlement $settlement): Settlement
    {
        if ($settlement->status === Settlement::STATUS_PAID) {
            throw new BusinessRuleException('A paid settlement cannot be cancelled.');
        }

        return DB::transaction(function () use ($settlement) {
            // فكّ ربط القيود أولًا — mass update (لا model events).
            PartnerLedgerEntry::query()
                ->where('settlement_id', $settlement->id)
                ->update(['settlement_id' => null]);

            $settlement->items()->delete();

            $settlement->update(['status' => Settlement::STATUS_CANCELLED]);

            return $settlement->fresh();
        });
    }
}