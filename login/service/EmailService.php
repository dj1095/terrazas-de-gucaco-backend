<?php
    require_once '../../config/Database.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;

    class EmailService{

        public function __construct()
        {
        }
        
        public function send_email($from_address, $from_name, $to_address, $to_name, $messageSubject, $messageBody){
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPAuth = true;

            $mail->Host = getenv('EMAIL_HOST') ?? null;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('EMAIL_PORT') ?? 587;

            $mail->Username =  getenv('EMAIL_USERNAME') ?? null;
            $mail->Password = getenv('EMAIL_PASSWORD') ?? null;
            
            $mail->setFrom($from_address, $from_name);
            $mail->addAddress($to_address, $to_name);

            $mail->Subject = $messageSubject;
            $mail->Body = $messageBody;
            return $mail->send();
        }
    }