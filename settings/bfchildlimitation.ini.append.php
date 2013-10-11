<?php /*

[General]
#To activate this, you need bfdebug extension, and it will display the full set of rules and their aftermath in an array at the top of the page
Debug=0

#[ChildListing]
ExcludeTypes[]
#Then exclude any types that you don't want to see in your child list
#ExcludeTypes[]=bfpromoholder

#[ChildAddition]
#RuleSet=FullSiteChildAdd

#[ExtraChildlistColumns]
#Columns[]
#Columns[]=sunrise_date
#Columns[]=sunset_date

#[ExtraChildlistColumns_sunrise_date]
#Label=Sunrise
#Note: for extra formatting (not ToString()), use the following static method somewhere. We'll pass the attributename (so "sunrise_date"), and a full object to it (in case we need more info) 
#If you don't specify this, we'll try to get an attribute with this identifier, we'll just run ToString() on it
#ValueGenerationStaticMethod=someclass::somemethod
#IsSortable=true
#IsResizable=true

#[ExtraChildlistColumn_sunset_date]
#Label=Sunset
#Note: for extra formatting (not ToString()), use the following static method somewhere. We'll pass the attributename (so "sunset_date"), and a full object to it (in case we need more info) 
#If you don't specify this, we'll try to get an attribute with this identifier, we'll just run ToString() on it
#ValueGenerationStaticMethod=someclass::somemethod
#IsSortable=true
#IsResizable=true

*/ ?>