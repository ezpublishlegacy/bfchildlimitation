bfchildlimitation eZPublish extension
==================

Purpose: To modify/give extra capability to the child listing panel of the admin content/view/full for all nodes.
More specifically:

1. to limit the kinds of children one can add (see at the very bottom for why plain security need some help there)
2. to limit the kinds of children we'll list
3. to allow (via configuration alone) to add any special columns out of your content to the default set of columns
	(see docs/README-CUSTOMATTRIBUTECOLUMNS.txt for step by step instructions on how to make this happen)
	TODO: add a switch that determines what columns get activated where, based on at least current user/current node

Software Dependencies:
--------------------------------

Written in eZPublish 4.5, and tested in eZPublish 4.6-4.7.
Dependencies: 

1. bfezrules extension (if you are using the RuleSet_FullSiteChildAdd)
2. bfadminnodeedit (if you want limitations to propagate to front-end (toolbar) as well
3. bfdebug (to use debug information)

Multilanguage considerations:
--------------------------------

None.

Multisite considerations:
--------------------------------

None.

Installation and Setup, Class Add Limitation
--------------------------------

To install, just add as extension, make sure this is included (in site.ini) before your project extension. Also install dependent extensions (see above).

If you want to limit the kinds of children you can add, here's the general idea:

1) You create a set of xml files with each containing a list of xml "rule" nodes. 
2) In those sequentially processed rules, each rule contains a logic node, and one or more action nodes. (examples of actions are: hide "Standard" class group, or Remove all classes except those in "Media" class group - see full list at the top of "standardExclusions-Multisite.xml" file).

This extension gives these rules for processing to the bfezrules code. bfezrules runs through all the rules, and all the logic, returning back with a simple list of applicable actions.

Those actions are then executed, in order, on the initial list of available classes.

Specifically, to set up your initial exclusions, do this:

0) Make sure you have bfezrules extension installed too.
1) In your client extension's bfchildlimitation.ini.append.php, add the following:

[ChildAddition]
RuleSet=FullSiteChildAdd

3) In your client extension, create a bfezrules.ini.append.php file.
4) In that file, create a Rule Set for "FullSiteChildAdd" like this:

[RuleSet_FullSiteChildAdd]
RuleSet[]
RuleSet[]=extension/bfezrules/settings/standardVariableGather.xml
RuleSet[]=extension/bfchildlimitation/settings/standardExclusions-Multisite.xml
RuleSet[]=extension/{clientextension}/settings/standardExclusions-{ClientName}.xml

5) In that last file, write special rules for your standard exclusions:

extension/{clientextension}/settings/standardExclusions-{ClientName}.xml

To understand the rules, use standardExclusions-Multisite.xml file. It basically takes core ezPublish installation, and whittles it down to something that makes sense to start with (take a look at the user section, for instance).

To further understand what is going on, you can always use the <logic> segments to print out any data with PHP.
Or there is also a debug mode (for that, you'll also need bfdebug extension).

Installation and Setup, Class List Limitation
--------------------------------

To use this, please just add "invisible" classes to bfchildlimitation.ini, [ChildListing] group.

Installation and Setup, Class List Limitation
--------------------------------

FOR ADDING CUSTOM COLUMNS, PLEASE SEE docs/README-CUSTOMATTRIBUTECOLUMNS.txt

Usage
--------------------------------

Should be automatic.

1) For child limitation, your users (including admin, unless otherwise specified) should be seeing a moderated list of available classes when trying to add new objects. 

2) For class list limitation, you should not be seeing specified classes anymore

3) For custom columns, those columns should be the same as other columns - listed right alongside name/class, etc. 

TODOs
--------------------------------

None at the moment.


WHY NORMAL SECURITY NEEDED A LITTLE HELP
--------------------------------

In a sentence, there is no "except" clause to security, once you have the right to add an object of a certain class somewhere, you cannot make any more exceptions to it.

While it is true that THEORETICALLY the existing security system is sufficient, in PRACTICE the administrator/developer simply has to jump through too many hoops to make the system work with the precision that it should.

Before we go any further, let's say what we're NOT saying here:

1) That this extension takes place of full site security. (if anything, during builds, use this system to start with, then "transcribe" most of that security into the regular security model - or stop if this is a site with simple needs, like no layers of security or workflow).

2) That this extension deals with full security (custom modules, etc, etc) - it doesn't, it merely modifies a list of classes that can be added in any particular spot on the site.

3) That this extension's security cannot be defeated - it's merely a cleaning of the list of available classes (to add as children) - after they've already passed security. There are no further hooks on the receiving side of the forms. So if you want to try to fake the system into adding "bad" classes, you can.

So let's go with a few examples:

1) The user logs in as admin, goes to a section of the subsite, and they have an option of ALL the classes on the system, the list usually being at least 40-50 choices. Or the admin goes to the user section, and has the option of adding multimedia entries in the user "directory". Theoretical solution: don't give them admin, ever? 

...then you build your entire security from scratch, the way that you're supposed to. Anonymous users first, then site editors, then publishers, then admins. Then you do the sections, tie the security to classes and subsections, stick those rules into policies, then those into roles, and roles to user groups, all of that.

2) A user logs in, and under homepage, they see they can add folders, landing pages and basic articles. Great. Chances are, this was globally allowed, anywhere in that section/subtree. As a developer, you are building a slideshow. You create the about us page (plain article), and under that, you want to have a folder (plain folder) holding the slides for your slideshow, shown on that custom page. How do you specify that under that one slide folder, you want just slides, and nothing else? 

So as a dev, you can switch to class-based rules for what's allowed where. Under Frontpage, allow Landing pages, articles, and folders. Under Landing pages or articles, allow folders, or other articles. Etc, etc. But that means that any time you want a plain page with a different list of allowed children, you need to create a new class, regardless of just copying the fields. In a complex system, that leads to a lot of class duplication, with extra headache for overrides, modifications to common attributes, etc. There is no subclassing mechanism in ez.

We just need the ability to tweak that list of available items, to suit our particular site best (based on node, path, user, time of day, phase of the moon - whatever). And for when we log in as admins, to not be lost in choices that don't make sense.

And that's what the "Class Add Limitation" part of what this extension is for.