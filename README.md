# PWCashScheduler
PWCashScheduler is a way to send cash to users based on cultivation level (level2)

# Installation
 - git clone https://github.com/brucedeity/PWCashScheduler.git
 - composer install
 - Change .env.example name to .env in includes folder
 - Configure .env with the connection to your mysql
 - Open includes/configs.php, edit it based on server version, ports and define a value in cash for all cultivation levels
 > Note: if you don't want a certain cultivation level to receive any reward you should just put a 0 value.
 - Edit cron key with a valid cron command
 - Run ./start.sh to start scheduler
 - And ./stop.sh top stop scheduler
