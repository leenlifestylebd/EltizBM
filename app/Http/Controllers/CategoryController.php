<?php
namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $bizId = auth()->user()->business_id;
        $categories = Category::where('business_id', $bizId)->withCount('products')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }
    public function store(Request $request)
    {
        $request->validate(['name'=>'required|string|max:100','image'=>'nullable|image|mimes:jpeg,png,jpg,webp|max:1024']);
        $bizId = auth()->user()->business_id;
        $image = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image')->store("businesses/{$bizId}/categories", 'public');
        }
        Category::create(['business_id'=>$bizId,'parent_id'=>$request->parent_id?:null,'name'=>$request->name,'slug'=>Str::slug($request->name).'-'.$bizId,'image'=>$image,'description'=>$request->description,'is_active'=>true]);
        return back()->with('success', 'Category created!');
    }
    public function update(Request $request, Category $category)
    {
        abort_if($category->business_id !== auth()->user()->business_id, 403);
        $request->validate(['name'=>'required|string|max:100']);
        $bizId = auth()->user()->business_id;
        if ($request->hasFile('image')) {
            if ($category->image) Storage::disk('public')->delete($category->image);
            $category->image = $request->file('image')->store("businesses/{$bizId}/categories", 'public');
        }
        $category->update(['name'=>$request->name,'description'=>$request->description,'parent_id'=>$request->parent_id?:null,'is_active'=>$request->boolean('is_active',true)]);
        return back()->with('success', 'Category updated!');
    }
    public function destroy(Category $category)
    {
        abort_if($category->business_id !== auth()->user()->business_id, 403);
        // products_count is only set by withCount() on the index query — reading it
        // here was always null, so the guard never fired and categories could be
        // deleted out from under their products. Count for real.
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products.');
        }
        if ($category->image) Storage::disk('public')->delete($category->image);
        $category->delete();
        return back()->with('success', 'Category deleted!');
    }
    public function create() { return view('categories.index'); }
    public function show(Category $category) { return redirect()->route('categories.index'); }
    public function edit(Category $category) { return view('categories.index'); }
}
