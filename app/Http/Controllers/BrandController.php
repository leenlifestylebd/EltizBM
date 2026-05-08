<?php
namespace App\Http\Controllers;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index()
    {
        $bizId = auth()->user()->business_id;
        $brands = Brand::where('business_id', $bizId)->withCount('products')->orderBy('name')->get();
        return view('brands.index', compact('brands'));
    }
    public function store(Request $request)
    {
        $request->validate(['name'=>'required|string|max:100','logo'=>'nullable|image|mimes:jpeg,png,jpg,webp|max:1024']);
        $bizId = auth()->user()->business_id;
        $logo  = null;
        if ($request->hasFile('logo')) $logo = $request->file('logo')->store("businesses/{$bizId}/brands",'public');
        Brand::create(['business_id'=>$bizId,'name'=>$request->name,'slug'=>Str::slug($request->name).'-'.$bizId,'logo'=>$logo,'is_active'=>true]);
        return back()->with('success', 'Brand created!');
    }
    public function update(Request $request, Brand $brand)
    {
        abort_if($brand->business_id !== auth()->user()->business_id, 403);
        $brand->update(['name'=>$request->name]);
        return back()->with('success', 'Brand updated!');
    }
    public function destroy(Brand $brand)
    {
        abort_if($brand->business_id !== auth()->user()->business_id, 403);
        $brand->delete();
        return back()->with('success', 'Brand deleted!');
    }
    public function create() { return view('brands.index'); }
    public function show(Brand $brand) { return redirect()->route('brands.index'); }
    public function edit(Brand $brand) { return view('brands.index'); }
}
