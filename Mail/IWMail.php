<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 06/06/16
 * Time: 12:18
 */

namespace IrideWeb\Mail;


use IrideWeb\Core\IWGlobal;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class IWMail{

    protected $email_to; //Array di indirizzi
    protected $email_cc; //Array di indirizzi
    protected $email_ccn;//Array di indirizzi
    protected $notifica_lettura;
    protected $is_allega_doc_collegati;
    protected $is_pec;
    protected $is_body_from_template;
    protected $username;
    protected $from;
    protected $n_mailings;
    protected $failures;

    protected $mail_config;

    protected $transport;
    protected $mailer;

    public $Subject, $Body;

    public function __construct(){
        $config = IWGlobal::get("config");
        $this->mail_config = $config["mail"];

        $Host = $this->mail_config["host"];
        $Port = $this->mail_config["port"] == "" ? 25 : $this->mail_config["port"];
        $this->username = $this->mail_config["username"];
        $Password = $this->mail_config["password"];

        $this->transport = Swift_SmtpTransport::newInstance($Host,$Port)->setUsername($this->username)->setPassword($Password);
        $this->email_to = $this->email_cc = $this->email_ccn = [];
        $this->from = $this->mail_config["standard_from"];

    }

    public function AddA($emails=array())
    {
        $this->email_to = $emails;
    }

    public function AddC($emails=array())
    {
        $this->email_cc = $emails;
    }

    public function AddCCN($emails=array())
    {
        $this->email_ccn = $emails;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     * @return IWMail
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function addAddress($address, $name = ''){
        if($name!="") $this->email_to[$address] = $name;
        else $this->email_to[]=$address;
    }

    public function Invia()
    {
        $mailer = Swift_Mailer::newInstance($this->transport);
        if(empty($this->from)) return "Define the sender";

        /**
         * @var $message Swift_Message
         */
        $message = Swift_Message::newInstance($this->Subject)->setFrom($this->from)->setBody($this->Body, 'text/html');

        //Usefully to make tests on the format of email
        if($this->mail_config["force_receiver"]!="") {
            $destinatari=["email_to","email_cc","email_ccn"];
            $i=0;
            $string_destinatari="";
            foreach ($destinatari as $dest) {
                $text="A: ";
                if($i==1) $text="<br>CC: ";
                if($i==2) $text="<br>CCN: ";
                foreach ($this->$dest as $ar_dest) {
                    $text.=$ar_dest." ";
                }
                $string_destinatari.=$text;
                $i++;
            }
            $this->Body.="<br><br>".$string_destinatari;
            $message->setBody($this->Body);
            $this->email_to=explode(",",$this->mail_config["force_receiver"]);
            $this->email_cc = $this->email_ccn = [];
        }

        $message->setTo($this->email_to);
        if(!empty($this->email_cc)) $message->setCc($this->email_cc);
        if(!empty($this->email_ccn)) $message->setBcc($this->email_ccn);

        if(intval($this->mail_config["disable_send"]) == 1) return "";

        $this->n_mailings = $mailer->send($message,$this->failures);
        if($this->n_mailings == 0) return "No mailings<br>";

        return "";
    }

    public function getFailures(){
        return $this->failures;
    }

    /**
     * @return mixed
     */
    public function getNMailings()
    {
        return $this->n_mailings;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->Subject;
    }

    /**
     * @param mixed $Subject
     */
    public function setSubject($Subject)
    {
        $this->Subject = $Subject;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->Body;
    }

    /**
     * @param mixed $Body
     */
    public function setBody($Body)
    {
        $this->Body = $Body;
    }

}