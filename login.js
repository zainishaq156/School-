// login.js
function togglePassword() {
    var x = document.getElementById("password-field");
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}
document.addEventListener('DOMContentLoaded', function(){
    var toggle = document.querySelector('.toggle-password');
    if(toggle) toggle.addEventListener('click', togglePassword);
});
