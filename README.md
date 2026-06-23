
# Visitor Statistics with Crawler Detection

A plugin for Cotonti CMF that provides detailed tracking of site visits with extended analytics for each visit, and detection of search robots, crawlers, and spiders.  
Data is stored in the database and is available for viewing in the administration panel.

## Key Features

### 📊 Collection and Storage of Detailed Visit Information

For every request to the site (excluding admin AJAX requests — configurable optionally), the following data is recorded in the `cot_visitor_stats` table:

- **Visitor IP address** with support for Cloudflare headers (`CF-Connecting-IP`), `X-Forwarded-For`, and `X-Real-IP`.  
  If the IP fails validation, `0.0.0.0` is recorded.
- **User‑Agent** (browser/robot string) — saved in full (up to 500 characters).
- **Cotonti user ID** (if the visitor is logged in).
- **Site page** (REQUEST_URI) the visitor accessed.
- **Referer** — traffic source (up to 500 characters).
- **Date and time** of the visit in UNIX timestamp format.

### 🤖 Detection of Search Bots and Crawlers

A ported version of the [Crawler-Detect](https://github.com/JayBizzle/Crawler-Detect) library (author Mark Beech, MIT) is used.  
The plugin does not require Composer — all library classes are located inside the `lib/` directory and are adapted to work without namespaces.

Based on signatures (over 100 bots), the visitor is determined to be a robot. When detected, the following is written to the database:

- `vs_crawler_name` — bot name (e.g., `Googlebot`, `Bingbot`, `YandexBot`, `Chrome-Lighthouse`, `Google-InspectionTool`, etc.).
- `vs_is_bot` — flag `1` (bot) or `0` (human).

The bot list is expandable and can be extended by the administrator by editing the `lib/Fixtures/Crawlers.php` file.

Additionally, a heuristic is implemented to detect suspicious User‑Agents masquerading as outdated devices (Android 4–6, Nexus 5, Nexus 5X and similar). Such visits are marked as `Suspicious UA` and are considered bots.

### 🌍 Geolocation and Network Data

- **Country** — extracted from the `CF-IPCountry` header (Cloudflare) or remains empty if the header is missing.
- **ISP** and **VPN/Proxy indicator** — obtained through the free [ip-api.com](http://ip-api.com) API (fields `isp`, `proxy`, `hosting`). The result is cached in the session for 1 hour to avoid exceeding the service limits.
- **VPN/Proxy flag** (`vs_is_vpn`) — `1` if ip-api reports that the IP belongs to hosting or a public proxy.

### 📱 Device and Software Analysis

From the User‑Agent, the following are extracted:

- **Browser and version** (Firefox, Chrome, Safari, Edge, Opera, IE) — stored in `vs_browser`.
- **Operating system** (Windows, macOS, Linux, Android, iOS) — `vs_os`.
- **Device type** (Desktop, Mobile, Tablet) — `vs_device_type`.
- **Device model** (e.g., `Nexus 5`, `iPhone 13`, `SM-G973F`) — `vs_device_model`.  
  For PCs, `PC` is indicated.

### 👤 Unique Visitors

Based on a session variable, it is determined whether the visitor is new or returning.  
`vs_unique` = `1` for the first visit, `0` for subsequent visits within the same session.

### 📈 Administration Panel

In the "Other" section of the Cotonti admin panel, a statistics page is available.  
It provides:

- **Cards** with overall figures: total visits, humans, bots, unique visitors.
- **Period filter** (days) — currently decorative, will affect the data selection in the future.
- **"Show only bots" filter** — when enabled, the log table displays only rows where a bot/crawler was detected.
- **Visit log table** with all stored fields (date, IP, country, browser, OS, type, model, ISP, VPN, bot, uniqueness, crawler name, page, referrer). Data is sorted by date (newest first).
- **Pagination** (50 records per page).
- **Full data clear button** — deletes all records from three tables (`cot_visitor_stats`, `cot_visitor_stats_daily`, `cot_visitor_stats_crawlers`) without removing their structure. Only available to administrators.

The interface is built on Bootstrap 5.3, which is embedded in the Cotonti admin panel (no external styles need to be connected).

### 🌐 Localization

Russian and English languages are supported. Localization files are located in `lang/`.

---

## Plugin Structure and File Descriptions
```

plugins/visitor_stats/
├── visitor_stats.setup.php               # Plugin configuration
├── visitor_stats.admin.php               # Administration panel
├── visitor_stats.global.php              # Global hook (runs on every request)
├── visitor_stats.header.first.php        # Early blocking hook for unwanted bots
├── inc/
│   ├── visitor_stats.functions.php       # Helper functions and component loading
│   ├── CrawlerDetectService.php          # Wrapper service for the CrawlerDetect library
│   ├── VisitorStatsService.php           # Main business logic (data collection and recording)
│   └── VisitorStatsRepository.php        # Database interaction layer
├── lib/
│   ├── CrawlerDetect.php                 # Main bot detection class (ported version)
│   └── Fixtures/
│       ├── AbstractProvider.php          # Abstract class for signature lists
│       ├── Crawlers.php                  # Bot and crawler signatures
│       ├── Exclusions.php                # Patterns excluded from User‑Agent before checking
│       ├── Headers.php                   # Headers that may contain the User‑Agent
│       └── WhitelistBots.php            # Whitelist of allowed bots
├── setup/
│   ├── visitor_stats.install.sql         # SQL queries for table creation
│   └── visitor_stats.uninstall.sql       # SQL queries for table removal
├── lang/
│   ├── visitor_stats.en.lang.php         # English localization
│   └── visitor_stats.ru.lang.php         # Russian localization
├── tpl/
│   └── visitor_stats.admin.tpl           # Administration panel template
└── index.html                            # Placeholder

```

### Description of Key Components

- **visitor_stats.setup.php** — plugin metadata: code, name, category, version, author, license, access rights for guests and users.
- **visitor_stats.global.php** — entry point on every request. Loads the necessary files and calls `VisitorStatsService::recordVisit()` to record the visit. Contains the `global` hook.
- **visitor_stats.admin.php** — controller for the statistics page in the admin panel. Processes filters, pagination, data clearing, builds database queries, and passes variables to the template.
- **inc/visitor_stats.functions.php** — registers the plugin tables with `Cot::$db`, loads service classes, and contains a collection of functions for extracting visit information: `getRealIp()`, `getBrowser()`, `getOS()`, `getDeviceType()`, `getDeviceModel()`, `getCountry()`, `getIspInfo()`, `isUniqueVisitor()`. These functions can be used anywhere in Cotonti (including other plugins and templates).
- **inc/CrawlerDetectService.php** — singleton service providing `isCrawler($ua)` and `getCrawlerName($ua)` methods. Each call creates a `CrawlerDetect` instance with the passed User‑Agent, ensuring up‑to‑date checking.
- **inc/VisitorStatsService.php** — the main service that aggregates all visit data. The `recordVisit()` method collects IP, UA, geolocation, bot and uniqueness indicators, and then passes the array to the repository for database insertion.
- **inc/VisitorStatsRepository.php** — database interaction class. Contains methods for inserting records (`insert`), getting statistics for a period (`countVisits`, `countBotVisits`, `countUniqueVisitors`), extracting top lists (`getTopPages`, `getTopReferers`, `getTopCrawlers`), and daily breakdowns (`getDailyBreakdown`). These methods are not yet used in the admin panel but are ready for use.
- **lib/CrawlerDetect.php** and **Fixtures/\*** — ported bot detection library. `Crawlers.php` contains the signature list (bot names), `Exclusions.php` contains patterns removed from the User‑Agent before checking (for speed and reducing false positives), `Headers.php` lists headers that may carry the User‑Agent.
- **lang/** — language files. They contain all text strings used in the admin panel and frontend (if needed).
- **tpl/visitor_stats.admin.tpl** — statistics page template using Bootstrap 5.3 classes. No external stylesheets are needed since Cotonti already includes Bootstrap.
- **setup/\*.sql** — SQL queries for creating and removing the plugin tables.

---

## Database Structure

The plugin works with three tables (the prefix `cot_` is defined by the Cotonti configuration; `cot_` by default).

### Main Table `cot_visitor_stats`

Stores every recorded session. Field details:

| Field            | Type                         | Description |
|------------------|------------------------------|-------------|
| vs_id            | BIGINT UNSIGNED AUTO_INCREMENT | Primary key |
| vs_date          | INT UNSIGNED                 | Visit date (UNIX timestamp) |
| vs_ip            | VARCHAR(45)                  | IP address (IPv4 or IPv6) |
| vs_user_id       | INT UNSIGNED                 | Cotonti user ID (0 for guests) |
| vs_referer       | VARCHAR(500)                 | Traffic source (HTTP_REFERER) |
| vs_user_agent    | VARCHAR(500)                 | User‑Agent string |
| vs_page          | VARCHAR(500)                 | Page URL (REQUEST_URI) |
| vs_crawler_name  | VARCHAR(255)                 | Detected crawler name (NULL if not a bot) |
| vs_browser       | VARCHAR(255)                 | Browser and version |
| vs_os            | VARCHAR(255)                 | Operating system |
| vs_device_type   | VARCHAR(50)                  | Device type (Desktop/Mobile/Tablet) |
| vs_device_model  | VARCHAR(255)                 | Device model |
| vs_country       | VARCHAR(10)                  | Country code (from Cloudflare or ip-api) |
| vs_isp           | VARCHAR(255)                 | ISP |
| vs_is_vpn        | TINYINT(1)                   | 1 — VPN/proxy in use, 0 — no |
| vs_is_bot        | TINYINT(1)                   | 1 — bot, 0 — human |
| vs_unique        | TINYINT(1)                   | 1 — new visitor, 0 — returning |

Indexes: PRIMARY (`vs_id`), KEY on `vs_date`, `vs_ip`, `vs_user_id`, `vs_crawler_name`.

### Daily Summary Table `cot_visitor_stats_daily`

| Field              | Type                      | Description |
|--------------------|---------------------------|-------------|
| vsd_id             | INT UNSIGNED AUTO_INCREMENT | Primary key |
| vsd_date           | DATE                      | Date (unique) |
| vsd_total_visits   | INT UNSIGNED              | Total visits for the day |
| vsd_human_visits   | INT UNSIGNED              | Human visits |
| vsd_bot_visits     | INT UNSIGNED              | Bot visits |
| vsd_unique_visitors| INT UNSIGNED              | Unique visitors |
| vsd_updated_at     | INT UNSIGNED              | Last update time (timestamp) |

Unique key on `vsd_date`.

### Crawler Statistics Table `cot_visitor_stats_crawlers`

| Field            | Type                      | Description |
|------------------|---------------------------|-------------|
| vsc_id           | INT UNSIGNED AUTO_INCREMENT | Primary key |
| vsc_date         | DATE                      | Date |
| vsc_crawler_name | VARCHAR(255)              | Crawler name |
| vsc_visits       | INT UNSIGNED              | Number of visits by this crawler on that day |
| vsc_updated_at   | INT UNSIGNED              | Last update time |

Unique key on the combination `vsc_date` + `vsc_crawler_name`.

### Why Are the `cot_visitor_stats_daily` and `cot_visitor_stats_crawlers` Tables Empty?

Currently, the plugin writes **only to the main `cot_visitor_stats` table**.  
Aggregation of daily totals and crawler summaries into the respective tables **is not performed automatically**.  
These tables were created "for the future": their structure is ready, and future versions are planned to implement a periodic (e.g., via cron) or event‑driven fill mechanism to speed up reports over large time intervals.

The current admin panel builds all statistics directly via SQL queries to `cot_visitor_stats` (COUNT, grouping), so the summary tables remain empty for now.

---

## Installation

1. Copy the `visitor_stats` folder into your site's `plugins` directory.
2. Go to the Cotonti admin panel → "Extensions".
3. Find the **Visitor Statistics with Crawler Detection** plugin in the list and click "Install".
4. During installation, the SQL queries from `setup/visitor_stats.install.sql` will execute automatically — three tables will be created.
5. After installation, the plugin starts recording visits immediately (on frontend pages and the admin panel).

To remove the plugin, use the standard procedure in the admin panel: the `visitor_stats.uninstall.sql` file will be executed, which deletes the tables.

---

## Usage

### Viewing Statistics

In the Cotonti admin panel, under the **"Other"** section, a **"Visitor Statistics"** (or **Visitor Statistics** in the English version) item will appear.  
The page contains:

- **Four cards** at the top: total visits, human visits, bot visits, unique visitors.
- **Filter form**: a period in days can be set (does not affect the selection yet, left for future implementation) and a "Show only bots" checkbox can be ticked — then the table will show only rows where a crawler was detected.
- **Log table** with paginated output of the latest visits. Columns correspond to all collected fields.
- **"Clear all data" button** (only available to administrators). When clicked with confirmation, it completely deletes all records from the three tables without altering their structure.

### Information Functions

Anywhere in Cotonti (templates, other plugins), the following functions are available:

- `getRealIp()`
- `getUserAgent()`
- `getCountry()`
- `getBrowser($ua = null)`
- `getOS($ua = null)`
- `getDeviceType($ua = null)`
- `getDeviceModel($ua = null)`
- `getIspInfo()`
- `isUniqueVisitor()`

They are defined in `visitor_stats.functions.php` and can be used after the plugin is activated.

___

## 🛡️ Bot Whitelist and Early Blocking of Unwanted Crawlers

### Why is this needed?

The plugin not only collects bot statistics but can also **selectively block** those that do not provide value to your site.  
This allows you to:

- Reduce server load by filtering out useless or malicious bots before the main Cotonti code runs.
- Only allow search robots and services that actually help with promotion (Google, Yandex, Bing, Facebook, PageSpeed Insights, Ahrefs, etc.).
- Protect content from scraping by unwanted crawlers.

### How it works

1. **Whitelist** (`lib/Fixtures/WhitelistBots.php`)  
   Contains an array of bot names that are allowed access. Matching is performed by **case‑insensitive substring search**.  
   If the detected bot name contains any string from the whitelist, it is allowed through.  
   The administrator can easily edit this file to add or remove bots as needed.

2. **Hook `header.first`** (`visitor_stats.header.first.php`)  
   Executes at the earliest stage of request processing, before any data is loaded.  
   - Determines whether the visitor is a bot using `CrawlerDetectService`.
   - If a bot is detected, calls the `isAllowedBot()` function, which checks the bot name against the whitelist.
   - If the bot is **not allowed**:
     - The script immediately returns an HTTP **403 Forbidden** response.
     - The response body contains the plain text `Access denied for this bot.`
     - Further Cotonti execution is stopped (`exit`), conserving resources.

3. **Function `isAllowedBot()`** (in `inc/visitor_stats.functions.php`)  
   - Always allows humans (`null` or empty bot name).
   - Blocks “suspicious” UAs marked as `Suspicious UA` (old Android versions, emulation of outdated devices).
   - Compares the bot name against the whitelist using `stripos()`.

4. **Debug mode**  
   The `visitor_stats.header.first.php` file contains a `$debug` flag.  
   When set to `true`, the plugin writes every trigger event to `debug_bot.log`: timestamp, User‑Agent, bot name, and the decision (ALLOWED/BLOCKED).  
   This helps track which bots are trying to access the site and whether any desired bots are being blocked.

### How to add a new bot to the whitelist

1. Open the file `plugins/visitor_stats/lib/Fixtures/WhitelistBots.php`.
2. Add a new line to the `getAllowed()` array with a substring of the bot’s name, e.g.:
   ```
   'yourNameBot',
   ```
3. Save the file.

Changes take effect immediately (on the next request).

### Recommendations for configuring the whitelist

- Keep only bots that provide real value:
  - Search engines (Google, Bing, Yandex, Seznam, Baidu, Sogou, etc.).
  - Performance analyzers (Lighthouse, PageSpeed Insights).
  - Webmaster services (Google Inspection Tool, Facebook External Hit).
  - Popular SEO tools (Ahrefs, Semrush, DotBot), if you are fine with their crawling.
  - Useful AI crawlers (GPTBot, ChatGPT‑User, Claude‑Web), if you want your content to be used for model training or to appear in AI‑powered search results.
- All other bots will be automatically blocked by the plugin, reducing parasitic traffic.

### Verifying the setup

After adding new rules, you can enable debug mode (`$debug = true`) and check the `debug_bot.log` file to ensure that desired bots are allowed and unwanted ones are blocked.  
Remember to turn off debug mode once verification is complete to prevent the log from growing indefinitely.

___

## Technical Details

- **Requirements:** Cotonti ≥ 1.0 (with PHP 8.x support), PHP 8.0+, MySQL 5.7+ (or MariaDB).
- **Crawler‑Detect library** ported from the original JayBizzle/Crawler-Detect repository (MIT).  
  Changes: namespaces and `use` statements removed, regular expressions adapted (uses `#` delimiter instead of `/`), additional signatures added (`Chrome-Lighthouse`, `Google-InspectionTool`, `meta-externalagent`).
- **External dependencies:** none. Composer is not required.
- **ip-api.com API:** used to obtain ISP and proxy indicators. Requests are cached in the session for 1 hour. The service is free but has a limit of 45 requests per minute from a single IP.
- **Sessions:** the plugin starts a session when necessary to cache ip-api data and to track unique visitors.

---

## Roadmap

- Implement background filling of `cot_visitor_stats_daily` and `cot_visitor_stats_crawlers` tables (via cron or delayed script).
- Add charts and visualizations for statistics.
- Introduce a date filter in the admin panel with actual data selection limits.
- Expand the number of detected bots.
- Add data export capabilities (CSV, Excel).
- Integration with Cotonti API to retrieve statistics via REST.

---

## License

The plugin is distributed under the BSD-3-Clause license.  
The Crawler-Detect library is MIT.

Author: [webitproff](https://github.com/webitproff).  
Repository: [https://github.com/webitproff/visitor-stats-crawler-cotonti](https://github.com/webitproff/visitor-stats-crawler-cotonti)

---

# Русский: Статистика посещений сайта с проверкой ботов и пауков

Плагин для Cotonti CMF, обеспечивающий детальный учёт посещений сайта с расширенной аналитикой по каждому визиту и определением поисковых роботов, краулеров и пауков.  
Данные сохраняются в базу данных и доступны для просмотра в административной панели.

## Основные возможности

### 📊 Сбор и хранение детальной информации о каждом визите

Для каждого обращения к сайту (за исключением административных AJAX-запросов — настраивается опционально) в таблицу `cot_visitor_stats` записываются следующие данные:

- **IP-адрес** посетителя с поддержкой заголовков Cloudflare (`CF-Connecting-IP`), `X-Forwarded-For` и `X-Real-IP`.  
  Если IP не проходит валидацию, записывается `0.0.0.0`.
- **User‑Agent** (строка браузера/робота) — сохраняется полностью (до 500 символов).
- **Идентификатор пользователя Cotonti** (если посетитель авторизован).
- **Страница сайта** (REQUEST_URI), на которую зашёл посетитель.
- **Referer** — источник перехода (до 500 символов).
- **Дата и время** визита в формате UNIX timestamp.

### 🤖 Определение поисковых ботов и краулеров

Используется портированная версия библиотеки [Crawler-Detect](https://github.com/JayBizzle/Crawler-Detect) (автор Mark Beech, MIT).  
Плагин не требует установки Composer — все классы библиотеки расположены внутри каталога `lib/` и адаптированы для работы без пространств имён.

На основе сигнатур (более 100 ботов) определяется, является ли посетитель роботом. В случае обнаружения в базу записывается:

- `vs_crawler_name` — имя бота (например, `Googlebot`, `Bingbot`, `YandexBot`, `Chrome-Lighthouse`, `Google-InspectionTool` и т.д.).
- `vs_is_bot` — флаг `1` (бот) или `0` (человек).

Список ботов пополняется и может быть расширен администратором путём редактирования файла `lib/Fixtures/Crawlers.php`.

Дополнительно реализована эвристика для выявления подозрительных User‑Agent, маскирующихся под устаревшие устройства (Android 4–6, Nexus 5, Nexus 5X и подобные). Такие визиты помечаются как `Suspicious UA` и считаются ботами.

### 🌍 Геолокация и данные о сети

- **Страна** — извлекается из заголовка `CF-IPCountry` (Cloudflare) или остаётся пустой, если заголовок отсутствует.
- **Провайдер (ISP)** и **признак VPN/прокси** — получаются через бесплатный API [ip-api.com](http://ip-api.com) (поле `isp`, `proxy`, `hosting`). Результат кешируется в сессии на 1 час, чтобы не превышать лимиты сервиса.
- **Флаг VPN/прокси** (`vs_is_vpn`) — `1`, если ip-api сообщает, что IP принадлежит хостингу или публичному прокси.

### 📱 Анализ устройства и программного обеспечения

Из User‑Agent извлекаются:

- **Браузер и версия** (Firefox, Chrome, Safari, Edge, Opera, IE) — сохраняется в `vs_browser`.
- **Операционная система** (Windows, macOS, Linux, Android, iOS) — `vs_os`.
- **Тип устройства** (Desktop, Mobile, Tablet) — `vs_device_type`.
- **Модель устройства** (например, `Nexus 5`, `iPhone 13`, `SM-G973F`) — `vs_device_model`.  
  Для ПК указывается `PC`.

### 👤 Уникальные посетители

На основе сессионной переменной определяется, новый это посетитель или вернувшийся.  
`vs_unique` = `1` для первого визита, `0` для последующих заходов в рамках одной сессии.

### 📈 Административная панель

В разделе «Прочее» админки Cotonti доступна страница статистики.  
Она предоставляет:

- **Карточки** с общими цифрами: всего визитов, людей, ботов, уникальных посетителей.
- **Фильтр по периоду** (дни) — пока декоративный, в будущем будет влиять на выборку.
- **Фильтр «Показать только ботов»** — при включении таблица журнала отображает только строки, где обнаружен бот/краулер.
- **Таблица журнала посещений** со всеми сохранёнными полями (дата, IP, страна, браузер, ОС, тип, модель, провайдер, VPN, бот, уникальность, имя краулера, страница, реферер). Данные отсортированы по дате (сначала новые).
- **Пагинация** (по 50 записей на страницу).
- **Кнопка полной очистки данных** — удаляет все записи из трёх таблиц (`cot_visitor_stats`, `cot_visitor_stats_daily`, `cot_visitor_stats_crawlers`) без удаления структуры. Доступна только администраторам.

Интерфейс построен на Bootstrap 5.3, встроенном в админку Cotonti (стили подключать не нужно).

### 🌐 Локализация

Поддерживаются русский и английский языки. Файлы локализации находятся в `lang/`.

---

## Структура плагина и назначение файлов
```
plugins/visitor_stats/
├── visitor_stats.setup.php               # Конфигурация плагина
├── visitor_stats.admin.php               # Административная панель
├── visitor_stats.global.php              # Глобальный хук (запускается при каждом запросе)
├── visitor_stats.header.first.php        # Хук ранней блокировки нежелательных ботов
├── inc/
│   ├── visitor_stats.functions.php       # Вспомогательные функции и подключение компонентов
│   ├── CrawlerDetectService.php          # Сервис-обёртка над библиотекой CrawlerDetect
│   ├── VisitorStatsService.php           # Основная бизнес-логика (сбор и запись данных)
│   └── VisitorStatsRepository.php        # Слой работы с базой данных
├── lib/
│   ├── CrawlerDetect.php                 # Главный класс детектирования ботов (портированная версия)
│   └── Fixtures/
│       ├── AbstractProvider.php          # Абстрактный класс для списков сигнатур
│       ├── Crawlers.php                  # Сигнатуры ботов и краулеров
│       ├── Exclusions.php                # Шаблоны, исключаемые из User‑Agent перед проверкой
│       ├── Headers.php                   # Заголовки, в которых может передаваться User‑Agent
│       └── WhitelistBots.php            # Белый список разрешённых ботов
├── setup/
│   ├── visitor_stats.install.sql         # SQL-запросы для создания таблиц
│   └── visitor_stats.uninstall.sql       # SQL-запросы для удаления таблиц
├── lang/
│   ├── visitor_stats.en.lang.php         # Английская локализация
│   └── visitor_stats.ru.lang.php         # Русская локализация
├── tpl/
│   └── visitor_stats.admin.tpl           # Шаблон административной панели
└── index.html                            # Заглушка


```

### Описание ключевых компонентов

- **visitor_stats.setup.php** — метаданные плагина: код, название, категория, версия, автор, лицензия, права доступа для гостей и пользователей.
- **visitor_stats.global.php** — точка входа при каждом запросе. Подключает необходимые файлы и вызывает `VisitorStatsService::recordVisit()` для записи визита. Содержит хук `global`.
- **visitor_stats.admin.php** — контроллер страницы статистики в админке. Обрабатывает фильтры, пагинацию, очистку данных, формирует запросы к БД и передаёт переменные в шаблон.
- **inc/visitor_stats.functions.php** — регистрирует таблицы плагина в `Cot::$db`, подключает сервисные классы и содержит коллекцию функций для извлечения информации о визите: `getRealIp()`, `getBrowser()`, `getOS()`, `getDeviceType()`, `getDeviceModel()`, `getCountry()`, `getIspInfo()`, `isUniqueVisitor()`. Эти функции могут использоваться в любом месте Cotonti (в том числе в других плагинах и шаблонах).
- **inc/CrawlerDetectService.php** — сервис-одиночка, предоставляющий методы `isCrawler($ua)` и `getCrawlerName($ua)`. При каждом вызове создаёт экземпляр `CrawlerDetect` с переданным User‑Agent, что гарантирует актуальную проверку.
- **inc/VisitorStatsService.php** — главный сервис, агрегирующий все данные о визите. В методе `recordVisit()` собираются IP, UA, геолокация, признаки бота и уникальности, после чего массив передаётся в репозиторий для вставки в БД.
- **inc/VisitorStatsRepository.php** — класс для работы с базой данных. Содержит методы для вставки записей (`insert`), получения статистики за период (`countVisits`, `countBotVisits`, `countUniqueVisitors`), извлечения топов (`getTopPages`, `getTopReferers`, `getTopCrawlers`) и дневной разбивки (`getDailyBreakdown`). Эти методы пока не задействованы в админке, но готовы к использованию.
- **lib/CrawlerDetect.php** и **Fixtures/\*** — портированная библиотека детектирования ботов. `Crawlers.php` содержит список сигнатур (имена ботов), `Exclusions.php` — шаблоны, которые удаляются из User‑Agent перед проверкой (для ускорения и снижения ложных срабатываний), `Headers.php` — список заголовков, в которых может передаваться User‑Agent.
- **lang/** — языковые файлы. Содержат все текстовые строки, используемые в админке и фронтальной части (при необходимости).
- **tpl/visitor_stats.admin.tpl** — шаблон страницы статистики с использованием классов Bootstrap 5.3. Не требует подключения внешних стилей, так как Cotonti уже включает Bootstrap.
- **setup/\*.sql** — SQL-запросы для создания и удаления таблиц плагина.

---

## Структура базы данных

Плагин работает с тремя таблицами (префикс `cot_` задаётся конфигурацией Cotonti; по умолчанию `cot_`).

### Основная таблица `cot_visitor_stats`

Хранит каждую зафиксированную сессию. Состав полей:

| Поле             | Тип             | Описание |
|------------------|------------------|----------|
| vs_id            | BIGINT UNSIGNED AUTO_INCREMENT | Первичный ключ |
| vs_date          | INT UNSIGNED     | Дата визита (UNIX timestamp) |
| vs_ip            | VARCHAR(45)      | IP-адрес (IPv4 или IPv6) |
| vs_user_id       | INT UNSIGNED     | ID пользователя Cotonti (0 для гостей) |
| vs_referer       | VARCHAR(500)     | Источник перехода (HTTP_REFERER) |
| vs_user_agent    | VARCHAR(500)     | Строка User‑Agent |
| vs_page          | VARCHAR(500)     | URL страницы (REQUEST_URI) |
| vs_crawler_name  | VARCHAR(255)     | Имя обнаруженного краулера (NULL, если не бот) |
| vs_browser       | VARCHAR(255)     | Браузер и версия |
| vs_os            | VARCHAR(255)     | Операционная система |
| vs_device_type   | VARCHAR(50)      | Тип устройства (Desktop/Mobile/Tablet) |
| vs_device_model  | VARCHAR(255)     | Модель устройства |
| vs_country       | VARCHAR(10)      | Код страны (из Cloudflare или ip-api) |
| vs_isp           | VARCHAR(255)     | Провайдер (ISP) |
| vs_is_vpn        | TINYINT(1)       | 1 — используется VPN/прокси, 0 — нет |
| vs_is_bot        | TINYINT(1)       | 1 — бот, 0 — человек |
| vs_unique        | TINYINT(1)       | 1 — новый посетитель, 0 — вернувшийся |

Индексы: PRIMARY (`vs_id`), KEY по `vs_date`, `vs_ip`, `vs_user_id`, `vs_crawler_name`.

### Таблица ежедневной сводки `cot_visitor_stats_daily`

| Поле               | Тип         | Описание |
|--------------------|-------------|----------|
| vsd_id             | INT UNSIGNED AUTO_INCREMENT | Первичный ключ |
| vsd_date           | DATE        | Дата (уникальная) |
| vsd_total_visits   | INT UNSIGNED| Всего визитов за день |
| vsd_human_visits   | INT UNSIGNED| Визитов людей |
| vsd_bot_visits     | INT UNSIGNED| Визитов ботов |
| vsd_unique_visitors| INT UNSIGNED| Уникальных посетителей |
| vsd_updated_at     | INT UNSIGNED| Время последнего обновления (timestamp) |

Уникальный ключ по `vsd_date`.

### Таблица статистики по краулерам `cot_visitor_stats_crawlers`

| Поле             | Тип         | Описание |
|------------------|-------------|----------|
| vsc_id           | INT UNSIGNED AUTO_INCREMENT | Первичный ключ |
| vsc_date         | DATE        | Дата |
| vsc_crawler_name | VARCHAR(255)| Имя краулера |
| vsc_visits       | INT UNSIGNED| Количество визитов этого краулера за день |
| vsc_updated_at   | INT UNSIGNED| Время последнего обновления |

Уникальный ключ по комбинации `vsc_date` + `vsc_crawler_name`.

### Почему таблицы `cot_visitor_stats_daily` и `cot_visitor_stats_crawlers` пусты?

На данный момент плагин ведёт запись **только в основную таблицу `cot_visitor_stats`**.  
Агрегация дневных итогов и суммирование по краулерам в соответствующих таблицах **не выполняется автоматически**.  
Эти таблицы были созданы «на будущее»: их структура готова, и в следующих версиях планируется реализовать механизм периодического (например, по cron) или событийного заполнения, чтобы ускорить отчёты за большие промежутки времени.

Текущая админка строит всю статистику напрямую через SQL‑запросы к `cot_visitor_stats` (подсчёт COUNT, группировки), поэтому сводные таблицы пока остаются незаполненными.

---

## Установка

1. Скопируйте папку `visitor_stats` в каталог `plugins` вашего сайта.
2. Перейдите в админку Cotonti → «Расширения».
3. Найдите в списке плагин **Visitor Statistics with Crawler Detection** и нажмите «Установить».
4. При установке автоматически выполнятся SQL‑запросы из `setup/visitor_stats.install.sql` — будут созданы три таблицы.
5. После установки плагин сразу начинает фиксировать визиты (на страницы фронтальной части и админки).

Для удаления плагина используйте стандартную процедуру в админке: будет выполнен файл `visitor_stats.uninstall.sql`, который удалит таблицы.

---

## Использование

### Просмотр статистики

В админке Cotonti в разделе **«Прочее»** появится пункт **«Статистика посещений»** (или **Visitor Statistics** в английской версии).  
Страница содержит:

- **Четыре карточки** в верхней части: общее количество визитов, визиты людей, визиты ботов, уникальные посетители.
- **Форму фильтра**: можно задать период в днях (пока не влияет на выборку, оставлено для будущей реализации) и отметить чекбокс «Показать только ботов» — тогда таблица покажет только строки, где обнаружен краулер.
- **Таблицу журнала** с постраничным выводом последних визитов. Колонки соответствуют всем собранным полям.
- **Кнопку «Очистить все данные»** (доступна только администраторам). При нажатии с подтверждением полностью удаляет все записи из трёх таблиц, но не трогает их структуру.

### Информационные функции

В любом месте Cotonti (шаблоны, другие плагины) доступны функции:

- `getRealIp()`
- `getUserAgent()`
- `getCountry()`
- `getBrowser($ua = null)`
- `getOS($ua = null)`
- `getDeviceType($ua = null)`
- `getDeviceModel($ua = null)`
- `getIspInfo()`
- `isUniqueVisitor()`

Они определены в `visitor_stats.functions.php` и могут использоваться после активации плагина.

___

## 🛡️ Белый список ботов и ранняя блокировка нежелательных краулеров

### Зачем это нужно

Плагин не только собирает статистику по ботам, но и умеет **избирательно блокировать** тех из них, которые не представляют ценности для вашего сайта.  
Это позволяет:

- Снизить нагрузку на сервер, отсекая бесполезных или вредоносных ботов до выполнения основного кода Cotonti.
- Пропускать только тех поисковых роботов и сервисы, которые реально помогают продвижению (Google, Яндекс, Bing, Facebook, PageSpeed Insights, Ahrefs и т.п.).
- Защитить контент от парсинга нежелательными краулерами.

### Как это работает

1. **Белый список** (`lib/Fixtures/WhitelistBots.php`)  
   Содержит массив имён ботов, которым разрешён доступ. Проверка идёт по **частичному совпадению без учёта регистра**.  
   Если имя обнаруженного бота содержит любую из строк белого списка — он пропускается.  
   Администратор может легко редактировать этот файл, добавляя или удаляя нужных ботов.

2. **Хук `header.first`** (`visitor_stats.header.first.php`)  
   Выполняется на самом раннем этапе обработки запроса, до загрузки каких‑либо данных.  
   - Определяет, является ли посетитель ботом, с помощью `CrawlerDetectService`.
   - Если бот обнаружен, вызывает функцию `isAllowedBot()`, которая сверяет имя бота с белым списком.
   - Если бот **не разрешён**:
     - Скрипт немедленно отдаёт HTTP‑ответ **403 Forbidden**.
     - Тело ответа содержит простой текст `Access denied for this bot.`
     - Дальнейшее выполнение Cotonti прекращается (вызов `exit`), что экономит ресурсы.

3. **Функция `isAllowedBot()`** (в `inc/visitor_stats.functions.php`)  
   - Всегда разрешает людей (`null` или пустое имя бота).
   - Блокирует «подозрительные» UA, помеченные как `Suspicious UA` (старые Android, эмуляция устаревших устройств).
   - Сравнивает имя бота с белым списком через `stripos()`.

4. **Отладочный режим**  
   В файле `visitor_stats.header.first.php` есть флаг `$debug`.  
   Если установить его в `true`, плагин будет записывать в лог `debug_bot.log` каждое срабатывание: время, User‑Agent, имя бота и решение (ALLOWED/BLOCKED).  
   Это помогает отследить, какие боты пытаются заходить и не блокируется ли кто‑то нужный.

### Как добавить нового бота в белый список

1. Откройте файл `plugins/visitor_stats/lib/Fixtures/WhitelistBots.php`.
2. В массиве `getAllowed()` добавьте новую строку с частью имени бота, например:
   ```
   'yourNameBot',
   ```
3. Сохраните файл.

Изменения вступают в силу немедленно (при следующем запросе).

### Рекомендации по настройке белого списка

- Оставляйте в списке только тех ботов, которые приносят реальную пользу:
  - Поисковые системы (Google, Bing, Яндекс, Seznam, Baidu, Sogou и др.).
  - Анализаторы скорости (Lighthouse, PageSpeed Insights).
  - Сервисы для вебмастеров (Google Inspection Tool, Facebook External Hit).
  - Популярные SEO‑инструменты (Ahrefs, Semrush, DotBot), если вы не против их сканирования.
  - Полезные AI‑краулеры (GPTBot, ChatGPT‑User, Claude‑Web), если вы хотите, чтобы ваш контент использовался для обучения моделей или появлялся в результатах AI‑поиска.
- Всех остальных ботов плагин будет автоматически блокировать, снижая паразитный трафик.

### Проверка работы

После добавления новых правил можно включить отладку (`$debug = true`) и проанализировать лог `debug_bot.log`, чтобы убедиться, что нужные боты пропускаются, а нежелательные блокируются.  
Не забудьте выключить отладку после проверки, чтобы лог не разрастался.

___
## Технические детали

- **Требования:** Cotonti ≥ 1.0 (с поддержкой PHP 8.x), PHP 8.0+, MySQL 5.7+ (или MariaDB).
- **Библиотека Crawler‑Detect** портирована из оригинального репозитория JayBizzle/Crawler-Detect (MIT).  
  Изменения: убраны namespace и use, адаптированы регулярные выражения (используется разделитель `#` вместо `/`), добавлены дополнительные сигнатуры (`Chrome-Lighthouse`, `Google-InspectionTool`, `meta-externalagent`).
- **Внешние зависимости:** отсутствуют. Composer не требуется.
- **API ip-api.com:** используется для получения ISP и признака прокси. Запросы кэшируются в сессии на 1 час. Сервис бесплатный, но имеет ограничение 45 запросов в минуту с одного IP.
- **Сессии:** плагин стартует сессию при необходимости для кэширования данных ip-api и для отслеживания уникальных посетителей.

---

## Планы по развитию

- Реализовать фоновое заполнение таблиц `cot_visitor_stats_daily` и `cot_visitor_stats_crawlers` (через cron или отложенный скрипт).
- Добавить графики и визуализацию статистики.
- Внедрить фильтр по дате в админке с реальным ограничением выборки.
- Расширить количество определяемых ботов.
- Добавить возможность экспорта данных (CSV, Excel).
- Интеграция с Cotonti API для получения статистики через REST.

---


## Лицензия

Плагин распространяется под лицензией BSD-3-Clause.  
Библиотека Crawler-Detect — MIT.

Автор плагина: [webitproff](https://github.com/webitproff).  
Репозиторий: [https://github.com/webitproff/visitor-stats-crawler-cotonti](https://github.com/webitproff/visitor-stats-crawler-cotonti)
