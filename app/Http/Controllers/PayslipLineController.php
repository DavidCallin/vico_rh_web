<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePayslipLineRequest;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Payroll\PayrollCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PayslipLineController extends Controller
{
    public function update(UpdatePayslipLineRequest $request, Payslip $payslip, PayslipLine $line): RedirectResponse
    {
        abort_unless($line->payslip_id === $payslip->id, 404);

        $line->load('payrollItem');

        if ($line->is_locked || $line->payrollItem->code === PayrollCodes::ITS) {
            throw ValidationException::withMessages([
                'amount' => 'La ligne ITS est calculée automatiquement et ne peut pas être modifiée.',
            ]);
        }

        $line->update([
            'amount' => number_format((float) $request->input('amount'), 2, '.', ''),
        ]);

        return back();
    }
}
