<?php
	header('Location: https://' . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI] . "public/", true, 302);
    exit();
?>