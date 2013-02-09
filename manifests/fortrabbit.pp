class fortrabbit {
	file { '/etc/apt/sources.list.d':
		ensure  => 'directory',
		owner   => 'root',
		group   => 'root';
	}
	file { '/etc/apt/sources.list.d/dotdeb.list':
		ensure  => 'present',
		owner   => 'root',
		group   => 'root',
		mode 	=> '0600',
		content => "deb http://packages.dotdeb.org squeeze all\ndeb-src http://packages.dotdeb.org squeeze all\ndeb http://packages.dotdeb.org squeeze-php54 all\ndeb-src http://packages.dotdeb.org squeeze-php54 all";
	}
	file { '/etc/apt/sources.list.d/frbit.list':
		ensure  => 'present',
		owner   => 'root',
		group   => 'root',
		mode 	=> '0600',
		content => "deb http://debrepo.frbit.com/ frbit-squeeze main";
	}
	exec { 'dotdeb-key':
		path 	=> '/bin:/usr/bin',
		cwd		=> '/tmp',
		command => "wget -O - http://www.dotdeb.org/dotdeb.gpg | sudo apt-key add -",
		require => File['/etc/apt/sources.list.d/dotdeb.list'],
		notify	=> Exec["update-apt"]; 
	}
	exec { 'frbit-key':
		path 	=> '/bin:/usr/bin',
		command => "wget -O - http://debrepo.frbit.com/frbit.gpg | sudo apt-key add -",
		require => File['/etc/apt/sources.list.d/frbit.list'],
		notify	=> Exec["update-apt"];
	}
	exec { 'update-apt':
		path 		=> '/bin:/usr/bin',
		command 	=> 'apt-get update',
		require 	=>	Exec['dotdeb-key', 'frbit-key'],
		refreshonly => true;
	}
	package {
		'apache2-mpm-worker':
			ensure => installed,
			require => Exec['update-apt'];

		['php5-fpm', 'libapache2-mod-fastcgi']: 
			ensure => installed,
			require => Package['apache2-mpm-worker'],
			notify	=> Exec['upgrade-apache'];

		'php5-cli':
			ensure => installed,
			require => Package['php5-fpm'];

		[ "php5-xdebug", "php5-tidy", "php5-sqlite", "php5-redis", "php5-pgsql", "php5-mysqlnd", "php5-memcache", "php5-memcached", "php5-mcrypt", "php5-imagick", "php5-http", "php5-gmp", "php5-gd", "php5-curl", "php5-apc", "php5-intl", "php5-igbinary", "php5-mongo", "php5-oauth", "php5-phalcon", "php5-runkit", "php5-stats", "php5-stomp", "php5-yaf", "php5-yaml" ]: 
			ensure	=> installed, 
			require => Package['php5-fpm'];
	}

	exec { 'upgrade-apache':
		path => '/bin:/usr/bin:/usr/sbin',
		command => 'a2enmod actions ; a2enmod rewrite ; service apache2 restart',
		require => Package['libapache2-mod-fastcgi', 'apache2-mpm-worker'];
	}
	exec { 'composer':
		path => '/bin:/usr/bin',
		command => 'curl -s https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer',
		require => Package[ 'php5-cli' ];
	}

	$gitHookDir = '/home/vagrant/PHP-GIT-Hooks'
	exec { 'getPHooks':
		path	=> '/bin:/usr/bin',
		cwd		=> '/home/vagrant/',
		command	=> "[ -d ${gitHookDir}/.git ] && cd ${gitHookDir} && git pull --all || git clone https://github.com/gmanricks/PHP-GIT-Hooks.git";
	}
	file { '/etc/php5/fpm/pool.d/www.conf':
		ensure => absent,
		require => Package['php5-fpm']
	}
	file { '/usr/local/bin/composer':
		owner   => 'vagrant',
		group   => 'vagrant',
		mode    => '0755',
		ensure  => present,
		require => Exec['composer']; 
	}
	file { '/etc/motd' :
		content => template("motd.erb");
	}
	file { '/usr/bin/stagr' :
		owner	=> 'vagrant',
		group	=> 'vagrant',
		mode	=> '0755',
		ensure	=> present,
		content => template("stagr.erb");
	}
	file { '/home/vagrant/.bash_profile' :
		owner	=> 'vagrant',
		group	=> 'vagrant',
		mode	=> '0644',
		ensure	=> present,
		content	=> template("bash_profile.erb");
	}
	file { '/home/vagrant/.vimrc' :
		owner 	=> 'vagrant',
		group	=> 'vagrant',
		mode	=> '0644',
		ensure	=> present,
		content	=> template("vimrc.erb");
	}
	file { '/home/vagrant/.vim':
		ensure	=> 'directory',
		owner	=> 'vagrant',
		group	=> 'vagrant';
	}
	file { '/home/vagrant/.vim/colors':
		ensure  => 'directory',
		owner   => 'vagrant',
		group   => 'vagrant',
		require => File['/home/vagrant/.vim'];
	}
	file { '/home/vagrant/.vim/colors/solarized.vim':
		ensure  => 'present',
		owner   => 'vagrant',
		group   => 'vagrant',
		mode 	=> '0644',
		content => template("solarized.erb"),
		require => File['/home/vagrant/.vim/colors'];
	}

	/*
		MySQL Server
	*/
	package {
		'mysql-server-5.5':
			ensure	=> installed,
			require	=> Exec['update-apt'];

	}
}

include fortrabbit
