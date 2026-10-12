<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBankTransferSettingsController extends Controller
{
    private const KEYS = [
        'bank_name',
        'account_name',
        'account_number',
        'instructions',
    ];

    public function edit()
    {
        $settings = ShopSetting::whereIn('key', self::KEYS)->pluck('value', 'key');

        return $this->view('pages.admin.settings.bank-transfer', compact('settings'), 'Bank Transfer Settings');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:120'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:40', 'regex:/^[0-9 -]+$/'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (self::KEYS as $key) {
                ShopSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $validated[$key] ?? null]
                );
            }
        });

        return redirect()->route('admin.settings.bank-transfer.edit')
            ->with('status', 'Bank transfer details updated.');
    }
}
