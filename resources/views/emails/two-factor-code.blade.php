{{-- resources/views/emails/two-factor-code.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e3a8a;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .code-box {
            background-color: #fff;
            border: 2px dashed #1e3a8a;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 5px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Lukefield Finance Company Limited</h2>
        <p>Archive Repository System</p>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $user->name }}</strong>,</p>
        
        <p>A login attempt was made to your account. Please use the verification code below to complete your login:</p>
        
        <div class="code-box">
            <div class="code">{{ $code }}</div>
            <p style="margin: 10px 0 0 0; color: #6b7280; font-size: 14px;">
                This code expires in 2 minutes
            </p>
        </div>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong><br>
            If you did not attempt to log in, please ignore this email and contact your system administrator immediately.
        </div>
        
        <p><strong>Login Details:</strong></p>
        <ul>
            <li>Time: {{ now()->format('F j, Y, g:i a') }}</li>
            <li>IP Address: {{ request()->ip() }}</li>
        </ul>
        
        <p>For security reasons, do not share this code with anyone.</p>
        
        <p>Best regards,<br>
        <strong>Lukefield Finance IT Security Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} Lukefield Finance Company Limited. All rights reserved.</p>
    </div>
</body>
</html>