# -*- mode: ruby -*-
# vi: set ft=ruby :


#we need to clone our submodules before we can continue
system('git submodule update --init --recursive --remote') || exit!
system('git submodule foreach --recursive git checkout master') || exit!

require './.vagrant/utilities/magento-module'

#set our data
config = {
    :project_name => 'eco-minify',
    :sync_folder  => File.dirname(__FILE__),
    :ip           => '192.168.56.122'
}

configure_magento_module(config)
