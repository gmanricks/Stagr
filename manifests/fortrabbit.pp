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
	exec { 'dotdeb-key':
		path 	=> '/bin:/usr/bin',
		cwd		=> '/tmp',
		command => "wget http://www.dotdeb.org/dotdeb.gpg && cat dotdeb.gpg | sudo apt-key add -",
		require => File['/etc/apt/sources.list.d/dotdeb.list'],
		notify	=> Exec["update-apt"]; 
	}
	exec { 'update-apt':
		path 		=> '/bin:/usr/bin',
		command 	=> 'apt-get update',
		require 	=>	Exec['dotdeb-key'],
		refreshonly => true;
	}
	package { 'php5' : 
		ensure => installed,
		require => Exec['update-apt'];
	}
	$packagesArr = [ "php5-xdebug", "php5-tidy", "php5-sqlite", "php5-redis", "php5-pgsql", "php5-mysqlnd", "php5-memcache", "php5-memcached", "php5-mcrypt", "php5-imagick", "php5-http", "php5-gmp", "php5-gd", "php5-curl", "php5-apc", "php5-intl" ]
	package { $packagesArr: 
		ensure	=> installed, 
		require => Package['php5']; 
	}
	exec { 'composer':
		path => '/bin:/usr/bin',
		command => 'curl -s https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer',
		require => Package[$packagesArr];
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
}

include fortrabbit
