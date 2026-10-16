<?php

namespace App\Services;

use App\Models\GlobalSequence;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    /**
     * Generate a concurrency-safe unique transaction number.
     * Format: {TYPE}-{STORE_CODE}-{YYYYMMDD}-{6_DIGIT_SERIAL}
     * Example: INV-ST001-20261016-000001
     */
    public static function generate(string $type, string $storeCode): string
    {
        $dateStr = now()->format('Ymd');
        $cleanStoreCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $storeCode));
        $cleanType = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $type));
        $sequenceKey = "{$cleanType}_{$dateStr}";

        $serial = DB::transaction(function () use ($sequenceKey) {
            $seq = GlobalSequence::where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = GlobalSequence::create([
                    'sequence_key' => $sequenceKey,
                    'current_serial' => 1,
                ]);
                return 1;
            }

            $seq->increment('current_serial');
            return $seq->current_serial;
        });

        $paddedSerial = str_pad((string) $serial, 6, '0', STR_PAD_LEFT);
        return "{$cleanType}-{$cleanStoreCode}-{$dateStr}-{$paddedSerial}";
    }
}
