<?php require_once 'nav.php'; ?>
<div id="reports"></div>
<script type="text/javascript" src="js/moment-2.11.1.min.js"></script>
<script type="text/javascript" src="js/plotly-1.43.1.min.js"></script>
<?php require_once 'footer.php';
include_once '../endpoints/twilio-client.php';

$actions = ['Volunteer', 'Meetings', 'Just For Today'];
$rows = getMetric()->fetchAll();
$plots = array();
foreach ($rows as $row) {
    $plots[$row['searchType']][] = [
        'x' => $row['timestamp'],
        'y' => $row['counts']
    ];
}
?>
<script type="text/javascript">
    $(function() {
        var datasets = [];
        var plots = <?php echo json_encode($plots, true) ?>;
        var actions = <?php echo json_encode($actions, true) ?>;
        var colors = ['red', 'blue', 'green'];
        for (var a = 0; a < actions.length; a++) {
            var xAgg = [];
            var yAgg = [];
            for (var p = 0; p < plots[a+1].length; p++) {
                xAgg.push(plots[a+1][p].x);
                yAgg.push(plots[a+1][p].y);
            }

            datasets.push({
                type: 'scatter',
                mode: 'lines+markers',
                name: actions[a],
                x: xAgg,
                y: yAgg,
                line: { color: colors[a] }
            })
        }

        Plotly.newPlot("reports", datasets, {
            title: 'Usage Report',
            xaxis: {
                title: 'Day',
                type: 'date'
            },
            yaxis: {
                title: 'Occurrences'
            }
        });
    });
</script>
<div class="container">
    <h3>Volunteer Records</h3>
<table border="1">
    <tr><th>Conference Id</th><th>Recordings</th><th>Duration (in seconds)</th><th>Participant Id</th><th>Phone Number</th></thd><th>Role</th><th>Timestamp</th></tr>
<?php
$rows = getConferences()->fetchAll();
$conferences = [];
foreach ($rows as $row) {
    $participant = $twilioClient->calls($row['callsid'])->fetch();
    $role = $participant->direction == 'outbound-api' ? "volunteer" : "caller";
    $phone_number = $role == "volunteer" ? $participant->to : $participant->from;

    if (isset($lastconferencesid) && $row['conferencesid'] == $lastconferencesid) {
        echo "<tr><td colspan='3'></td><td>" . $row['callsid'] . "</td><td>$phone_number</td><td>$role</td><td>" . $row['timestamp'] . "</td></tr>";
    } else {
        $conference = $twilioClient->conferences($row['conferencesid'])->fetch();
        $recordings = $participant->recordings->read();
        $recordingUri = count($recordings) > 0 ? "<a href='https://api.twilio.com" . str_replace(".json", ".mp3", $recordings[0]->uri) . "' target='_blank'>Play</a>" : "";
        echo "<tr><td>" . $row['conferencesid'] . "</td><td>$recordingUri</a></td><td>" . ($conference->dateCreated)->diff($conference->dateUpdated)->s . "</td><td>" . $row['callsid'] . "</td><td>$phone_number</td><td>$role</td><td>" . $row['timestamp'] . "</td></tr>";
    }
    $lastconferencesid = $row['conferencesid'];
}
?>
</table>
</div>
