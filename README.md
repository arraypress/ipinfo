# Conditional Rules Example Plugin

This plugin demonstrates how to use conditional rules in a plugin.

## Installation

To install this plugin, clone the repository into your WordPress plugins
directory:

```bash
git clone git@github.com:blockifywp/conditional-plugin.git
```

Navigate into the plugin directory:

```bash
cd conditional-plugin
```

Install the dependencies:

```bash
composer install
```

## Usage

This plugin adds a new example post type called 'Conditional Rules'. Follow the
steps below to test the conditional rules:

1. Create a new post of the 'Conditional Rules'.
2. In the Conditional Rules meta box, set the operator and a time value.
3. Enter a title for the post and publish it.
4. Visit the front end of your site.
5. If the condition is true, a paragraph with the current time will be
   displayed.
