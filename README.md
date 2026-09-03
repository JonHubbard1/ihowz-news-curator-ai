# iHowz News Curator AI — Laravel Edition

A mobile-first, AI-driven PRS news curation pipeline built as a **Laravel** application for the **Laravel Herd** environment.

## Stack

- **Framework:** Laravel 13 + Livewire 4 + Tailwind CSS
- **Database:** SQLite by default (swap to MySQL in `.env` if desired)
- **Queue:** Database-driven Laravel jobs
- **AI:** OpenAI PHP client (GPT-4o / DALL-E 3)
- **CMS Bridge:** WordPress REST API + Application Passwords

## Local Setup (Herd)

1. **Install PHP dependencies**

```bash
composer install
```

2. **Install Node dependencies and build assets**

```bash
npm install
npm run build
```

3. **Environment**

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and add:

```dotenv
NEWS_BRAND_NAME=iHowz
NEWS_BRAND_VOICE="professional, clear, practical guidance for landlords and letting agents"
NEWS_KEYWORDS="Private Rental Sector,Landlord Law,HMO Regulations,PRS,Section 21,Section 8,Buy to Let"
```

4. **Database & seed user**

```bash
php artisan migrate:fresh --seed
```

Default login:

- **Email:** `admin@ihowz.test`
- **Password:** `password`

5. **Queue worker** (required for AI + WordPress publishing)

```bash
php artisan queue:work
```

6. **Run discovery manually** (or rely on the scheduled every-4-hours cron)

```bash
php artisan news:discover
```

7. **Open in browser**

Visit `https://the-news-feed.test` (Herd) or `http://127.0.0.1:8080` if using `php artisan serve`.

## Configuration

Go to **Admin** in the app to set:

- **OpenAI API key**, LLM model, image model, brand voice.
- **WordPress base URL**, username, and application password.

## Workflow

1. **Triage Feed** — review discovered PRS stories, tap **Use** or **Scrap**.
2. **AI Factory** — queued job reads the source URL, rewrites the article, generates a DALL-E image, suggests category/tags.
3. **Editorial Feed** — review and edit the draft, run AI commands, regenerate image.
4. **Publish** — one tap pushes to WordPress with featured image, categories, and tags.

## Scheduler

Discovery runs automatically every 4 hours. Add to your server crontab:

```bash
* * * * * cd /Users/jonhubbard/Herd/the-news-feed && php artisan schedule:run >> /dev/null 2>&1
```

## PWA

The app is installable on iOS and Android with:

- `/manifest.json`
- `/service-worker.js`
- Apple mobile web app meta tags
- One-handed, thumb-driven mobile UI

## Security Notes

- All admin/triage/editorial routes require authentication.
- AI and WordPress credentials are stored in the database (admin panel) and never committed to code.

## Project Structure

```
app/
  Http/Controllers/     Triage, Editorial, Admin
  Jobs/                 ProcessStoryWithAi, PublishToWordPress
  Models/               Story, StoryEdit, AiSetting, WpSetting
  Services/             DiscoveryService, AiFactoryService, WordPressService
  Console/Commands/     news:discover
resources/views/        Blade templates (mobile-first PWA)
public/                 manifest.json, service-worker.js
routes/                 web.php, auth.php, console.php
```
