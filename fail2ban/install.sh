#!/bin/bash
# IgG CMS fail2ban 安裝腳本
# 使用方式: sudo bash install.sh

set -e

FILTER_DIR="/etc/fail2ban/filter.d"
JAIL_DIR="/etc/fail2ban/jail.d"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# 自動偵測 CMS 實際路徑
CMS_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_PATH="$CMS_DIR/logs"

echo "=== IgG CMS fail2ban 安裝 ==="
echo "偵測到 CMS 路徑: $CMS_DIR"
echo "Log 路徑: $LOG_PATH"
echo ""

# 替換 placeholder 並複製 filter 檔案
echo "安裝 filter 檔案..."
cp "$SCRIPT_DIR/igg-cms-login.conf" "$FILTER_DIR/"
cp "$SCRIPT_DIR/igg-cms-attack.conf" "$FILTER_DIR/"

# 替換 placeholder 並複製 jail 檔案
echo "安裝 jail 檔案..."
sed "s|__LOG_PATH__|$LOG_PATH|g" "$SCRIPT_DIR/igg-cms.conf" > "$JAIL_DIR/igg-cms.conf"

# 重啟 fail2ban
echo "重啟 fail2ban..."
systemctl restart fail2ban

# 確認狀態
echo ""
echo "=== 安裝完成 ==="
echo ""
echo "查看 jail 狀態："
echo "  fail2ban-client status"
echo ""
echo "查看 IgG CMS login jail："
echo "  fail2ban-client status igg-cms-login"
echo ""
echo "查看 IgG CMS attack jail："
echo "  fail2ban-client status igg-cms-attack"
echo ""
echo "手動解除 IP 封鎖："
echo "  fail2ban-client set igg-cms-login unbanip <IP>"
echo "  fail2ban-client set igg-cms-attack unbanip <IP>"
