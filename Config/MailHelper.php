<?php
// Load các file thư viện PHPMailer thủ công (theo cấu trúc thư mục của bạn)
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/Env.php'; // Load biến môi trường

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper {

    /**
     * Gửi Email với giao diện HTML đẹp mắt (Responsive Template)
     * * @param string $to Email người nhận
     * @param string $subject Tiêu đề Email (hiển thị ở inbox)
     * @param string $title Tiêu đề lớn trong nội dung thư (Header)
     * @param string $bodyContent Nội dung chính (có thể chứa HTML thẻ p, b, a...)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendCustomMail($to, $subject, $title, $bodyContent) {
        // Load cấu hình từ file .env
        
        
        $mail = new PHPMailer(true);

        try {
            // 1. Cấu hình Server (SMTP)
            $mail->isSMTP();
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = 2; // Bật debug chi tiết
            // Chỉ log debug vào error_log, không in ra output để không phá JSON
            $mail->Debugoutput = function($str, $level) { error_log("[MAIL] $str"); };
            ob_start(); // Bắt mọi output thừa từ PHPMailer (nếu có)
            $mail->Host       = Env::get('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = Env::get('SMTP_USER');
            $mail->Password   = Env::get('SMTP_PASS');
            $port             = Env::get('SMTP_PORT') ?: 587;
            $mail->Port       = $port;
            $mail->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Hostname   = 'hydrangeshop.com'; // Định danh máy chủ gửi

            // Cấu hình bỏ qua kiểm tra SSL (Sửa lỗi không gửi được mail trên XAMPP/Localhost)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // 2. Người gửi & Người nhận
            $mail->setFrom(Env::get('SMTP_USER'), 'Hydrange Shop');
            $mail->addAddress($to);
            $mail->addReplyTo(Env::get('SMTP_USER'), 'Hydrange Shop Support');

            // 3. Xây dựng Template HTML
            // Lưu ý: Email Client hỗ trợ CSS nội tuyến (inline style) tốt nhất.
            
            $year = date('Y');
            // Bạn có thể thay đường dẫn logo bên dưới bằng link ảnh online của shop bạn
            $logoUrl = "https://res.cloudinary.com/dxnynxcxx/image/upload/v1765344169/msbwq7e8e6ukmlltkq2o.png"; 

            $htmlTemplate = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$subject</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%); padding: 30px 20px;">
                            <img src="$logoUrl" alt="Hydrange Shop" width="64" height="64" style="display: block; margin-bottom: 15px; border-radius: 50%; background-color: white; padding: 5px;">
                            
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0; font-weight: 700; letter-spacing: 0.5px;">
                                $title
                            </h1>
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
                            <p style="margin: 5px 0;">
                                &copy; $year Hydrange Shop. All rights reserved.
                            </p>
                            <p style="margin: 5px 0;">
                                Đây là email tự động, vui lòng không trả lời email này.<br>
                                Nếu cần hỗ trợ, hãy liên hệ hotline: <a href="tel:1900xxxx" style="color: #2563eb; text-decoration: none;">1900 xxxx</a>
                            </p>
                            
                            <div style="margin-top: 10px;">
                                <a href="#" style="margin: 0 5px;"><img src="https://cdn-icons-png.flaticon.com/128/733/733547.png" width="20" alt="Facebook"></a>
                                <a href="#" style="margin: 0 5px;"><img src="https://cdn-icons-png.flaticon.com/128/2111/2111463.png" width="20" alt="Instagram"></a>
                            </div>
                        </td>
                    </tr>
                </table>
                <p style="text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px;">
                    Hydrange Shop - Mang cả thế giới đến ngôi nhà của bạn
                </p>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;

            // 4. Thiết lập nội dung email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlTemplate;
            $mail->AltBody = strip_tags($bodyContent); // Nội dung dự phòng cho client không hỗ trợ HTML

            $mail->send();
            ob_end_clean(); // Xóa mọi output thừa trước khi trả về
            return ['success' => true, 'message' => 'Email đã được gửi thành công.'];

        } catch (Throwable $e) {
            ob_end_clean(); // Xóa mọi output thừa trước khi trả về
            // Log lỗi vào file server nếu cần
            // error_log($e->getMessage());
            return ['success' => false, 'message' => "Không thể gửi email. Lỗi: " . $e->getMessage()];
        }
    }

    /**
     * Hàm bổ trợ để gửi mail xác thực nhanh (Dùng cho update_info.php)
     */
    public static function sendVerificationEmail($email, $token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $verifyLink = "$protocol://$host/web/Config/verify_email.php?token=$token";

        $subject = "Xác nhận thay đổi Email";
        $title = "Xác Thực Email Mới";
        $body = "
            <p>Bạn vừa yêu cầu thay đổi email trên hệ thống Hydrange Shop.</p>
            <p>Vui lòng nhấn vào nút bên dưới để xác nhận email mới:</p>
            <div style='text-align: center; margin: 20px 0;'>
                <a href='$verifyLink' style='background-color: #0ea5e9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Xác Nhận Email
                </a>
            </div>
            <p>Nếu không phải bạn thực hiện, vui lòng đổi mật khẩu ngay lập tức.</p>
        ";

        $result = self::sendCustomMail($email, $subject, $title, $body);
        return $result['success'];
    }
}
?>