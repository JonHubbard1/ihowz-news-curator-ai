---
paths:
  - app/Services/WordPressService.php
---

# Services

## Publish with the publisher's WordPress Application Password
WordPress auth resolves per-publish: if the publishing User has wp_application_password, use their email + that password (WP accepts email as Basic-auth username); otherwise fall back to global WpSetting username/application_password. The publisher id is passed through PublishToWordPress because queued jobs have no auth context.
