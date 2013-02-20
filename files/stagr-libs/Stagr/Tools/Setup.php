<?php

/*
 * This file is part of the Stagr framework.
 *
 * (c) Gabriel Manricks <gmanricks@me.com>
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stagr\Tools;

use Symfony\Component\Console\Output\OutputInterface;
use Cilex\Application;
use Cilex\Command\Command;
use Stagr\Tools\Setup;

/**
 * Setup logic for creating applications
 *
 * @author Gabriel Manricks <gmanricks@me.com>
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Setup
{
    /**
     * @const string Home folder of stagr/vagrant user
     */
    const STAGR_HOME_DIR = '/home/vagrant';

    /**
     * @var string Template for App git folder
     */
    const APP_GIT_DIR_TMPL = '/home/vagrant/apps/%s.git';

    /**
     * @var string Template for App docroot
     */
    const APP_WWW_DIR_TMPL = '/var/www/web/%s';

    /**
     * @var string Template for App docroot
     */
    const APP_FPM_SOCK_DIR_TMPL = '/var/fpm/socks/%s';

    /**
     * @var string Template for App docroot
     */
    const APP_FPM_PREPEND_DIR_TMPL = '/var/fpm/prepend/%s';

    /**
     * @var Type
     */
    public static $DEFAULT_SETTINGS = array(
        'env'      => array(),
        'doc-root' => '',
        'hooks'    => array(
            'webcall'  => false,
        ),
        'php' => array(
            'date-timezone'       => 'Europe/Berlin',
            'max_execution_time'  => 300,
            'memory_limit'        => '64M',
            'apc-shm_size'        => '64M',
            'upload_max_filesize' => '128M',
            'post_max_size'       => '128M',
            'short_open_tag'      => 'On',
            'output_buffering'    => 4096
        )
    );

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \Cilex\Command\Command
     */
    private $command;

    /**
     * @var \Cilex\Application
     */
    private $app;

    /**
     * @var string
     */
    private $appName;

    /**
     * Creates setup object
     *
     * @param OutputInterface  $output   Current output stream
     * @param Command          $command  Command in use
     */
    public function __construct($appName, OutputInterface &$output, Command &$command)
    {
        $this->appName = $appName;
        $this->output  = $output;
        $this->command = $command;
        $this->app     = $command->getApplication()->getContainer();
    }


    /**
     * Prints Stagr Log to STDOUT
     *
     * @param  string  $action  Output action title, eg Setup
     */
    public static function printLogo($action = 'Setup')
    {
        echo <<<LOGO

[1m
       ___ _
      / __| |_ __ _ __ _ _ _
      \__ \  _/ _` / _` | '_|
      |___/\__\__,_\__, |_|
                   |___/
[0m
     [31mStaging Enviroment[0m $action


LOGO;
    }

    /**
     * Checks whether email and at least one SSH key is there -> if not asks user to input now
     *
     * @param  OutputInterface  $output   For questioning user
     * @param  OutputInterface  $command  Current command
     */
    public function initEmailAndSsh()
    {
        // assure email
        if (!$this->app->configParam('email')) {
            $email = $this->command->readStdin($this->output, '<question>Please enter your E-Mail:</question> ');
            $this->app->configParam('email', $email);
        }

        // assure ssh key
        if (!$this->app->configParam('sshkeys')) {
            $sshKey = $this->command->readStdin($this->output, '<question>Please enter your SSH public key:</question> ');
            $this->app->configParam('sshkeys', [$sshKey]);
            file_put_contents(Setup::STAGR_HOME_DIR.'/.ssh/authorized_keys', $sshKey, FILE_APPEND);
        }
    }

    /**
     * Writes webserver (Apache VHost, FPM config + prepend) files and restarts Aapache and FPM
     */
    public function setupWebserver()
    {
        //Create Folder
        $this->output->write('Creating Directories ... ');
        foreach (array(self::APP_WWW_DIR_TMPL. '/htdocs', self::APP_WWW_DIR_TMPL. '/redir', self::APP_FPM_SOCK_DIR_TMPL, self::APP_FPM_PREPEND_DIR_TMPL) as $tmpl) {
            $dir = sprintf($tmpl, $this->appName);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            chown($dir, "vagrant");
            chgrp($dir, "vagrant");
        }
        $this->output->writeln('<info>OK</info>');

        //Create Symlink for PHP-FPM
        $this->output->write('Creating Symlink for PHP-FPM ... ');
        $linkRedirDir = sprintf(self::APP_WWW_DIR_TMPL. '/redir', $this->appName);
        if (!is_link("$linkRedirDir/php")) {
            exec("chdir $linkRedirDir; ln -s ../htdocs php");
        }
        $this->output->writeln('<info>OK</info>');

        //Create Vhost File
        $this->output->write('Creating Site File ... ');
        $this->rebuildVhost();
        $this->output->writeln('<info>OK</info>');

        //Symlink Vhost to sites-enabled
        $vhostLink = "/etc/apache2/sites-enabled/$this->appName";
        if (!is_link($vhostLink)) {
            symlink("/etc/apache2/sites-available/" . $this->appName, $vhostLink);
        }

        //Create FPM Config
        $this->output->write('Creating FPM Config ... ');
        $this->rebuildFpmConfig();
        $this->output->writeln('<info>OK</info>');

        //Create PHP/FPM prepend file
        $this->output->write('Creating PHP/FPM Prepend File ... ');
        file_put_contents(sprintf(self::APP_FPM_PREPEND_DIR_TMPL. '/prepend.php', $this->appName), $this->generateFpmPrepend());
        $this->output->writeln('<info>OK</info>');

        $this->restartServices();
    }



    /**
     * Writes/updates MySQL user and creates database
     */
    public function setupMySQL()
    {
        //Creating MySQL User and database
        $this->output->write('Creating MySQL User and database ... ');
        $dbh = new \PDO('mysql:host=localhost;dbname=mysql', 'root');
        $sth = $dbh->prepare('SHOW DATABASES LIKE ?');
        $sth->bindParam(1, $this->appName);
        $sth->execute();
        if ($sth->rowCount() === 0) {
            //$this->appName is save cause checked for [a-z0-9-]
            $sth = $dbh->prepare('CREATE DATABASE `'. $this->appName. '`');
            $sth->bindParam(1, $this->appName);
            $sth->execute();
        }

        foreach (array('%', 'localhost') as $host) {
            $sth = $dbh->prepare('INSERT INTO user (Host, User, Password) VALUES (?, ?, PASSWORD(?)) ON DUPLICATE KEY UPDATE Host = Host');
            $sth->bindParam(1, $host);
            $sth->bindParam(2, $this->appName);
            $sth->bindParam(3, $this->appName);
            $sth->execute();

            $sth = $dbh->prepare('INSERT INTO db (Host, Db, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, References_priv, Index_priv, Alter_priv, Create_tmp_table_priv, Lock_tables_priv, Create_view_priv, Show_view_priv) VALUES (?, ?, ?, "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y") ON DUPLICATE KEY UPDATE Host = Host');
            $sth->bindParam(1, $host);
            $sth->bindParam(2, $this->appName);
            $sth->bindParam(3, $this->appName);
            $sth->execute();
        }
        $dbh->query('FLUSH PRIVILEGES');
        $this->output->writeln('<info>OK</info>');

        //Update hosts file to assure mysql hostname is present
        $this->output->write('Update hostname for mysql ... ');
        $hostsFile = '/etc/hosts';
        $hostsOld = preg_grep('/^\s*(?:#|$)/', preg_split('/\n/', file_get_contents($hostsFile)), PREG_GREP_INVERT);
        $hostsNew = array();
        $hostsSeen = false;
        foreach ($hostsOld as $hostLine) {
            list($ip, $hosts) = preg_split('/\s+/', $hostLine, 2);
            if (preg_match('/\b'. $this->appName. '\.mysql\.dev/', $hosts)) {
                $hostsSeen = true;
                break;
            }
        }
        if (!$hostsSeen) {
            $hf = fopen($hostsFile, "a");
            fwrite($hf, "127.0.0.1    $this->appName.mysql.dev\n");
            fclose($hf);
        }
        $this->output->writeln('<info>OK</info>');

    }


    /**
     * Init git repo, writes git hooks
     */
    public function setupGit()
    {
        //Create folder for Bare Repo
        $this->output->write('Creating Bare Repository ... ');
        $gitDir = sprintf(self::APP_GIT_DIR_TMPL, $this->appName);
        if (!is_dir($gitDir)) {
            mkdir($gitDir, 0755, true);
        }
        chown($gitDir, "vagrant");
        chgrp($gitDir, "vagrant");
        if (!file_exists("$gitDir/HEAD")) {
            chdir($gitDir);
            exec("sudo -u vagrant git init --bare");
        }
        $this->output->writeln('<info>OK</info>');

        //Create Site's Repo
        $this->output->write('Creating Repo in Sites Directory ... ');
        $webDir = sprintf(self::APP_WWW_DIR_TMPL. '/htdocs', $this->appName);
        if (!is_dir("$webDir/.git")) {
            chdir($webDir);
            exec("sudo -u vagrant git init");
            //Link Both Repos
            exec("sudo -u vagrant git remote add origin " . $gitDir);
        }
        $this->output->writeln('<info>OK</info>');

        //Add the two hooks (pre-receive & post-receive)
        $this->output->write('Writing Hooks ... ');
        file_put_contents("$gitDir/hooks/pre-receive", $this->generateGitPreHook());
        file_put_contents("$gitDir/hooks/post-receive", $this->generateGitPostHook());

        //Set permission to executable on both hooks
        foreach (array('pre', 'post') as $n) {
            chmod("$gitDir/hooks/$n-receive", 0775);
            chown("$gitDir/hooks/$n-receive", "vagrant");
            chgrp("$gitDir/hooks/$n-receive", "vagrant");
        }
        $this->output->writeln('<info>OK</info>');

    }

    /**
     * Function to rebuild Vhost with new Env Vars
     */
    public function rebuildVhost()
    {
        file_put_contents("/etc/apache2/sites-available/" . $this->appName, $this->generateVhostContent());
    }

    /**
     * Function to rebuild FPM's Config
     */
    public function rebuildFpmConfig()
    {
        file_put_contents("/etc/php5/fpm/pool.d/" . $this->appName . ".conf", $this->generateFpmConfig());
    }

    /**
     * Function to restart Apache and FPM
     */
    public function restartServices()
    {
        //Restart Apache
        $this->output->write('Restarting Apache ... ');
        exec("service apache2 restart");
        $this->output->writeln('<info>OK</info>');

        //Restart PHP-FPM
        $this->output->write('Restarting PHP-FPM ... ');
        exec("service php5-fpm restart");
        $this->output->writeln('<info>OK</info>');
    }


    /**
     * Display IP address of current VM for hosts purposes
     */
    public function printIpInfo()
    {
        $this->output->writeln("To get started, add the correct record to your <info>HOSTS</info> file: (<info>/etc/hosts</info>)\n");
        $this->output->writeln($this->getIps());
        $this->output->writeln("If you're not sure try accessing the <info>IPs</info> from your computer (<info>browser, ping, curl, etc..</info>)");
    }


    /**
     * Display the GIT remote address
     */
    public function printGitInfo()
    {
        $this->output->writeln("And add this server to your <info>GIT</info> repository:\n");
        $this->output->writeln("     git remote add staging vagrant@{$this->appName}.dev:{$this->appName}.git");
    }


    /**
     * Display MySQL connection info
     */
    public function printMySQLInfo()
    {
        $this->output->writeln("To connect to your MySQL database from your App use:\n");
        $this->output->writeln("     Host:     {$this->appName}.mysql.dev");
        $this->output->writeln("     User:     {$this->appName}");
        $this->output->writeln("     Password: {$this->appName}");
    }




    /**
     * Generates apache vhost config file content
     */
    protected function generateVhostContent()
    {
        $email = $this->app->configParam('email');
        $settings = $this->app->configParam('apps.'. $this->appName);
        $docRoot = $settings['doc-root'];
        $baseDir = sprintf(self::APP_WWW_DIR_TMPL, $this->appName);
        $socksDir = sprintf(self::APP_FPM_PREPEND_DIR_TMPL, $this->appName);
        $vHost = <<<SITE

FastCgiExternalServer $baseDir/redir/php -socket $socksDir/sock -idle-timeout 305 -flush

<VirtualHost *:80>
    ServerAdmin $email
    ServerName {$this->appName}.dev
    DocumentRoot $baseDir/htdocs/$docRoot

    SetEnv APP_NAME "{$this->appName}"

SITE;
        for ($i = 0; $i < count($settings['env']); $i++) {
            $row = $settings['env'][$i];
            foreach ($row as $key => $value) {
                $vHost .= '    SetEnv ' . $key . ' "' . $value . '"' . "\n";
            }
        }

        $vHost .= <<<SITE
    # PHP Settings
    <FilesMatch \.php$>
        SetEnv no-gzip dont-vary
        Options +ExecCGI
    </FilesMatch>
    AddHandler php5-{$this->appName}-fcgi .php
    Action php5-{$this->appName}-fcgi /.ctrl/~~~php
    Alias /.ctrl/~~~php $baseDir/redir/php/$docRoot

    <Directory $baseDir/htdocs/$docRoot>
        # PathInfo for PHP-FPM
        RewriteEngine On
        RewriteCond %{REQUEST_URI} \.php/ [NC]
        RewriteRule ^(.*)\.php/(.*)$    /$1.php [NC,L,QSA,E=PATH_INFO:/$2]

        Options -All +SymLinksIfOwnerMatch
        AllowOverride AuthConfig Limit FileInfo Indexes Options=SymLinksIfOwnerMatch,MultiViews,Indexes
        Order allow,deny
        allow from all
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/{$this->appName}.log
    LogLevel debug
</VirtualHost>

SITE;
        return $vHost;
    }


    /**
     * Generates FPM config file content
     */
    protected function generateFpmConfig()
    {

        // php settings
        $settings = $this->app->configParam('apps.'. $this->appName. '.php');
        $phpAppSettings = '';
        foreach ($settings as $confName => $confValue) {
            $phpAppSettings .= sprintf('php_value[%s] = "%s"', preg_replace('/\-/', '.', $confName), $confValue). "\n";
        }

        // dirs & files
        $sockFile = sprintf(self::APP_FPM_SOCK_DIR_TMPL. '/sock', $this->appName);
        $prependFile = sprintf(self::APP_FPM_PREPEND_DIR_TMPL. '/prepend.php', $this->appName);
        $htdocsDir = sprintf(self::APP_WWW_DIR_TMPL. '/htdocs', $this->appName);

        return <<<FPMCONF
[{$this->appName}]
listen = $sockFile

listen.owner = vagrant
listen.group = www-data
listen.mode  = 0660

user = vagrant
group = www-data

pm = dynamic
pm.max_children = 3
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 1000
request_terminate_timeout = 300
php_value[open_basedir] = ""
php_value[include_path] = ".:/usr/share/php:$htdocsDir"
php_value[upload_tmp_dir] = "/tmp"
php_value[session.save_path] = "/tmp"
php_value[apc.shm_size] = "32M"
php_value[auto_prepend_file] = "$prependFile"
php_value[default_charset] = "UTF-8"
$phpAppSettings


FPMCONF;
    }



    /**
     * Generates prepend file loaded from FPM config
     */
    protected function generateFpmPrepend()
    {
        return <<<FPMPREPEND
<?php

if (!defined('__PREPEND_INITED')) {
    define('__PREPEND_INITED', true);

    foreach (array('SCRIPT_NAME', 'PHP_SELF') as \$env) {
        \$_SERVER[\$env] = str_replace('/.ctrl/~~~php/', '/', str_replace('/.ctrl/php/', '/', \$_SERVER[\$env]));
    }
    foreach (array('SCRIPT_FILENAME') as \$env) {
        \$_SERVER[\$env] = str_replace('/.ctrl/~~~php/', '/htdocs/', str_replace('/.ctrl/php/', '/htdocs/', \$_SERVER[\$env]));
    }
    foreach (array_keys(\$_SERVER) as \$env_key) {
        if (\$env_key == 'REDIRECT_STATUS') {
            continue;
        }
        elseif (preg_match('/^(?:REDIRECT_)+(.+)$/', \$env_key, \$match)) {
            if (! isset(\$_SERVER[\$match[1]])) {
                \$_SERVER[\$match[1]] = \$_SERVER[\$env_key];
            }
            unset(\$_SERVER[\$env_key]);
        }
    }
    foreach (array('ORIG_SCRIPT_FILENAME', 'ORIG_PATH_INFO', 'ORIG_PATH_TRANSLATED', 'ORIG_SCRIPT_NAME', 'HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_PORT', 'HTTP_X_FORWARDED_FOR') as \$key) {
        unset(\$_SERVER[\$key]);
    }
}

FPMPREPEND;
    }



    /**
     * Genrate Pre-Hook for Git
     */
    protected function generateGitPreHook()
    {
        return <<<PREHOOK
#!/usr/bin/php
<?php
echo "\\e[33mStep1: Updating repository\\[0m\\n";
?>
PREHOOK;
    }



    /**
     * Genrate Post-Hook for Git (deployment)
     */
    protected function generateGitPostHook()
    {
        $htdocsDir = sprintf(self::APP_WWW_DIR_TMPL. '/htdocs', $this->appName);
        $deployGitDir = sprintf(self::APP_WWW_DIR_TMPL. '/htdocs/.git', $this->appName);
        $homeDir = self::STAGR_HOME_DIR;

        return <<<POSTHOOK
#!/usr/bin/php
<?php

require("$homeDir/PHP-GIT-Hooks/PHook.php");
\$ph = new PHook;

\$ph->clear(" -> ")->cyan("OK")->withoutACommand();

\$ph->say("Step2: Deploying")
    ->thenRun(function(){
        \$git = "git --git-dir=$htdocsDir/.git/ --work-tree=$htdocsDir/";
        exec("\$git fetch -q origin");
        exec("\$git reset --hard origin/master");
    })
    ->clear("\n -> ")->plain("")->andFinallySay("OK");

\$ph->onTrigger("[trigger:composer]")
    ->say("Step3: Composer Hook")->clear("\n -> Triggering install - get a ")->cyan("coffee")
    ->thenRun(function(){
        chdir("$htdocsDir");
        putenv("GIT_DIR");
        exec("composer update");
    })
    ->clear("\n -> ")->plain("")->andFinallySay("OK");

\$ph->say(">> All Done <<")->withoutACommand();

POSTHOOK;
    }



    /**
     * Helper function to get IP's
     */
    protected function getIps()
    {
        exec("ip -4 -o addr show label eth*", $arr);
        foreach ($arr as $k => $ip) {
            $arr[$k] = "     " . str_pad(self::filterIP($ip), 15) . "   " . $this->appName . ".dev\n";
        }
        return implode("", $arr);
    }

    /**
     * Helper function to extract IP's from console text
     */
    protected static function filterIP($ipstr)
    {
        $start = strpos($ipstr, "inet") + 5;
        $length = (strpos($ipstr, "/", $start)) - $start;
        return substr($ipstr, $start, $length);
    }
}
