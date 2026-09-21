---
paths:
  - app/Services/WordPressService.php
---

# Services

## Publish with the publisher's WordPress Application Password
WordPress auth is strictly per-user: the publishing User's email + their wp_application_password are the Basic-auth credentials (WP accepts email as username). There is no global fallback — wp_settings.username/application_password were dropped. Publishing without one fails with a clear message. The publisher id is passed through PublishToWordPress because queued jobs have no auth context.
