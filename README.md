# IgG Flat CMS - Lightweight Flat-File CMS

A lightweight PHP 8.1+ flat-file CMS. No database required — everything is file-based.

## Why This CMS?

This is not a "built for open-source" project.

It all started because my wife doesn't use Markdown or FTP, so every time she needed to update the website, she had to ask me.  
I began by writing a simple admin panel, then kept improving it as I used it.

I spent an entire week on it, and it grew far more complete than I expected.  
When I was about to push it to GitHub, I realized — **the entire project was under 1MB**. Even I was surprised.

The result is a genuinely lightweight, practical, zero-database flat-file CMS.  
No complicated business model, no hidden affiliate marketing — just something useful, shared.

## Requirements

- PHP 8.1 or higher
- Apache or Nginx web server
- mod_rewrite (Apache) or equivalent URL rewriting
- Composer (for installing dependencies)

> ⚠️ **Important for Apache users**
>
> This project uses `.htaccess` to protect sensitive directories and enable clean URLs. **Apache must allow `.htaccess` execution:**
>
> In your Apache config (`/etc/apache2/sites-available/000-default.conf` or vhost config), add to the project directory block:
>
> ```apache
> <Directory /path/to/html/>
>     Options Indexes FollowSymLinks
>     AllowOverride All
>     Require all granted
> </Directory>
> ```
>
> If `AllowOverride` cannot be enabled (e.g., shared hosting), `.htaccess` will be ignored, exposing sensitive directories (`vendor/`, `content/`, `libs/`, etc.) via HTTP.

## Features

- **No database** — all content stored as Markdown files
- **Blog system** — posts, tags, drafts, tag filtering, pagination (12 per page)
- **Product management** — catalog, pricing (supports text like "Custom Quote"), stock, drag-and-drop sorting
- **Static pages** — custom pages with Markdown content
- **Contact form** — built-in form with message management and reply functionality
- **File management** — image upload, drag & drop, file browser (EasyMDE integration)
- **Internal documents** — company knowledge base / document management
- **Visit counter** — automatically tracks page, blog, and product views
- **Theme customization** — customize colors and navigation style from admin panel
- **Admin panel** — full-featured backend for content management
- **Multi-language** — YAML-based language pack system, one-click switching, extendable to any language (Traditional Chinese and English included), with per-string overrides
- **Auto-install dependencies** — automatically runs `composer install` on first access
- **Responsive design** — mobile-friendly RWD layout
- **Security** — CSRF protection, bcrypt password hashing, path traversal protection, brute-force login protection, secure session settings

## Installation

### 1. Download

```bash
git clone https://github.com/Solo-man-IGG/IgG-Flat-CMS
```

### 2. Set permissions

Ensure the following directories are writable:

```bash
chmod -R 755 content
chmod -R 755 cache
chmod -R 755 logs
chmod -R 755 uploads
```

### 3. Web server configuration

#### Apache

**1. Enable required modules:**

```bash
sudo a2enmod rewrite headers expires
sudo systemctl restart apache2
```

**2. Verify AllowOverride is enabled:**

Edit your Apache vhost config (usually `/etc/apache2/sites-available/000-default.conf` or `/etc/httpd/conf.d/`) and ensure the project directory block includes `AllowOverride All`:

