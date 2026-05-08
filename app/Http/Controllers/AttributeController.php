<?php
namespace App\Http\Controllers;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        $bizId = auth()->user()->business_id;
        $attributes = Attribute::where('business_id', $bizId)->with('values')->get();
        return view('attributes.index', compact('attributes'));
    }
    public function store(Request $request)
    {
        $request->validate(['name'=>'required|string|max:100','type'=>'required|in:text,select,color,size','values.*'=>'string|max:100']);
        $bizId = auth()->user()->business_id;
        $attribute = Attribute::create(['business_id'=>$bizId,'name'=>$request->name,'type'=>$request->type]);
        if ($request->values) {
            foreach (array_filter($request->values) as $i => $val) {
                AttributeValue::create(['attribute_id'=>$attribute->id,'value'=>$val,'sort_order'=>$i]);
            }
        }
        return back()->with('success', 'Attribute created!');
    }
    public function destroy(Attribute $attribute)
    {
        abort_if($attribute->business_id !== auth()->user()->business_id, 403);
        $attribute->delete();
        return back()->with('success', 'Attribute deleted!');
    }
    public function create() { return view('attributes.index'); }
    public function show(Attribute $attribute) { return redirect()->route('attributes.index'); }
    public function edit(Attribute $attribute) { return view('attributes.index'); }
    public function update(Request $request, Attribute $attribute) { return back(); }
}
