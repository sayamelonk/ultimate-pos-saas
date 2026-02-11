<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BatchSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchSettingController extends Controller
{
    public function edit(): View
    {
        $tenantId = $this->getTenantId();
        $settings = BatchSetting::getForTenant($tenantId);

        return view('inventory.batches.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
            'expiry_critical_days' => ['required', 'integer', 'min:1', 'max:90', 'lt:expiry_warning_days'],
            'batch_prefix' => ['required', 'string', 'max:10'],
        ]);

        $tenantId = $this->getTenantId();
        $settings = BatchSetting::getForTenant($tenantId);

        $settings->update([
            'expiry_warning_days' => $request->expiry_warning_days,
            'expiry_critical_days' => $request->expiry_critical_days,
            'auto_mark_expired' => $request->boolean('auto_mark_expired'),
            'block_expired_sales' => $request->boolean('block_expired_sales'),
            'enable_fefo' => $request->boolean('enable_fefo'),
            'notify_expiry_warning' => $request->boolean('notify_expiry_warning'),
            'notify_expiry_critical' => $request->boolean('notify_expiry_critical'),
            'daily_expiry_report' => $request->boolean('daily_expiry_report'),
            'auto_generate_batch' => $request->boolean('auto_generate_batch'),
            'batch_prefix' => $request->batch_prefix,
        ]);

        return back()->with('success', 'Batch settings updated successfully.');
    }
}