```apache
<Directory /path/to/html/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

If using cPanel, Cloudways, or similar hosting, `AllowOverride All` is typically enabled by default.

**3. Using .htaccess:**

The included `.htaccess` contains all rewrite, cache, and security rules. The `AllowOverride All` directive above allows Apache to read these rules.

> If your environment does not allow `AllowOverride`, refer to the Nginx example below and translate the rules into equivalent Apache vhost directives.

#### Nginx

Add the following rules to your Nginx `server` block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/html;
    index index.php;

    # Block sensitive directories
    location ~ ^/(content|cache|libs|vendor|logs)/ {
        deny all;
    }

    # Allow image access in uploads, block executable files
    location /uploads/ {
        location ~ \.(php|phtml|php3|php4|php5|phar|pl|cgi|py|rb|asp|aspx|sh|bat|exe)$ {
            deny all;
        }
    }

    # Block direct config file access
    location = /composer.json  { deny all; }
    location = /composer.lock  { deny all; }
    location = /composer.json  { deny all; }
    location = /.env           { deny all; }
    location ~ \.md$           { deny all; }

    # URL rewrite — pass all non-file/directory requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Browser cache for static assets
    location ~ \.(css|js|jpg|jpeg|png|gif|webp|ico|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP processing
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;  # Adjust to your PHP version
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 4. Create an admin account

Visit `/admin` in your browser and follow the on-screen instructions to create the first admin account.

Alternatively, manually edit `content/config/users.json`:

```json
[
    {
        "id": "admin",
        "username": "admin",
        "password_hash": "$2y$12$...",
        "email": "admin@example.com",
        "role": "admin",
        "created_at": "2024-01-01T00:00:00+00:00"
    }
]
```

Generate a password hash with:

```bash
php -r "echo password_hash('your-password', PASSWORD_BCRYPT, ['cost' => 12]);"
```

### 5. Access the site

Open your browser and visit your site. Admin panel: `/admin`

## Directory Structure

```
/
├── composer.json             # Dependencies and autoload config
├── .env                      # Environment variables (SMTP password, etc., not in VCS)
├── .gitignore
├── .htaccess                 # Apache rewrite & security rules
├── index.php                 # Single entry point
├── admin/                    # Admin pages
│   ├── login.php             # Login page
│   ├── dashboard.php         # Dashboard
│   ├── pages.php             # Page management
│   ├── blog.php              # Blog management
│   ├── products.php          # Product management
│   ├── files.php             # File management
│   ├── messages.php          # Message management
│   ├── documents.php         # Internal documents
│   ├── settings.php          # System settings
│   ├── themes.php            # Theme customization
│   ├── users.php             # User management
│   ├── signature.php         # Signature
│   └── logout.php
├── content/                  # Content directory
│   ├── blog/                 # .md blog posts
│   ├── products/             # .md products
│   ├── pages/                # .md static pages
│   ├── documents/            # .md internal documents
│   ├── messages/             # .json contact messages
│   ├── counters/             # .json visit counters
│   └── config/               # Configuration files
│       ├── menu.yaml         # Menu settings
│       ├── settings.json     # Mail, site title, etc. (not in VCS)
│       ├── theme.json        # Theme color config
│       ├── users.json        # Admin accounts
│       └── signature.txt     # Signature text
├── cache/                    # Cache directory
│   └── pages/                # HTML cache
├── data/                     # Language data
│   ├── default_lang.yaml     # Default language (English base)
│   ├── custom_lang.yaml      # User custom overrides
│   ├── active_lang           # Active language code
│   └── lang/                 # Language packs
│       ├── en.yaml           # English language pack
│       └── zh-TW.yaml        # Traditional Chinese language pack
├── logs/                     # Error logs
├── libs/                     # Core class libraries
│   ├── Router.php
│   ├── FileHandler.php
│   ├── MarkdownParser.php
│   ├── Cache.php
│   ├── Auth.php
│   ├── MenuManager.php
│   ├── ContactHandler.php
│   ├── Mailer.php
│   ├── Counter.php
│   └── Controllers/
│       ├── BaseController.php
│       ├── PageController.php
│       ├── BlogController.php
│       ├── ProductController.php
│       ├── ContactController.php
│       └── AdminController.php
├── templates/                # Template directory
│   ├── default/              # Frontend templates
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── page.php
│   │   ├── blog-list.php
│   │   ├── blog-post.php
│   │   ├── products-list.php
│   │   ├── product.php
│   │   ├── contact.php
│   │   ├── style.css
│   │   └── custom.css
│   └── admin/                # Admin templates
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
├── uploads/                  # Image uploads directory
└── vendor/                   # Composer dependencies (not in VCS)
```

## Content Format

### Blog Frontmatter

```markdown
---
title: Post Title
slug: article-slug
date: 2026-06-01
author: Author Name
published: true
tags:
  - tag1
  - tag2
banner: https://example.com/banner.jpg
---

This is the post content, supports Markdown syntax.
```

Blog listing supports tag filtering and pagination (12 posts per page).

### Product Frontmatter

```markdown
---
title: Product Name
slug: product-slug
date: 2026-06-01
price: "NT$ 1,500起"
description: Short product description
tags:
  - tag1
  - tag2
image: https://example.com/image.jpg
sort_order: 1
---

