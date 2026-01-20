// fee.js
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.querySelector('.print-btn');
    if(btn) btn.addEventListener('click', function(){
        window.print();
    });
    // Optional: Confirm "mark as paid"
    document.querySelectorAll('a.del-btn').forEach(function(link){
        link.addEventListener('click', function(e){
            if(!confirm('Mark as paid?')) e.preventDefault();
        });
    });
});
