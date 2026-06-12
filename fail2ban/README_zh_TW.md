# fail2ban 設定檔

## 檔案說明

| 檔案 | 說明 |
|------|------|
| `igg-cms-login.conf` | 過濾登入失敗的 log 格式 |
| `igg-cms-attack.conf` | 過濾攻擊行為的 log 格式 |
| `igg-cms.conf` | fail2ban jail 設定（含 `__LOG_PATH__` placeholder） |
| `install.sh` | 一鍵安裝腳本（自動偵測路徑並替換 placeholder） |

## 安裝

```bash
cd /var/www/html/div_html/fail2ban
sudo bash install.sh
```

安裝腳本會自動：
1. 偵測 CMS 實際安裝路徑
2. 替換 `igg-cms.conf` 中的 `__LOG_PATH__` 為正確路徑
3. 複製到 fail2ban 設定目錄
4. 重啟 fail2ban

## Jail 規則

| Jail | 觸發條件 | 封鎖時間 |
|------|----------|----------|
| `igg-cms-login` | 15 分鐘內 5 次登入失敗 | 1 小時 |
| `igg-cms-attack` | 10 分鐘內 5 次攻擊行為 | 1 天 |

## Log 格式

```
[2026-06-09 16:30:00] [login] 192.168.1.100 POST /admin/login 401 user=admin
[2026-06-09 16:30:00] [attack] 192.168.1.100 GET /../../../etc/passwd 400
[2026-06-09 16:30:00] [bot] 192.168.1.100 GET /blog/hello 200 ua="Googlebot/2.1"
[2026-06-09 16:30:00] [access] 192.168.1.100 GET / 200
```

## 常用指令

```bash
# 查看所有 jail 狀態
fail2ban-client status

# 查看特定 jail
fail2ban-client status igg-cms-login
fail2ban-client status igg-cms-attack

# 手動解除 IP 封鎖
fail2ban-client set igg-cms-login unbanip 192.168.1.100

# 查看被封鎖的 IP
fail2ban-client status igg-cms-login | grep "Banned IP"
```

## 注意事項

- Log 檔案位於 `<CMS安裝目錄>/logs/`
- 確保 `logs/` 目錄可被 fail2ban 讀取（權限 755 以上）
- 若使用 Nginx，需確認 error log 路徑
