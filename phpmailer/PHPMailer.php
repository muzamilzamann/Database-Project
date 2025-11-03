<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $Host = 'localhost';
    public $Port = 25;
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $ErrorInfo = '';
    private $to = [];
    private $isHTML = false;
    
    const ENCRYPTION_STARTTLS = 'tls';
    
    public function isSMTP() {
        // Set mailer to use SMTP
        ini_set('SMTP', $this->Host);
        ini_set('smtp_port', $this->Port);
        
        if ($this->SMTPAuth) {
            ini_set('smtp_username', $this->Username);
            ini_set('smtp_password', $this->Password);
        }
    }
    
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }
    
    public function addAddress($address) {
        $this->to[] = $address;
    }
    
    public function isHTML($isHTML = true) {
        $this->isHTML = $isHTML;
    }
    
    public function send() {
        $headers = "From: {$this->FromName} <{$this->From}>\r\n";
        $headers .= "Reply-To: {$this->From}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($this->isHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message = $this->Body;
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message = $this->AltBody ?: strip_tags($this->Body);
        }
        
        if ($this->SMTPAuth) {
            ini_set('SMTP', $this->Host);
            ini_set('smtp_port', $this->Port);
            ini_set('sendmail_from', $this->From);
            
            if ($this->SMTPSecure === self::ENCRYPTION_STARTTLS) {
                ini_set('SMTPSecure', 'tls');
            }
        }
        
        foreach ($this->to as $recipient) {
            if (!mail($recipient, $this->Subject, $message, $headers)) {
                $this->ErrorInfo = error_get_last()['message'];
                return false;
            }
        }
        return true;
    }
} 