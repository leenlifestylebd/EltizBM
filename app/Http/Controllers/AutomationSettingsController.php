<?php

namespace App\Http\Controllers;

use App\Models\BusinessAutomation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AutomationSettingsController extends Controller
{
    private const PUBLIC_FIELDS = ['openrouter_model', 'telegram_allowed_user_ids', 'facebook_page_id', 'n8n_webhook_url'];

    public function edit(Request $request)
    {
        return $this->page($request);
    }

    private function page(Request $request, ?string $newToken = null)
    {
        $record = BusinessAutomation::where('business_id', $request->user()->business_id)->first();
        $stored = $record?->secrets ?? [];

        return response()->view('settings.automation', [
            'automation' => $record, 'settings' => $record?->settings ?? [],
            'configured' => array_fill_keys(array_keys($stored), true),
            'abilities' => BusinessAutomation::ABILITIES, 'newToken' => $newToken,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request)
    {
        $rules = [
            'enabled' => 'nullable|boolean',
            'abilities' => 'nullable|array',
            'abilities.*' => ['string', Rule::in(array_keys(BusinessAutomation::ABILITIES))],
            'openrouter_model' => 'nullable|string|max:200|regex:/^[a-zA-Z0-9_.:\/\-]+$/',
            'telegram_allowed_user_ids' => 'nullable|string|max:2000|regex:/^\d+(\s*,\s*\d+)*$/',
            'facebook_page_id' => 'nullable|string|max:40|regex:/^\d+$/',
            'n8n_webhook_url' => 'nullable|url:https|max:2048',
            'clear_secrets' => 'nullable|array',
            'clear_secrets.*' => ['string', Rule::in(BusinessAutomation::SECRET_FIELDS)],
        ];
        foreach (BusinessAutomation::SECRET_FIELDS as $key) {
            $rules[$key] = 'nullable|string|max:4096';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Never flash provider tokens to session/old input on validation errors.
            return redirect()->route('settings.automation')->withErrors($validator)
                ->withInput($request->only([...self::PUBLIC_FIELDS, 'enabled', 'abilities', 'abilities_present']));
        }
        $data = $validator->validated();
        DB::transaction(function () use ($request, $data) {
            DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
            $record = BusinessAutomation::firstOrNew(['business_id' => $request->user()->business_id]);
            $secrets = $record->secrets ?? [];
            foreach (BusinessAutomation::SECRET_FIELDS as $key) {
                if (in_array($key, $data['clear_secrets'] ?? [], true)) {
                    unset($secrets[$key]);
                } elseif (! empty($data[$key])) {
                    $secrets[$key] = $data[$key];
                }
            }
            $record->fill([
                'enabled' => $request->boolean('enabled'),
                'abilities' => array_values(array_unique($data['abilities'] ?? [])),
                'settings' => array_intersect_key($data, array_flip(self::PUBLIC_FIELDS)),
                'secrets' => $secrets,
            ])->save();
        });

        return redirect()->route('settings.automation')->with('success', 'Automation settings saved. Connections are not activated automatically.');
    }

    public function rotate(Request $request)
    {
        $token = 'eltiz_'.bin2hex(random_bytes(32));
        DB::transaction(function () use ($request, $token) {
            DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
            $record = BusinessAutomation::firstOrNew(['business_id' => $request->user()->business_id]);
            if (! $record->exists) {
                $record->abilities = ['catalog:read'];
            }
            $record->fill([
                'issued_by' => $request->user()->id,
                'token_hash' => hash('sha256', $token), 'token_suffix' => substr($token, -4),
            ])->save();
        });

        // Render once, without putting the plaintext token in a URL, database or session.
        return $this->page($request, $token);
    }

    public function revoke(Request $request)
    {
        BusinessAutomation::where('business_id', $request->user()->business_id)->update([
            'token_hash' => null, 'token_suffix' => null, 'enabled' => false,
        ]);

        return redirect()->route('settings.automation')->with('success', 'API token revoked.');
    }
}
