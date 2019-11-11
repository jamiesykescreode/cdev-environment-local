# cdev-environment-local
The Local environment plugin for cdev

Over the past few months we have had some hiccups with Docker and memory usage etc causing some strange issues. Environments have also been very slow to run lately.

With the last AMPPS update being over a year ago and the latest supported PHP version being 7.1 it's difficult to test certain sites fully as some newer sites are currently using PHP 7.2.

The CDev Local environment plugin aims to help this by using the existing local modules Apache, PHP and MariaDB (a mysql drop in replacement). It aims to improve the workflow however due to the nature of it there are a number of very in depth dependencies required in order to get it to work.

## Installing

You can install the plugin with:

`cdev plugin:install cdev/environment-local`

## Dependencies

The following dependencies are required in order to get CDev Local to run properly. The plugin is still in early stages at the moment with a number of features absent but the basics of running your dev environment are available (starting, stopping and nuking, and database imports).

Here is a list of the requirements in order to get CDev Local running:

 - [Apache](https://app.tettra.co/teams/creodechat/pages/cdev-local-apache)
 - [PHP](https://app.tettra.co/teams/creodechat/pages/cdev-local-php)
 - [MySQL/MariaDB](https://app.tettra.co/teams/creodechat/pages/cdev-local-mysql)
 - [DNSMasq](https://app.tettra.co/teams/creodechat/pages/cdev-local-dnsmasq)
 - [PV (Optional)](https://app.tettra.co/teams/creodechat/pages/cdev-local-pv)

## Configuration

Once the above dependencies have been installed correctly and the plugin has been installed via the *Installing* section above you can run the cdev configure command inside the directory you are setting up. This should now show a `local` plugin alongside the docker one. You can follow the steps as normal with a few extra options:

- You can now select the version of PHP to use as part of this.
- There is a new subfolder for apache setting which is for websites that have been setup using composer or where the main entrypoint (index.php file) to the application exists inside a subfolder and not directly inside the src folder.

The rest of the process is similar and cdev local will setup a new apache host in the following format `[project_name].dev.com`.

## Troubleshooting

Whilst working on this and doing a database import originally I got a `MySQL has gone away` error. In order to fix this you can do the following: 

- `nano /usr/local/etc/my.cnf`
- Add `max_allowed_packet=64M` under a [mysqld] header

On AMPPS I had this setting set to 256M but 64 seemed to solve it for my particular environment.