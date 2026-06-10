<?php
namespace App\Http\Controllers;
use App\Models\Shipment;
use App\Models\Order;
use App\Models\CourierSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $query = Shipment::where('business_id',$bizId)->with('order')->orderByDesc('created_at');
        if ($status = $request->status) $query->where('status',$status);
        $shipments = $query->paginate(20);
        // Single grouped query replaces 7 separate count queries
        $countsRaw = Shipment::where('business_id', $bizId)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $total = $countsRaw->sum();
        $statusCounts = ['' => $total];
        foreach (['pending','picked','in_transit','delivered','returned','cancelled'] as $s) {
            $statusCounts[$s] = $countsRaw->get($s, 0);
        }
        return view('courier.index', compact('shipments','statusCounts'));
    }
    public function create(Request $request)
    {
        $bizId  = auth()->user()->business_id;
        $orders = Order::where('business_id',$bizId)->whereIn('status',['confirmed','processing'])->orderByDesc('created_at')->get();
        $defaultCharge = 60;
        return view('courier.create', compact('orders','defaultCharge'));
    }
    public function store(Request $request)
    {
        $request->validate(['order_id'=>'required|integer','courier_name'=>'required|string']);
        $bizId = auth()->user()->business_id;
        $order = Order::where('id',$request->order_id)->where('business_id',$bizId)->firstOrFail();
        $shipment = Shipment::create(['business_id'=>$bizId,'order_id'=>$order->id,'courier_name'=>$request->courier_name,'tracking_id'=>$request->tracking_id,'status'=>$request->status??'pending','charge'=>$request->charge??0,'cod_amount'=>$request->cod_amount??0,'notes'=>$request->notes,'shipped_at'=>$request->shipped_at]);
        $order->update(['status'=>'shipped']);
        (new \App\Services\SmsService($bizId))->sendShipmentUpdate($order, $request->courier_name, $request->tracking_id ?? '');
        return redirect()->route('courier.index')->with('success', 'Shipment created!');
    }
    public function show(Shipment $shipment)
    {
        abort_if($shipment->business_id !== auth()->user()->business_id, 403);
        $shipment->load('order');
        return view('courier.show', compact('shipment'));
    }
    public function update(Request $request, Shipment $shipment)
    {
        abort_if($shipment->business_id !== auth()->user()->business_id, 403);
        $shipment->update(['status'=>$request->status??$shipment->status,'tracking_id'=>$request->tracking_id??$shipment->tracking_id,'delivered_at'=>$request->status==='delivered'?now():$shipment->delivered_at]);
        $orderStatus = match($request->status) { 'delivered'=>'delivered','returned'=>'returned','cancelled'=>'processing',default=>null };
        if ($orderStatus) {
            $shipment->order->update(['status'=>$orderStatus]);
            if ($request->status==='delivered' && $shipment->order->payment_method==='cod') {
                $shipment->order->update(['paid_amount'=>$shipment->order->total,'due_amount'=>0,'payment_status'=>'paid']);
            }
        }
        return back()->with('success', 'Shipment updated!');
    }
    public function track(Request $request, Shipment $shipment)
    {
        abort_if($shipment->business_id !== auth()->user()->business_id, 403);
        return back()->with('info', 'Tracking: '.($shipment->tracking_id ?? 'No tracking ID'));
    }
    public function returns()
    {
        $bizId = auth()->user()->business_id;
        $shipments = Shipment::where('business_id',$bizId)->where('status','returned')->with('order')->orderByDesc('created_at')->paginate(20);
        return view('courier.returns', compact('shipments'));
    }
}
