<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: #2563eb; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 32px 24px; }
        .content h2 { color: #0f172a; font-size: 20px; margin-top: 0; }
        .info-box { background: #f1f5f9; padding: 16px; border-radius: 6px; margin: 20px 0; }
        .info-box p { margin: 8px 0; }
        .button { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 16px; font-size: 16px; }
        .footer { background: #f1f5f9; text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
        .warning { font-size: 12px; color: #6b7280; margin-top: 24px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Bulletin de paie disponible</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $payslip->employee->first_name }},</h2>
            <p>Votre bulletin de paie du mois de <strong>{{ $payslip->payPeriod->label ?? 'la période' }}</strong> est maintenant disponible.</p>
            
            <div class="info-box">
                <p><strong>💰 Salaire net :</strong> {{ number_format($payslip->net_salary ?? 0, 2, ',', ' ') }} €</p>
                <p><strong>📅 Date de génération :</strong> {{ $payslip->generation_date ? \Carbon\Carbon::parse($payslip->generation_date)->format('d/m/Y') : 'Aujourd\'hui' }}</p>
            </div>
            
            <p style="text-align: center; margin: 32px 0;">
                <a href="{{ $downloadUrl }}" class="button">
                    ⬇️ Télécharger mon bulletin
                </a>
            </p>

            <p class="warning">
                🔒 Ce lien est personnel et sécurisé. Il expirera dans 30 jours pour des raisons de sécurité.
            </p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>