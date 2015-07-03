=== ObjSpace Backups ===
Contributors: Ipstenu
Tags: cloud, objspace, objspacebackup, backup
Requires at least: 3.4
Tested up to: 4.2
Stable tag: 3.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Backup your WordPress site to ObjSpace.

== Description ==

This plugin is a fork of https://wordpress.org/plugins/dreamobjects/ which has been adapted to work with Yomura's obj.space object store. For support please open a ticket via the client portal.


Well now that we've gotten the sales-pitch out of the way, ObjSpace Connections will plugin your WordPress site into ObjSpace, tapping into the amazing power of automated backups!

= Backup Features =
* Automatically backs up your site (DB and files) to your ObjSpace cloud on a daily, weekly, or monthly schedule.
* Retains a limitable number of backups at any given time (so as not to charge you the moon when you have a large site).
* Provides <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> hooks to do the same

= To Do =
* Offer syncing backup as an alternative (see <a href="http://blogs.aws.amazon.com/php/post/Tx2W9JAA7RXVOXA/Syncing-Data-with-Amazon-S3">Syncing Data with Amazon S3</a>)
* Option to email results (if logging, email log? Have to split up by attempt for that)

= Credit =

Version 3.5 and up would not have been possible without the work Brad Touesnard did with <a href="https://wordpress.org/plugins/amazon-web-services/">Amazon Web Services</a>. His incorporation of the AWS SDK v 2.x was the cornerstone to this plugin working better.

== Installation ==

1. Sign up for <a href="http://www.obj.space/">ObjSpace</a>
1. Install and Activate the plugin
1. Fill in your Key and Secret Key
1. Go to the backups page
1. Pick your backup Bucket
1. Select what you want to backup
1. Chose when you want to backup
1. Relax and let ObjSpace do the work

== Frequently asked questions ==

= General Questions =

<strong>What does it do?</strong>

ObjSpace Connection connects your WordPress site to your ObjSpace cloud storage, allowing you to automatically store backups of your content.

<strong>Can I use this on Multisite?</strong>

Not at this time. Backups for Multisite are a little messier, and I'm not sure how I want to handle that yet.

<strong>What does it backup?</strong>

Your database and your wp-content folder.

In a perfect world it would also backup your wp-config.php and .htaccess, but those are harder to grab since there aren't constant locations.

<strong>How big a site can this back up?</strong>

PHP has a hard limit of 2G (see <a href="http://docs.aws.amazon.com/aws-sdk-php/guide/latest/faq.html#why-can-t-i-upload-or-download-files-greater-than-2gb">Why can't I upload or download files greater than 2GB?</a>), so as long as this is uploading a zip of your content, it will be stuck there. Sorry.

<strong>Why does my backup run but not back anything up?</strong>

Your backup may be too big for your server to handle.

A quick way to test if this is happening still is by trying to only backup the SQL. If that works, then it's the size of your total backup.

<strong>Wait, you said it could back up 2G! What gives?</strong>

There are a few things at play here:

1. The size of your backup
2. The file upload limit size in your PHP
3. The amount of server memory
4. The amount of available CPU

In a perfect world, you have enough to cope with all that. When you have a very large site, however, not so much. You can try increasing your PHP memorylimit, or if your site really is that big, consider a VPS. Remember you're using WordPress to run backups here, so you're at the mercy of a middle-man. Just because PHP has a hard limit of 2G doesn't mean it'll even get that far.

I have, personally, verified a 250MB zip file, with no timeouts, no server thrashing, and no PHP errors, so if this is still happening, turn on debugging and check the log. If the log stalls on creating the zip, then you've hit the memory wall. It's possible to increase your memory limit via PHP, <em>however</em> doing this on a shared server means you're probably getting too big for this sort of backup solution in the first place. If your site is over 500megs and you're still on shared, you need to seriously think about your future. This will be much less of an issue on VPS and dedi boxes, where you don't have the same limits.

<strong>Where's the Database in the zip?</strong>

I admit, it's in a weird spot: /wp-content/upgrade/objspace-db-backup.sql

Why there? Security. It's a safer spot, though safest would be a non-web-accessible folder. Maybe in the future. Keeping it there makes it easy for me to delete.

<strong>My backup is small, but it won't back up!</strong>

Did you use defines for your HOME_URL and/or SITE_URL? For some reason, PHP gets bibbeldy about that. I'm working on a solution!

= Using the Plugin =

<strong>How often can I schedule backups?</strong>

You can schedule them daily, weekly, or monthly.

<strong>Can I force a backup to run now?</strong>

Yep! It actually sets it to run in 60 seconds, but works out the same.

<strong>I disabled wp-cron. Will this work?</strong>

Yes, <em>provided</em> you still call cron via a grownup cron job (i.e. 'curl http://domain.com/wp-cron.php'). That will call your regular backups. ASAP backup, however, will need you to manually visit the cron page.

<strong>I kicked off an ASAP backup, but it says don't refresh the page. How do I know it's done?</strong>

By revisiting the page, <em>but not</em> pressing refresh. Refresh is a funny thing. It re-runs what you last did, so you might accidently kick off another backup. You probably don't want that. The list isn't dynamically generated either, so just sitting on the page waiting won't do anything except entertain you as much as watching paint dry.

My suggestions: Visit another part of your site and go get a cup of coffee, or something else that will kill time for about two minutes. Then come back to the backups page. Just click on it from the admin sidebar. You'll see your backup is done.

(Yes, I want to make a better notification about that, I have to master AJAX.)

<strong>How long does it keep backups?</strong>

Since you get charged on space used for ObjSpace, the default is to retain the last 15 backups. If you need more, you can save up to 90 backups, however that's rarely needed.

<strong>Can I keep them forever?</strong>

If you chose 'all' then yes, however this is not recommended. ObjSpace (like most S3/cloud platforms) charges you based on space and bandwidth, so if you have a large amount of files stored, you will be charged more money.

<strong>Why is upload files gone?</strong>

Becuase it was klunkly and hacky and a security hole.

<strong>How do I use the CLI?</strong>

If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server, you can use the following commands:

<pre>
wp objspacebackup backup
wp objspacebackup resetlog
</pre>

The 'backup' command runs an immediate backup, while the 'resetlog' command wipes your debug log.

<strong>Why doesn't it have a CDN?</strong>

Because we went with a slightly different feature with the CDN, and as such it's best as a separate plugin. Don't worry, they'll play nice!

<strong>Where did the uploader go!?</strong>

Away. It was never really used well and the CDN plugin will handle this much better. WP's just not the best tool for the job there.

= Errors =

<strong>Can I see a log of what happens?</strong>

You can enable logging on the main ObjSpace screen. This is intended to be temporary (i.e. for debugging weird issues) rather than something you leave on forever. If you turn off logging, the log wipes itself for your protection.

<strong>The automated backup is set to run at 3am but it didn't run till 8am!</strong>

That's not an error. WordPress kicks off cron jobs when someone visits your site, so if no one visted the site from 3am to 8am, then the job to backup wouldn't run until then.

<strong>Why is nothing happening when I press the backup ASAP button?</strong>

First turn on logging, then run it again. If it gives output, then it's running, so read the log to see what the error is. If it just 'stops', it should have suggestions as to why.

You can also log in via SSH and run 'wp objspacebackup backup' to see if that works.

== Screenshots ==
1. ObjSpace Private Key
1. Your ObjSpace Public Key
1. The Settings Page
1. The backup page
1. The uploader page, as seen by Admins
1. The uploader page, as seen by Authors
