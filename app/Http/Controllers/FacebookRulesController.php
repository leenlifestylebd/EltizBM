<?php

namespace App\Http\Controllers;

use App\Models\BusinessAutomation;
use App\Models\FacebookProductMapping;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacebookRulesController extends Controller
{
    public function index(Request $r)
    {
        $business = $r->user()->business_id;

        return response()->view('settings.facebook-rules', ['rules' => FacebookProductMapping::with('product')->where('business_id', $business)->latest()->get(), 'products' => Product::where('business_id', $business)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku'])])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $r)
    {
        $business = $r->user()->business_id;
        $d = $r->validate(['source_type' => ['required', Rule::in(['ad', 'post'])], 'external_id' => 'required|string|max:100|regex:/^\d+(?:_\d+)?$/D', 'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('business_id', $business)->where('is_active', true)], 'auto_reply' => 'nullable|boolean']);
        $c = BusinessAutomation::where('business_id', $business)->first();
        abort_unless(! empty($c?->settings['facebook_page_id']), 422, 'Connect a Facebook Page first.');
        FacebookProductMapping::updateOrCreate(['business_id' => $business, 'page_id' => $c->settings['facebook_page_id'], 'source_type' => $d['source_type'], 'external_id' => $d['external_id']], ['product_id' => $d['product_id'], 'auto_reply' => $r->boolean('auto_reply')]);

        return back()->with('success', 'Product rule saved. Automatic replies are limited to simple price and availability questions.');
    }

    public function destroy(Request $r, int $id)
    {
        FacebookProductMapping::where('business_id', $r->user()->business_id)->whereKey($id)->firstOrFail()->delete();

        return back()->with('success','Product rule removed.');
    }
}
