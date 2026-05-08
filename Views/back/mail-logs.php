<?php

$logsDir = __DIR__ . '/../storage/mail_logs';
$emails = [];

if (is_dir($logsDir)) {
    $files = array_reverse(glob($logsDir . '/emails_*.log'));
    
    if (!empty($files)) {
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            
            $separator = str_repeat('=', 80);
            $parts = explode($separator, $content);
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                
                
                if (strpos($part, 'EMAIL LOG ENTRY') !== false) {
                    $emails[] = $part;
                }
            }
        }
    }
}


$emails = array_reverse($emails);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log Viewer - Stabilis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }
        
        .email-entry {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 15px;
            background: #f9f9f9;
            transition: all 0.2s ease;
        }
        
        .email-entry:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .email-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .email-field {
            display: flex;
            flex-direction: column;
        }
        
        .email-field-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .email-field-value {
            color: #333;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .email-subject {
            grid-column: 1 / -1;
        }
        
        .email-body {
            background: white;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            color: #555;
            font-family: 'Courier New', monospace;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
        }
        
        .no-emails {
            padding: 60px 20px;
            text-align: center;
            color: #999;
        }
        
        .no-emails p {
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999;
        }
        
        .footer code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            📧 Email Log Viewer
        </h1>
        <p class="subtitle">Development environment - Emails are logged to files instead of being sent</p>
        
        <?php if (empty($emails)): ?>
            <div class="no-emails">
                <p style="font-size: 16px; color: #666;">No emails logged yet.</p>
                <p style="font-size: 13px;">Test the email system from the Products page by clicking:</p>
                <p style="font-size: 13px; color: #667eea; font-weight: 600;">🧪 Tester l'alerte mail</p>
            </div>
        <?php else: ?>
            <div class="header-bar">
                <span style="color: #666;">Showing logged emails:</span>
                <span class="badge"><?php echo count($emails); ?> Email<?php echo count($emails) !== 1 ? 's' : ''; ?></span>
            </div>
            
            <?php foreach ($emails as $email): ?>
                <?php 
                    preg_match('/\[([^\]]+)\]/', $email, $timeMatch);
                    preg_match('/TO:\s*([^\n]+)/i', $email, $toMatch);
                    preg_match('/FROM:\s*([^\n]+)/i', $email, $fromMatch);
                    preg_match('/SUBJECT:\s*([^\n]+)/i', $email, $subjectMatch);
                    preg_match('/\-+\n(.*?)$/s', $email, $bodyMatch);
                    
                    $time = $timeMatch[1] ?? 'N/A';
                    $to = htmlspecialchars($toMatch[1] ?? 'N/A');
                    $from = htmlspecialchars($fromMatch[1] ?? 'N/A');
                    $subject = htmlspecialchars($subjectMatch[1] ?? 'N/A');
                    $body = htmlspecialchars(trim($bodyMatch[1] ?? $email));
                ?>
                <div class="email-entry">
                    <div class="email-header">
                        <div class="email-field">
                            <div class="email-field-label">⏰ Timestamp</div>
                            <div class="email-field-value"><?php echo $time; ?></div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">📧 To</div>
                            <div class="email-field-value"><?php echo $to; ?></div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">👤 From</div>
                            <div class="email-field-value"><?php echo $from; ?></div>
                        </div>
                        <div class="email-field email-subject">
                            <div class="email-field-label">📝 Subject</div>
                            <div class="email-field-value"><?php echo $subject; ?></div>
                        </div>
                    </div>
                    <div class="email-body"><?php echo $body; ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="footer">
            <p>📁 Log files location: <code>storage/mail_logs/</code></p>
            <p style="margin-top: 10px;">💡 To enable real SMTP delivery to your Gmail inbox, edit <code>config/mail.php</code> and change <code>method: 'log'</code> to <code>method: 'smtp'</code></p>
        </div>
    </div>
</body>
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Log Viewer - Stabilis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .email-entry {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .email-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .email-field {
            display: flex;
            flex-direction: column;
        }
        
        .email-field-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .email-field-value {
            color: #333;
            word-break: break-all;
            font-family: monospace;
        }
        
        .email-body {
            background: white;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            color: #555;
            font-family: 'Courier New', monospace;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .no-emails {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }
        
        .no-emails svg {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span>📧</span> Email Log Viewer
        </h1>
        <p class="subtitle">Development environment - Emails are logged to files instead of being sent</p>
        
        <?php if (empty($emails)): ?>
            <div class="no-emails">
                <p>No emails logged yet.</p>
                <p style="font-size: 12px; margin-top: 10px;">Test the email system by clicking "Tester email d'alerte" on the dashboard.</p>
            </div>
        <?php else: ?>
            <p style="margin-bottom: 20px; color: #666;">
                <span class="badge badge-success"><?php echo count($emails); ?> Email(s)</span>
            </p>
            
            <?php foreach (array_reverse($emails) as $email): ?>
                <?php 
                    preg_match('/\[([^\]]+)\]/', $email, $timeMatch);
                    preg_match('/TO:\s*([^\n]+)/i', $email, $toMatch);
                    preg_match('/FROM:\s*([^\n]+)/i', $email, $fromMatch);
                    preg_match('/SUBJECT:\s*([^\n]+)/i', $email, $subjectMatch);
                    preg_match('/\-+\n(.*)/s', $email, $bodyMatch);
                ?>
                <div class="email-entry">
                    <div class="email-header">
                        <div class="email-field">
                            <div class="email-field-label">⏰ Timestamp</div>
                            <div class="email-field-value"><?php echo $timeMatch[1] ?? 'N/A'; ?></div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">📧 To</div>
                            <div class="email-field-value"><?php echo htmlspecialchars($toMatch[1] ?? 'N/A'); ?></div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">👤 From</div>
                            <div class="email-field-value"><?php echo htmlspecialchars($fromMatch[1] ?? 'N/A'); ?></div>
                        </div>
                        <div class="email-field">
                            <div class="email-field-label">📝 Subject</div>
                            <div class="email-field-value"><?php echo htmlspecialchars($subjectMatch[1] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="email-body"><?php echo htmlspecialchars($bodyMatch[1] ?? $email); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #999;">
            <p>📁 Log files location: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">storage/mail_logs/</code></p>
            <p style="margin-top: 10px;">💡 To enable real SMTP delivery, edit <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">config/mail.php</code> and set <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">method: 'smtp'</code> with valid credentials.</p>
        </div>
    </div>
</body>
</html>
