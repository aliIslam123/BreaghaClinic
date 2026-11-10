# Brèagha Clinic website

PHP website for Brèagha Clinic.

- GitHub repository: https://github.com/aliIslam123/BreaghaClinic
- Deployment branch: `main`
- Hostinger domain: `breaghaclinic.com`

## Production configuration

Database and Telegram credentials are never committed. For local work, keep them in ignored files named `config/connection.local.php` and `config/telegram-config.local.php`. On Hostinger, store the production copies outside the public document root at `../private/connection.php` and `../private/telegram-config.php` relative to `public_html`. The tracked files in `config/` load those private settings. Create or copy these production files before deploying to `public_html`.

## Automatic deployment

In Hostinger hPanel, connect this GitHub repository under **Websites → breaghaclinic.com → Advanced → Git**, choose `main`, and deploy to `public_html`. After the first deployment, pushes to `main` can trigger automatic deployment.
