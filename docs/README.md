# Email Log

Logs all emails sent by HumHub (recipient, subject, content, status and error), with a searchable administration list, failure alerts and auto-purge.

## Overview

- Every email sent by HumHub is logged, one row per recipient (To, Cc and Bcc): date, recipient user (if registered), email address, subject, content, status and error message
- Statuses: success, failed, suppressed (demo addresses ignored by HumHub) and cancelled (sending vetoed by another module)
- Administration list (system administrators only) with pagination, free text search, status and date range filters, and counters per status
- Preview of the email (HTML and plain text) and resend option
- Configurable content storage (full, plain text only, or no content) and redaction of security tokens (password recovery, registration, email change links, etc.) before storage
- Error in the HumHub log (with recipient and subject) when an email cannot be sent
- Notification to system administrators when an email cannot be sent (at most once per hour)
- Automatic deletion of logs older than a configurable period

With [the "Analytics" module](https://marketplace.humhub.com/module/analytics/description), you also get statistics about the emails sent: a chart of emails sent and emails that could not be sent, per day, and the total numbers.

## Pricing

This module is free, but the result of a lot of work for design and maintenance over time.

If it's useful to you, please consider [making a donation](https://www.cuzy.app/checkout/donate/) or [participating in the code](https://github.com/cuzy-app/email-log). Thanks!

## Repository

https://github.com/cuzy-app/email-log

## Publisher

[CUZY.APP](https://www.cuzy.app/)

## Licence

[GNU AGPL](https://github.com/cuzy-app/clean-theme/blob/master/docs/LICENCE.md)
