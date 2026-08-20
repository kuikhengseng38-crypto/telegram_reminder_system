# KHS Reminder

Telegram 定时提醒管理系统。适合学习 **Core PHP + MySQL + Cron + Telegram Bot API** 的教学项目。

后台可管理提醒、Telegram 用户、发送记录和管理员账号。到期后由 Cron（或后台保活）自动把消息按顺序发给指定 `chat_id`。

## Screenshots

### Dashboard

![Dashboard](docs/screenshots/dashboard.png)

### Reminders

![Reminders](docs/screenshots/reminders.png)

### Telegram Users

![Telegram Users](docs/screenshots/telegram-users.png)

### Message Logs

![Message Logs](docs/screenshots/message-logs.png)

## Features

- 一键账单 / 会议 / 任务等提醒模板
- 同一提醒可发给多个 Telegram 用户，并按顺序发送多条消息
- 发送状态：Pending / Sent / Failed / Partial
- 每条消息的投递日志
- 管理员登录、忘记密码、个人资料

## Tech stack

- PHP 7.4+（无需 Composer / Laravel）
- MySQL / MariaDB
- Telegram Bot API
- 每分钟 Cron（cPanel 可用）

## Quick start

1. 在主机上创建 MySQL 数据库和用户。
2. 复制示例配置并填入你自己的值（不要提交真实文件）：
   - `config/database.example.php` → `config/database.php`
   - `config/app.example.php` → `config/app.php`
   - `config/telegram.example.php` → `config/telegram.php`
3. 或打开 `/install.php` 完成安装。
4. 导入 `database/schema.sql`（本地）或 `database/schema_cpanel.sql`（cPanel，仅表结构）。
5. 用 `/admin/login.php` 登录。账号是你在安装时自己设置的。
6. 安装成功后删除 `install.php`，并立刻使用强密码。
7. 配置每分钟 Cron，详见 [INSTALL.md](INSTALL.md) 和 [CPANEL.md](CPANEL.md)。

## Configuration

GitHub 仓库只包含 example 文件。真实密钥只放在服务器上的 `config/*.php`。

| Example | Copy to | Purpose |
| --- | --- | --- |
| `config/database.example.php` | `config/database.php` | 数据库主机、库名、用户名、密码 |
| `config/telegram.example.php` | `config/telegram.php` | Bot Token、机器人用户名 |
| `config/app.example.php` | `config/app.php` | 时区、`app_key`、`cron_secret` |

占位符示例：`your_database_name`、`your_db_password`、`YOUR_TELEGRAM_BOT_TOKEN`、`YOUR_CRON_SECRET`。

HTTP Cron 请写成：

```bash
curl -s "https://YOUR_DOMAIN/cron/send_reminders.php?key=YOUR_CRON_SECRET" >/dev/null 2>&1
```

不要把真实 key、数据库密码或 Bot Token 写进文档。

## Docs

- [INSTALL.md](INSTALL.md) — 主机、数据库、Cron
- [TELEGRAM_SETUP.md](TELEGRAM_SETUP.md) — BotFather、`chat_id`、测试发送
- [CPANEL.md](CPANEL.md) — cPanel 上传步骤

## License

本项目以教育学习为目的发布，详见 [LICENSE](LICENSE)。
