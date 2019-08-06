<?php

/*mysql backup
 *help you to generate backup to your database and transfer it to ftp server or save it in local server
 *for any help contact me on facebook https://fb.com/escode4
 */


/*
 * example to use this class
 *
 *
 * $backup = new MySqlBackup();
 * $backup->mysql_host = "localhost"; // database host
 * $backup->mysql_user = "root"; // database user name
 * $backup->mysql_pass = "root"; // database password
 * $backup->mysql_db = "testdb"; //database name
 * $backup->limit_backups = "3"; // maximum number of backups in directory
 * $backup->backupFtp = true; // if you want save backup on ftp server set it `true` set it false
 * $backup->backupLocal = true; // if you want save backup on local server set it `true` else set it false
 * $backup->backups_folder = "/home/user-name/DbBackups"; // your local backup directory
 * $backup->ftp_host = "127.0.0.1";  // ftp server
 * $backup->ftp_user = "ftpuser"; // ftp user name
 * $backup->ftp_pass = "ftppassword"; // ftp password
 * $backup->ftp_backup_folder = "files/backups"; // ftp backup directory
 * $backup->sendMail = false; // send an email report
 * $backup->from_mail = "test@mail.com"; // from mail
 * $backup->to_mail = "test@mail.com"; // to mail
 * $backup->subject = "new backup"; // mail subject
 * echo $backup->Run();
 */


/*
 *  you can use crontab to schedule backups
 * just type in terminal crontab -e and type "0 0 * * * php mysqlbackup,php"  to generate backup every day at At 12:00 am
 */

class MySqlBackup
{

    public $limit_backups = 3;
    public $backups_folder;
    public $mysql_db;
    public $mysql_host;
    public $mysql_user;
    public $mysql_pass;
    public $ftp_user;
    public $ftp_pass;
    public $ftp_host;
    public $ftp_backup_folder;
    public $backupFtp = true;
    public $backupLocal = true;
    public $sendMail = true;
    public $to_mail;
    public $from_mail;
    public $subject;
    protected $new_backup_file_name;
    protected $messages;
    protected $errors;
    protected $ftp_conn;


    public function Run()
    {
        $this->new_backup_file_name = $this->mysql_db . '-backup-' . date("Y-m-d-H:i:s") . '.sql.gz';

        if ($this->BackupDBinLocal()) {
            if ($this->backupFtp === true) {
                if ($this->BackupDbinFtp()) {
                    if ($this->backupLocal !== true) {
                        $this->DeleteOldBackups([], "", true);
                    }
                }
            }
        }
        return $this->SendReport();
    }


    protected function BackupDBinLocal()
    {
        $old_backups = scandir($this->backups_folder);
        $sortBackups = $this->ReturnOldBackup($old_backups, "local");
        exec("mysqldump -h $this->mysql_host -u $this->mysql_user -p$this->mysql_pass $this->mysql_db  | gzip >$this->backups_folder/$this->new_backup_file_name 2> /dev/null", $output, $result);
        if ($result == 0) {
            $this->DeleteOldBackups($sortBackups, "local");
            if ($this->backupLocal === true) {
                $this->messages .= "[+]The backup was successfully created on local \n[+]Backup Name:$this->new_backup_file_name \n";
            }
            return true;
        }
        $this->errors .= "[-]can't backup mysql database because mysqldump don't run correct \n";
        return false;
    }

    protected function BackupDbinFtp()
    {
        $this->ftp_conn = ftp_connect($this->ftp_host);
        if (!$this->ftp_conn) {
            $this->errors .= "[-]can't connect to ftp server  \n";
            return false;
        }
        $login_result = ftp_login($this->ftp_conn, $this->ftp_user, $this->ftp_pass);
        if ($login_result) {
            $fp = fopen($this->backups_folder . '/' . $this->new_backup_file_name, 'r');
            $old_backups = ftp_nlist($this->ftp_conn, $this->ftp_backup_folder);
            $sortBackups = $this->ReturnOldBackup($old_backups, "ftp");
            if (ftp_fput($this->ftp_conn, $this->ftp_backup_folder . '/' . $this->new_backup_file_name, $fp, FTP_ASCII)) {
                $this->DeleteOldBackups($sortBackups, "ftp");
                $this->messages .= "[+]The backup was sent to ftp server successfully \n";
                return true;
            } else {

                return false;
            }
            ftp_close($this->ftp_conn);
            fclose($fp);
        }

        $this->errors .= "[-]can't login to ftp server \n";
        return false;
    }


    protected function ReturnOldBackup($old_backups, $type = "local")
    {
        $ignored = ['.', '..'];
        $all_backups = [];
        foreach ($old_backups as $backup) {
            if (in_array($backup, $ignored)) continue;
            if ($type == "ftp") {
                $all_backups[basename($backup)] = ftp_mdtm($this->ftp_conn, $backup);
            } else {
                $all_backups[basename($backup)] = filemtime($this->backups_folder . '/' . $backup);
            }
        }
        arsort($all_backups);
        return array_keys($all_backups);
    }


    protected function DeleteOldBackups($backups = [], $type = "local", $current = false)
    {
        if ($current !== false) {
            unlink($this->backups_folder . '/' . $this->new_backup_file_name);
            return true;
        }
        if (count($backups) >= $this->limit_backups) {
            $first_backup = $backups[$this->limit_backups - 1];
            if ($type == "ftp") {
                if (!ftp_chdir($this->ftp_conn, $this->ftp_backup_folder)) {
                    $this->errors .= "[-]can't delete old backups because ftp_chdir not working  \n";
                }
                if (!ftp_delete($this->ftp_conn, $first_backup)) {
                    $this->errors .= "[-]can't delete old backups because ftp_delete not working  \n";
                }
            } else {
                if (!unlink($this->backups_folder . '/' . $first_backup)) {
                    $this->errors .= "[-]can't delete old backups because unlink not working  \n";
                }
            }
        }
    }


    protected function SendReport()
    {

        if (empty($this->errors)) {
            $this->messages .= "[+]My code tells me there are no problems and a backup has been created `$this->new_backup_file_name`. Please check this \n";
        }
        $report_msg = $this->messages . $this->errors;
        if ($this->sendMail === true) {
            $headers = "From: $this->from_mail \r\n" .
                "Reply-To: $this->from_mail \r\n" .
                "X-Mailer: PHP/" . phpversion();
            mail($this->to_mail, $this->subject, $report_msg, $headers);
        }
        return $report_msg;
    }

}
