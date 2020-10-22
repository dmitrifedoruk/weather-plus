<?php

require 'base_weather.php';

require __DIR__ . '/vendor/autoload.php';

//require_once 'Mobile_Detect.php';
$detect = new Mobile_Detect;

use GuzzleHttp\Client;

//consumes API with Guzzle
function consumeAPI(string $url) {
    //new client instance
    $client = new GuzzleHttp\Client();

    //response
    $res = $client->request('GET', $url);

    //response to string
    $body = $res->getBody();

    //decoded into json
    $jsonArray = json_decode($body, true);

    return $jsonArray;
}



// Different rendering for mobile device
if( $detect->isMobile() && !$detect->isTablet() ){

}

$search_string = '';

//checks for POST data and executes appropriate action
if (isset($_GET) && !empty($_GET)) {
    extract($_GET);
    if ($search_string != '') {
        $search = trim($search_string);
        $url = 'http://www.mapquestapi.com/geocoding/v1/address?key=' . $locationKey . '&location=' . $search;
    } else {
        $url = 'http://www.mapquestapi.com/geocoding/v1/address?key=' . $locationKey . '&location=Baltimore,MD';
    }
} else {
    $url = 'http://www.mapquestapi.com/geocoding/v1/address?key=' . $locationKey . '&location=Baltimore,MD';
}

//get location data
$jsonArray = consumeAPI($url);

$latitude = $jsonArray['results'][0]['locations'][0]['latLng']['lat'];

$longitude = $jsonArray['results'][0]['locations'][0]['latLng']['lng'];

$location = $latitude . ',' . $longitude;

//formatted to be displayed with place name, so user has very specific info
$latLongDisplay = $latitude . ', ' . $longitude;


$city = $jsonArray['results'][0]['locations'][0]['adminArea5'];

$state = $jsonArray['results'][0]['locations'][0]['adminArea3'];

$country = $jsonArray['results'][0]['locations'][0]['adminArea1'];

if($country === 'XZ'){
    $country = 'International Territory';
}

$countryName = $country;



//conditional in case no state/province/etc info given
if ($state != "" && $city != "") {
    $locationDesc = $city . ', ' . $state . ', ' . $countryName;
}
elseif ($state != "" && $city === ""){
    $locationDesc = $state . ', ' . $countryName;
}
elseif ($state === "" && $city != ""){
    $locationDesc = $city . ', ' . $countryName;
}
else {
    $locationDesc = $countryName;
}


//get reliable time zone info, specifically gmtOffset and dst
$url = 'http://api.timezonedb.com/v2.1/get-time-zone?key=' . $tzKey . '&format=json&by=position&lat=' . $latitude . '&lng=' . $longitude;

//decoded into json
$jsonArray = consumeAPI($url);

//convert to hours
$gmtOffset = $jsonArray['gmtOffset']/3600;

$dst = $jsonArray['dst'];

//check for daylight savings
if($dst === 1) {
    $gmtOffset += 1;
}


//get weather from search location or local default
$url = 'https://api.darksky.net/forecast/' . $secret . '/' . $location;

//$url = 'https://api.darksky.net/forecast/' . $secret . '/39.5753,-76.9959';


//decoded into json
$jsonArray = consumeAPI($url);

//on the spot
if (isset($jsonArray['minutely'])) {
    $currentCond = $jsonArray['minutely']['summary'];
}
else {
    $currentCond = $jsonArray['currently']['summary'];
}

$currentTemp = $jsonArray['currently']['temperature'];

$apparentTemp = $jsonArray['currently']['apparentTemperature'];


$todayLabel = "";
$weatherToday = "";

if(isset($jsonArray['daily']['data']['0']['summary'])) {
    $todayLabel = "Today: ";
    $weatherToday = $jsonArray['daily']['data']['0']['summary'];
}


$timeZone = $jsonArray['timezone'];

//
//date_default_timezone_set($timeZone);


//$date=date_create();
//
//$currentTime = date_timestamp_get($date);

$currentTime = $jsonArray['currently']['time'];

//$currentDateTimeObj = new DateTime($currentTime);

$currentTempTime = date("H:i",$currentTime);

$uvIndex = $jsonArray['currently']['uvIndex'];

$hiTemp = $jsonArray['daily']['data']['0']['temperatureHigh'];

$hiTime = $jsonArray['daily']['data']['0']['temperatureHighTime'];

