=== Zeno Report Comments ===
Contributors: mpol
Tags: flag comments, report comments, spam comment, safe report comments, crowd control
Requires at least: 3.7
Tested up to: 5.3
Stable tag: 1.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin gives your visitors the possibility to report a comment as inappropriate. After a set threshold the comment is put into moderation.

== Description ==

This plugin gives your visitors the possibility to report a comment as inappropriate. After a set threshold is reached the comment is put into moderation where the moderator can decide whether or not he want to approve the comment or not. If a comment is approved by a moderator it will not be auto-moderated again while still counting the amount of reports.

This plugin is a fork of safe-report-comments and has some features incorporated from crowd-control (a different fork).

= Compatibility =

This plugin is compatible with [ClassicPress](https://www.classicpress.net).


== Installation ==

1. Download and unzip the plugin.
2. Copy the zeno-report-comments directory into your plugins folder.
3. Visit your Plugins page and activate the plugin.
4. A new checkbox called "Allow comment flagging" will appear in the Settings->Discussion page.
5. Activate the flag and set the threshold value which will appear on the same page after activation.

The plugin should work by default in most themes. If something does not work, please start a topic at the support forum.

== Screenshots ==

1. Simple activation via discussion settings.
2. Amount of reports per comment is shown in comments administration screen.
3. Fits well within most themes without any further action.
4. Ajax feedback right in place where available.


== Known issues ==

Automatic mode implementation currently does not work with threaded comments in the last level of threading. As the script attaches itself to the comment_reply which is not displayed once the maximum threading level is reached the abuse link is missing at this point. As a workaround set the threading level higher than the likely amount of threading depth.


== Changelog ==

= 1.2.4 =
* 2019-04-24
* Add filter manage_edit-comments_sortable_columns so the column can be sorted (thanks wmeric).

= 1.2.3 =
* 2018-10-29
* Add reporter ip address to abuse report email.

= 1.2.2 =
* 2018-10-03
* Don't use new function on wp-admin.

= 1.2.1 =
* 2018-10-03
* Return comment content on threaded comments.

= 1.2.0 =
* 2018-10-03
* Support link with unthreaded comments too.

= 1.1.2 =
* 2018-06-26
* Add example text to the privacy policy.

= 1.1.1 =
* 2018-06-11
* When moderating comment, set status to approve.
* Filter all frontend messages.

= 1.1.0 =
* 2017-05-22
* Don't show flagging link if already moderated (really this time).
* Rewrite the html of the link.
* Ues wp_localize_script also for nonce.
* Add rel="nofollow" to the link.
* Delete reports after moderating as allowed.
* Add link for moderators to moderate/allow comments and remove reports.
* Add Admin JavaScript.
* Add version to JavaScripts.

= 1.0.0 =
* 2016-07-31
* Forked from safe-report-comments.
* Support localization and translation.
* Make sure cookie_data is an array, as returned by json_decode.
* Make it not possible to report your own comments.
* Don't show flagging link if already moderated.
* Load JavaScript in footer, not in head.
* Add function get_user_ip for proxies (props Thorsten Ott).
* Add Copyright notices.
* Add settings for mail and add function for wp_mail (props Postmatic).
* Add action for each report and add function for wp_mail.
* Remove unused action register_admin_panel in admin_menu.

= 0.4.1 =
* 2014-07-23
* Typo fix, props spencermorin.

= 0.4 =
* 2014-07-23
* Security fix, h/t vortfu.

= 0.3.2 =
* 2013-03-06
* New 'safe_report_comments_allow_moderated_to_be_reflagged' filter allows comments to be reflagged after being moderated.

= 0.3.1 =
* 2012-11-21
* Use home_url() for generating the ajaxurl on mapped domains, but admin_url() where the domain isn't mapped.

= 0.3 =
* 2012-11-07
* Coding standards and cleanup.
