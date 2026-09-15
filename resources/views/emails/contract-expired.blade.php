<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contrat expiré - Action requise</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ef4444;">🚨 Contrat expiré - Action requise</h2>
        
        <p>Bonjour,</p>
        
        <p>Le contrat de <strong>{{ $contract->employee->full_name }}</strong> a expiré le <strong>{{ $contract->end_date->format('d/m/Y') }}</strong>.</p>
        
        <div style="background: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ef4444;">
            <p><strong>Type de contrat :</strong> {{ strtoupper($contract->contract_type) }}</p>
            <p><strong>Matricule :</strong> {{ $contract->employee->matricule }}</p>
        </div>
        
        <p><strong>Action requise :</strong> Merci de régulariser la situation de cet employé dans les plus brefs délais.</p>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 12px;">
            Cet email a été envoyé automatiquement par {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>