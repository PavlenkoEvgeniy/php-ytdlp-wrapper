# Symfony Flex recipe scaffold

This directory contains a ready-to-submit recipe scaffold for Symfony Flex.

## Included version

- `1.0` (for package versions `1.0.*`)

## Files

- `1.0/manifest.json`
- `1.0/config/packages/yt_dlp.yaml`

## Publish to recipes-contrib

1. Fork `symfony/recipes-contrib`.
2. Copy the `1.0` directory to:
   `p3s/php-ytdlp-wrapper/1.0/`
3. Open a pull request to `symfony/recipes-contrib`.

After merge, Symfony Flex will auto-enable `P3s\\YtDlp\\YtDlpBundle` and install default config when users run:

```bash
composer require p3s/php-ytdlp-wrapper
```
