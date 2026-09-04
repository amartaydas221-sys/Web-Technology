<!DOCTYPE html>
<html>
<head>
        <title>Online Job Application</title>
</head>
<body>

                 <h2>Online Job Application Form</h2>

<form action="process.php" method="POST" enctype="multipart/form-data">

    <label>Applicant ID:</label>
       
       <input type="text" name="applicant_id">
    <br><br>

    <label>Full Name:</label>
      
    <input type="text" name="name">
    <br><br>

    <label>Email:</label>

          <input type="text" name="email">
    <br><br>

    
    <label>Phone Number:</label>
     
    <input type="text" name="phone">
    <br><br>

    
    <label>Password:</label>

    <input type="password" name="password">
    <br><br>

           <label>Gender:</label>

 <input type="radio" name="gender" value="Male">
    Male

     <input type="radio" name="gender" value="Female">
    Female

    <br><br>

    <label>Job Position:</label>

    <select name="position">

            <option value="">Select Job Position</option>

 <option value="Software Developer">
            Software Developer
        </option>

 <option value="Web Developer">
            Web Developer
    </option>

    <option value="Database Administrator">
            Database Administrator
        </option>

    <option value="Network Engineer">
            Network Engineer
        </option>

    </select>

    <br><br>

    <label>Educational Qualification:</label>

      <input type="text" name="qualification">
    <br><br>

    <label>Address:</label>
    <br>

    <textarea name="address" rows="5" cols="40"></textarea>

    <br><br>

    <label>Upload CV (PDF/DOC/DOCX):</label>

    <input type="file" name="cv">

    <br><br>

    <input type="submit" value="Apply">

</form>

</body>
</html>
