=== Dodo ===
Contributors: Stelios Petrakis, Christos Zigkolis
Donate link: http://www.getdodo.com
Tags: personalization, recommendation,users,tracking
Requires at least: 2.1
Tested up to: 2.7.0
Stable tag: 1.7.0

Dodo is a Wordpress plugin that personalizes your blog homepage to any of your registered users.

== Description ==

Dodo is a Wordpress plugin that personalizes your blog homepage to any of your registered users. Dodo offers a personalized view based on the user profile, which is created by tracking searches, tags and categories a user visits, giving a generalized view to the blogger as well as informative widgets to each user, for their favorite tags and categories. Widgets also include answers to user comments, personal tag cloud and recommendation of related posts based on similar users.

Blogger and users are able to select what actions will be tracked and which widgets (and in what order) will be shown in the personalized view, having the option to switch off the whole view, in one click.

Blogger has also the ability to know every time he/she writes a story, which are the "hot" tags and the most interesting category on his blog, based on the records from post/search tracking, in order to come up with more interesting stories. Dodo admin interface gives blogger the possibility to watch in real time, how tracking goes, edit widget css and track the 5 hottest tags / categories / searches.

== Installation ==

1. Place `personalized-blog/` folder in `<your-blog-url>/wp-contents/plugins/` folder
2. Edit your theme, by opening index.php file and changing this line:

`<?php if (have_posts()) : 
	?>`

into this:

`<?php if($dodo->getInterface())
	if (have_posts()) : ?>`
	
== Frequently Asked Questions ==

= Where I can find more information about Dodo =

You can visit our site at http://www.getdodo.com


== Screenshots ==

1. General admin interface, tracking tools
2. Widget drag & drop technique
3. Personalized Blog Homepage
4. Post recommendation engine
5. Post widget for blogger
