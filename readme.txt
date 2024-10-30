=== Bullshit Bingo Plugin for WordPress ===
Contributors: semper tiro
Donate link: 
Tags: buzzword, bullshit, bingo, random, words
Requires at least: 2.7.1
Tested up to: 2.8.6
Stable tag: 0.3

Create your own Bullshit Bingo (also known as Buzzword Bingo) card in any of
your posts or pages! Buzzwords are randomly chosen from a list you provide.

== Description ==

Displays a [Bullshit Bingo](http://en.wikipedia.org/wiki/Buzzword_bingo) (also
known as Buzzword Bingo) card in any of your posts or pages using a shortcode
(like for example `[bullshitbingo rows="5" columns="5"]`). As shown here, the
number of rows and columns is configurable. Buzzwords are stored in a WordPress
database table and may be arranged using tags (or categories or keywords, to
your liking). Each card displayed can show Buzzwords from any combination of
these tags. A new card will randomly be generated upon each call of the post or
page.  

The plugin sports a somewhat fancy backend and is prepared for
internationalization.

Support is provided at
[WordPress Support](http://wordpress.org/tags/bullshit-bingo-plugin-for-wordpress),
but please be so kind and read through this documentation before asking
questions.

== Installation ==

1. Upload Upload the 'bullshitbingo' folder to the /wp-content/plugins/ directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. See the 'Usage' section in the 'Other Notes' tab for more information on how to show Bullshit Bingo cards in your posts or pages

== Frequently Asked Questions ==

= Is there a version available in my local language? =

Well, as of writing this, the only words shown to the frontend user are the
buzzwords you enter, so there is no actual translation neccessary. As for the
administrative backend, the plugin is completely prepared for
internationalization. A German language version is to follow somewhat soon, and
you could even provide a translation to your language rather easily. Why don't
you take a look at
[I18n for WordPress Developers](http://codex.wordpress.org/I18n_for_WordPress_Developers)
for starters.  

== Screenshots ==

1. A sample Bullshit Bingo card on the plugin author's private blog.
1. The plugin's administrative backend.

== Changelog ==

= 0.3 =
* Allowed for bulk adding buzzwords
* Added German language translation
* Updated documentation
* Implemented using the url field to have buzzwords appear as links
* Minor bug fixes
* Corrected minor spelling errors

= 0.2 =
* First version to be hosted in the WordPress Plugin Directory.
* Implemented the administrative backend.

= 0.1 =
* First public release with basic functionality.

== Usage ==

To get your Bullshit Bingo card or cards up and running, you will first need to
put some buzzwords into the database. Just find the Bullshit Bingo Settings in
you adminsitrative backend, scroll to the bottom of the page and start typing.

* Assign exactly one 'Tag' to each item (a single word, no commas), or leave the field blank
* Fill the 'Buzzword' field
* You may assign an 'URL' for each buzzword, or leave the field blank - if there is an URL assigned to the buzzword, it will appear as link in the card
* Set the 'Visible' field to 'Yes' if you actually want to use this buzzword, leave the field at 'No' to not show this item right now

If want to add more than just a few buzzwords to the database, you might want to
use the bulk add form at the bottom of the page. Just type your buzzwords into
the textbox, one word per line. Or even better, cut and paste them from a
prepared list of words. Set the 'Tag' and 'Visible' fields as described above.
The downside to this approach is that the URL will be blank for each buzzword.

Next, create a new post or page or edit an existing one. Whereever you want the
Bullshit Bingo card to be displayed, insert the following shortcode, preferably
on a line by itself.

`[bullshitbingo]`

This will show a Bullshit Bingo card with 5 x 5 items with buzzwords selected
randomly from all active buzzwords.

Note: The card will only be displayed, if the database table contains enough
active buzzwords to fill it, i. e. to fill a 5 x 5 card you will need at least
25 active buzzwords in the database table.

Attributes may be added to the shortcode to customize the card.

`[bullshitbingo tag="default" rows="4" columns="3"]`

This will display a 4 x 3 card with active buzzwords tagged 'default'.

`[bullshitbingo tag="foo,bar" rows="3" columns="2"]`

This will display a 3 x 2 card with active buzzwords tagged 'foo' *or* 'bar'.

== Sample implementations ==

If you want to show up in the list of sample implementations, just drop the
plugin author a short note.

* [Bullshit Bingo fuer Laeufer](http://www.semper-ti.de/bullshit-bingo-fuer-laeufer) shows a card in German language with running related buzzwords.

== Roadmap ==

If there is any features you would like to see implemented in this plugin, go
ahead and contact the plugin author. Or, even better, just go ahead and
implement them yourself (and let me know). It's Open Source, after all.

1. Allow for customizing the table design, preferably with CSS
1. Add some AJAX magic to allow for creating a new card without having to reload the entire page
1. Add some sort of printer-friendly view to the card
1. Allow users (viewers) to suggest new buzzwords (which have to be apporved by an administrator)
1. Add some sort of statistics (number of buzzwords in the database)
1. Make the card 'playable' by allowing to cross out words.
1. Collect buzzwords from some appropriate web site creating a "Bullshit Bingo card of the day" for a specific topic
1. Add an attribute to select the target for links

== Support ==

Just add a new topic in the
[WordPress Support](http://wordpress.org/tags/bullshit-bingo-plugin-for-wordpress)
and I will get to you as soon as possible.

== Support this plugin ==

If you like this plugin, you are more than welcome to vote for it on the
[plugin page](http://wordpress.org/extend/plugins/bullshit-bingo-plugin-for-wordpress/).
Also, I will gladly receive your comments, feedback, questions, and suggestions.
I have not thought of any other ways how you could support or donate for this
plugin, but I might come up with something in the future...