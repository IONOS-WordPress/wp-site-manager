=== Auto Updater ===
Contributors: 1and1, ionos, markoheijnen, pfefferle, gdespoulain
Tags: autoupdate, changelog, admin, upgrade, install, automatic
Requires at least: 3.8
Tested up to: 5.3
Stable tag: 1.1.4
Requires PHP: 5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Update WordPress, themes and plugins to improve stability and security.

== Description ==

The Site Update Manager keeps your WordPress and all its themes and plugins up to date. It all works automatically and in the background. You can customize the update settings, if you prefer. The changelog provides an overview and details on all installed updates.

== Installation ==

1. Upload the `Auto Updater` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In your WordPress Dashboard select: Auto Updater > Settings.
4. Customize your settings or keep the default settings.
5. Save your changes.

== Frequently Asked Questions ==

= When will updates happen? =

Auto Updater checks for updates **every 12 hours**. Updates are then installed automatically.

= Why do major updates (WordPress core) take more time in some cases? =

Major updates are delivered by the WordPress core team and might take more than 12-24 hours to appear. You can perform major updates manually if needed.

= Why are some settings not available for my installation? =

When your installation doesn’t allow major updates the settings will not be visible within the Auto Updater Settings.

== Screenshots ==

1. Updater Settings
2. Updater Changelog

== Changelog ==

Project and support maintained on github at [1and1/wp-site-manager](https://github.com/1and1/wp-site-manager).

= 1.1.4 =
* Update versions and requirements
* Fix PHP warning on settings page
* Add missing text-domains

= 1.1.3 =
* Update versions and requirements
* Add 1&1 IONOS as contributor

= 1.1.2 =
* Fix autodeployment

= 1.1.1 =
* Update "Tested up to" version

= 1.1.0 =
* Fix "logging installations" of plugins and themes.
* UI improvements

= 1.0.2 =
* Some hardening for possible errors. Soon new cool features.

= 1.0.1 =
* Fix issue when activating this plugin.

= 1.0 =
* Update WordPress, themes and plugins.
* Detailed log and history for all your updates.
* Customize the update settings. Select what you want to update automatically/manually.
