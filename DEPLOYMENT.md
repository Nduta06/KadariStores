# Deploying to Oracle Cloud (Always Free) with MySQL

This guide takes the app from "running locally with SQLite" to "running on a
real, always-on server with MySQL", while **keeping all data you've already
entered** — you will not need to re-type items, deliveries, or sales.

It assumes you're moving from a local setup where the database lives at
`database/database.sqlite` (the default for local development, per the main
[README](README.md)).

## 1. Create the VPS

1. Sign up at [cloud.oracle.com](https://cloud.oracle.com) for an Always Free
   account.
2. Create a Compute instance using an **Always Free eligible shape** (either
   an Ampere A1 "VM.Standard.A1.Flex" or a "VM.Standard.E2.1.Micro"), with
   **Ubuntu 24.04** as the image.
3. Download the generated SSH key pair when prompted — you'll need it to log
   in.
4. Note the instance's **public IP address**.
5. Under the instance's **Virtual Cloud Network → Security Lists**, add
   ingress rules for TCP ports `80` (HTTP) and `443` (HTTPS) from
   `0.0.0.0/0`, in addition to the default `22` (SSH) rule.

Connect to confirm access:

```bash
ssh -i /path/to/your-key.pem ubuntu@<server-ip>
```

## 2. A domain name (needed for HTTPS)

Browsers require HTTPS for full PWA support (install prompt, offline
caching). That requires a real hostname pointing at the server — a bare IP
address can't get a certificate.

- If you already own a domain, add an `A` record pointing a subdomain (e.g.
  `shop.yourdomain.com`) at the server's public IP.
- If not, [DuckDNS](https://www.duckdns.org) gives a free subdomain (e.g.
  `kadari-stores.duckdns.org`) that works fine with Let's Encrypt.

The rest of this guide uses `your-domain.example` — replace it with yours.

## 3. Install the server stack

On the server:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server git unzip curl

# PHP 8.4 and the extensions Laravel needs
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node (for building the frontend assets)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

## 4. Create the MySQL database

```bash
sudo mysql -e "
CREATE DATABASE kadari_stores CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kadari'@'localhost' IDENTIFIED BY 'CHANGE-THIS-PASSWORD';
GRANT ALL PRIVILEGES ON kadari_stores.* TO 'kadari'@'localhost';
FLUSH PRIVILEGES;
"
```

Use a real, unique password — this guide's placeholder is not safe to keep.

## 5. Get the app onto the server

```bash
cd /var/www
sudo git clone https://github.com/Nduta06/Kadari-Stores.git kadari-stores
sudo chown -R $USER:$USER kadari-stores
cd kadari-stores
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 6. Configure `.env` for MySQL

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kadari_stores
DB_USERNAME=kadari
DB_PASSWORD=CHANGE-THIS-PASSWORD
```

Create the schema — **without** `--seed`, so it doesn't create a second,
conflicting owner account (your real one is about to be imported):

```bash
php artisan migrate --force
```

## 7. Bring your existing data across

This is the step that keeps everything you've already entered. From your
**local WSL machine** (not the server), copy your current SQLite file to the
server:

```bash
scp "/var/www/html/Personal Projects/Kadari-Stores/database/database.sqlite" \
    ubuntu@<server-ip>:/tmp/kadari-old.sqlite
```

Back on the **server**, import it:

```bash
cd /var/www/kadari-stores
php artisan data:import-sqlite /tmp/kadari-old.sqlite
```

This copies your users (including whatever email/password you've already
set), items, stock-ins and sales into MySQL, preserving IDs and
relationships, and leaves MySQL's auto-increment counters correctly
positioned so new records don't collide with imported ones.

Verify before moving on:

```bash
php artisan tinker --execute="echo App\Models\Item::count().' items, '.App\Models\Sale::count().' sales'.PHP_EOL;"
```

Then remove the uploaded copy — it's no longer needed and shouldn't be left
lying around:

```bash
rm /tmp/kadari-old.sqlite
```

## 8. Set file permissions

```bash
sudo chown -R www-data:www-data /var/www/kadari-stores
sudo chmod -R 775 storage bootstrap/cache
```

## 9. Configure Nginx

Create `/etc/nginx/sites-available/kadari-stores`:

```nginx
server {
    listen 80;
    server_name your-domain.example;
    root /var/www/kadari-stores/public;

    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it:

```bash
sudo ln -s /etc/nginx/sites-available/kadari-stores /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

At this point `http://your-domain.example` should load the app.

## 10. Enable HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.example
```

Certbot edits the Nginx config to serve HTTPS and redirect HTTP to it, and
sets up auto-renewal. Once it finishes, `https://your-domain.example` is
your app's real address — this is the URL to open on the shop owner's phone
and "Add to Home Screen".

## 11. Finish up

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Log in with the account that was imported in step 7 (your real email — not
the `owner@kadaristores.test` placeholder, since that only exists in a fresh
seed, not in your imported data) and confirm the dashboard shows your real
historical items, stock-ins and sales.

## Keeping it updated later

```bash
cd /var/www/kadari-stores
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
