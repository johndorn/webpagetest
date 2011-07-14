<?php
if( !defined('BARE_UI') )
    define('BARE_UI', true);
include 'common.inc';

// load the secret key (if there is one)
$secret = '';
$keys = parse_ini_file('./settings/keys.ini', true);
if( $keys && isset($keys['server']) && isset($keys['server']['secret']) )
  $secret = trim($keys['server']['secret']);
    
$connectivity = parse_ini_file('./settings/connectivity.ini', true);
$locations = LoadLocations();
$loc = ParseLocations($locations);

$page_keywords = array('Comparison','Webpagetest','Website Speed Test','Page Speed');
$page_description = "Comparison Test$testLabel.";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>WebPagetest - Comparison Test</title>
        <?php $gaTemplate = 'PSS'; include ('head.inc'); ?>
        <style type="text/css">
            #nav_bkg {display:none;}
        </style>
    </head>
    <body>
        <div class="page">
            <?php
            include 'header.inc';
            
            if( $supportsAuth && !($admin || strpos($_COOKIE['google_email'], '@google.com') !== false) )
            {
                echo '<h2 class="centered">Restricted Access<br><span class="small">If you are a Googler, please lick on the "Login with Google" link at the top of the page to login through OAuth with your @google.com account</span></h2>';
            }
            else
            {
            ?>
            <form name="urlEntry" action="/runtest.php" method="POST" enctype="multipart/form-data" onsubmit="return PreparePSSTest(this)">
            
            <input type="hidden" name="private" value="1">
            <input type="hidden" name="view" value="simple">
            <input type="hidden" name="label" value="">
            <input type="hidden" name="video" value="1">
            <input type="hidden" name="priority" value="0">
            <input type="hidden" name="mv" value="1">
            <input type="hidden" name="web10" value="1">
            <?php
            if( strlen($_GET['origin']) )
            {
                echo "<input type=\"hidden\" name=\"script\" value=\"setDnsName&#09;%HOST%&#09;{$_GET['origin']}&#10;navigate&#09;%URL%\">\n";
                echo "<input type=\"hidden\" name=\"runs\" value=\"5\">\n";
            }
            else
            {
                echo "<input type=\"hidden\" name=\"script\" value=\"setDnsName&#09;%HOST%&#09;ghs.google.com&#10;overrideHost&#09;%HOST%&#09;wpt.pssdemos.com&#10;navigate&#09;%URL%\">\n";
                echo "<input type=\"hidden\" name=\"runs\" value=\"8\">\n";
                echo "<input type=\"hidden\" name=\"discard\" value=\"3\">\n";
            }
            ?>
            <input type="hidden" name="bulkurls" value="">
            <input type="hidden" name="vo" value="<?php echo $owner;?>">
            <?php
            if( strlen($secret) ){
              $hashStr = $secret;
              $hashStr .= $_SERVER['HTTP_USER_AGENT'];
              $hashStr .= $owner;
              
              $now = date('c');
              echo "<input type=\"hidden\" name=\"vd\" value=\"$now\">\n";
              $hashStr .= $now;
              
              $hmac = sha1($hashStr);
              echo "<input type=\"hidden\" name=\"vh\" value=\"$hmac\">\n";
              
              if( strlen($_GET['origin']) )
                echo '<h2 class="cufon-dincond_black"><small>Measure your original site performance for a site optimized by <a href="http://code.google.com/speed/pss">Page Speed Service</a></small></h2>';
              else
                echo '<h2 class="cufon-dincond_black"><small>Measure your site performance when optimized by <a href="http://code.google.com/speed/pss">Page Speed Service</a></small></h2>';
            }
            ?>

            <div id="test_box-container">
                <div id="analytical-review" class="test_box">
                    <ul class="input_fields">
                        <li><input type="text" name="testurl" id="testurl" value="Enter a Website URL" class="text large" onfocus="if (this.value == this.defaultValue) {this.value = '';}" onblur="if (this.value == '') {this.value = this.defaultValue;}"></li>
                        <li>
                            <label for="location">Test From</label>
                            <select name="where" id="location">
                                <?php
                                foreach($loc['locations'] as &$location)
                                {
                                    $selected = '';
                                    if( $location['checked'] )
                                        $selected = 'selected';
                                        
                                    echo "<option value=\"{$location['name']}\" $selected>{$location['label']}</option>";
                                }
                                ?>
                            </select>
                            <?php if( $settings['map'] ) { ?>
                            <input id="change-location-btn" type=button onclick="SelectLocation();" value="Select from Map">
                            <?php } ?>
                            <span class="cleared"></span>
                        </li>
                        <li>
                            <label for="browser">Browser</label>
                            <select name="browser" id="browser">
                                <?php
                                foreach( $loc['browsers'] as $key => &$browser )
                                {
                                    $selected = '';
                                    if( $browser['selected'] )
                                        $selected = 'selected';
                                    echo "<option value=\"{$browser['key']}\" $selected>{$browser['label']}</option>\n";
                                }
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="location">Connection</label>
                            <select name="location" id="connection">
                                <?php
                                foreach( $loc['connections'] as $key => &$connection )
                                {
                                    $selected = '';
                                    if( $connection['selected'] )
                                        $selected = 'selected';
                                    echo "<option value=\"{$connection['key']}\" $selected>{$connection['label']}</option>\n";
                                }
                                ?>
                            </select>
                            <br>
                            <table class="configuration hidden" id="bwTable">
                                <tr>
                                    <th>BW Down</th>
                                    <th>BW Up</th>
                                    <th>Latency</th>
                                    <th>Packet Loss</th>
                                </tr>
                                <tr>
                                    <?php
                                        echo '<td class="value"><input id="bwDown" type="text" name="bwDown" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['down'] . '"> Kbps</td>';
                                        echo '<td class="value"><input id="bwUp" type="text" name="bwUp" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['up'] . '"> Kbps</td>';
                                        echo '<td class="value"><input id="latency" type="text" name="latency" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['latency'] . '"> ms</td>';
                                        echo '<td class="value"><input id="plr" type="text" name="plr" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['plr'] . '"> %</td>';
                                    ?>
                                </tr>
                            </table>
                        </li>
                        <li>
                            <label for="wait">Expected Wait</label>
                            <span id="wait"></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div id="start_test-container">
                <p><input id="start_test-button" type="submit" name="submit" value="" class="start_test"></p>
                <p><a href="/pss_samples.php">View Sample Tests</a></p>
            </div>
            <div class="cleared"><br></div>

            <div id="location-dialog" style="display:none;">
                <h3>Select Test Location</h3>
                <div id="map">
                </div>
                <p>
                    <select id="location2">
                        <?php
                        foreach($loc['locations'] as &$location)
                        {
                            $selected = '';
                            if( $location['checked'] )
                                $selected = 'SELECTED';
                                
                            echo "<option value=\"{$location['name']}\" $selected>{$location['label']}</option>";
                        }
                        ?>
                    </select>
                    <input id="location-ok" type=button class="simplemodal-close" value="OK">
                </p>
            </div>
            
            </form>

            <?php
            }
            include('footer.inc'); 
            ?>
        </div>

        <script type="text/javascript">
        <?php 
            echo "var maxRuns = {$settings['maxruns']};\n";
            echo "var locations = " . json_encode($locations) . ";\n";
            echo "var connectivity = " . json_encode($connectivity) . ";\n";

            $sponsors = parse_ini_file('./settings/sponsors.ini', true);
            if( strlen($GLOBALS['cdnPath']) )
            {
                foreach( $sponsors as &$sponsor )
                {
                    if( isset($sponsor['logo']) )
                        $sponsor['logo'] = $GLOBALS['cdnPath'] . $sponsor['logo'];
                    if( isset($sponsor['logo_big']) )
                        $sponsor['logo_big'] = $GLOBALS['cdnPath'] . $sponsor['logo_big'];
                }
            }
            echo "var sponsors = " . json_encode($sponsors) . ";\n";
           
        ?>
        </script>
        <script type="text/javascript" src="<?php echo $GLOBALS['cdnPath']; ?>/js/test.js?v=<?php echo VER_JS_TEST;?>"></script> 
        <script type="text/javascript">
            function PreparePSSTest(form)
            {
                var url = form.testurl.value;
                if( url == "" || url == "Enter a Website URL" )
                {
                    alert( "Please enter an URL to test." );
                    form.url.focus();
                    return false
                }
                
                form.label.value = 'Page Speed Service Comparison for ' + url;
                
                <?php
                // build the psuedo batch-url list
                if( strlen($_GET['origin']) )
                    echo 'var batch = "Original=" + url + "\nOptimized=" + url + " noscript";' . "\n";
                else
                    echo 'var batch = "Original=" + url + " noscript\nOptimized=" + url;' . "\n";
                ?>
                
                form.bulkurls.value=batch;
                
                return true;
            }
        </script>
    </body>
</html>


<?php
/**
* Load the location information
* 
*/
function LoadLocations()
{
    $locations = parse_ini_file('./settings/locations.ini', true);
    FilterLocations( $locations, 'pss', array('Chrome', 'dynaTrace') );
    
    // strip out any sensitive information
    foreach( $locations as $index => &$loc )
    {
        if( isset($loc['browser']) )
        {
            GetPendingTests($index, $count, $avgTime);
            if( !$avgTime )
                $avgTime = 30;  // default to 30 seconds if we don't have any history
            $loc['backlog'] = $count;
            $loc['avgTime'] = $avgTime;
            $loc['testers'] = GetTesterCount($index);
            $loc['wait'] = -1;
            if( $loc['testers'] )
            {
                $testCount = 26;
                if( $loc['testers'] > 1 )
                    $testCount = 16;
                $loc['wait'] = ceil((($testCount + ($count / $loc['testers'])) * $avgTime) / 60);
            }
        }
        
        unset( $loc['localDir'] );
        unset( $loc['key'] );
        unset( $loc['remoteDir'] );
    }
    
    return $locations;
}

?>