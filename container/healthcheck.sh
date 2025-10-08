#!/bin/sh

check_cron() {
  service cron status
}

check_apache2() {
  nc -z -v 127.0.0.1 80
}

# Check if cron is running
check_cron || service cron start
sleep 1
check_cron || exit 1

# Check if apache2/httpd is running
check_apache2 || exit 1
