<?php
require_once('../connect.php');
require_once('../../CLASSES/LeaveTypes.php');

$details = array(
					"regularization" => $_POST['regularization'],
					"staggered" => $_POST['staggered']
				);

$class = new LeaveTypes(
			                NULL,
			                $_POST['name'],
			                $_POST['code'],
			                $_POST['days'],
			                $details,
			                NULL
						);

$data = $class-> add();

header("HTTP/1.0 500 Internal Server Error");
if($data['status']==true){
       header("HTTP/1.0 200 OK");
}                  

header('Content-Type: application/json');
print(json_encode($data));

?>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 