# 安装指南（cPanel 共享主机）

本系统使用 **Core PHP + MySQL + Cron**，不依赖 Laravel / Composer 框架。要求 PHP 7.4+、PDO MySQL、cURL（推荐）。

---

## 1. 上传文件

将整个项目上传到网站根目录，例如：

- `public_html/`
- 或 `public_html/reminder/`

确保以下目录可写（权限 755 或 750）：

- `/config`
- `/logs`

---

## 2. 创建 MySQL 数据库

在 cPanel → **MySQL Databases**：

1. 创建数据库（例如 `user_telegram`）
2. 创建数据库用户并设置强密码
3. 将该用户加入数据库，权限选 **ALL PRIVILEGES**

记下：主机（一般为 `localhost`）、库名、用户名、密码。

---

## 3. 安装方式 A：网页向导（推荐）

浏览器打开：

`https://你的域名/install.php`

填写：

- 数据库连接信息
- 管理员账号 / 邮箱 / 密码
- Bot Token（从 BotFather 获取）
- Cron 密钥

安装成功后：

1. 访问 `/admin/login.php` 登录
2. **立刻删除** `install.php`

---

## 4. 安装方式 B：手动导入

1. 用 phpMyAdmin 选中你的数据库
2. 导入 `database/schema.sql`
   - 若 `CREATE DATABASE` 报权限错误：删掉文件开头的 `CREATE DATABASE` 和 `USE` 两句后再导入
3. 编辑配置文件：
   - `config/database.php` — 数据库账号
   - `config/telegram.php` — Bot Token
   - `config/app.php` — 时区、`cron_secret`
4. 默认管理员：
   - 用户名：`admin`
   - 密码：`Admin@123`
   - **登录后立即修改密码**

---

## 5. 配置 Cron（必须，每 1 分钟）

到点后的提醒由 Cron 发送。不配置 Cron，消息不会自动发出。

### 方式 1：CLI（优先）

cPanel → Cron Jobs → Common Settings 选 **Once Per Minute**：

```bash
php /home/你的cPanel用户名/public_html/cron/send_reminders.php >/dev/null 2>&1
```

若项目在子目录，把路径改成实际路径。

### 方式 2：HTTP curl（部分主机禁用 CLI 时使用）

`config/app.php` 里的 `cron_secret` 必须与 URL 中的 `key` 一致：

```bash
curl -s "https://你的域名/cron/send_reminders.php?key=YOUR_CRON_SECRET" >/dev/null 2>&1
```

没有正确 `key` 的访问会返回 403。

### 时区

默认时区：`Asia/Kuala_Lumpur`（在 `config/app.php` 修改）。PHP 与 MySQL 会话时区会按该值对齐，避免漏发。

---

## 6. 登录后台

- 地址：`/admin/login.php`
- 功能：显示密码、忘记密码、安全 Session
- 忘记密码会发邮件；若主机 `mail()` 不可用，重置链接会写入受保护的 `logs/password_reset.log`

---

## 7. 使用流程

1. **Telegram Users**：添加姓名 + `chat_id`
2. 可点 **Test** 发一条测试消息（对方必须先向机器人发过 `/start`）
3. **Reminders → Create**：标题、时间、多条顺序消息、勾选多个用户
4. 保存后状态为 **Pending**
5. Cron 每分钟检查 `scheduled_time <= 当前时间` 的待发送提醒
6. 按用户循环、按 `sort_order` 顺序发送，消息之间延迟 1 秒
7. 状态更新为 **Sent / Failed / Partially Sent**
8. 在 **Message Logs** 或提醒详情中查看每条发送记录

---

## 8. 安全检查清单

- [ ] 删除 `install.php`
- [ ] 修改默认管理员密码
- [ ] `config/` 与 `logs/` 已通过 `.htaccess` 禁止网页访问
- [ ] Cron 使用密钥或 CLI，不要把密钥发到公开聊天
- [ ] Bot Token 只放在 `config/telegram.php`
- [ ] 所有数据库操作为 PDO 预处理语句

---

## 9. 常见问题

**Cron 在跑但没发消息**  
查看 `logs/cron.log` 和 `logs/telegram.log`。确认提醒状态是 `pending`，时间已到期。

**Telegram 报 bot was blocked / chat not found**  
用户必须先在 Telegram 打开 `@khsreminder_bot` 并发送 `/start`。`chat_id` 必须完全正确。

**忘记密码收不到邮件**  
用 FTP 打开 `logs/password_reset.log` 复制链接；或在 phpMyAdmin 把 `admins.password` 改成新的 bcrypt 哈希。

**429 Too Many Requests**  
系统已在每条消息之间加入延迟。若一次提醒人数很多，可把 `config/app.php` 的 `message_delay_ms` 调到 `1500` 或 `2000`。
