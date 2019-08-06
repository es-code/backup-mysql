# php-backup-mysql-ftp
this php class help you to generate backup to your mysql database and transfer it to ftp server or save it in local server
for any help contact me on facebook https://fb.com/escode4


#example
/*
 * example to use this class
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
 *
 */
