<?php
require_once __DIR__ . '/Env.php';

class MailHelper {
    /**
     * Lấy Access Token mới từ Refresh Token
     */
    private static function getAccessToken() {
        $clientId = trim(Env::get('GMAIL_CLIENT_ID'));
        $clientSecret = trim(Env::get('GMAIL_CLIENT_SECRET'));
        $refreshToken = trim(Env::get('GMAIL_REFRESH_TOKEN'));

        if (!$clientId || !$clientSecret || !$refreshToken) {
            return ['token' => null, 'error' => 'Missing credentials in .env'];
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]));

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return ['token' => null, 'error' => 'cURL Error: ' . $err];
        }

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return ['token' => $data['access_token'], 'error' => null];
        } else {
            return ['token' => null, 'error' => 'API Response: ' . $response];
        }
    }

    /**
     * Gửi Email qua Gmail API (REST)
     * @param string $to Email người nhận
     * @param string $subject Tiêu đề Email
     * @param string $title Tiêu đề lớn trong nội dung thư
     * @param string $bodyContent Nội dung chính (HTML)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendCustomMail($to, $subject, $title, $bodyContent) {
        $result = self::getAccessToken();
        $accessToken = $result['token'];
        
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Không thể lấy Access Token từ Google. Lỗi: ' . $result['error']];
        }

        $fromEmail = Env::get('GMAIL_FROM_EMAIL', 'your_email@gmail.com');
        $fromName = Env::get('SMTP_FROM_NAME', 'Hydrange Shop');

        // Tạo nội dung email theo chuẩn RFC 2822
        $boundary = uniqid('np', true);
        $rawMessage = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $rawMessage .= "To: <$to>\r\n";
        $rawMessage .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";

        // Phần HTML của email
        $year = date('Y');
        $logoUrl = "https://res.cloudinary.com/dxnynxcxx/image/upload/v1765344169/msbwq7e8e6ukmlltkq2o.png";
        
        $htmlTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%); padding: 30px 20px;">
                            <img src="$logoUrl" alt="Hydrange Shop" width="64" height="64" style="display: block; margin-bottom: 15px; border-radius: 50%; background-color: white; padding: 5px;">
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0; font-weight: 700; letter-spacing: 0.5px;">$title</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px; color: #334155; font-size: 16px; line-height: 1.6;">
                            $bodyContent
                            <br><br>
                            <p style="margin: 0; color: #64748b; font-size: 14px;">
                                Trân trọng,<br>
                                <strong>Đội ngũ Hydrange Shop</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #e2e8f0;"></td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px; color: #94a3b8; font-size: 12px;">
                            <p style="margin: 5px 0;">&copy; $year Hydrange Shop. All rights reserved.</p>
                            <p style="margin: 5px 0;">Đây là email tự động, vui lòng không trả lời email này.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $rawMessage .= "--$boundary\r\n";
        $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessage .= chunk_split(base64_encode($htmlTemplate)) . "\r\n";
        $rawMessage .= "--$boundary--";

        // Mã hóa Base64URL safe cho Gmail API
        $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));

        // Gửi qua Gmail API
        $ch = curl_init("https://gmail.googleapis.com/gmail/v1/users/me/messages/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['raw' => $encodedMessage]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Email đã được gửi thành công qua Gmail API.'];
        } else {
            return ['success' => false, 'message' => "Lỗi từ Gmail API (HTTP $httpCode): " . $response];
        }
    }

    /**
     * Hàm hỗ trợ gửi mail xác thực
     */
    public static function sendVerificationEmail($email, $token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $verifyLink = "$protocol://$host/web/Config/verify_email.php?token=$token";
        
        $subject = "Xác nhận tài khoản Hydrange Shop";
        $title = "Xác Thực Email";
        $body = "
            <p>Cảm ơn bạn đã đăng ký tài khoản tại Hydrange Shop.</p>
            <p>Vui lòng nhấn vào nút bên dưới để hoàn tất quá trình xác thực:</p>
            <div style='text-align: center; margin: 20px 0;'>
                <a href='$verifyLink' style='background-color: #0ea5e9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Xác Nhận Email
                </a>
            </div>
            <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
        ";
        
        $result = self::sendCustomMail($email, $subject, $title, $body);
        return $result['success'];
    }
}
?>