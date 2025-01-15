#!/bin/bash

# Path to your PHP file (relative path)
PHP_FILE="./version_checker.php"

# Cron job schedule (every 12 hours)
CRON_SCHEDULE="0 */12 * * *"

# Add the cron job
(crontab -l 2>/dev/null; echo "$CRON_SCHEDULE /usr/bin/php $PHP_FILE") | crontab -