$hiTempTime = date("H:i",$jsonArray['daily']['data']['0']['temperatureHighTime']);

$lowTemp = $jsonArray['daily']['data']['0']['temperatureLow'];

$lowTime = $jsonArray['daily']['data']['0']['temperatureLowTime'];

$lowTempTime = date("H:i",$jsonArray['daily']['data']['0']['temperatureLowTime']);

$windSpeed = $jsonArray['currently']['windSpeed'];

$windGust = $jsonArray['currently']['windGust'];

$precip = $jsonArray['daily']['data']['0']['precipProbability'];

$moonPhase = $jsonArray['daily']['data']['0']['moonPhase'];




//using astr. twilight rather than 'sunrise/sunset' allows for a longer day length
//and may provide more nuanced effects for the dayLight feature

$sunrise = json_encode(date_sunrise($currentTime+$gmtOffset*3600, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 108, $gmtOffset));
$sunset = json_encode(date_sunset($currentTime+$gmtOffset*3600, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 108, $gmtOffset));


?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">
    <title>Weather Plus</title>

    <link rel="stylesheet" href="css/styles_weather_plus.css"/>
    <link rel="icon" type="image/png" href="lightning-icon.png"/>

    <script src="moment-with-locales.js"></script>
    <script src="moment-timezone-with-data-10-year-range.js"></script>

</head>

<body>

<header>

<!--    https://www.google.com/maps/place/39°34'30.8"N+76°59'45.7"W -->

<div id="location">
    <div>
        <div><?php echo $locationDesc?></div>
        <div id="latLongDisplay">
            <span class="parentheses">(</span>
            <a href="https://www.google.com/maps/place/<?php echo $location;?>"
               target="_blank"><?php echo $latLongDisplay?></a>
            <span class="parentheses">)</span>
        </div>
    </div>
</div>



<fieldset id="search">
    <legend>Search</legend>
    <form action="" method="GET">
        <input type="text" name="search_string" title="Enter location"
               placeholder="Location" required>
<!--        <input type="hidden" name="offset" value="0">-->
        <button type="submit" id="searchButton">Submit</button>
    </form>
</fieldset>

    <div id="attribution"><span id="attrLabel">Powered by </span><a href="http://darksky.net">Dark Sky</a></div>

</header>

<div id="wrapper">




    <div id="moonContainer">

        <div class="object" id="element"></div>

        <div class="object" id="element2"></div>

    </div>

<!--    <header id="coords""></header>-->

    <div id="current">
        <?php echo $currentCond; ?>
    </div>
    <div class="currentTemperature">
        <?php echo $currentTemp; ?>
        <span class="fahrenheit">&#176;F</span>
        <span class="tempTime" id="currentTempTime"></span>
        <div class="feelsLike"><span class="feelsLikeDesc">Feels like </span>
            <?php echo $apparentTemp; ?><span class="fahrenheit">&#176;F</span>
            <span class="uv">
                <span class="uvDesc">UV Index: </span>
                <span id="uvIndex"><?php echo $uvIndex; ?></span>
            </span>
        </div>
    </div>


    <div id="today">
        <span id="todayLabel"><?php echo $todayLabel; ?></span><?php echo $weatherToday; ?>
    </div>



    <div class="hiAndLow">
        <div>
            <span class="hiLoEtc">Hi:</span>
            <span><?php echo $hiTemp; ?></span>
            <span class="fahrenheit">&#176;F</span>
            <span class="tempTime" id="hiTempTime">&nbsp;</span>
        </div>
        <div>
            <span class="hiLoEtc">Low:</span>
            <?php echo $lowTemp; ?>
            <span class="fahrenheit">&#176;F</span>
            <span class="tempTime" id="loTempTime">&nbsp;</span>
        </div>
    </div>

    <div class="wind">
        <div>
            <span class="hiLoEtc">Wind/Gust:&nbsp;</span>
            <?php echo $windSpeed; ?>/<?php echo $windGust; ?>
            <span class="mph">mph</span>
        </div>


    </div>

</div>


