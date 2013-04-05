class fortrabbit {
	
	$phpExtensions = [ 'php5-xdebug', 'php5-tidy', 'php5-sqlite', 'php5-redis', 'php5-pgsql', 'php5-mysqlnd', 'php5-memcache', 'php5-memcached', 'php5-mcrypt', 'php5-imagick', 'php5-http', 'php5-gmp', 'php5-gd', 'php5-curl', 'php5-apc', 'php5-intl', 'php5-igbinary', 'php5-mongo', 'php5-oauth', 'php5-phalcon', 'php5-runkit', 'php5-stats', 'php5-stomp', 'php5-yaf', 'php5-yaml' ]
	$gitHookDir = '/home/vagrant/PHP-GIT-Hooks'
	
	File {
		owner	=> root,
		group	=> root,
		ensure	=> present,
	}
	
	Exec {
		path	=> '/bin:/usr/bin:/sbin:/usr/sbin'
	}
	
	Package {
		ensure	=> installed
	}
	
	file {
		
		/*
		 * System & user files
		 */
		'/etc/motd':
			source	=> '/vagrant/files/motd';
		
		'/home/vagrant/.bash_profile':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0644',
			source	=> '/vagrant/files/bash_profile';
	
		'/home/vagrant/.vimrc':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0644',
			source	=> '/vagrant/files/vimrc';
		
		'/home/vagrant/.vim':
			ensure	=> directory,
			owner	=> vagrant,
			group	=> vagrant;
		
		'/home/vagrant/.vim/colors':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> File['/home/vagrant/.vim'];
		
		'/home/vagrant/.vim/colors/solarized.vim':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0644',
			source	=> '/vagrant/files/solarized.vim',
			require	=> File['/home/vagrant/.vim/colors'];
		
		/*
		 * Admin Site
		 */
		'/var/www/web':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require => Package['apache2-mpm-worker'];

		'/var/www/web/stagr':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			mode    => '0755',
			source  => '/vagrant/files/default-site',
			recurse => true,
			require => File['/var/www/web'];

		'/etc/apache2/sites-available/0000-default':
			owner   => 'root',
			group   => 'root',
			mode    => '0644',
			source  => '/vagrant/files/default-vhost.conf',
			require => File['/var/www/web/stagr'];

		'/etc/apache2/sites-enabled/0000-default':
			ensure	=> 'link',
			target	=> '/etc/apache2/sites-available/0000-default',
			require => File['/etc/apache2/sites-available/0000-default'];

		'/etc/apache2/sites-enabled/000-default':
			ensure	=> 'absent',
			require	=> Package['apache2-mpm-worker'];


		'/etc/php5/fpm/pool.d/stagr.conf':
			owner   => 'root',
			group   => 'root',
			mode    => '0644',
			source  => '/vagrant/files/default-fpm.conf',
			require	=> Package['php5-fpm'];
		
		'/var/fpm':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> Package['php5-fpm'];

		'/var/fpm/socks':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> File['/var/fpm'];

		'/var/fpm/socks/stagr':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> File['/var/fpm/socks'];

		'/var/fpm/prepend':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> File['/var/fpm'];

		'/var/fpm/prepend/stagr':
			ensure	=> directory,
			owner	=> 'vagrant',
			group	=> 'vagrant',
			require	=> File['/var/fpm/prepend'];

		'/var/fpm/prepend/stagr/prepend.php':
			owner   => 'vagrant',
			group   => 'vagrant',
			mode    => '0644',
			source  => '/vagrant/files/default-prepend.conf',
			require	=> File['/var/fpm/prepend/stagr'];
		
		'/var/www/web/stagr/redir/php':
			ensure	=> 'link',
			target	=> '/var/www/web/stagr/htdocs',
			require => File['/var/www/web/stagr'];

		/*
		 * Apt sources
		 */
		'/etc/apt/sources.list.d':
			ensure	=> directory;
		
		'/etc/apt/sources.list.d/dotdeb.list':
			mode	=> '0600',
			content	=> "deb http://packages.dotdeb.org squeeze all\ndeb-src http://packages.dotdeb.org squeeze all\ndeb http://packages.dotdeb.org squeeze-php54 all\ndeb-src http://packages.dotdeb.org squeeze-php54 all",
			require	=> File['/etc/apt/sources.list.d'];
		
		'/etc/apt/sources.list.d/frbit.list':
			mode	=> '0600',
			content	=> "deb http://debrepo.frbit.com/ frbit-squeeze main",
			require	=> File['/etc/apt/sources.list.d'];
		
		
		/*
		 * FPM configuration
		 */
		'/etc/php5/fpm/pool.d/www.conf':
			ensure	=> absent,
			require	=> Package['php5-fpm'],
			notify	=> Exec['restart-fpm'];
		
		
		/*
		 * Stagr
		 */
		'/usr/bin/stagr':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0755',
			source	=> '/vagrant/files/stagr.php';
		
		'/opt/stagr':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0755',
			ensure	=> directory;
		
		'/opt/stagr/lib':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0755',
			ensure	=> directory,
			source	=> '/vagrant/files/stagr-libs',
			recurse	=> true,
			purge	=> true,
			require	=> File['/opt/stagr'];
		
		'/opt/stagr/lib/cilex.phar':
			owner	=> vagrant,
			group	=> vagrant,
			mode	=> '0644',
			source	=> '/vagrant/files/cilex.phar',
			require	=> File['/opt/stagr'];
	}
	
	exec {
		
		/*
		 * Apt keys & update & upgrade
		 */
		'dotdeb-key':
			command	=> 'wget -O /etc/apt/.dotdeb.key http://www.dotdeb.org/dotdeb.gpg && sudo apt-key add /etc/apt/.dotdeb.key',
			require	=> File['/etc/apt/sources.list.d/dotdeb.list'],
			unless	=> 'test -f /etc/apt/.dotdeb.key',
			notify	=> Exec['update-apt'];
		
		'frbit-key':
			command => 'wget -O /etc/apt/.frbit.key http://debrepo.frbit.com/frbit.gpg && sudo apt-key add /etc/apt/.frbit.key',
			require => File['/etc/apt/sources.list.d/frbit.list'],
			unless	=> 'test -f /etc/apt/.frbit.key',
			notify	=> Exec['update-apt'];
		
		'update-apt':
			command		=> 'apt-get update',
			require		=> Exec['dotdeb-key', 'frbit-key'],
			notify		=> Exec['upgrade-apt'],
			refreshonly	=> true;
		
		# [maybe: call on every update?]
		'upgrade-apt':
			command		=> 'apt-get upgrade -y -q -o Dpkg::Options::=--force-confold --force-yes',
			require		=> Exec['update-apt'],
			refreshonly	=> true;
		
		
		/*
		 * Apache upgrade after install
		 */
		'upgrade-apache':
			command		=> 'a2enmod actions ; a2enmod expires ; a2enmod auth_plain ; a2enmod fastcgi ; a2enmod headers ; a2enmod proxy ; a2enmod proxy_http ; a2enmod rewrite ; service apache2 restart',
			require		=> Package['libapache2-mod-fastcgi', 'apache2-mpm-worker'],
			subscribe	=> Package['libapache2-mod-fastcgi', 'apache2-mpm-worker'];
		
		
		/*
		 * FPM restart after install
		 */
		'restart-fpm':
			command		=> 'service php5-fpm restart',
			require		=> File['/etc/php5/fpm/pool.d/www.conf'],
			subscribe	=> File['/etc/php5/fpm/pool.d/www.conf'];
		
		
		/*
		 * Composer install
		 */
		'install-composer':
			cwd		=> '/tmp',
			command	=> 'curl -s https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod 0755 /usr/local/bin/composer',
			require	=> Package[ 'php5-cli' ],
			unless	=> 'test -f /usr/local/bin/composer';
		
		
		/*
		 * Install hooks package
		 */
		# [maybe: call on every update?]
		'install-phooks':
			path	=> '/bin:/usr/bin',
			cwd		=> '/home/vagrant/',
			command	=> "[ -d ${gitHookDir}/.git ] && cd ${gitHookDir} && git pull --all || git clone https://github.com/gmanricks/PHP-GIT-Hooks.git",
			unless	=> 'test -d /home/vagrant/PHP-GIT-Hooks/.git';
		
		/*
		 * Run stagr setup-admin
		 *//*
		'setup-admin':
			path		=> '/bin:/usr/bin',
			command		=> 'stagr install-admin',
			require		=> [
				File['/opt/stagr/lib', '/usr/bin/stagr'],
				Package['php5-cli']
			],
			refreshonly	=> true;*/
	}
	
	
	package {
		
		/*
		 * Apache, FPM & PHP
		 */
		'apache2-mpm-worker':
			require	=> Exec['update-apt'];
		
		['php5-fpm', 'libapache2-mod-fastcgi']: 
			require	=> Package['apache2-mpm-worker'],
			notify	=> Exec['upgrade-apache'];

		'php5-cli':
			require	=> Package['php5-fpm'];

		$phpExtensions:
			require	=> Package['php5-fpm'];
		
		
		/*
		 * MySQL server
		 */
		'mysql-server-5.5':
			require	=> Exec['update-apt'];
	}

}

include fortrabbit
