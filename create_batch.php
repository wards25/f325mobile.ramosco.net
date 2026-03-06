```php
<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['create_batch'])) {

    if (!isset($_POST['items']) || !is_array($_POST['items']) || count($_POST['items']) === 0) {
        $_SESSION['error'] = "Please select at least one row.";
        $redirectUrl = "for_pullout_details.php";
        if (isset($_GET['category'])) $redirectUrl .= "?category=" . urlencode($_GET['category']);
        if (isset($_GET['company'])) $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . "company=" . urlencode($_GET['company']);
        header("Location: $redirectUrl");
        exit();
    }

    $items = $_POST['items'];
    $userId = $_SESSION['id'];
    $username = $_SESSION['fname'];

    $f325numbers = [];
    $mdccodes = [];

    foreach ($items as $item) {
        $parts = explode('|', $item);
        if (count($parts) === 2) {
            $f325numbers[] = intval($parts[0]);
            $mdccodes[] = intval($parts[1]);
        }
    }

    if (count($f325numbers) === 0 || count($mdccodes) === 0) {
        $_SESSION['error'] = "Invalid item selection.";
        header("Location: for_pullout_details.php");
        exit();
    }

    /* CHECK IF ITEMS BELONG TO SAME LOCATION */

    $fPlaceholders = implode(',', array_fill(0, count($f325numbers), '?'));
    $mPlaceholders = implode(',', array_fill(0, count($mdccodes), '?'));

    $sqlCheck = "
        SELECT DISTINCT location
        FROM dbraw
        WHERE f325number IN ($fPlaceholders)
        AND mdccode IN ($mPlaceholders)
    ";

    $stmtCheck = $conn->prepare($sqlCheck);

    $typesCheck = str_repeat('i', count($f325numbers) + count($mdccodes));
    $paramsCheck = array_merge($f325numbers, $mdccodes);

    $tmpCheck = [];
    foreach ($paramsCheck as $key => $value) {
        $tmpCheck[$key] = &$paramsCheck[$key];
    }

    array_unshift($tmpCheck, $typesCheck);
    call_user_func_array([$stmtCheck, 'bind_param'], $tmpCheck);

    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 1) {
        header("Location: for_pullout.php?status=error");
        exit();
    }

    $stmtCheck->close();

    /* START TRANSACTION */

    $conn->begin_transaction();

    try {

        $yearMonth = date('y');
        $prefix = "PU-$yearMonth-";

        $sqlLast = "
            SELECT batchnumber_forpullout
            FROM dbraw
            WHERE batchnumber_forpullout LIKE ?
            ORDER BY batchnumber_forpullout DESC
            LIMIT 1
        ";

        $stmtLast = $conn->prepare($sqlLast);

        $like = $prefix . '%';
        $stmtLast->bind_param('s', $like);
        $stmtLast->execute();

        $result = $stmtLast->get_result();

        $nextNumber = 1;

        if ($row = $result->fetch_assoc()) {
            $lastNumber = intval(substr($row['batchnumber_forpullout'], -6));
            $nextNumber = $lastNumber + 1;
        }

        $stmtLast->close();

        $sequence = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        $batchNumber = $prefix . $sequence;

        /* UPDATE SELECTED ROWS */

        $sql = "
            UPDATE dbraw
            SET batchnumber_forpullout = ?
            WHERE f325number IN ($fPlaceholders)
            AND mdccode IN ($mPlaceholders)
        ";

        $stmt = $conn->prepare($sql);

        $types = 's' . str_repeat('i', count($f325numbers) + count($mdccodes));
        $params = array_merge([$batchNumber], $f325numbers, $mdccodes);

        $tmp = [];
        foreach ($params as $key => $value) {
            $tmp[$key] = &$params[$key];
        }

        array_unshift($tmp, $types);
        call_user_func_array([$stmt, 'bind_param'], $tmp);

        $stmt->execute();
        $stmt->close();

        /* GET DATA FOR DBPULLOUT */

        $sqlInfo = "
            SELECT category, vendorcode, location
            FROM dbraw
            WHERE f325number IN ($fPlaceholders)
            AND mdccode IN ($mPlaceholders)
            LIMIT 1
        ";

        $stmtInfo = $conn->prepare($sqlInfo);

        $typesInfo = str_repeat('i', count($f325numbers) + count($mdccodes));
        $paramsInfo = array_merge($f325numbers, $mdccodes);

        $tmpInfo = [];
        foreach ($paramsInfo as $key => $value) {
            $tmpInfo[$key] = &$paramsInfo[$key];
        }

        array_unshift($tmpInfo, $typesInfo);
        call_user_func_array([$stmtInfo, 'bind_param'], $tmpInfo);

        $stmtInfo->execute();
        $resultInfo = $stmtInfo->get_result();
        $data = $resultInfo->fetch_assoc();

        $principal = $data['category'];
        $company   = $data['vendorcode'];
        $location  = $data['location'];

        $stmtInfo->close();

        /* INSERT INTO DBPULLOUT */

        $status = "FOR PULL-OUT";
        $dateprocessed = date("Y-m-d");

        $stmtPullout = $conn->prepare("
            INSERT INTO dbpullout
            (reference, principal, company, preparedby, dateprocessed, location, status)
            VALUES (?,?,?,?,?,?,?)
        ");

        $stmtPullout->bind_param(
            "sssssss",
            $batchNumber,
            $principal,
            $company,
            $username,
            $dateprocessed,
            $location,
            $status
        );

        $stmtPullout->execute();
        $stmtPullout->close();

        /* INSERT HISTORY */

        $timeprocessed = date("H:i:s");
        $processed = "Created Pullout Batch: $batchNumber";

        foreach ($f325numbers as $f325number) {

            mysqli_query($conn,"INSERT INTO dbhistory
            (processnumber,name,processed,dateprocessed,timeprocessed)
            VALUES
            ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");
        }

        $conn->commit();

        $_SESSION['success'] = "Batch created successfully! Batch Number: $batchNumber";

        header("Location: for_pullout.php?status=succ");
        exit();

    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['error'] = "Error: " . $e->getMessage();

        header("Location: for_pullout_details.php");
        exit();
    }
}
?>
```
