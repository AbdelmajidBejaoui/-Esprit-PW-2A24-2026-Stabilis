<?php



class SimpleSmtpMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $secure;
    private $timeout = 15;
    private $debug = false;

    public function __construct($host, $port, $username, $password, $secure = 'tls', $debug = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->secure = $secure ?: 'tls';
        $this->debug = $debug;
    }

    public function send($to, $subject, $body, $from_email, $from_name, $isHtml = false) {
        try {
            
            if (!$isHtml && strpos($body, '<html') !== false) {
                $isHtml = true;
            }

            $contentType = $isHtml ? 'text/html' : 'text/plain';
            return $this->sendRaw($to, $subject, $body, $from_email, $from_name, "$contentType; charset=UTF-8");

        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendWithAttachment($to, $subject, $body, $from_email, $from_name, $attachmentName, $attachmentContent, $attachmentMime = 'application/pdf', $isHtml = true) {
        try {
            $boundary = 'stabilis_' . md5(uniqid('', true));
            $safeName = str_replace(["\r", "\n", '"'], '', $attachmentName);
            $encodedAttachment = chunk_split(base64_encode($attachmentContent));
            $contentType = $isHtml ? 'text/html' : 'text/plain';

            $messageBody = "--$boundary\r\n";
            $messageBody .= "Content-Type: $contentType; charset=UTF-8\r\n";
            $messageBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $messageBody .= $body . "\r\n\r\n";
            $messageBody .= "--$boundary\r\n";
            $messageBody .= "Content-Type: $attachmentMime; name=\"$safeName\"\r\n";
            $messageBody .= "Content-Transfer-Encoding: base64\r\n";
            $messageBody .= "Content-Disposition: attachment; filename=\"$safeName\"\r\n\r\n";
            $messageBody .= $encodedAttachment . "\r\n";
            $messageBody .= "--$boundary--";

            return $this->sendRaw(
                $to,
                $subject,
                $messageBody,
                $from_email,
                $from_name,
                "multipart/mixed; boundary=\"$boundary\""
            );
        } catch (Exception $e) {
            error_log("SMTP Attachment Error: " . $e->getMessage());
            return false;
        }
    }

    private function sendRaw($to, $subject, $body, $from_email, $from_name, $contentType) {
        $socket = null;
        try {
            $socket = $this->connect();
            if (!$socket) {
                return false;
            }

            if (!$this->expect($socket, '220', 'greeting')) return false;
            if (!$this->command($socket, "EHLO stabilis.local", '250', 'EHLO')) return false;
            if ($this->secure === 'tls') {
                if (!$this->command($socket, "STARTTLS", '220', 'STARTTLS')) return false;

                $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')
                    ? STREAM_CRYPTO_METHOD_TLS_CLIENT
                    : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

                if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    error_log('SMTP: Failed to establish TLS connection');
                    return false;
                }

                if (!$this->command($socket, "EHLO stabilis.local", '250', 'post-TLS EHLO')) return false;
            }
            if (!$this->command($socket, "AUTH LOGIN", '334', 'AUTH LOGIN')) return false;
            if (!$this->command($socket, base64_encode($this->username), '334', 'SMTP username', true)) return false;

            $this->write($socket, base64_encode($this->password), true);
            $authResponse = $this->readResponse($socket);
            if (!$this->responseHasCode($authResponse, '235')) {
                error_log('SMTP: Authentication failed: ' . trim($authResponse));
                return false;
            }

            if (!$this->command($socket, "MAIL FROM:<$from_email>", '250', 'MAIL FROM')) return false;
            if (!$this->command($socket, "RCPT TO:<$to>", ['250', '251'], 'RCPT TO')) return false;
            if (!$this->command($socket, "DATA", '354', 'DATA')) return false;

            $headers = "From: $from_name <$from_email>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: $contentType\r\n";
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "X-Mailer: Stabilis SMTP\r\n";
            $headers .= "\r\n";

            fputs($socket, $headers . $this->dotStuff($body) . "\r\n.\r\n");
            $response = $this->readResponse($socket);
            $sent = $this->responseHasCode($response, '250');

            if (!$sent) {
                error_log('SMTP: Message rejected: ' . trim($response));
                return false;
            }

            $this->write($socket, "QUIT");
            $this->readResponse($socket);
            return true;
        } catch (Exception $e) {
            error_log("SMTP Raw Error: " . $e->getMessage());
            return false;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    private function connect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $scheme = $this->secure === 'ssl' ? 'ssl' : 'tcp';
        $socket = stream_socket_client(
            "{$scheme}://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("SMTP Connection Error: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, $this->timeout);
        return $socket;
    }

    private function command($socket, $data, $expectedCodes, $label, $secret = false) {
        $this->write($socket, $data, $secret);
        return $this->expect($socket, $expectedCodes, $label);
    }

    private function expect($socket, $expectedCodes, $label) {
        $response = $this->readResponse($socket);
        if (!$this->responseHasCode($response, $expectedCodes)) {
            $codes = is_array($expectedCodes) ? implode('/', $expectedCodes) : $expectedCodes;
            error_log("SMTP: $label failed, expected $codes, got: " . trim($response));
            return false;
        }
        return true;
    }

    private function responseHasCode($response, $codes) {
        $codes = is_array($codes) ? $codes : [$codes];
        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote((string)$code, '/') . '[ -]/m', $response)) {
                return true;
            }
        }
        return false;
    }

    private function write($socket, $data, $secret = false) {
        $this->log('>>> ' . ($secret ? '[hidden]' : $data));
        fputs($socket, $data . "\r\n");
    }

    private function readResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            if ($line === '') {
                break;
            }
            $response .= $line;
            $this->log("<<< " . trim($line));
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }
        return $response;
    }

    private function dotStuff($body) {
        $body = str_replace(["\r\n", "\r"], "\n", (string)$body);
        $body = preg_replace('/^\./m', '..', $body);
        return str_replace("\n", "\r\n", $body);
    }

    private function log($message) {
        if ($this->debug) {
            error_log("[SMTP] $message");
        }
    }
}
