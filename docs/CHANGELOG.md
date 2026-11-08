Changelog
=========

1.0.1 (Unreleased)
------------------
- Enh: The daily purge deletes the expired logs in chunks of 5,000 rows, so that a large purge does not lock the table while emails are being logged
- Enh: Searching for an email address (or its beginning) uses the index on the email column instead of scanning every log, user and profile

1.0.0 (October 15, 2026)
--------------------
- Enh: Initial release
