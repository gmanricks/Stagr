VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
	# base settings
	config.vm.box = "debian"
	config.vm.box_url = "https://dl.dropbox.com/u/30949096/debian.box"
	config.vm.network "public_network"
	# virtualbox specific customisation(s)
	config.vm.provider "virtualbox" do |v|
		v.customize ["modifyvm", :id, "--memory", "512"]
		v.customize ["modifyvm", :id, "--cpuexecutioncap", "50"]
	end
	# puppet provisioning
	config.vm.provision :puppet do |puppet|
		puppet.manifests_path = "manifests"
		puppet.manifest_file = "fortrabbit.pp"
		puppet.options = ["--templatedir", "/vagrant/templates"]
	end
end
