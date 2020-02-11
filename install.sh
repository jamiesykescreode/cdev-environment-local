#!/bin/bash


echo '======================='
echo '== APACHE STEP BEGIN =='
echo '======================='

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

echo "Updating docroot to $apache_docroot"
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

echo '======================='
echo '=== APACHE STEP END ==='
echo '======================='
echo ''
echo ''
echo '======================='
echo '=== PHP STEPS BEGIN ==='
echo '======================='

echo 'Tapping exolnet/homebrew-deprecated'
brew tap exolnet/homebrew-deprecated

echo 'Installing all the phpses'
brew install php@5.6 php@7.0 php@7.1 php@7.2 php@7.3 php@7.4

echo 'Updating php.ini date.timezone to UTC'
echo 'for php 5.6'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/5.6/php.ini
echo 'for php 7.0'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/7.0/php.ini
echo 'for php 7.1'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/7.1/php.ini
echo 'for php 7.2'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/7.2/php.ini
echo 'for php 7.3'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/7.3/php.ini
echo 'for php 7.4'
sudo sed -i -e 's,;date.timezone =,date.timezone = UTC,g' /usr/local/etc/php/7.4/php.ini

echo 'Switching to PHP 7.3'
brew unlink php@5.6 php@7.0 php@7.1 php@7.2 && brew link --force --overwrite php@7.3

echo 'Adding LoadModule statements in httpd.conf'

apache_add_loadmodule ( ) {
    loadmodule_statement=$1

    grep -q "$loadmodule_statement" /usr/local/etc/httpd/httpd.conf

    if [ $? == 1 ]
    then
        echo 'LoadModule statement not found, adding '$loadmodule_statement 
        echo "#$loadmodule_statement" >> /usr/local/etc/httpd/httpd.conf
    fi
}

apache_add_loadmodule "LoadModule php5_module /usr/local/opt/php@5.6/lib/httpd/modules/libphp5.so"
apache_add_loadmodule "LoadModule php7_module /usr/local/opt/php@7.0/lib/httpd/modules/libphp7.so"
apache_add_loadmodule "LoadModule php7_module /usr/local/opt/php@7.1/lib/httpd/modules/libphp7.so"
apache_add_loadmodule "LoadModule php7_module /usr/local/opt/php@7.2/lib/httpd/modules/libphp7.so"
apache_add_loadmodule "LoadModule php7_module /usr/local/opt/php@7.3/lib/httpd/modules/libphp7.so"
apache_add_loadmodule "LoadModule php7_module /usr/local/opt/php@7.4/lib/httpd/modules/libphp7.so"

echo 'Adding index.php as default directory index in apache (httpd)'
sudo sed -i -e '/<IfModule dir_module>/,/<\/IfModule>/ s/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /usr/local/etc/httpd/httpd.conf

echo 'Adding php handler in apache (httpd)'
grep -q "<FilesMatch \\\.php$>" /usr/local/etc/httpd/httpd.conf
if [ $? == 1 ]; then
    echo 'php handler not found, adding'
    sudo tee -a /usr/local/etc/httpd/httpd.conf > /dev/null <<EOT

<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>
EOT
fi


echo 'Restarting apache'
sudo apachectl -k stop
sudo apachectl start


echo 'Adding PHP version switching tool (sphp)'
if [ ! -f "/usr/local/bin/sphp" ]; then
    echo 'sphp does not exist, downloading'
    curl -L https://gist.githubusercontent.com/rhukster/f4c04f1bf59e0b74e335ee5d186a98e2/raw > /usr/local/bin/sphp
fi

if [ ! -x "/usr/local/bin/sphp" ]; then
    echo "sphp can't be executed, adding permissions"
    chmod +x /usr/local/bin/sphp
fi

echo "Switching to PHP 7.3"
sphp 7.3


echo "Updating PHP listen port"
echo 'for php 5.6'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9056/g' /usr/local/etc/php/5.6/php-fpm.conf
echo 'for php 7.0'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9070/g' /usr/local/etc/php/7.0/php-fpm.d/www.conf
echo 'for php 7.1'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9071/g' /usr/local/etc/php/7.1/php-fpm.d/www.conf
echo 'for php 7.2'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9072/g' /usr/local/etc/php/7.2/php-fpm.d/www.conf
echo 'for php 7.3'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9073/g' /usr/local/etc/php/7.3/php-fpm.d/www.conf
echo 'for php 7.4'
sudo sed -i -e 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9074/g' /usr/local/etc/php/7.4/php-fpm.d/www.conf


echo "Setting PHP agents to run on boot"
ln -sfv /usr/local/opt/php@5.6/*.plist ~/Library/LaunchAgents
ln -sfv /usr/local/opt/php@7.0/*.plist ~/Library/LaunchAgents
ln -sfv /usr/local/opt/php@7.1/*.plist ~/Library/LaunchAgents
ln -sfv /usr/local/opt/php@7.2/*.plist ~/Library/LaunchAgents
ln -sfv /usr/local/opt/php@7.3/*.plist ~/Library/LaunchAgents
ln -sfv /usr/local/opt/php@7.4/*.plist ~/Library/LaunchAgents

echo "Starting PHP agents"
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@5.6.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@7.0.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@7.1.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@7.2.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@7.3.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.php@7.4.plist


echo '======================='
echo '==== PHP STEPS END ===='
echo '======================='
echo ''
echo ''
echo '======================='
echo '== MYSQL STEPS BEGIN =='
echo '======================='

echo 'Installing mariadb'
brew install mariadb

echo 'Starting mariadb'
brew services start mariadb


echo '======================='
echo '=== MYSQL STEPS END ==='
echo '======================='
echo ''
echo ''
echo '======================='
echo '= DNSMASQ STEPS BEGIN ='
echo '======================='

echo 'Installing dnsmasq'
brew install dnsmasq

echo 'Adding dnsmasq for .dev.com'
grep -q ".dev.com" /usr/local/etc/dnsmasq.conf
if [ $? == 1 ]
then
    echo 'dnsmasq for .dev.com not found, adding'
    echo 'address=/.dev.com/127.0.0.1' > /usr/local/etc/dnsmasq.conf
fi

echo 'Starting dnsmasq'
sudo brew services start dnsmasq

if [ ! -f "/etc/resolver/dev.com" ]
then
    echo 'adding resolver for dev.com'
    sudo mkdir -vp /etc/resolver

    sudo bash -c 'echo "nameserver 127.0.0.1" > /etc/resolver/dev.com'
fi


echo '======================='
echo '== DNSMASQ STEPS END =='
echo '======================='
echo ''
echo ''
echo '======================='
echo '==== PV STEP BEGIN ===='
echo '======================='

echo 'Installing pv'
brew install pv

echo '======================='
echo '===== PV STEP END ====='
echo '======================='


echo 'Installation Complete'

