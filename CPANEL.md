# cPanel 上传说明

不要把带真实密码、Bot Token、Cron 密钥的配置文件上传到公开仓库。
服务器上使用 `config/*.php`；GitHub 只提交 `config/*.example.php`。

## 1. 上传

把项目文件上传到 `public_html/` 或子目录，例如 `public_html/reminder/`。

## 2. 配置

复制示例文件并填入你自己的值：

- `config/database.example.php` → `config/database.php`
- `config/app.example.php` → `config/app.php`
- `config/telegram.example.php` → `config/telegram.php`

或打开 `/install.php` 完成安装（安装成功后立刻删除 `install.php`）。

## 3. 导入数据表

phpMyAdmin 中选中你的数据库，导入 `database/schema_cpanel.sql`。

该文件只有表结构，没有管理员密码哈希，也没有 Telegram 用户数据。
管理员账号请用安装向导创建，或安装后在后台自行添加。

不要导入带真实用户、日志或告警记录的 dump。

## 4. 登录

打开：`https://YOUR_DOMAIN/admin/login.php`

使用你在安装时设置的账号。安装后请立刻确认密码足够强。
不要把生产环境密码写进 README 或本文件。

## 5. Cron（每分钟）

CLI（优先）：

```bash
php /home/YOUR_CPANEL_USER/public_html/cron/send_reminders.php >/dev/null 2>&1
```

HTTP（主机禁用 CLI 时）：

```bash
curl -s "https://YOUR_DOMAIN/cron/send_reminders.php?key=YOUR_CRON_SECRET" >/dev/null 2>&1
```

`YOUR_CRON_SECRET` 必须与 `config/app.php` 里的 `cron_secret` 一致。不要把真实 key 写进文档或提交到 GitHub。
