<?php

class SmtpMailer
{
    public function send(string $to, string $subject, string $text): void
    {
        if (MAIL_USERNAME === '' || MAIL_PASSWORD === '' || MAIL_FROM_ADDRESS === '') {
            throw new RuntimeException('Chưa cấu hình email gửi đi.');
        }

        $socket = @stream_socket_client(
            'tcp://' . MAIL_HOST . ':' . MAIL_PORT,
            $errno,
            $errstr,
            20
        );
        if (!$socket) {
            throw new RuntimeException('Không kết nối được máy chủ email.');
        }

        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO trendstyle.local');
            $this->expect($socket, 250);
            $this->command($socket, 'STARTTLS');
            $this->expect($socket, 220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Không bật được kết nối mã hóa tới Gmail.');
            }

            $this->command($socket, 'EHLO trendstyle.local');
            $this->expect($socket, 250);
            $this->command($socket, 'AUTH LOGIN');
            $this->expect($socket, 334);
            $this->command($socket, base64_encode(MAIL_USERNAME));
            $this->expect($socket, 334);
            $this->command($socket, base64_encode(MAIL_PASSWORD));
            $this->expect($socket, 235);
            $this->command($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>');
            $this->expect($socket, 250);
            $this->command($socket, 'RCPT TO:<' . $to . '>');
            $this->expect($socket, 250);
            $this->command($socket, 'DATA');
            $this->expect($socket, 354);
            $this->command($socket, $this->message($to, $subject, $text));
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function message(string $to, string $subject, string $text): string
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $from = MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>';
        $body = rtrim(chunk_split(base64_encode($text), 76, "\r\n"));
        $headers = [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    }

    private function command($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function expect($socket, int $code): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ((int) substr($response, 0, 3) !== $code) {
            $detail = trim(strtok($response, "\r\n") ?: '');
            throw new RuntimeException('Gmail từ chối gửi email. ' . $detail);
        }
    }
}
