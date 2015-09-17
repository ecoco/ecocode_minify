# -*- mode: ruby -*-
# vi: set ft=ruby :


#we need to clone our submodules before we can continue
if File.exist?('.vagrant/utilities')
    system('(cd .vagrant/utilities && git pull origin master)')|| exit!
else
    system('git clone git@bitbucket.org:ecocode/vagrant-utilities.git .vagrant/utilities') || exit!

end

require './.vagrant/utilities/magento-module'

#set our data
config = {
    :project_name => 'eco-minify',
    :sync_folder  => File.dirname(__FILE__),
    :ip           => '192.168.56.122'
}

configure_magento_module(config)
