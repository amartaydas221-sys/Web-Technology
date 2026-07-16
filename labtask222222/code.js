// Select Elements
const form = document.getElementById("appointmentForm");
const success = document.getElementById("success");

// Register Button
form.addEventListener("submit", function(event){

    event.preventDefault();

    // Get Values
    let fname = document.getElementById("fname").value;
    let lname = document.getElementById("lname").value;
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;
    let department = document.getElementById("department").value;
    let description = document.getElementById("description").value;

    // Gender
    let gender = document.querySelector('input[name="gender"]:checked');

    if(gender != null){
        gender = gender.value;
    }
    else{
        gender = "Not Selected";
    }

    // Preferred Services
    let services = document.querySelectorAll('input[name="service"]:checked');

    let serviceList = "";

    services.forEach(function(service){
        serviceList += service.value + " ";
    });

    if(serviceList == ""){
        serviceList = "None";
    }

    // Show Registered Data
    success.innerHTML =
    "<h3>Registration complete Successfully</h3>" +
    "<p><b>First Name:</b> " + fname + "</p>" +
    "<p><b>Last Name:</b> " + lname + "</p>" +
    "<p><b>Email:</b> " + email + "</p>" +
    "<p><b>Password:</b> " + password + "</p>" +
    "<p><b>Confirm Password:</b> " + confirmPassword + "</p>" +
    "<p><b>Gender:</b> " + gender + "</p>" +
    "<p><b>Preferred Service:</b> " + serviceList + "</p>" +
    "<p><b>Department:</b> " + department + "</p>" +
    "<p><b>Health Description:</b> " + description + "</p>";

});