<?php

session_unset();
session_destroy();

 echo '<div class="container mt-5">
                <div class="alert text-center">
                    <h3 class="page-title"> You Have Successfully logged out. </h3>
                    <a href="index.php" class="btn btn-outline-light mt-3">Return Home</a>
                </div>
              </div>';
        include 'footer.php';
exit();
?>

