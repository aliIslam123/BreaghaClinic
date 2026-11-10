# Brèagha Clinic website

PHP website for Brèagha Clinic.

- GitHub repository: https://github.com/aliIslam123/BreaghaClinic
- Deployment branch: `main`
- Hostinger domain: `breaghaclinic.com`

## Production configuration

`config/connection.php` and `config/telegram-config.php` contain production credentials and are intentionally excluded from Git. Keep the production copies safely configured on Hostinger. Before deploying to `public_html`, make sure the live database configuration remains available to the app; Hostinger deployment can replace files in the target directory.

## Automatic deployment

In Hostinger hPanel, connect this GitHub repository under **Websites → breaghaclinic.com → Advanced → Git**, choose `main`, and deploy to `public_html`. After the first deployment, pushes to `main` can trigger automatic deployment.
