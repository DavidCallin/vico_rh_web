<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nouvelle demande de congé</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Nouvelle demande de congé</h2>
        
        <p>Bonjour {{ $leave->validator->first_name ?? 'Manager' }},</p>
        
        <p>Une nouvelle demande de congé a été soumise par <strong>{{ $leave->employee->full_name }}</strong>.</p>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Type de congé :</strong> {{ $leave->leave_type }}</p>
            <p><strong>Du :</strong> {{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}</p>
            <p><strong>Au :</strong> {{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</p>
            <p><strong>Durée :</strong> {{ $leave->days_count }} jour(s)</p>
            @if($leave->reason)
                <p><strong>Motif :</strong> {{ $leave->reason }}</p>
            @endif
        </div>
        
        <p>Connectez-vous à votre espace RH pour valider ou rejeter cette demande.</p>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 12px;">
            Cet email a été envoyé automatiquement par {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>