<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie disponible</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Votre bulletin de paie est disponible</h2>
        
        <p>Bonjour {{ $payslip->employee->first_name }},</p>
        
        <p>Votre bulletin de paie du mois de <strong>{{ $payslip->payPeriod->label ?? 'la période' }}</strong> est maintenant disponible dans votre espace personnel.</p>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Salaire net :</strong> {{ number_format($payslip->net_salary, 2, ',', ' ') }} €</p>
            <p><strong>Date de génération :</strong> {{ $payslip->generation_date->format('d/m/Y') }}</p>
        </div>
        
        <p>Connectez-vous à votre espace employé pour consulter et télécharger votre bulletin.</p>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 12px;">
            Cet email a été envoyé automatiquement par {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>