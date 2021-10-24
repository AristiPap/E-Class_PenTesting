apt-get update
apt-get install -y --no-install-recommends software-properties-common		# for add-apt-repository

add-apt-repository ppa:sergey-dryabzhinsky/php53
add-apt-repository ppa:mati75/php
apt-get update

apt install -y --no-install-recommends --allow-unauthenticated \
	apache2 php53p-cli php53p-mod-mysqlnd php53p-mod-mcrypt php53p-mod-pcntl php53p-mod-mbstring php53-mod-gd libapache2-mod-php53p

a2dismod mpm_event
a2enmod mpm_prefork
a2enmod php53

php53-modset -s apache2 -e 02-mbstring 03-openssl 04-mysqlnd gd mcrypt mysql mysqli pcntl pdo_mysq

exit 0
