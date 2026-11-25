<?php

namespace App\Services\Finance;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\PartnerLedgerEntry;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Append-only writer for partner_ledger_entries.
 *
 * Idempotent عبر entry_key (unique). لا يُعدّل قيودًا موجودة.
 * يُستدعى من داخل Transaction الأب (مثل checkOut) — لا يفتح transaction بنفسه.
 *
 * التوقيع المالي (amount_cents مُوقَّع):
 *   sale        : +gross   (إجمالي المدفوع من العميل)
 *   commission  : -fee     (عمولة المنصة)
 *   صافي الشريك = sale + commission
 */
class PartnerLedgerService
{
    /**
     * تسجيل إيراد حجز مكتمل: قيد sale + قيد commission (إن وُجدت).
     * idempotent: النداء المتكرر لنفس الحجز يرجع القيود الموجودة بدون تكرار.
     *
     * @return PartnerLedgerEntry[]
     */
    public function recordBookingSale(Booking $booking, ?int $createdBy = null): array
    {
        $orgId     = (int) $booking->organization_id;
        $bookingId = (int) $booking->getKey();

        if ($orgId <= 0) {
            throw new BusinessRuleException(
                "Booking #{$bookingId} has no organization_id; cannot write ledger entry."
            );
        }

        $gross      = (int) $booking->total_cents;
        $commission = (int) $booking->commission_cents;

        // حجز بقيمة صفر: لا شيء ماليًا يُسجَّل
        if ($gross <= 0) {
            return [];
        }

        if ($commission < 0 || $commission > $gross) {
            throw new BusinessRuleException(
                "Invalid commission ({$commission}) for booking #{$bookingId} with gross {$gross}."
            );
        }

        $entries = [];

        $entries[] = $this->insertOnce([
            'organization_id' => $orgId,
            'entry_key'       => "sale:{$bookingId}",
            'type'            => PartnerLedgerEntry::TYPE_SALE,
            'amount_cents'    => $gross,
            'booking_id'      => $bookingId,
            'description'     => "Sale for booking #{$bookingId}",
            'created_by'      => $createdBy,
        ]);

        if ($commission > 0) {
            $entries[] = $this->insertOnce([
                'organization_id' => $orgId,
                'entry_key'       => "commission:{$bookingId}",
                'type'            => PartnerLedgerEntry::TYPE_COMMISSION,
                'amount_cents'    => -$commission,
                'booking_id'      => $bookingId,
                'description'     => "Platform commission for booking #{$bookingId}",
                'created_by'      => $createdBy,
            ]);
        }

        return $entries;
    }

    /**
     * إدراج قيد idempotent:
     * - إن كان entry_key موجود → يرجع الموجود.
     * - وإلا ينشئه؛ وفي حالة race condition على unique constraint يرجع الموجود.
     */
    private function insertOnce(array $data): PartnerLedgerEntry
    {
        $existing = PartnerLedgerEntry::where('entry_key', $data['entry_key'])->first();
        if ($existing) {
            return $existing;
        }

        try {
            return PartnerLedgerEntry::create($data);
        } catch (UniqueConstraintViolationException $e) {
            return PartnerLedgerEntry::where('entry_key', $data['entry_key'])->firstOrFail();
        }
    }
}