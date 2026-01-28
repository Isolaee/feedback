# Anonymous Feedback

WordPress plugin that adds a `[anonymous_feedback]` shortcode for collecting anonymous user feedback via email.

## How it works

The shortcode renders a button that opens a popup modal. Users can write feedback and submit it. The feedback is sent to `info@growthrocket.fi` using `wp_mail()`. No personal information is collected.

## Installation

1. Copy the `feedback` folder to `wp-content/plugins/`
2. Activate "Anonymous Feedback" in the WordPress admin
3. Ensure your WordPress site has email sending configured (e.g. via an SMTP plugin)

## Usage

Place the shortcode on any page or post:

```
[anonymous_feedback]
```

Customize the button text:

```
[anonymous_feedback button_text="Lähetä palautetta"]
```

## Email content

Each feedback email includes:

- The feedback message
- The page URL where it was submitted
- A timestamp

## Requirements

- WordPress 5.3+
- PHP 7.4+
- A working `wp_mail()` configuration
