---
paths:
  - '**/*.php'
---

# General

## Reload PHP-FPM after every Malbec deploy (OPcache staleness)
Production (ploi@100.74.201.7, ~/newsfeed.ihowz.uk) runs php8.4-fpm with opcache.validate_timestamps=0, so changed files (especially compiled Blade views) are invisible on the web until `sudo systemctl reload php8.4-fpm` runs. Deploy sequence must end with this reload. CLI checks (tinker) use opcache-enabled=Off and can show new code while the site still serves old — don't be fooled.
