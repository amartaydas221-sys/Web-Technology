<?php

   session_start();

  $errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$applicant_id = $_POST["applicant_id"];
  $name = $_POST["name"];
 $email = $_POST["email"];
  $phone = $_POST["phone"];
 $password = $_POST["password"];
 $gender = $_POST["gender"] ?? "";
 $position = $_POST["position"];
 $qualification = $_POST["qualification"];
$address = $_POST["address"];

if (empty($applicant_id)) {
        $errors[] = "Applicant ID is required.";
 }

 if (empty($name)) {
        $errors[] = "Name is required.";
    }

 if (empty($email)) {
        $errors[] = "Email is required.";
} 
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

if (empty($phone)) {
        $errors[] = "Phone number is required.";
 } 
 elseif (!preg_match("/^[0-9]{11}$/", $phone)) {
        $errors[] = "Phone number must contain 11 digits.";
}

if (empty($password)) {
        $errors[] = "Password is required.";
} 
elseif (strlen($password) < 6) {
        $errors[] = "Password must contain at least 6 characters.";
 }

 if (empty($gender)) {
        $errors[] = "Please select your gender.";
}

if (empty($position)) {
        $errors[] = "Please select a job position.";
 }

 if (empty($qualification)) {
        $errors[] = "Qualification is required.";
}

 if (empty($address)) {
        $errors[] = "Address is required.";
 }

 if (!isset($_FILES["cv"]) || $_FILES["cv"]["error"] != 0) {

        $errors[] = "Please upload your CV.";

} 
else {

     $file_name = $_FILES["cv"]["name"];
   $file_size = $_FILES["cv"]["size"];
   $file_tmp = $_FILES["cv"]["tmp_name"];

  $allowed_extensions = ["pdf", "doc", "docx"];

  $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

 if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = "Only PDF, DOC and DOCX files are allowed.";
        }

  if ($file_size > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2 MB.";
        }
    }

 if (count($errors) == 0) {

        $upload_folder = "uploads/";

        $new_file_name = time() . "_" . basename($file_name);

        $file_path = $upload_folder . $new_file_name;

        move_uploaded_file($file_tmp, $file_path);

    
    $_SESSION["email"] = $email;
 $_SESSION["phone"] = $phone;
  $_SESSION["gender"] = $gender;
    $_SESSION["position"] = $position;
 $_SESSION["qualification"] = $qualification;
      $_SESSION["address"] = $address;

     header(
     "Location: result.php?id=" .
          urlencode($applicant_id) .
            "&name=" .
            urlencode($name) .
            "&cv=" .
            urlencode($new_file_name)
        );

 exit();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Application Result</title>
</head>
<body>

<h2>Application Result</h2>

<?php

if (count($errors) > 0) {

    echo "<h3>Application Failed!</h3>";

    foreach ($errors as $error) {

        echo "<p>$error</p>";

    }

    echo '<a href="index.php">Go Back</a>';

} else {

    echo "<p>Application submitted successfully.</p>";

}

?>

</body>
</html>
