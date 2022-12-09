#!/bin/bash
while true; do
    sleep 60
        runuser -u www-data -- touch /var/www/schedule_date.log
	runuser -u www-data -- echo `date` >> /var/www/schedule_date.log
	runuser -u www-data -- /usr/local/bin/php /var/www/artisan schedule:run
done
