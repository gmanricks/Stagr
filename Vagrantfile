Vagrant.configure("2") do |config|
	config.vm.box = "debian"
	config.vm.box_url = "https://dl.dropbox.com/u/30949096/debian.box"
	config.vm.network :public_network
	config.vm.provider "virtualbox" do |v|
		v.customize ["modifyvm", :id, "--memory", 512]
	end
	config.vm.provision :puppet do |puppet|
		puppet.manifests_path = "manifests"
		puppet.manifest_file = "fortrabbit.pp"
		puppet.options = ["--templatedir", "/vagrant/templates"]
	end
end
