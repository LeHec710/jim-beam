/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/main.scss';
import 'animate.css';
import 'bootstrap';
import init from './custom';

// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    $('.was-validated :invalid ~ .invalid-feedback')
                        .hide()
                        .slideDown()
                } else {
                    // var data = new FormData(form);
                    // console.log(data);
                    // $.ajax({
                    //     url: 'submit',
                    //     method: 'POST',
                    //     data: data,
                    //     processData: false,
                    //     contentType: false,
                    //     success: function (res) {
                    //         console.log(res);
                    //         if (res.success == true) {
                    //             document.location = 'confirmation';
                    //         }
                    //         if (res.error) {
                    //             $('#error')
                    //                 .html(res.error)
                    //                 .show();
                    //         }
                    //     }
                    // });
                }

            }, false);
        })

    init();
})()
