const form = document.getElementById("appointmentForm");
const success = document.getElementById("success");

form.addEventListener("submit", function (event) {

    event.preventDefault();

    let fname = document.getElementById("fname").value;
    let lname = document.getElementById("lname").value;
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;
    let confirmPassword = document.getElementById("confirmPassword").value;
    let department = document.getElementById("department").value;
    let description = document.getElementById("description").value;

    let genders = document.getElementsByName("gender");
    let gender = "Not Selected";

    if (genders[0].checked) {
        gender = genders[0].value;
    }
    else if (genders[1].checked) {
        gender = genders[1].value;
    }
    else {
        gender = "Not Selected";
    }

    let services = document.getElementsByName("service");
    let serviceList = "";

    if (services[0].checked) {
        serviceList += services[0].value + " ";
    }

    if (services[1].checked) {
        serviceList += services[1].value + " ";
    }

    if (services[2].checked) {
        serviceList += services[2].value + " ";
    }

    if (serviceList == "") {
        serviceList = "None";
    }

    success.innerHTML =
        "<h3>Registration Complete Successfully</h3>" +
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