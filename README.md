SETUP MAIL SERVER
1. Place files to server root directory
2. Edit ./config/defines.php, fix paths and urls to yours and setup DB connection (mysql).
3. Create table "mailqueue":
	CREATE TABLE `mailqueue` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `emails` varchar(255) NOT NULL,
	  `subject` varchar(255) NOT NULL,
	  `body` text NOT NULL,
	  `mtype` int(11) NOT NULL,
	  `files` text NOT NULL,
	  `fileopts` text NOT NULL,
	  `status` int(11) NOT NULL,
	  `trydate` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
4. Edit cron_rpc_mailer.php, fix paths (use fullpath).
5. Setup crontab (with correct path):
* * * * *	/var/www/rpcmail/cron_rpc_mailer.php
6. Edit ./htaccess, setup path for AuthUserFile, allow your IPs.
7. Use "htpasswd" command to create some rpc-server users. Ex.: htpasswd -m ./config/.htpasswd mailer1

SETUP MAIL CLIENT
1. Before including client.class.php please define authorisation data to AUTH_USER and AUTH_PASS constants, and server url to RPC_SERV constant.
2. Call one of 4 methods: sendtxt, sendhtml, sendattache, sendembedimg from class RpcMail(). (see "sample.php")

<a href="https://github.com/effus/rpc-mail/"><img src="https://img.shields.io/github/license/effus/rpc-mail" alt=""></a>
