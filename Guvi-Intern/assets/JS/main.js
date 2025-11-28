/* ---------- SIGNUP FORM ---------- */

$('#signupForm').on('submit', function(e){
    e.preventDefault();

    // Collect form values
    var name = $('#s_name').val().trim();
    var email = $('#s_email').val().trim();
    var phone = $('#s_phone').val().trim();
    var altphone = $('#s_altphone').val().trim();
    var password = $('#s_password').val();

    // Basic validation
    if (!name || !email || !phone || !password) {
        _notify("Please fill all required fields");
        return;
    }

    // AJAX request
    $.ajax({
        url: "backend/register.php",
        method: "POST",
        data: {
            name: name,
            email: email,
            phone: phone,
            altphone: altphone,
            password: password
        },
        success: function(res){
            _notify(res, 2000);

            if (res.toLowerCase().includes("successful")) {
                setTimeout(function(){
                    window.location.href = "index.html"; // redirect to login
                }, 1200);
            }
        },
        error: function(){
            _notify("Registration failed. Please try again.");
        }
    });
});
function _notify(msg, timeout){
    timeout = timeout || 1600;

    var $t = $('<div/>').css({
        position:'fixed',
        right:'20px',
        bottom:'20px',
        padding:'12px 16px',
        background:'rgba(0,0,0,0.75)',
        color:'#fff',
        borderRadius:'10px',
        fontSize:'13px',
        zIndex:99999,
        boxShadow:'0 6px 20px rgba(0,0,0,0.5)'
    }).text(msg).appendTo('body').hide().fadeIn(200);

    setTimeout(function(){
        $t.fadeOut(250, function(){ $t.remove(); });
    }, timeout);
}
