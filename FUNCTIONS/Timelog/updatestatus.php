<?php
require_once('../connect.php');
require_once('../../CLASSES/ManualLog.php');

$class = new ManualLog(	
						NULL,	
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						NULL
					);

$extra['pk'] = $_POST['pk'];
$extra['status'] = $_POST['status'];
$extra['employees_pk'] = $_POST['employees_pk'];
$data = $class->update($extra);


header("HTTP/1.0 404 User Not Found");
if($data['status']){
	header("HTTP/1.0 200 OK");
}

header('Content-Type: application/json');
print(json_encode($data));
?> 