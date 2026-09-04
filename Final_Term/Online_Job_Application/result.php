<?php

  session_start();


   $id = $_GET["id"] ?? "";
  $name = $_GET["name"] ?? "";
$cv = $_GET["cv"] ?? "";


  $request_id = $_REQUEST["id"] ?? "";
   $request_name = $_REQUEST["name"] ?? "";

$email = $_SESSION["email"] ?? "";
   $phone = $_SESSION["phone"] ?? "";
$gender = $_SESSION["gender"] ?? "";
  $position = $_SESSION["position"] ?? "";
 $qualification = $_SESSION["qualification"] ?? "";
   $address = $_SESSION["address"] ?? "";

?>

 <!DOCTYPE html>
 <html>
<head>
             <title>Application Successful</title>
</head>

<body>

<pre>
  =================================
    APPLICATION SUCCESSFUL
  =================================
  Applicant ID: <?php echo htmlspecialchars($id); ?>

 Name: <?php echo htmlspecialchars($name); ?>

  Email: <?php echo htmlspecialchars($email); ?>

Phone: <?php echo htmlspecialchars($phone); ?>

  Gender: <?php echo htmlspecialchars($gender); ?>

 Job Position: <?php echo htmlspecialchars($position); ?>

  Qualification: <?php echo htmlspecialchars($qualification); ?>

  Address: <?php echo htmlspecialchars($address); ?>

  Uploaded CV: <?php echo htmlspecialchars($cv); ?>
<br><br>
 
 Application submitted successfully.
</pre>

  <a href="index.php">Apply Again</a>

  </body>
 </html>
