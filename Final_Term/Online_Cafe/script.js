const form = document.getElementById("myForm");
const resultBox = document.getElementById("resultBox");

form.addEventListener("submit", function (event) {

      event.preventDefault();

    clearErrors();

 let fullName = document.getElementById("fullName");
 let email = document.getElementById("email");
let phone = document.getElementById("phone");
let studentId = document.getElementById("studentId");
 let department = document.getElementById("department");
let quantity = document.getElementById("quantity");
 let instructions = document.getElementById("instructions");

 let gender = document.querySelector('input[name="gender"]:checked');
 let foodItems = document.querySelectorAll('input[name="food"]:checked');
 
         


let valid = true;


 if (fullName.value.trim() == "") {

 showError(fullName, "fullNameError", "Name cannot be empty.");
valid = false;

    }

else {

        showSuccess(fullName);
}

if (email.value.trim() == "") {

        showError(email, "emailError", "Email cannot be empty.");
        valid = false;

 }
 else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {

  showError(email, "emailError", "Invalid email format.");
 valid = false;

}
 else {

  showSuccess(email);

 }

 if (phone.value.trim() == "") {

 showError(phone, "phoneError", "Phone number cannot be empty.");
valid = false;

}
else {

 showSuccess(phone);

}

 if (studentId.value.trim() == "") {

  showError(studentId, "studentIdError", "Student ID cannot be empty.");
 valid = false;

 }
else {

  showSuccess(studentId);

}

if (gender == null) {

document.getElementById("genderError").innerHTML ="Please select your gender.";
        valid = false;

 }

 if (department.value == "") {

  showError(department, "departmentError", "Please select a department.");
 valid = false;
 }
 else {

 showSuccess(department);

 }

 if (foodItems.length == 0) {

 document.getElementById("foodError").innerHTML ="Please select at least one food item.";

        valid = false;

  }

if (quantity.value.trim() == "" || Number(quantity.value) <= 0) {
        showError(quantity, "quantityError", "Quantity must be greater than 0.");
        valid = false;

 }
 else {
        showSuccess(quantity);

 }

 if (!valid) {

      return;
 }

 let itemsHtml = "";
let subtotal = 0;

foodItems.forEach(function (item) {

 let price = Number(item.getAttribute("data-price"));
subtotal = subtotal + price;

itemsHtml = itemsHtml + "<li>" + item.value + " - $" + price + "</li>";

 });

 let qty = Number(quantity.value);
 let totalBill = subtotal * qty;

 document.getElementById("resultName").innerHTML =
     "<strong>Customer Name:</strong> " + fullName.value.trim();

 document.getElementById("resultStudentId").innerHTML =
     "<strong>Student ID:</strong> " + studentId.value.trim();

 document.getElementById("resultDepartment").innerHTML =
     "<strong>Department:</strong> " + department.value;

 document.getElementById("resultItemsLabel").innerHTML =
    "<strong>Selected Items:</strong>";

document.getElementById("resultItems").innerHTML = itemsHtml;

 document.getElementById("resultQuantity").innerHTML =
    "<strong>Quantity:</strong> " + qty;

 document.getElementById("resultTotal").innerHTML =
     "<strong>Total Bill:</strong> $" + totalBill;

document.getElementById("confirmationMessage").innerHTML =
  "Order placed successfully! " + fullName.value.trim() +
      ", your total bill is $" + totalBill + ".";

    resultBox.style.display = "block";


});



function showError(input, errorId, message) {

    input.classList.add("errorBorder");
    input.classList.remove("successBorder");

    document.getElementById(errorId).innerHTML = message;

}

function showSuccess(input) {

    input.classList.remove("errorBorder");
    input.classList.add("successBorder");

}

function clearErrors() {

    let errors = document.querySelectorAll(".error");

    errors.forEach(function (item) {

        item.innerHTML = "";


    });

    

    let fields = document.querySelectorAll("input, select, textarea");

    fields.forEach(function (field) {

        field.classList.remove("errorBorder");
        field.classList.remove("successBorder");

    });

}
