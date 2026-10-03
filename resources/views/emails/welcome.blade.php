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
        .credentials-box { background: #f1f5f9; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2563eb; }
        .credentials-box p { margin: 8px 0; }
        .credentials-box strong { color: #1e40af; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0; font-size: 14px; }
        .button { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 16px; }
        .footer { background: #f1f5f9; text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Bienvenue chez {{ config('app.name', 'VICO RH') }} !</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $user->name }},</h2>
            <p>Votre compte employé a été créé avec succès. Vous pouvez maintenant accéder à votre espace personnel pour consulter vos bulletins de paie, gérer vos congés et suivre vos informations RH.</p>
            
            <div class="credentials-box">
                <p><strong>📧 Email :</strong> {{ $user->email }}</p>
                <p><strong>🔑 Mot de passe temporaire :</strong> <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-family: monospace;">{{ $temporaryPassword }}</code></p>
            </div>
            
            <div class="warning">
                ⚠️ <strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe dès votre première connexion.
            </div>
            
            <p>Connectez-vous dès maintenant pour accéder à votre espace :</p>
            
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/login" class="button">
                Se connecter à mon espace
            </a>
            
            <p style="margin-top: 24px; font-size: 14px; color: #64748b;">
                Si vous avez des questions ou besoin d'aide, n'hésitez pas à contacter le service RH.
            </p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par {{ config('app.name', 'VICO RH') }}.</p>
            <p>Merci de ne pas répondre à ce message.</p>
        </div>
    </div>
</body>
</html>