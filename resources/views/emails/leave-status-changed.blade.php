<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Demande de congé {{ $status === 'approved' ? 'approuvée' : 'rejetée' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: {{ $status === 'approved' ? '#10b981' : '#ef4444' }};">
            Demande de congé {{ $status === 'approved' ? 'approuvée' : 'rejetée' }}
        </h2>
        
        <p>Bonjour {{ $leave->employee->first_name }},</p>
        
        <p>
            Votre demande de congé du <strong>{{ $leave->start_date->format('d/m/Y') }}</strong> 
            au <strong>{{ $leave->end_date->format('d/m/Y') }}</strong> a été 
            <strong>{{ $status === 'approved' ? 'approuvée' : 'rejetée' }}</strong>.
        </p>
        
        @if($status === 'rejected' && $leave->reason)
            <div style="background: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ef4444;">
                <p><strong>Motif du rejet :</strong> {{ $leave->reason }}</p>
            </div>
        @endif
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 12px;">
            Cet email a été envoyé automatiquement par {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>