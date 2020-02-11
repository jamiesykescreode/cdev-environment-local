#!/bin/bash


#### INSTALLING APACHE
echo "Stopping apache"
sudo apachectl stop
echo "Removing apache autostart on boot"
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null

echo "Installing apache (httpd) with brew"
brew install httpd

echo "Starting brew service - httpd"
sudo brew services start httpd

### CONFIGURING APACHE
echo "Switching apache from port 8080 to 80"
sudo sed -i -e 's/Listen 8080/Listen 80/g' /usr/local/etc/httpd/httpd.conf

echo "Switching apache default docroot"
echo "Where would you like the default docroot to be? Please use a full path (i.e. no ~)"
read apache_docroot
echo ''

echo 'Creating docroot dir (if it doesnt already exist)'
mkdir -p $apache_docroot

echo 'Adding sample index.html to new docroot'
echo "<h1>My User Web Root</h1>" > $apache_docroot/index.html

echo "Updating docroot"
sudo sed -i -e 's,DocumentRoot "/usr/local/var/www",DocumentRoot "'$apache_docroot'",g' /usr/local/etc/httpd/httpd.conf
sudo sed -i -e 's,Directory "/usr/local/var/www",Directory "'$apache_docroot'",g' /usr/local/etc/httpd/httpd.conf

echo "Setting 'AllowOverride All' for docroot"
# Escape apache docroot for use in sed command
replacement="\/"
apache_docroot_escaped="${apache_docroot//\//$replacement}"
# now replace AllowOverride None with All
sudo sed -i -e '/<Directory "'$apache_docroot_escaped'">/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /usr/local/etc/httpd/httpd.conf

# enable modules
echo 'Enabling rewrite_module'
sudo sed -i -e 's,#LoadModule rewrite_module,LoadModule rewrite_module,g' /usr/local/etc/httpd/httpd.conf
echo 'Enabling proxy_module'
sudo sed -i -e 's,#LoadModule proxy_module,LoadModule proxy_module,g' /usr/local/etc/httpd/httpd.conf
echo 'Enabling proxy_http_module'
sudo sed -i -e 's,#LoadModule proxy_http_module,LoadModule proxy_http_module,g' /usr/local/etc/httpd/httpd.conf
echo 'Enabling proxy_fcgi_module'
sudo sed -i -e 's,#LoadModule proxy_fcgi_module,LoadModule proxy_fcgi_module,g' /usr/local/etc/httpd/httpd.conf
echo 'Enabling vhost_alias_module'
sudo sed -i -e 's,#LoadModule vhost_alias_module,LoadModule vhost_alias_module,g' /usr/local/etc/httpd/httpd.conf

echo 'Including extra vhost configs'
sudo sed -i -e 's,#Include /usr/local/etc/httpd/extra/httpd-vhosts.conf,Include /usr/local/etc/httpd/extra/httpd-vhosts.conf,g' /usr/local/etc/httpd/httpd.conf

echo 'Switching httpd user to you (' $(whoami) ')'
sudo sed -i -e 's,User _www,User '$(whoami)',g' /usr/local/etc/httpd/httpd.conf

echo 'Switching httpd group to staff'
sudo sed -i -e 's,Group _www,Group staff,g' /usr/local/etc/httpd/httpd.conf

echo 'Setting servername to localhost'
sudo sed -i -e 's,#ServerName www.example.com:8080,ServerName localhost,g' /usr/local/etc/httpd/httpd.conf

echo 'Restarting apache'
sudo apachectl -k restart

exit;

### EXAMPLE COMMANDS BELOW


echo "----"
echo "What's the domain name you're setting up? (no http, www or uat.creode.co.uk - e.g. farma, nexus)"
read input_domain
echo ''

full_domain="$input_domain.uat.creode.co.uk"






echo "----"
read -p "Setup site in apache? [y/N]"
echo ''
if [[ $REPLY =~ ^[Yy]$ ]]
then
    sudo sed -i -e 's/\[domainname\]/'$full_domain'/g' /etc/apache2/sites-available/$full_domain.conf

    sudo a2ensite $full_domain

fi
