# IgG Flat CMS

A full-featured CMS that fits on a floppy disk.

IgG Flat CMS is a lightweight, database-free content management system  
designed for developers and IT consultants who want a simple, stable,  
and fully controllable website solution.

✅ ~400KB core  
✅ ~1MB total size  
✅ No database  
✅ Built-in admin panel  
✅ Ready for production use  

Originally built because someone didn’t want to learn Markdown. 🙂

# IgG Flat CMS - Lightweight Flat-File CMS

一個基於 PHP 8.1+ 的輕量級 IgG Flat CMS - Lightweight Flat-File CMS，無需資料庫，完全依賴檔案系統。

## 系統需求

- PHP 8.1 或更高版本
- Apache 或 Nginx 網頁伺服器
- mod_rewrite（Apache）或等效的 URL 重寫功能
- Composer（用於安裝依賴）

## 功能特色

- **無資料庫設計**：所有內容以 Markdown 檔案儲存
- **部落格系統**：支援文章發布、標籤、草稿、標籤篩選、分頁瀏覽（每頁 12 篇）
- **產品管理**：支援產品目錄、價格（支援文字如「客製報價」）、庫存管理、拖曳排序
- **靜態頁面**：自定義靜態頁面
- **聯絡表單**：內建聯絡表單與留言管理（含回覆功能）
- **檔案管理**：圖片上傳、拖曳上傳、檔案瀏覽器（整合 EasyMDE）
- **內部文件**：公司內部知識庫／文件管理
- **瀏覽計數器**：自動記錄頁面、文章、產品瀏覽次數
- **主題自訂**：後台可自訂主題顏色與導覽樣式
- **後台管理**：完整的後台介面進行內容管理
- **自動安裝依賴**：首次執行自動偵測並執行 `composer install`，無需手動下指令
- **響應式設計**：支援 RWD 行動裝置瀏覽
- **安全性**：CSRF 防護、bcrypt 密碼加密、Path Traversal 防護、登入暴力破解防護、Session 安全設定

## 安裝步驟

### 1. 下載專案

```bash
git clone <repository-url>
cd div_html
```

### 2. 設定權限

確保以下目錄具有寫入權限：

```bash
chmod -R 755 content
chmod -R 755 cache
chmod -R 755 logs
chmod -R 755 uploads
```

### 3. 配置網頁伺服器

#### Apache

確保啟用 `mod_rewrite` 與 `mod_headers`：

```bash
sudo a2enmod rewrite headers expires
sudo systemctl restart apache2
```

專案內附 `.htaccess` 已包含所有重寫、快取及安全規則，無需額外設定。

#### Nginx

在 Nginx 配置中添加以下規則：

```nginx
server {
    # ... 基本設定 ...

    # 阻擋敏感目錄
    location ~ ^/(content|cache|libs|vendor|logs)/ {
        deny all;
    }

    # 允許上傳目錄的圖片存取
    location /uploads/ {
        # 阻擋可執行檔
        location ~ \.(php|phtml|php3|php4|php5|phar|pl|cgi|py|rb|asp|aspx|sh|bat|exe)$ {
            deny all;
        }
    }

    # 阻擋直接存取設定檔
    location = /composer.json { deny all; }
    location = /composer.lock { deny all; }
    location ~ \.md$ { deny all; }

    # URL 重寫
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 瀏覽器快取
    location ~ \.(css|js|jpg|jpeg|png|gif|webp|ico|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 4. 新增管理員帳號

開啟瀏覽器訪問 `/admin/register`，依畫面指示建立第一個管理員帳號。

或者手動編輯 `content/config/users.json` 加入帳號：

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

密碼可使用以下 PHP 指令產生：

```bash
php -r "echo password_hash('your-password', PASSWORD_BCRYPT, ['cost' => 12]);"
```

### 5. 訪問網站

開啟瀏覽器訪問您的網站。後台路徑：`/admin/login`

## 目錄結構

```
/
├── composer.json             # 套件依賴與 Autoload 設定
├── .env                      # 環境變數（SMTP 密碼等，不進版本控制）
├── .gitignore
├── .htaccess                 # Apache 重寫與安全規則
├── index.php                 # 唯一入口
├── admin/                    # 後台管理頁面
│   ├── login.php             # 登入頁面
│   ├── dashboard.php         # 儀表板
│   ├── pages.php             # 頁面管理
│   ├── blog.php              # 文章管理
│   ├── products.php          # 產品管理
│   ├── files.php             # 檔案管理
│   ├── messages.php          # 留言管理
│   ├── documents.php         # 內部文件管理
│   ├── settings.php          # 系統設定
│   ├── themes.php            # 主題自訂
│   ├── users.php             # 使用者管理
│   ├── signature.php         # 數位簽章
│   └── logout.php
├── content/                  # 內容目錄
│   ├── blog/                 # .md 文章
│   ├── products/             # .md 產品
│   ├── pages/                # .md 靜態頁面
│   ├── documents/            # .md 內部文件
│   ├── messages/             # .json 聯絡表單留言
│   ├── counters/             # .json 瀏覽計數
│   └── config/               # 配置檔案
│       ├── menu.yaml         # 選單設定
│       ├── settings.json     # 郵件、網站標題等（不進版本控制）
│       ├── theme.json        # 主題顏色設定
│       ├── users.json        # 後台帳號
│       └── signature.txt     # 數位簽章文字
├── cache/                    # 快取目錄
│   └── pages/                # HTML 快取
├── logs/                     # 系統錯誤與日誌
├── libs/                     # 核心類別庫
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
├── templates/                # 模板目錄
│   ├── default/              # 前台模板
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
│   └── admin/                # 後台模板
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
├── uploads/                  # 圖片上傳目錄
└── vendor/                   # Composer 依賴（不進版本控制）
```

## 內容格式

### 部落格 Frontmatter

```markdown
---
title: 文章標題
slug: article-slug
date: 2026-06-01
author: 作者名稱
published: true
tags:
  - 標籤1
  - 標籤2
