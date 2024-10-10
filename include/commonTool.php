<?php

function messageBox($text){
	if(!empty($text)){
		echo "<script>alert('$text');</script>";
	}
}