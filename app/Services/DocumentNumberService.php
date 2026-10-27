<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $key, string $prefix, ?string $period = null, int $padding = 5): string
    {
        return DB::transaction(function () use ($key, $prefix, $period, $padding) {
            DocumentSequence::query()->insertOrIgnore([
                'key' => $key,
                'prefix' => $prefix,
                'period' => $period,
                'next_number' => 1,
                'padding' => $padding,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::query()->where('key', $key)->lockForUpdate()->firstOrFail();
            $number = $sequence->next_number;

            $sequence->forceFill(['next_number' => $number + 1])->save();

            return $sequence->prefix.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }

    public function paymentReference(): string
    {
        $date = now()->format('Ymd');

        return $this->next("payment:{$date}", "PAY-{$date}-");
    }

    public function receiptNumber(): string
    {
        $date = now()->format('Ymd');

        return $this->next("receipt:{$date}", "RCP-{$date}-");
    }

    public function batchNumber(): string
    {
        $date = now()->format('Ymd');

        return $this->next("batch:{$date}", "COL-{$date}-");
    }

    public function lodgementNumber(): string
    {
        $date = now()->format('Ymd');

        return $this->next("lodgement:{$date}", "LOD-{$date}-");
    }

    public function adjustmentReference(): string
    {
        $date = now()->format('Ymd');

        return $this->next("adjustment:{$date}", "ADJ-{$date}-");
    }

    public function discrepancyReference(): string
    {
        $date = now()->format('Ymd');

        return $this->next("discrepancy:{$date}", "DSC-{$date}-");
    }

    public function treatmentReference(): string
    {
        $date = now()->format('Ymd');

        return $this->next("treatment:{$date}", "TRT-{$date}-");
    }

    public function reconciliationReference(): string
    {
        $date = now()->format('Ymd');

        return $this->next("reconciliation:{$date}", "REC-{$date}-");
    }
}
