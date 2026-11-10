# Brèagha Clinic website

PHP website for Brèagha Clinic. The GitHub repository is the source for code updates; Hostinger deployment should target `breaghaclinic.com` from the `main` branch.

## Sensitive runtime files

`config/connection.php` and `config/telegram-config.php` contain server credentials and are intentionally excluded from Git. Keep the production copies on Hostinger. Do not commit credentials, session files, logs, cookies, or uploaded customer data.

## Deployment

The project is hosted on Hostinger. Connect the private GitHub repository `aliIslam123/BreaghaClinc` in hPanel and enable automatic deployment from `main` to the existing website document root. Keep Hostinger's production database configuration in place when deploying.
