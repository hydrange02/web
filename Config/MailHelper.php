<?php
// File: Web php/Config/MailHelper.php

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper {
    
    // CẤU HÌNH GMAIL SMTP
    private static $SMTP_HOST = 'smtp.gmail.com';
    private static $SMTP_USER = 'hydrange02@gmail.com'; 
    private static $SMTP_PASS = 'rsjz nuep rygo ppqq'; 
    private static $SMTP_PORT = 587;

    // Hàm gửi mail cơ bản (Private)
    private static function send($to, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = self::$SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$SMTP_USER;
            $mail->Password   = self::$SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::$SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(self::$SMTP_USER, 'Hydrange Shop'); // Tên người gửi đẹp hơn
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    // 1. Email Xác Thực Tài Khoản (Giữ nguyên nhưng làm đẹp code chút)
    public static function sendVerificationEmail($email, $token) {
        $link = "http://localhost/Web%20php/Config/verify_email.php?email=$email&token=$token";
        
        // Giao diện Email Verification
        $body = "
        <div style='font-family: Helvetica, Arial, sans-serif; min-width:1000px; overflow:auto; line-height:2'>
          <div style='margin:50px auto; width:70%; padding:20px 0'>
            <div style='border-bottom:1px solid #eee'>
              <a href='' style='font-size:1.4em; color: #00466a; text-decoration:none; font-weight:600'>Hydrange Shop</a>
            </div>
            <p style='font-size:1.1em'>Xin chào,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại Hydrange Shop. Vui lòng nhấn vào nút bên dưới để xác thực email của bạn.</p>
            <a href='$link' style='background: #00466a; margin: 0 auto; width: max-content; display: block; border-radius: 4px; color: #fff; padding: 12px 30px; text-decoration: none; font-weight: bold;'>Xác Thực Ngay</a>
            <p style='font-size:0.9em;'>Link này sẽ hết hạn sau 24 giờ.<br />Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
            <hr style='border:none;border-top:1px solid #eee' />
            <div style='float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300'>
              <p>Hydrange Shop Inc</p>
              <p>123 Đường ABC, TP.HCM</p>
              <p>Vietnam</p>
            </div>
          </div>
        </div>";
        
        return self::send($email, "Xác thực tài khoản", $body);
    }

    // 2. Email Mã OTP (ĐÃ NÂNG CẤP GIAO DIỆN)
    public static function sendOtpEmail($email, $otp, $title = "Mã xác thực OTP", $msg = "Sử dụng mã OTP bên dưới để hoàn tất yêu cầu của bạn.") {
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                .container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; padding: 40px; }
                .card { max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
                .header { background: linear-gradient(135deg, #4f46e5, #3b82f6); padding: 30px; text-align: center; }
                .header h1 { margin: 0; color: #ffffff; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; }
                .content { padding: 40px 30px; color: #333; text-align: center; }
                .otp-box { background-color: #f0f7ff; border: 2px dashed #3b82f6; color: #1d4ed8; font-size: 36px; font-weight: 800; padding: 20px; margin: 30px 0; letter-spacing: 8px; border-radius: 8px; display: inline-block; }
                .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
                .warning { color: #ef4444; font-size: 13px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='card'>
                    <div class='header'>
                        <h1>Hydrange Security</h1>
                    </div>
                    <div class='content'>
                        <h2 style='color: #1f2937; margin-top: 0;'>$title</h2>
                        <p style='font-size: 15px; line-height: 1.5; color: #4b5563;'>$msg</p>
                        
                        <div class='otp-box'>$otp</div>
                        
                        <p class='warning'>⚠️ Tuyệt đối không chia sẻ mã này với bất kỳ ai, kể cả nhân viên hỗ trợ.</p>
                        <p style='font-size: 13px; color: #9ca3af;'>Mã có hiệu lực trong <strong>15 phút</strong>.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Hydrange Shop. All rights reserved.</p>
                        <p>Đây là email tự động, vui lòng không trả lời.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";

        return self::send($email, $title . " - Hydrange Shop", $body);
    }
}
?>