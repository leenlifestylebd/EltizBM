<?php
namespace App\Http\Controllers;
use App\Models\WoocommerceSetting;
use App\Models\CourierSetting;
use App\Models\SmsSetting;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        $bizId = auth()->user()->business_id;
        $wooConnected = WoocommerceSetting::where('business_id', $bizId)->where('is_active', true)->exists();
        $bkashActive  = PaymentGatewaySetting::where('business_id', $bizId)->where('gateway','bkash')->where('is_active',true)->exists();
        $nagadActive  = PaymentGatewaySetting::where('business_id', $bizId)->where('gateway','nagad')->where('is_active',true)->exists();
        return view('integrations.index', compact('wooConnected','bkashActive','nagadActive'));
    }

    public function paymentGateways()
    {
        $bizId = auth()->user()->business_id;
        $bkash = PaymentGatewaySetting::where('business_id', $bizId)->where('gateway','bkash')->first();
        $nagad = PaymentGatewaySetting::where('business_id', $bizId)->where('gateway','nagad')->first();
        return view('integrations.payment-gateways', compact('bkash','nagad'));
    }
    public function courierProviders()
    {
        $bizId = auth()->user()->business_id;
        $settings = CourierSetting::where('business_id', $bizId)->get()->keyBy('provider');
        return view('integrations.courier-providers', compact('settings'));
    }
    public function saveCourierProvider(Request $request)
    {
        $request->validate(['provider'=>'required|in:pathao,steadfast,redx,custom','api_key'=>'required|string']);
        $bizId = auth()->user()->business_id;
        CourierSetting::updateOrCreate(['business_id'=>$bizId,'provider'=>$request->provider],['api_key'=>$request->api_key,'api_secret'=>$request->api_secret,'is_active'=>true]);
        return back()->with('success', ucfirst($request->provider).' API saved!');
    }
    public function sms()
    {
        $bizId = auth()->user()->business_id;
        $smsSettings = SmsSetting::where('business_id', $bizId)->first();
        return view('integrations.sms', compact('smsSettings'));
    }
    public function saveSms(Request $request)
    {
        $bizId = auth()->user()->business_id;
        SmsSetting::updateOrCreate(['business_id'=>$bizId],['provider'=>$request->provider,'api_key'=>$request->api_key,'sender_id'=>$request->sender_id,'order_confirm'=>$request->boolean('order_confirm'),'shipment_update'=>$request->boolean('shipment_update'),'payment_received'=>$request->boolean('payment_received')]);
        return back()->with('success', 'SMS settings saved!');
    }
    public function testSms(Request $request)
    {
        $bizId = auth()->user()->business_id;
        $sms = new \App\Services\SmsService($bizId);
        $result = $sms->test($request->phone ?? '01700000000');
        return response()->json($result);
    }
}
