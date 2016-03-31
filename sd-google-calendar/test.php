<?php

include "inc/simple_html_dom.php";

//parameters needed are timezone and calendar ids:
$timezone = "America/New_York";

$calendarurl = "https://calendar.google.com/calendar/htmlembed?mode=AGENDA&";
$calendarurl .= "ctz=".urlencode($timezone)."&";
$calendars = array();
$calendars[0] = "i7a4rktg67fq0e5c1kird2j63nqt9ik2@import.calendar.google.com";
$calendars[1] = "lfh37fl0ml52kb5066fn4829lv5bpdn8@import.calendar.google.com";
$calendars[2] = "n6acnnejidnlu3v58svpk9hovca743bp@import.calendar.google.com";
$calendars[3] = "3hvrtq3l7hi9sssk0srbqppvf6nhb2ub@import.calendar.google.com";
$calendars[4] = "info@whatsupaugusta.com";
$calendars[5] = "gchrl.org_rqpvmg0cc0pbud9r6t3734n13s@group.calendar.google.com";
$calendars[6] = "gchrl.org_n3m1nf0iirje6annpk0gldiur4@group.calendar.google.com";
$calendars[7] = "gchrl.org_mt9iq7lfjconf60391hcqfk75k@group.calendar.google.com";
$calendars[8] = "gchrl.org_oqipkpnlpi08krb0c6peqe8rf8@group.calendar.google.com";
$calendars[9] = "gchrl.org_2jguin31a4ac76bpbri5dq6jb4@group.calendar.google.com";
$calendars[10] = "gchrl.org_viss8p74vac3rq116r9nnj7aos@group.calendar.google.com";
$calendars[11] = "gchrl.org_o03oplm6c754hij3j7eq99tktc@group.calendar.google.com";
$calendars[12] = "smartwaredesign.com_pqd0o8j6ufddo1kp69ad2b6s18@group.calendar.google.com";
foreach($calendars as $cal){
  $calendarurl .= "src=".urlencode($cal)."&";
}
$html = file_get_html($calendarurl);
//dates=20160530/20160630
//'https://calendar.google.com/calendar/htmlembed?title=What%27s%20Up%20Augusta%3F&showTitle=0&showNav=0&showDate=0&showPrint=0&showTabs=0&showCalendars=0&mode=AGENDA&height=400&wkst=1&bgcolor=%23ffffff&src=i7a4rktg67fq0e5c1kird2j63nqt9ik2%40import.calendar.google.com&color=%2323164E&src=lfh37fl0ml52kb5066fn4829lv5bpdn8%40import.calendar.google.com&color=%2343364E&src=n6acnnejidnlu3v58svpk9hovca743bp%40import.calendar.google.com&color=%2353464E&src=3hvrtq3l7hi9sssk0srbqppvf6nhb2ub%40import.calendar.google.com&color=%2363564E&src=info%40whatsupaugusta.com&color=%2373664E&src=gchrl.org_rqpvmg0cc0pbud9r6t3734n13s%40group.calendar.google.com&color=%2383764E&src=gchrl.org_n3m1nf0iirje6annpk0gldiur4%40group.calendar.google.com&color=%2393864E&src=gchrl.org_mt9iq7lfjconf60391hcqfk75k%40group.calendar.google.com&color=%2303964E&src=gchrl.org_oqipkpnlpi08krb0c6peqe8rf8%40group.calendar.google.com&color=%23A3064E&src=gchrl.org_2jguin31a4ac76bpbri5dq6jb4%40group.calendar.google.com&color=%23B3A64E&src=gchrl.org_viss8p74vac3rq116r9nnj7aos%40group.calendar.google.com&color=%23C3B64E&src=gchrl.org_o03oplm6c754hij3j7eq99tktc%40group.calendar.google.com&color=%23D3C64E&src=smartwaredesign.com_pqd0o8j6ufddo1kp69ad2b6s18%40group.calendar.google.com&color=%2323164E&ctz=America%2FNew_York');

$days = array();

$i=0;
foreach($html->find('div.date-section') as $element){
    $day = new stdClass();
    $day->events = array();
    $day->date = date_create_from_format('D M d, Y T',$element->find('div.date',0)->innertext . ' EST');
    $j=0;
	foreach($element->find('tr.event') as $event){
	   $evt = new stdClass();
       $evt->time = $event->find('td.event-time',0)->innertext;
       $evt->title = $event->find('span.event-summary',0)->innertext;
       $evt->link = 'https://calendar.google.com/calendar/'.$event->find('a.event-link',0)->href;
       $day->events[$j] = $evt;
       $j++;
    }
    $days[$i] = $day;
    $i++;
}
//print_r($days);


date_default_timezone_set($timezone);
$currentDate = new DateTime(date('Ymd'));
$futureDate = date_add($currentDate, new DateInterval('P10D'));
echo date('Ymd')." - ".$futureDate->format('Ymd');

//array of { [events] -> array( time, title, link ), date -> (date, timezone_type, timezone) } 

/**

Array
(
    [0] => stdClass Object
        (
            [events] => Array
                (
                    [0] => stdClass Object
                        (
                            [time] => 10:15am
                            [title] => Toddler Class
                            [link] => https://calendar.google.com/calendar/
                        )

                    [1] => stdClass Object
                        (
                            [time] => 11am
                            [title] => Preschool Class
                            [link] => https://calendar.google.com/calendar/
                        )

                )

            [date] => DateTime Object
                (
                    [date] => 2016-03-28 20:56:08
                    [timezone_type] => 2
                    [timezone] => EST
                )

        )
)
**/


?>