Detailed product description...
```

> `price` supports text formats, e.g. `"Custom Quote"`, `"NT$ 1,500 up"`.

## Admin Panel

Login at `/admin/login`

Available admin features:

| Feature | Description |
|---------|-------------|
| Dashboard | System overview (posts, products, pages, messages count) |
| Pages | Create, edit, delete static pages |
| Blog | Publish, edit, delete blog posts (tag filter, pagination, banner) |
| Products | Manage product catalog, pricing, stock, sort order |
| Files | Upload/delete images, drag & drop, copy URL |
| Messages | View contact form messages and reply |
| Documents | Internal knowledge base / document management |
| Users | Create, edit, delete admin accounts |
| Settings | Configure site title, menu, mail, homepage |
| Themes | Customize colors and navigation style |
| Language | One-click language switching, add language packs, override individual strings |
| Signature | Set post signature text |

## Multi-Language

The system uses a three-tier language architecture:

```
default_lang.yaml  →  data/lang/{lang}.yaml  →  custom_lang.yaml
(English base)        (language pack)           (user overrides)
```

### One-Click Language Switching

In the admin panel under "Language", use the dropdown at the top to select a language, then click "Apply". The entire site (frontend and admin) switches immediately.

### Adding a New Language

Create a `.yaml` file in `data/lang/`. The first line defines the display name:

```yaml
lang.display_name: 日本語
lang.attr: ja
# ... all translation keys
```

The system auto-detects it and adds it to the language dropdown. See `zh-TW.yaml` for reference.

### Custom Overrides

On the "Language" page, you can manually override individual strings (shown with a blue border). These overrides persist across language switches, useful for tweaking specific terms or fixing translations.

## Mail Configuration

### Method 1: Admin Panel

Configure SMTP under "Settings → Mail Settings":

- SMTP Host
- SMTP Port (default 587)
- SMTP Username
- SMTP Password
- Sender Email
- Sender Name

### Method 2: Environment Variable (Recommended)

To avoid committing the SMTP password to version control, create a `.env` file in the project root (excluded by `.gitignore`):

```bash
MAIL_PASSWORD=your-smtp-password
```

Environment variables take precedence over admin panel values.

### Notification Recipient

Contact form notifications are sent to the email address configured in "Sender Email".

## Cache Management

The system automatically caches parsed Markdown content for performance.

- **Blog cache** — automatically cleared when posts are created, edited, or deleted
- **Product cache** — automatically cleared when products are created, edited, or deleted
- **Page cache** — automatically cleared when static pages are updated

You can also manually clear all cache from the "Settings" page in the admin panel.

## Security

- **Path traversal protection** — all file operations handled by `FileHandler` with multi-layer validation (null byte stripping, realpath resolution, basepath prefix check)
- **Password storage** — bcrypt with cost 12
- **CSRF protection** — all admin operations and contact forms include CSRF token validation
- **Session security** — `HttpOnly` + `Secure` + `SameSite=Lax`, idle timeout (1 hour) and absolute timeout (4 hours)
- **Login protection** — 5 failed attempts lock out for 15 minutes, preventing brute-force attacks
- **XSS protection**:
  - All frontend templates use `htmlspecialchars()` for output escaping
  - Markdown parser runs with Safe Mode off to support advanced syntax like HTML tables (Parsedown filters dangerous tags)
  - YAML frontmatter built with `Yaml::dump()`, preventing injection
- **Sensitive directory protection** — `content/`, `cache/`, `libs/`, `vendor/`, `logs/` blocked via `.htaccess` (Apache) or `location` rules (Nginx); **Apache users must ensure AllowOverride is enabled**
- **File upload security** — whitelist extension filtering, filename sanitization, MIME type restricted to images
- **Error handling** — production errors suppressed, logged to file only
- **SMTP password protection** — can be set via `MAIL_PASSWORD` environment variable, avoiding VCS exposure

## Troubleshooting

### 500 Internal Server Error

**Causes** (most common):

- Composer dependencies not installed → delete `vendor/` and refresh to auto-install
- `notFound()` permission issue → visit homepage `/` first (not `/html/`) to create route cache
- PHP version below 8.1 → upgrade to PHP 8.1+

### `/blog`, `/contact` URLs return 404

**Causes**: Apache `mod_rewrite` not enabled, or `.htaccess` not being read.

- Run `sudo a2enmod rewrite` and restart Apache
- Verify `AllowOverride` is not set to `None` in your Apache vhost

### Uploaded images inaccessible, or PHP files execute in uploads

**Causes**: `uploads/.htaccess` not being read (Apache `AllowOverride` disabled), or Nginx `location` rules not configured. See the Nginx config example above.

### Contact form cannot send email

**Causes**: SMTP not configured. Set up SMTP in the admin panel under "Settings → Mail Settings", or create a `.env` file:

```bash
MAIL_PASSWORD=your-smtp-password
```

## Tech Stack

- PHP 8.1+
- Composer (PSR-4 Autoloading)
- Parsedown (Markdown parsing, Safe Mode off for HTML support)
- Symfony YAML (YAML parsing/dumping)
- PHPMailer (email sending)
- EasyMDE (admin Markdown editor, loaded via CDN)

## License

MIT License

## Support

For issues or suggestions, please submit an Issue or Pull Request.
