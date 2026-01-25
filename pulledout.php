<style>
    .preview-wrapper {
    position: relative;
    display: inline-block;
    margin: 6px;
    text-align: center;
}

.preview-img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
}

</style>
<?php 
include_once("dbconnect.php");

$batchnumber = 'PU-26-000001';

$attachmentsQuery = "
    SELECT * 
    FROM tbl_attachments
    WHERE batchnumber = '$batchnumber'
    ORDER BY sequence_no ASC
";
$attachmentsResult = mysqli_query($conn, $attachmentsQuery);


echo '<div class="d-flex flex-wrap gap-2">';
if (mysqli_num_rows($attachmentsResult) > 0) {
    while ($row = mysqli_fetch_assoc($attachmentsResult)) {
        $filePath = htmlspecialchars($row['path']); // sanitized path
        $docType = htmlspecialchars($row['document_type']); // e.g. LOGP or PULLOUT
        $sequence = str_pad($row['sequence_no'], 3, '0', STR_PAD_LEFT);

        echo "
        <div class='preview-wrapper'>
            <img src='{$filePath}' class='preview-img' title='{$docType} {$sequence}'>
            <div class='text-center small mt-1'>{$docType} {$sequence}</div>
        </div>
        ";
    }
} else {
    echo "<p class='text-muted'>No attachments uploaded yet.</p>";
}
echo '</div>';
