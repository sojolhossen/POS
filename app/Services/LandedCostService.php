<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockBalance;

class LandedCostService
{
    /**
     * Calculate allocated additional costs and landed unit costs for received line items.
     * Proportional to purchase value basis.
     *
     * @param array $items Array of ['product_variant_id', 'quantity_accepted', 'unit_cost']
     * @param float $totalAdditionalCosts
     * @return array
     */
    public function calculateLineLandedCosts(array $items, float $totalAdditionalCosts): array
    {
        $totalPurchaseValue = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity_accepted'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? 0.0);
            $totalPurchaseValue += ($qty * $cost);
        }

        $result = [];
        foreach ($items as $item) {
            $qty = (int) ($item['quantity_accepted'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0.0);
            $itemValue = $qty * $unitCost;

            if ($totalPurchaseValue > 0 && $qty > 0) {
                $allocatedAdditional = ($itemValue / $totalPurchaseValue) * $totalAdditionalCosts;
                $additionalPerUnit = $allocatedAdditional / $qty;
                $landedUnitCost = $unitCost + $additionalPerUnit;
            } else {
                $allocatedAdditional = 0.0;
                $landedUnitCost = $unitCost;
            }

            $result[] = [
                'product_variant_id' => $item['product_variant_id'],
                'quantity_accepted' => $qty,
                'unit_cost' => round($unitCost, 2),
                'allocated_additional_cost' => round($allocatedAdditional, 2),
                'landed_unit_cost' => round($landedUnitCost, 2),
            ];
        }

        return $result;
    }

    /**
     * Update the Weighted Average Cost (WAC) on a ProductVariant upon inventory receipt.
     * Formula:
     * WAC_new = ((CurrentTotalStock * CurrentWAC) + (NewQty * NewLandedCost)) / (CurrentTotalStock + NewQty)
     */
    public function updateWeightedAverageCost(ProductVariant $variant, int $newReceivedQty, float $newLandedUnitCost): float
    {
        // Calculate current total stock across all stores for this variant
        $currentTotalStock = (int) StockBalance::where('product_variant_id', $variant->id)->sum('quantity_on_hand');
        $currentWac = (float) ($variant->weighted_average_cost > 0 ? $variant->weighted_average_cost : $variant->purchase_price);

        if ($currentTotalStock + $newReceivedQty <= 0) {
            return $currentWac;
        }

        $existingInventoryValue = max(0, $currentTotalStock) * $currentWac;
        $newInventoryValue = $newReceivedQty * $newLandedUnitCost;

        $newWac = ($existingInventoryValue + $newInventoryValue) / ($currentTotalStock + $newReceivedQty);
        $roundedWac = round($newWac, 2);

        $variant->update([
            'weighted_average_cost' => $roundedWac,
        ]);

        return $roundedWac;
    }
}
