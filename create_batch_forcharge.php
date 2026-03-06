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
        header("Location: for_charging.php");
        exit();
    }

    $items = $_POST['items'];
    $username = $_SESSION['fname'];

    $conn->begin_transaction();

    try {

        $groups = [];

        /* STEP 1: GET CATEGORY + LOCATION + VENDOR */

        foreach ($items as $item) {

            list($f325, $mdc) = explode('|', $item);

            $stmtInfo = $conn->prepare("
                SELECT category, location, vendorcode
                FROM dbraw
                WHERE f325number = ? AND mdccode = ?
            ");

            $stmtInfo->bind_param("ii", $f325, $mdc);
            $stmtInfo->execute();
            $resultInfo = $stmtInfo->get_result();

            if ($row = $resultInfo->fetch_assoc()) {

                $key = $row['category'] . '|' . $row['location'];

                $groups[$key]['category'] = $row['category'];
                $groups[$key]['location'] = $row['location'];
                $groups[$key]['vendorcode'] = $row['vendorcode'];

                $groups[$key]['rows'][] = [
                    'f325' => $f325,
                    'mdc'  => $mdc
                ];
            }

            $stmtInfo->close();
        }

        /* STEP 2: PROCESS EACH GROUP */

        foreach ($groups as $group) {

            $rows = $group['rows'];
            $category = $group['category'];
            $location = $group['location'];
            $vendorcode = $group['vendorcode'];

            $year = date('y');
            $prefix = "CH-$year-";

            $stmtLast = $conn->prepare("
                SELECT batchnumber_forcharging
                FROM dbraw
                WHERE batchnumber_forcharging LIKE ?
                ORDER BY batchnumber_forcharging DESC
                LIMIT 1
            ");

            $like = $prefix . '%';
            $stmtLast->bind_param("s", $like);
            $stmtLast->execute();
            $result = $stmtLast->get_result();

            $nextNumber = 1;

            if ($rowLast = $result->fetch_assoc()) {
                $lastNumber = intval(substr($rowLast['batchnumber_forcharging'], -6));
                $nextNumber = $lastNumber + 1;
            }

            $stmtLast->close();

            $sequence = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            $batchNumber = $prefix . $sequence;

            /* UPDATE ROWS */

            foreach ($rows as $r) {

                $stmtUpdate = $conn->prepare("
                    UPDATE dbraw
                    SET batchnumber_forcharging = ?, statusout = 'FOR CHARGING'
                    WHERE f325number = ? AND mdccode = ?
                ");

                $stmtUpdate->bind_param("sii", $batchNumber, $r['f325'], $r['mdc']);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                /* INSERT HISTORY */

                $dateprocessed = date("Y-m-d");
                $timeprocessed = date("H:i:s");
                $processed = "Created Charging Batch: $batchNumber";

                $stmtHistory = $conn->prepare("
                    INSERT INTO dbhistory
                    (processnumber, name, processed, dateprocessed, timeprocessed)
                    VALUES (?,?,?,?,?)
                ");

                $stmtHistory->bind_param(
                    "issss",
                    $r['f325'],
                    $username,
                    $processed,
                    $dateprocessed,
                    $timeprocessed
                );

                $stmtHistory->execute();
                $stmtHistory->close();
            }

            /* INSERT INTO DBPULLOUT */

            $status = "FOR CHARGING";
            $dateprocessed = date("Y-m-d");

            $stmtPullout = $conn->prepare("
                INSERT INTO dbpullout
                (reference, principal, company, preparedby, dateprocessed, location, status)
                VALUES (?,?,?,?,?,?,?)
            ");

            $stmtPullout->bind_param(
                "sssssss",
                $batchNumber,
                $category,
                $vendorcode,
                $username,
                $dateprocessed,
                $location,
                $status
            );

            $stmtPullout->execute();
            $stmtPullout->close();
        }

        $conn->commit();

        $_SESSION['success'] = "Charging batches created successfully!";
        header("Location: for_charging.php?status=succ");
        exit();

    } catch (Exception $e) {

        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: for_charging.php");
        exit();
    }
}
?>
```
