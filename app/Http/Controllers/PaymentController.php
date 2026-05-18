<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\PaymentGatewaySetting;
use App\Services\BkashService;
use App\Services\NagadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ══════════════════════════════════════════════════════════
    //  bKASH
    // ══════════════════════════════════════════════════════════

    public function bkashCreate(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        abort_if($order->due_amount <= 0, 400, 'Order already paid.');

        $setting = PaymentGatewaySetting::forBusiness($order->business_id, 'bkash');
        abort_if(!$setting, 404, 'bKash not configured.');

        try {
            $service  = new BkashService($setting);
            $callback = route('payment.bkash.callback', $order);
            $result   = $service->createPayment($order->order_number, $order->due_amount, $callback);

            if (empty($result['bkashURL'])) {
                return back()->with('error', 'bKash payment creation failed. Please try again.');
            }

            // Store paymentID in session
            session(["bkash_payment_id_{$order->id}" => $result['paymentID']]);

            return redirect($result['bkashURL']);
        } catch (\Exception $e) {
            Log::error('bKash create error: ' . $e->getMessage());
            return back()->with('error', 'bKash error: ' . $e->getMessage());
        }
    }

    public function bkashCallback(Request $request, Order $order)
    {
        $status    = $request->status;
        $paymentId = $request->paymentID;

        if ($status === 'cancel') {
            return redirect()->route('orders.show', $order)->with('error', 'bKash payment cancelled.');
        }

        if ($status === 'failure' || !$paymentId) {
            return redirect()->route('orders.show', $order)->with('error', 'bKash payment failed.');
        }

        try {
            $setting = PaymentGatewaySetting::forBusiness($order->business_id, 'bkash');
            $service = new BkashService($setting);
            $result  = $service->executePayment($paymentId);

            if (($result['statusCode'] ?? '') === '0000' && ($result['transactionStatus'] ?? '') === 'Completed') {
                $this->markOrderPaid($order, 'bkash', $paymentId, $result['trxID'] ?? '', $result['amount'] ?? $order->due_amount);
                return redirect()->route('orders.show', $order)->with('success', "Payment successful! bKash TrxID: {$result['trxID']}");
            }

            return redirect()->route('orders.show', $order)->with('error', 'Payment verification failed: ' . ($result['statusMessage'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('bKash callback error: ' . $e->getMessage());
            return redirect()->route('orders.show', $order)->with('error', 'Payment error: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    //  NAGAD
    // ══════════════════════════════════════════════════════════

    public function nagadCreate(Request $request, Order $order)
    {
        abort_if($order->business_id !== auth()->user()->business_id, 403);
        abort_if($order->due_amount <= 0, 400, 'Order already paid.');

        $setting = PaymentGatewaySetting::forBusiness($order->business_id, 'nagad');
        abort_if(!$setting, 404, 'Nagad not configured.');

        try {
            $service  = new NagadService($setting);
            $callback = route('payment.nagad.callback', $order);
            $result   = $service->initializePayment($order->order_number, $order->due_amount, $callback);

            $redirectUrl = $result['redirectURL'] ?? null;
            if (!$redirectUrl) {
                return back()->with('error', 'Nagad initialization failed.');
            }

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Nagad create error: ' . $e->getMessage());
            return back()->with('error', 'Nagad error: ' . $e->getMessage());
        }
    }

    public function nagadCallback(Request $request, Order $order)
    {
        $paymentRefId = $request->payment_ref_id;
        $status       = $request->status;

        if ($status !== 'Success' || !$paymentRefId) {
            return redirect()->route('orders.show', $order)->with('error', 'Nagad payment failed or cancelled.');
        }

        try {
            $setting = PaymentGatewaySetting::forBusiness($order->business_id, 'nagad');
            $service = new NagadService($setting);
            $result  = $service->verifyPayment($paymentRefId);

            if (($result['status'] ?? '') === 'Success') {
                $this->markOrderPaid($order, 'nagad', $paymentRefId, $result['issuerPaymentRefNo'] ?? '', $result['amount'] ?? $order->due_amount);
                return redirect()->route('orders.show', $order)->with('success', "Nagad payment successful! Ref: {$paymentRefId}");
            }

            return redirect()->route('orders.show', $order)->with('error', 'Nagad verification failed.');
        } catch (\Exception $e) {
            Log::error('Nagad callback error: ' . $e->getMessage());
            return redirect()->route('orders.show', $order)->with('error', 'Nagad error: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    //  SETTINGS (save credentials)
    // ══════════════════════════════════════════════════════════

    public function saveSettings(Request $request)
    {
        $gateway = $request->gateway;
        abort_if(!in_array($gateway, ['bkash', 'nagad']), 400);

        $bizId = auth()->user()->business_id;

        $rules = $gateway === 'bkash' ? [
            'app_key'    => 'required|string',
            'app_secret' => 'required|string',
            'username'   => 'required|string',
            'password'   => 'required|string',
        ] : [
            'merchant_id'  => 'required|string',
            'public_key'   => 'required|string',
            'private_key'  => 'required|string',
        ];

        $request->validate($rules);

        $credentials = $gateway === 'bkash' ? [
            'app_key'    => $request->app_key,
            'app_secret' => $request->app_secret,
            'username'   => $request->username,
            'password'   => $request->password,
        ] : [
            'merchant_id' => $request->merchant_id,
            'public_key'  => $request->public_key,
            'private_key' => $request->private_key,
        ];

        PaymentGatewaySetting::updateOrCreate(
            ['business_id' => $bizId, 'gateway' => $gateway],
            [
                'is_active'   => $request->boolean('is_active'),
                'sandbox'     => $request->boolean('sandbox', true),
                'credentials' => $credentials,
            ]
        );

        return back()->with('success', ucfirst($gateway) . ' settings saved.');
    }

    public function disableGateway(Request $request)
    {
        $gateway = $request->gateway;
        $bizId   = auth()->user()->business_id;

        PaymentGatewaySetting::where('business_id', $bizId)
            ->where('gateway', $gateway)
            ->update(['is_active' => false]);

        return back()->with('success', ucfirst($gateway) . ' disabled.');
    }

    // ══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════

    private function markOrderPaid(Order $order, string $gateway, string $paymentId, string $trxId, float $amount): void
    {
        DB::transaction(function () use ($order, $gateway, $paymentId, $trxId, $amount) {
            $paid = min((float) $amount, $order->due_amount);

            $order->update([
                'payment_gateway'    => $gateway,
                'gateway_payment_id' => $paymentId,
                'gateway_trx_id'     => $trxId,
                'paid_amount'        => $order->paid_amount + $paid,
                'due_amount'         => max(0, $order->due_amount - $paid),
                'payment_status'     => ($order->due_amount - $paid <= 0) ? 'paid' : 'partial',
                'payment_method'     => $gateway,
            ]);

            // Record transaction
            Transaction::create([
                'business_id'  => $order->business_id,
                'type'         => 'income',
                'amount'       => $paid,
                'description'  => ucfirst($gateway) . " payment for order #{$order->order_number}. TrxID: {$trxId}",
                'reference'    => $order->order_number,
                'date'         => now()->toDateString(),
            ]);
        });
    }
}
