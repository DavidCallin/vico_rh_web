<?php

namespace App\Services;

use App\Models\Contract;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class ContractGenerator
{
    public function generate(Contract $contract): Contract
    {
        $contract->loadMissing('employee.position.department');

        $html = view('contracts.document', [
            'contract' => $contract,
            'employer' => config('payroll.employer'),
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $path = 'contracts/contrat-'.$contract->id.'.pdf';
        Storage::disk('local')->put($path, $dompdf->output());
        $contract->update(['pdf_path' => $path]);

        return $contract->refresh();
    }
}
