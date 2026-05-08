# ============================================================
# FULFILLO — COMPLETE DEPLOYMENT GUIDE
# Laravel 11 + PHP 8.2 + MySQL
# ============================================================

# ============================================================
# OPTION A: SHARED HOSTING (cPanel / Namecheap / Hostinger)
# ============================================================

## Step 1: Upload files
Upload your project folder to /home/yourusername/fulfillo
(NOT inside public_html)

## Step 2: Point public_html to Laravel public folder
In cPanel File Manager, edit public_html/.htaccess:
OR create a symlink:

```bash
# Via SSH:
ln -s /home/yourusername/fulfillo/public /home/yourusername/public_html
```

OR edit your domain's Document Root to point to:
/home/yourusername/fulfillo/public

## Step 3: Create database
- cPanel → MySQL Databases → Create DB + User
- Assign user to DB with ALL PRIVILEGES

## Step 4: Configure .env
```bash
cp .env.example .env
```

Edit .env:
```
APP_NAME="Fulfillo"
APP_ENV=production
APP_KEY=  # Will be generated
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
```

## Step 5: Install dependencies & setup
```bash
# Via SSH or cPanel Terminal:
cd /home/yourusername/fulfillo

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 6: Set permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## Step 7: Cron Job (cPanel → Cron Jobs)
Add this cron job (every minute):
```
* * * * * cd /home/yourusername/fulfillo && php artisan schedule:run >> /dev/null 2>&1
```

## Step 8: Queue Worker (if supported)
```bash
php artisan queue:work --daemon &
```

---

# ============================================================
# OPTION B: VPS (Ubuntu 22.04 + Nginx)
# ============================================================

## Step 1: Server Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    php8.2-intl php8.2-redis

# Install Nginx
sudo apt install -y nginx

# Install MySQL 8
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (for assets if needed)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## Step 2: MySQL Database
```bash
sudo mysql -u root -p

CREATE DATABASE fulfillo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fulfillo'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON fulfillo.* TO 'fulfillo'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 3: Deploy Application
```bash
# Create directory
sudo mkdir -p /var/www/fulfillo
sudo chown -R $USER:$USER /var/www/fulfillo

# Clone or upload your project
cd /var/www
git clone https://github.com/yourusername/fulfillo.git fulfillo
# OR: scp -r ./fulfillo yourusername@your-server-ip:/var/www/

cd /var/www/fulfillo

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
nano .env  # Edit database credentials

# Generate app key
php artisan key:generate

# Run migrations + seeders
php artisan migrate --force

# Storage symlink
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 4: Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/fulfillo
```

Paste:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/fulfillo/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Max upload size
    client_max_body_size 20M;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/fulfillo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Step 5: SSL Certificate (Free — Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
# Auto-renewal:
sudo systemctl enable certbot.timer
```

## Step 6: Queue Worker (Supervisor)
```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/fulfillo-worker.conf
```

Paste:
```ini
[program:fulfillo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/fulfillo/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/fulfillo/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fulfillo-worker:*
```

## Step 7: Cron Job
```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/fulfillo && php artisan schedule:run >> /dev/null 2>&1
```

---

# ============================================================
# OPTION C: VERCEL + MYSQL (Serverless)
# ============================================================
# Note: Laravel doesn't run natively on Vercel.
# Use Railway, Render, or Digital Ocean App Platform instead.

## Digital Ocean App Platform (Easiest)
1. Push code to GitHub
2. Go to https://cloud.digitalocean.com/apps
3. Create App → Connect GitHub repo
4. Set Run Command: php artisan serve --host=0.0.0.0 --port=8080
5. Add MySQL managed database
6. Set environment variables from .env
7. Deploy!

---

# ============================================================
# POST-DEPLOYMENT CHECKLIST
# ============================================================

# 1. Test login
#    Visit https://yourdomain.com/login

# 2. Register first account
#    Visit https://yourdomain.com/register

# 3. Configure business settings
#    Settings → Business → Add logo, name, address

# 4. Connect WooCommerce (optional)
#    Integrations → WooCommerce → Enter API keys

# 5. Configure SMS (optional)
#    Integrations → SMS → Enter API key

# 6. Add products
#    Products → Add Product  OR  Products → Sync (from WooCommerce)

# 7. Test POS
#    Bottom nav → POS → Add products → Checkout

# ============================================================
# USEFUL ARTISAN COMMANDS
# ============================================================

# Clear all caches
php artisan optimize:clear

# Re-cache everything
php artisan optimize

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Sync WooCommerce manually
php artisan woo:sync

# Check queue
php artisan queue:monitor

# View logs
tail -f storage/logs/laravel.log

# ============================================================
# ENVIRONMENT VARIABLES REFERENCE
# ============================================================

# .env for production:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://yourdomain.com
#
# DB_CONNECTION=mysql
# DB_HOST=localhost
# DB_PORT=3306
# DB_DATABASE=fulfillo_db
# DB_USERNAME=fulfillo_user
# DB_PASSWORD=strong_password_here
#
# QUEUE_CONNECTION=database    # or redis
# CACHE_DRIVER=file            # or redis
# SESSION_DRIVER=file          # or database
# SESSION_LIFETIME=120
#
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.gmail.com
# MAIL_PORT=587
# MAIL_USERNAME=your@email.com
# MAIL_PASSWORD=app_password
# MAIL_FROM_ADDRESS=your@email.com
# MAIL_FROM_NAME="Fulfillo"
#
# FILESYSTEM_DISK=public
#
# # For Redis (faster, optional)
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
