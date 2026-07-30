const form = document.getElementById("orderForm");
const output = document.getElementById("output");

form.addEventListener("submit", function(event){

    event.preventDefault();

    document.getElementById("nameError").innerHTML = "";
    document.getElementById("emailError").innerHTML = "";
    document.getElementById("phoneError").innerHTML = "";
    document.getElementById("studentError").innerHTML = "";
    document.getElementById("genderError").innerHTML = "";
    document.getElementById("departmentError").innerHTML = "";
    document.getElementById("foodError").innerHTML = "";
    document.getElementById("quantityError").innerHTML = "";

    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let phone = document.getElementById("phone").value.trim();
    let studentid = document.getElementById("studentid").value.trim();
    let department = document.getElementById("department").value;
    let quantity = Number(document.getElementById("quantity").value);
    let instruction = document.getElementById("instruction").value;

    let gender = document.querySelector('input[name="gender"]:checked');
    let foods = document.querySelectorAll(".food:checked");

    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    let valid = true;

    if(name == ""){
        document.getElementById("nameError").innerHTML = "Name is required";
        valid = false;
    }

    if(email == ""){
        document.getElementById("emailError").innerHTML = "Email is required";
        valid = false;
    }
    else if(!emailPattern.test(email)){
        document.getElementById("emailError").innerHTML = "Invalid Email";
        valid = false;
    }

    if(phone == ""){
        document.getElementById("phoneError").innerHTML = "Phone Number is required";
        valid = false;
    }

    if(studentid == ""){
        document.getElementById("studentError").innerHTML = "Student ID is required";
        valid = false;
    }

    if(gender == null){
        document.getElementById("genderError").innerHTML = "Select Gender";
        valid = false;
    }

    if(department == ""){
        document.getElementById("departmentError").innerHTML = "Select Department";
        valid = false;
    }

    if(foods.length == 0){
        document.getElementById("foodError").innerHTML = "Select at least one food item";
        valid = false;
    }

    if(quantity <= 0 || isNaN(quantity)){
        document.getElementById("quantityError").innerHTML = "Quantity must be greater than 0";
        valid = false;
    }

    if(valid == false){
        return;
    }

    let total = 0;
    let selectedFoods = "";

    foods.forEach(function(food){

        let foodName = food.value;
        let price = Number(food.dataset.price);

        total = total + price;

        selectedFoods += foodName + " - $" + price + "<br>";

    });

    total = total * quantity;

    output.innerHTML =
    "<h2>Order placed successfully!</h2>" +

    "<strong>Customer Name:</strong> " + name + "<br><br>" +

    "<strong>Email:</strong> " + email + "<br><br>" +

    "<strong>Phone:</strong> " + phone + "<br><br>" +

    "<strong>Student ID:</strong> " + studentid + "<br><br>" +

    "<strong>Gender:</strong> " + gender.value + "<br><br>" +

    "<strong>Department:</strong> " + department + "<br><br>" +

    "<strong>Selected Items:</strong><br>" +
    selectedFoods + "<br>" +

    "<strong>Quantity:</strong> " + quantity + "<br><br>" +

    "<strong>Special Instruction:</strong> " + instruction + "<br><br>" +

    "<strong>Total Bill: $" + total + "</strong>";

});