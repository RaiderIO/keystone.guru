## Intro
This tutorial is for developers who would like to contribute to Keystone.guru and want to setup a local version of the site so they can start developing.

This is a step-by-step guide of starting from scratch and installing a working local development environment. Once I figure everything out myself,
I will provide a fully working Vagrant box which you can just start up and everything will work (bar a few things which I will explain).

PLEASE FOLLOW THE STEPS CLOSELY. IF THERE'S ANY ERROR PLEASE CONTACT ME. I'M PRETTY SURE I FORGOT THINGS OR CAN OTHERWISE HELP YOU GET SETUP.

## Installing VirtualBox
Go to https://www.virtualbox.org/ and follow the steps as necessary for your environment.

Make sure you have virtualization enabled in your BIOS.

## Download vagrant
Go to https://www.vagrantup.com/downloads.html and follow the steps as necessary for your environment.

## Download Homestead box
```bash
vagrant box add laravel/homestead
```

Select `virtualbox` when asked.

## Download Node.js, PHP and Composer
Before continuing, make sure you have a functioning [Node.js](https://nodejs.org/en/) version installed, 
a local [PHP](https://windows.php.net/download/) (take 7.3 when in doubt) installation (with PHP added to PATH) and [Composer](https://getcomposer.org/download/).

This should in theory not be needed, but in practice I couldn't ever run `npm install` from the VM without it erroring out. YMMV but I needed a functioning local environment for it. 


## Clone Homestead repo
You can install it in a different directory if you want.
```bash
cd ~
git clone https://github.com/laravel/homestead.git Homestead
```

Checkout proper version (I got recommended to use this one, guess you can skip this?)
```bash
cd Homestead 
git checkout v6.5.0
```

Once you have cloned the Homestead repository, run the bash init.sh command from the Homestead directory to create the Homestead.yaml configuration file. The Homestead.yaml file will be placed in the ~ /.homestead hidden directory:

```bash
./init.sh
```

## Checkout Keystone.guru
Create a folder where you'd like to store your code. Remember this path.

```bash
cd C:\Git\
git clone https://github.com/Wotuu/keystone.guru.git keystone.guru
```

This should place all Keystone.guru sources in a `keystone.guru` folder under `C:\Git`. You can of course change this directory to a location of your preference.

## Edit Homestead.yaml

Edit your `Homestead.yaml` file to contain the following:

```yaml
ip: "192.168.10.10"
memory: 2048
cpus: 1
provider: virtualbox

authorize: ~/.ssh/id_rsa.pub

keys:
    - PATH_TO_PRIVATE_KEY

folders:
    - map: C:/
      to: /home/vagrant/Git/

sites:
    - map: phpmyadmin.test
      to: /home/vagrant/phpmyadmin
    - map: keystone.test
      to: /home/vagrant/Git/keystone.guru/public

databases:
    - homestead
```


## Vagrantfile

Edit your `Vagrantfile` file to contain the following:

```yaml
Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

    (...)

	config.vm.provider "virtualbox" do |v|
		v.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
	end
	
end
```

The three lines at the end should simply be placed there. Any other lines must remain where they are. If you don't do this, you will not be able to run `npm` from your Vagrant machine.

Note: change the `PATH_TO_PRIVATE_KEY` to your SSH private key. If you don't know what this is, Google how to create a ssh keypair. You can possibly remove the entire block and I think they'll generate temporary keys for you but not 100% sure.

## Add to hosts
Open up `C:\Windows\system32\drivers\etc\hosts` file in an elevated notepad. Add the following two lines:

```
192.168.10.10  keystone.test
192.168.10.10  phpmyadmin.test
```

## Start your Vagrant box
In an elevated command prompt (run as administrator) `cd` to where you checked out Homestead, and run `vagrant up`. This should boot up your VM. If you get any errors at this point, hit me up.

## Once you're in
Run this bash to install PhpMyAdmin (skip if you use MySQLWorkbench or anything else to manage your database)
```bash
curl -sS https://raw.githubusercontent.com/grrnikos/pma/master/pma.sh | sh
```
See https://stackoverflow.com/a/23789879/771270 for more info.

Now that you've installed PHPMyAdmin, reboot the VM so that all changes are applied. Can't hurt to run `vagrant provision` as well.

## Update crontab

Add this line to your crontab:

``` 
*   * *   *   *   root php /home/vagrant/Git/keystone.guru/artisan schedule:run 
```

This will call the above command every minute, which Laravel requires in order to run scheduled tasks (refreshing of the route thumbnails).

## Setup database
Go to `http://phpmyadmin.test` and log in using `homestead//password`. If you get `no input file specified` you need to either run `vagrant provision` and/or verify your file mapping in your Homestead.yaml is 100% correct.

Create a database for Keystone.guru to save its data. Create a user (or use the default one, I guess) for it as well.
Create another database for the statistics tracker.

## Setup Keystone.guru
Go to the folder where you installed Keystone.guru and make a copy of `.env.example` and rename it `.env`. Fill it out for as far as you can. 
You probably don't need most of it. But you do need things like the database and its users.

Open a (Git) bash terminal on your Windows machine and run the following:

```bash
cd C:\Git\keystone.guru
./update_dependencies.sh
```

The above installs all dependencies that are necessary for Keystone.guru to function. This will take a while. Once this has completed, continue.

Start up Vagrant again, and CD to the folder where you downloaded Keystone.guru. Don't do this from your local machine.

Create an encryption key for the app.

```bash
php artisan key:generate
```

Create all relevant database tables:

```bash
./migrate.sh
```

Seed the database with info:

```bash
php artisan db:seed
```


### Map tiles
Contact me for a link to the map tiles, and extract those to `keystone.guru/public/images/tiles/bfa` etc.

## Running the site
By now it should work. Navigate to `keystone.test` and you should see the website. But that's probably wishful thinking that it works now.
At least these are MOST of the steps you need to do to get it running from a clean start.

## SFTP access to your VM
```
Host: 127.0.0.1
Port: 2222
Type: normal
Username: vagrant
Password: vagrant
```

## Increasing the speed of the VM
By default it's rather slow to be honest. Try the following:

```bash
vagrant plugin install vagrant-winnfsd
```

Then add `type: "nfs"` to your folder mapping under your `to:` directive. Provision the machine again and it will speed up the machine by a few factors.