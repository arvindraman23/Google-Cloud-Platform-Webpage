<!-- 2238-CSE-5335-001-WEB DATA MANAGEMENT -->
<!-- ARVIND RAMAN -->
<!-- 1002050501 -->

<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

// Put your MavID here
$mavid = 'axr0501';

// Authenticate using a keyfile path
$storage = new StorageClient([
    'keyFilePath' => 'application_default_credentials.json',
    'suppressKeyFileNotice' => true
]);

$bucket = $storage->bucket("cse5335_$mavid");

// Function to fetch and refill the image list
function refreshImageList() {
    global $bucket;
    $objects = $bucket->objects();
    $imageList = [];
    foreach ($objects as $object) {
        $imageName = $object->name();
        $imageList[] = $imageName;
    }
    return $imageList;
}

function removeFromArray(&$array, $item) {
    if (($key = array_search($item, $array)) !== false) {
        unset($array[$key]);
    }
}

// To delete the image from Cloud
if (isset($_GET['delete'])) {
    $imageToDelete = $_GET['delete'];
    $imageItem = $bucket->object($imageToDelete);
    if ($imageItem->exists()) {
        $imageItem->delete();
        $message = "Image '$imageToDelete' has been deleted.";
        $imageList = refreshImageList();
        removeFromArray($imageList, $imageToDelete);
    } else {
        $message = "Image '$imageToDelete' does not exist.";
    }
}

// To upload a file to Cloud
if (isset($_FILES['userfile']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageItem = $_FILES['userfile'];
    $imageName = $imageItem['name'];
    $tempFilePath = $imageItem['tmp_name'];
    if ($imageItem['error'] === UPLOAD_ERR_OK) {
        $object = $bucket->upload(
            fopen($tempFilePath, 'r'),
            ['name' => $imageName]
        );
        $message = "Image '$imageName' has been uploaded to Google Cloud Storage";
    } else {
        $message = "Error uploading the image.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Google Cloud Storage Photo Album</title>
    <script>
        function refreshImageList() {
            var imageListHtml = document.getElementById('imageList');
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'album.php?refreshImageList=true', true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var imageList = JSON.parse(xhr.responseText);
                    for ($i = 0; $i < count($imageList); $i++) {
                        $index = $i+1;
                        echo "<p>{$index}) <a href=\"album.php?display=$imageList[$i]\">$imageList[$i]</a>";
                        echo " | <a href=\"album.php?delete=$imageList[$i]\">Delete</a></p>";
                    }
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <h1>Google Cloud Storage Photo Album</h1>
    <!-- Upload Section -->
    <h2>Image Upload</h2>
    <form action="album.php" method="post" enctype="multipart/form-data">
        Browse Image to Upload:
        <input type="file" name="userfile">
        <input type="submit" value="Upload Image" name="submit">
    </form>
    <hr>
    <!-- List Section -->
    <h2>Images List</h2>
    <div id="imageList">
        <?php
        // Display existing images
        $imageList = refreshImageList();
        for ($i = 0; $i < count($imageList); $i++) {
            $index = $i+1;
            echo "<p>{$index}) <a href=\"album.php?display=$imageList[$i]\">$imageList[$i]</a>";
            echo " | <a href=\"album.php?delete=$imageList[$i]\">Delete</a></p>";
        }
        ?>
    </div>
    <hr>
    <?php
    if (isset($_GET['refreshImageList']) && $_GET['refreshImageList'] === 'true') {
        echo "<script>refreshImageList();</script>";
    }
    // View Section
    if (isset($_GET['display'])) {
        $imageToDisplay = $_GET['display'];
        $object = $bucket->object($imageToDisplay);
        $imagePath = 'tmp/' . $imageToDisplay;
        $object->downloadToFile($imagePath);
        echo "<h2>Image View</h2>";
        echo "<img src=\"$imagePath\"/>";
    }
    // To display info messages
    if (!empty($message)) {
        echo '<p>' . $message . '</p>';
    }
    ?>
</body>
</html>
