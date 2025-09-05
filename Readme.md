[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/GeorgRinger/20)


# TYPO3 Extension `news_redirect_slug_change`

This extension generates redirects for EXT:news records if the slug is changed.

## Setup

Install the extension just as any other extension as well.

- Use `composer req georgringer/news-redirect-slug-change`
- or from TER

*For TYPO3 12 and below, use version 1.2.x*

## Configuration

The only thing which must be configured is the page which is used as detail page.
This can be either done by PageTsConfig with `tx_news.redirect.pageId = 456` or
within the site configuration. See below for full example.

```yaml
redirectsNews:
 # Detail page id which can be overruled py pageTsConfig tx_news.redirect.pageId = 456
 pageId: 123
 # Automatically create redirects for news with a new slug (works only in LIVE workspace)
 # (default: true)
 autoCreateRedirects: true
 # Time To Live in days for redirect records to be created - `0` disables TTL, no expiration
 # (default: 0)
 redirectTTL: 30
 # HTTP status code for the redirect, see
 # https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections#Temporary_redirections
 # (default: 307)
 httpStatusCode: 307
```

## Say thanks

If you are using this extension in one of your projects or for a client, please think about sponsoring this extension.

- Paypal: https://www.paypal.me/GeorgRinger/20
- *or* contact me if you need an invoice

**Thanks!**
