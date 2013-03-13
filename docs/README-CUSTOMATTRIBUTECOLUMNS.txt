Purpose: to explain how to to add custom attributes into the child listing

I) Add the following items into your project's childlimitation.ini file:

[ExtraChildlistColumns]
Columns[]
Columns[]=sunrise_date
Columns[]=sunset_date

[ExtraChildlistColumn_sunrise_date]
# Note: if not present, we'll use raw name (sunrise_date)
Label=Sunrise Date
#Note: for extra formatting (not ToString()), use the following static method somewhere. We'll pass the attributename (so "sunrise_date"), and a full object to it (in case we need more info) 
#If you don't specify this, we'll try to get an attribute with this identifier, we'll just run ToString() on it
ValueGenerationStaticMethod=someclass::somemethod
# Note: if not present, we'll assume true
IsSortable=true
# Note: if not present, we'll assume true
IsResizable=true

[ExtraChildlistColumn_sunset_date]
.... repeat all fields above ....

II) Write your static method(s) to deal with creating the columns. This extension takes care of everything else.