banner: https://example.com/banner.jpg
---

這是文章內容，支援 Markdown 語法。
```

部落格列表支援依標籤篩選與分頁（每頁 12 篇）。

### 產品 Frontmatter

```markdown
---
title: 產品名稱
slug: product-slug
date: 2026-06-01
price: "NT$ 1,500 起"
description: 產品簡短描述
tags:
  - 標籤1
  - 標籤2
image: https://example.com/image.jpg
sort_order: 1
---

產品詳細內容...
```

> `price` 支援文字格式，例如 `"客製報價"`、`"NT$ 1,500 起"`。

## 後台管理

登入後台：`/admin/login`

後台功能包括：

| 功能 | 說明 |
|------|------|
| 儀表板 | 查看系統統計資訊（文章、產品、頁面、檔案數量） |
| 頁面管理 | 新增、編輯、刪除靜態頁面 |
| 文章管理 | 發布、編輯、刪除部落格文章（含標籤篩選、分頁瀏覽、Banner） |
| 產品管理 | 管理產品目錄、價格、庫存、排序 |
| 檔案管理 | 上傳／刪除圖片、拖曳上傳、複製 URL |
| 留言管理 | 查看聯絡表單留言並回覆 |
| 內部文件 | 公司內部知識庫／文件管理 |
| 使用者管理 | 新增、編輯、刪除管理員帳號 |
| 系統設定 | 配置網站標題、選單、郵件設定、首頁 |
| 主題設定 | 自訂主題顏色與導覽樣式 |
| 數位簽章 | 設定數位簽章文字 |

## 郵件設定

### 方式一：後台設定

在「系統設定 → 郵件設定」頁面配置 SMTP：

- SMTP 主機
- SMTP 埠號（預設 587）
- SMTP 使用者名稱
- SMTP 密碼
- 寄件者信箱
- 寄件者名稱

### 方式二：環境變數（推薦）

為避免 SMTP 密碼寫入版本控制，可在專案根目錄建立 `.env` 檔案（已被 `.gitignore` 排除）：

```bash
MAIL_PASSWORD=your-smtp-password
```

環境變數的優先度高於後台設定值。

### 通知收件者

聯絡表單的通知郵件會自動寄送到「寄件者信箱」設定的地址。

## 快取管理

系統會自動快取已解析的 Markdown 內容以提高效能。

- **文章快取**：新增/編輯/刪除文章時，自動清除該文章與列表快取
- **產品快取**：新增/編輯/刪除產品時，自動清除該產品與列表快取
- **頁面快取**：更新靜態頁面時，自動清除該頁面快取

您也可以在後台的「系統設定」頁面手動清除所有快取。

## 安全性

- **路徑遍歷防護**：所有檔案操作透過 `FileHandler` 類別統一處理，多層驗證（null byte 清除、realpath 解析、basepath 前綴檢查）
- **密碼儲存**：使用 bcrypt + cost 12 加密儲存
- **CSRF 防護**：所有管理操作與聯絡表單皆有 CSRF Token 驗證
- **Session 安全**：`HttpOnly` + `Secure` + `SameSite=Lax`，具備閒置逾時（1 小時）與絕對逾時（4 小時）
- **登入防護**：5 次失敗鎖定 15 分鐘，防止暴力破解
- **XSS 防護**：
  - 前端模板全面使用 `htmlspecialchars()` 輸出跳脫
  - Markdown 解析關閉 Safe Mode 以支援 HTML 表格等進階語法（Parsedown 本身已過濾危險標籤）
  - YAML frontmatter 使用 `Yaml::dump()` 建構，杜絕注入
- **敏感目錄保護**：`content/`、`cache/`、`libs/`、`vendor/`、`logs/` 透過 `.htaccess` 阻擋直接 HTTP 存取
- **檔案上傳安全**：白名單副檔名過濾、檔名清理、MIME 類型限制圖片為主
- **錯誤處理**：生產環境關閉錯誤顯示，錯誤訊息僅寫入日誌
- **SMTP 密碼保護**：可透過環境變數 `MAIL_PASSWORD` 設定，避免寫入版本控制

## 技術棧

- PHP 8.1+
- Composer (PSR-4 Autoloading)
- Parsedown (Markdown 解析，Safe Mode 關閉以支援 HTML)
- Symfony YAML (YAML 解析/輸出)
- PHPMailer (郵件發送)
- EasyMDE (後台 Markdown 編輯器，CDN 載入)

## 授權

MIT License

## 支援

如有問題或建議，請提交 Issue 或 Pull Request。
