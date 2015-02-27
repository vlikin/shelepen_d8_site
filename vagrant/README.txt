=== Summary ===
It tunes the development server environment.

=== Installation ===
sudo apt-get install virtualbox vagrant virtualbox-dkms ansible

* To add box:
vagrant box add ubuntu/trusty64 https://oss-binaries.phusionpassenger.com/vagrant/boxes/latest/ubuntu-14.04-amd64-vbox.box

* Set up environment.
vagrant up

* Update environment
vagarant provision

* Suspend the virtual server.
vagrant suspend

* Wake up the virtual server.
vagrant up

* Correct /etc/hosts
2.4.8.2    shelepen.vagrant

=== Usage ===