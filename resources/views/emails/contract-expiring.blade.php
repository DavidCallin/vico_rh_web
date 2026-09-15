<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rappel : Contrat expirant bientôt</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #f59e0b;">⚠️ Rappel : Contrat expirant bientôt</h2>
        
        <p>Bonjour,</p>
        
        <p>Le contrat de <strong>{{ $contract->employee->full_name }}</strong> arrive à expiration dans <strong>{{ $daysRemaining }} jour(s)</strong>.</p>
        
        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <p><strong>Type de contrat :</strong> {{ strtoupper($contract->contract_type) }}</p>
            <p><strong>Date de fin :</strong> {{ $contract->end_date->format('d/m/Y') }}</p>
            <p><strong>Salaire de base :</strong> {{ number_format($contract->base_salary, 2, ',', ' ') }} €</p>
        </div>
        
        <p>Merci de prendre les dispositions nécessaires (renouvellement,avenant, ou fin de contrat).</p>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 12px;">
            Cet email a été envoyé automatiquement par {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>