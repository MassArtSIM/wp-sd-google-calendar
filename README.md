# wp-sd-google-calendar
Wordpress Plugin for combining public Google Calendars for display
created by <a href="http://smartwaredesign.com">SmartwareDesign.com</a>

The user can combine multiple public Google calendars into one feed to use on the REST API or generate one agenda calendar on the Wordpress frontend.

The user can have multiple combined calendars to use on different API calls or front end views with different shortcodes.  This plugin allows the user to enter a list of all public Google Calendars, the number of look-ahead days, and the timezone for displaying the dates and times of the calendar.

The RESTful call generates JSON that looks like the following as an example:
{
 'date':'2016-03-31',
 'events':[
   {'title':'Mayor&#39;s Masters Reception',
    'link':'https://calendar.google.com/calendar/event?eid=X2Q...&ctz=America/New_York'},
   {'time':'10am',
    'title':'AARP Tax Help', 
    'link':'https://calendar.google.com/calendar/event?eid=aWwy...&ctz=America/New_York'}
  ]
}

The RESTful call can be made by using a custom url to:
<your base wordpress site url>/wp-json/sd-google-calendar/v1/sdcalendar/<post ID>

The agenda calendar can be inserted to your wordpress post using the shortcode:
[sd_show_calendar id="<cal id>"]

There's also a "type" parameter which is defaulted to "agenda" for now but will be further developed to eventually use full calendar displays for scrolling different months.