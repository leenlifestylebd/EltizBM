<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Http\Request;

class BomController extends Controller
{
    /**
     * Store a new BOM line for the given product.
     */
    public function store(Request $request, Product $product)
    {
        $bizId = auth()->user()->business_id;

        abort_if($product->business_id !== $bizId, 403);

        $validated = $request->validate([
            'material_name'       => 'required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'quantity_per_piece'  => 'required|numeric|min:0.001|max:99999',
            'unit'                => 'required|string|max:50',
            'wastage_percent'     => 'nullable|numeric|min:0|max:100',
            'color'               => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        $product->billOfMaterials()->create(array_merge($validated, [
            'business_id'    => $bizId,
            'wastage_percent'=> $validated['wastage_percent'] ?? 0,
        ]));

        return back()->with('success', 'Material added to Bill of Materials.');
    }

    /**
     * Delete a BOM line (must belong to same business).
     */
    public function destroy(Product $product, BillOfMaterial $bom)
    {
        $bizId = auth()->user()->business_id;

        abort_if($product->business_id !== $bizId, 403);
        abort_if($bom->product_id !== $product->id, 403);

        $bom->delete();

        return back()->with('success', 'Material removed.');
    }

    /**
     * Toggle active/inactive for a BOM line.
     */
    public function toggle(Product $product, BillOfMaterial $bom)
    {
        abort_if($product->business_id !== auth()->user()->business_id, 403);
        abort_if($bom->product_id !== $product->id, 403);

        $bom->update(['is_active' => !$bom->is_active]);

        return back()->with('success', 'Updated.');
    }
}
