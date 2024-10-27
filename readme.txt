=== minteye ===
Contributors: minteye
Author URI: http://www.minteye.com
Tags: minteye, slide to fit, image captcha, minteye,mobile CAPTCHA,  PHP CAPTCHA, Free CAPTCHA, CAPTCHA Code, Sliding CAPTCHA, Skip, spam, comments, register, bot, CAPTCHA, security, Ads Captcha, AdsCaptcha, spam prevention, antispam, comments, recaptcha, registration, advertising ,income, money, revenue, ads, advertisement, anti-spam
Requires at least: 3.6.1
Tested up to: 3.7.1
Stable tag: 1.1.0

minteye - STOP SPAM, MAKE MONEY!

== Description ==

minteye allows you to add [CAPTCHA](http://www.minteye.com/) boxes for spam prevention, Unique No typing Sliding CAPTCHA
With easy integration of [minteye](http://www.minteye.com/) Sliding CAPTCHA, monetize your CAPTCHA and increase the success rate while reduce your site bounce rate.  Smart, quick and user friendly.

[Sign up](http://www.minteye.com/) now in order to get your activation codes.

Features:
--------
 * No Typing CAPTCHA
 * Fully configured from the admin panel
 * Setting to hide the CAPTCHA from logged in users and/or admins
 * Setting to show the CAPTCHA on the registration form and/or comment form

Requirements/Restrictions:
-------------------------
 * Works with Wordpress 3.6+
 * PHP 4.3+
 * Your theme must have a `<?php do_action('comment_form', $post->ID); ?>` tag inside your comments.php form. Most themes do.
If not, in your comments.php file, put <?php do_action('comment_form', $post-&gt;ID); ?> before <input name="submit"..>.

== Installation ==

1. Download the plugin and extract the folder contained within.

2. Upload or copy the folder to your /wp-content/plugins directory.

3. Visit [http://www.minteye.com/](http://www.minteye.com/) and sign up for a FREE minteye keys

4. Activate the plugin through the 'Plugins' menu in WordPress

5. Go to the 'Settings' menu in WordPress and set your minteye keys

== Screenshots ==

1. screenshot-1.gif is the CAPTCHA on the comment form.

2. screenshot-2.gif is the CAPTCHA on the registration form.

3. screenshot-3.gif is the 'minteye Options' screen on the 'Admin > Settings > minteye page.

== Changelog ==

= 1.1.0 =
* Release date: 23 October 2013
* Fix HTTPS (SSL) captcha verification

= 1.0.9 =
* Release date: 07 May 2013
* Fix API server domain

= 1.0.8 =
* Release date: 23 Feb 2011
* New at AdsCaptcha: Security Slider CAPTCHA
* New themes and available dimensions

= 1.0.7 =
* Release date: 02 Feb 2011
* Automatic HTTPS (SSL) support
* Fixed non-js browsers IFRAME scrolling and dimensions

= 1.0.6 =
* Release date: 19 Jan 2011
* Modified no JavaScript support (noscript) template

= 1.0.5 =
* Release date: 10 Jan 2011
* New AdsCaptcha API version

= 1.0.4 =
* Release date: 19 Dec 2010
* Fixed (another issue) the single quote usage in the custom error messages

= 1.0.3 =
* Release date: 16 Dec 2010
* Fixed the single quote usage in the custom error messages

= 1.0.2 =
* Release date: 30 Nov 2010
* Fixed delete_preferences() warning

= 1.0.1 =
* Release date: 22 Nov 2010
* Fixed registration CAPTCHA validation

= 1.0 =
* Release date: 10 Oct 2010
* First release!

== Frequently Asked Questions ==

= What are the keys I need to provide? =

After you sign up to [minteye](http://www.minteye.com/Publisher/) and register your website, you will get three keys which identify your account and secure your web page.

Make sure to copy & paste the exact keys values to your plugin's settings.

= The CAPTCHA is not being shown =

By default, registered users will not see the CAPTCHA.
Log out and try again.

If you don't want to hide the CAPTCHA from registered users (or other permission groups), simply uncheck the
'Hide CAPTCHA...' option or change the desired permission group on the minteye plugin's settings.

= Sometimes the CAPTCHA is displayed AFTER the submit button on the comment form =

Best practice is to edit your current theme comments.php file and locate this line:
<?php do_action('comment_form', $post->ID); ?>
Move this line to BEFORE the comment textarea and the problem should be fixed.

Alernately, you can check the 'Rearrange CAPTCHA's position on the comment form automatically' option on the minteye plugin's settings, and javascript will attempt to rearrange it for you.
This option is less recomended.
