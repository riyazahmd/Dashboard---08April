<?php
if (class_exists('Firebase\JWT\JWT')) {
    echo "Firebase JWT library is available.";
} else {
    echo "Firebase JWT library is not available.";
}
?>
