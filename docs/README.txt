Purpose: 
1) to limit the kinds of children one can add
2) to limit the kinds of children we'll list
3) to allow (via configuration alone) to add any special columns out of your content to the default set of columns
	(see README-CUSTOMATTRIBUTECOLUMNS.txt for step by step instructions how to make this happen)
	TODO: add a switch that determines what columns get activated where, based on at least current user/current node

Dependencies:
bfezrules extension (if you are using the RuleSet_FullSiteChildAdd)
bfadminnodeedit (if you want limitations to propagate to front-end (toolbar) as well

Front-end considerations:
(see dependencies). Should be accounted for.

Multilanguage considerations:
None.

Multisite considerations (see below):
No problem.

Setup
=====
Just add as extension, make sure this is included (in site.ini) before your project extension.
If you want to limit the kinds of children you can add, do the following:

1) In your client extension's bfchildlimitation.ini.append.php, add the following:
[ChildAddition]
RuleSet=FullSiteChildAdd

2) Then make sure you turn on and add bfezrules extension
3) In your client extension, create a bfezrules.ini.append.php file.
4) In that file, create a Rule Set for "FullSiteChildAdd" like this:

[RuleSet_FullSiteChildAdd]
RuleSet[]
RuleSet[]=extension/bfezrules/settings/standardVariableGather.xml
RuleSet[]=extension/bfchildlimitation/settings/standardExclusions-Multisite.xml
RuleSet[]=extension/{clientextension}/settings/standardExclusions-{ClientName}.xml

5) Obviously, write special rules for your standard exclusions (in the file you just referenced)

extension/{clientextension}/settings/standardExclusions-{ClientName}.xml

FOR ADDING CUSTOM COLUMNS, PLEASE SEE README-CUSTOMATTRIBUTECOLUMNS.txt