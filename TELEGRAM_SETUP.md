# Telegram Bot 设置指南（BotFather）

本系统只使用官方 [Telegram Bot API](https://core.telegram.org/bots/api)，不接入第三方推送服务。

把 BotFather 发给你的 Token 填进 `config/telegram.php` 的 `bot_token`，或在 `install.php` 中填写。

若 Token 曾出现在聊天记录或公开仓库中，请在 BotFather 中 **Revoke** 旧 Token 并换成新 Token。

---

## 1. 用 BotFather 创建机器人

1. 打开 Telegram，搜索 `@BotFather`
2. 发送 `/newbot`
3. 按提示设置显示名称（例如 `KHS Reminder`）
4. 设置用户名，必须以 `bot` 结尾（例如 `YourBotUsername_bot`）
5. BotFather 会返回 **HTTP API Token**，格式类似：
   `123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
6. 把 Token 填进 `config/telegram.php` 的 `bot_token`，或在 `install.php` 中填写

常用 BotFather 命令：

- `/mybots` — 管理已有机器人
- `/revoke` — 作废旧 Token（泄露后必须做）
- `/setuserpic` — 设置头像
- `/setdescription` — 设置简介

---

## 2. 用户必须先与机器人对话

Telegram **不允许**机器人主动给从未互动过的账号发消息。

每个接收人需要：

1. 搜索你的机器人用户名（`config/telegram.php` 里的 `bot_username`）
2. 点击 **Start** 或发送 `/start`

否则 API 会返回 `Forbidden: bot was blocked by the user` 或 `chat not found`。

---

## 3. 获取 chat_id

后台「Telegram Users」需要填写数字 `chat_id`。

### 方法 A：IDBot

1. 搜索 `@userinfobot` 或 `@getidsbot`
2. 发送任意消息，它会返回你的数字 ID
3. 把该 ID 填入系统用户表的 `chat_id`

### 方法 B：getUpdates（适合自己的机器人）

用户向你的机器人发送 `/start` 后，浏览器访问（把 TOKEN 换成真实 Token）：

```
https://api.telegram.org/botTOKEN/getUpdates
```

在 JSON 里找到：

```json
"chat": { "id": 123456789, "first_name": "..." }
```

`id` 就是 `chat_id`。

---

## 4. 在本系统中测试

1. 登录后台 → **Telegram Users**
2. 确认该用户的 `chat_id`
3. 点击 **Test**
4. Telegram 应收到测试消息

若失败，打开 `logs/telegram.log` 查看 API 返回说明。

---

## 5. 发送规则（系统已实现）

- 使用 `sendMessage` 接口
- 函数：`sendTelegramMessage($chat_id, $message)`（见 `includes/telegram.php`）
- 成功 / 失败都会写入 `message_logs`
- 同一提醒内：先循环用户，再按 `sort_order` 顺序发多条消息
- 每条消息之间延迟（默认 1000ms），降低触发 flood limit 的概率
- 单条文本最长 4096 字符（Telegram 限制）

---

## 6. 隐私与安全

- 不要把 Bot Token 提交到公开 Git 仓库
- 不要把 Token 发给不信任的人
- Token 泄露后立刻在 BotFather **Revoke**
- Cron 脚本必须带密钥访问，禁止裸奔 URL