<script>


    let start = null;
    const element = document.getElementById('element');
    const element2 = document.getElementById('element2');

    let sunRise = <?php echo $sunrise; ?>;
    let sunSet = <?php echo $sunset; ?>;

    let current = <?php echo $currentTime; ?>;
    let hiTempTime = <?php echo $hiTime; ?>;
    let loTempTime = <?php echo $lowTime; ?>;
    let timeZone = "<?php echo $timeZone; ?>";

    let uvIndex = <?php echo $uvIndex; ?>;

    let precip = <?php echo $precip; ?>;

    console.log(precip);


    setTimeDisplays(current,hiTempTime,loTempTime);

    setDaylight(sunRise,sunSet,current);

    setUVColor(uvIndex);

    //sets time from php weather data but with better timezone support from Moment.js
    function setTimeDisplays(current, hi, lo) {

        let currentTimeDisplay = moment(current*1000).tz(timeZone).format('HH:mm');
        document.querySelector('#currentTempTime').innerText = currentTimeDisplay;

        let hiTimeDisplay = moment(hi*1000).tz(timeZone).format('HH:mm');
        document.querySelector('#hiTempTime').innerText = hiTimeDisplay;

        let loTimeDisplay = moment(lo*1000).tz(timeZone).format('HH:mm');
        document.querySelector('#loTempTime').innerText = loTimeDisplay;

    }



    function setMoonPhase(elem1, elem2, ln) {

        const dark = document.getElementsByTagName('body')[0].style.backgroundColor;
        //const dark = "transparent";
        console.log(dark);
        //const dark = "#3f4d51";
        const light = "rgba(228,227,168)";
        const darkToLight = "linear-gradient(90deg, "+dark+" 50%, "+light+" 50%)";
        const lightToDark = "linear-gradient(90deg, "+light+" 50%, "+dark+" 50%)";


        if(ln >= 0 && ln < .25){
            console.log('waxing crescent');
            elem1.style.background = darkToLight;
            elem2.style.background = dark;
            const degrees = (ln/.25)*90;
            elem2.style.transform = "rotateY("+degrees.toString()+"deg)";
        }

        else if(ln >= .25 && ln < .5){
            console.log('waxing gibbous');
            elem1.style.background = darkToLight;
            elem2.style.background = light;
            const degrees = ((ln-.25)/.25)*90;
            elem2.style.transform = "rotateY("+(degrees+90).toString()+"deg)";
        }

        else if(ln >= .5 && ln < .75){
            console.log('waning gibbous');
            elem1.style.background = lightToDark;
            elem2.style.background = light;
            const degrees = ((ln-.5)/.25)*90;
            elem2.style.transform = "rotateY("+degrees.toString()+"deg)";
            //elem2.style.dropShadow = "-20px 0px 5px inset "+bgDark;
        }

        else if(ln >= .75 && ln < 1){
            console.log('waning crescent');
            elem1.style.background = lightToDark;
            elem2.style.background = dark;
            const degrees = ((ln-.75)/.25)*90;
            elem2.style.transform = "rotateY("+(degrees+90).toString()+"deg)";
        }

        else{
            elem1.style.background = darkToLight;
            elem2.style.background = dark;
        }
    }

    //function to approximately simulate lightness of sky with the background
    //hence a day/night cycle;  based on distance of current time from midday
    function setDaylight(sunrise,sunset,currentTime) {

        let dayLightProportion = 0;

        //conditional fixing daylight in case of polar day/night
        if (sunrise === false || sunset === false) {
            dayLightProportion = 0.75;
            console.log('polar night');
            }
        else if(sunrise === true || sunset === true){
            dayLightProportion = 0.25;
            console.log('midnight sun');
            }
        else {

            let dayLength = sunset-sunrise;

            let midday = sunrise + dayLength/2;

            //using transit data from php instead of calculating; may be a little more accurate/simpler
            let distFromMidDay = Math.abs(currentTime - midday);

            dayLightProportion = (distFromMidDay / 3600) / 12;

        }

        const brightness = 74-(70*dayLightProportion)+15;

        document.getElementsByTagName('body')[0].style.backgroundColor = "hsl(192, 18%, "+brightness+"%)"

    }

    function setUVColor(uvIndex) {
        //const uv = parseInt(uvIndex, 10);
        const uvProp = uvIndex/10;
        let uvHue = 120 - uvProp*120;
        if(uvHue < 0){
            uvHue = 360 + uvHue;
        }
        //document.getElementById('uvIndex').style.color = "hsl("+uvHue+", 100%, 30%)";
        document.documentElement.style.setProperty('--uvColor', "hsl("+uvHue+", 100%, 42%)");
        if(uvIndex >= 6) {
            document.getElementById('uvIndex').classList.add('flashWarning');
        }
    }


    setMoonPhase(element,element2,<?php echo $moonPhase; ?>);


</script>


</body>


</html>



