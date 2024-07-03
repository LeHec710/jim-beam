(function () {
    const editorInstance = new FroalaEditor('.froala', {
        attribution: false,
        language: 'fr',
        key: "AVB8B-21D4D4B3F1C2C2ua2BD1IMNBUMRWAd1AYMSTRBUZYA-9H3E2J2C5C6C3A1B5B1G1==",
        enter: FroalaEditor.ENTER_P,
        placeholderText: null,
        imageUploadURL: '{{path("froala_upload_file")}}',
        imageManagerLoadURL: '{{path("froala_lib_files")}}',
        imageManagerDeleteURL: '{{path("froala_remove_file")}}',
        imageManagerPageSize: 5,
        videoUploadURL: '{{path("froala_upload_video")}}',
        events: {
            initialized: function () {
                const editor = this
            }
        }
    })
})()

$('.datepickerFr').datepicker({
    format: 'dd/mm/yyyy',
    language: 'fr',
    forceParse: false
});

$('.datetimepicker').datetimepicker({
    locale: 'fr',
    format: 'DD/MM/YYYY HH:mm',
    icons: {
        time: "fa fa-clock",
        date: "fa fa-calendar",
        up: "fa fa-chevron-up",
        down: "fa fa-chevron-down",
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-screenshot',
        clear: 'fa fa-trash',
        close: 'fa fa-remove'
    }
});

$('.selectMultiple').selectize({
    plugins: ['remove_button'],
    delimiter: ',',
    persist: false,
    create: function (input) {
        return {
            value: input,
            text: input
        }
    }
});

$("body").delegate(".confirmBox", "click", function () {
    var url = $(this).data('url');
    var message = $(this).data('message');
    $('#confirmBox').find('.modal-body').html(message);
    $('#confirmBox').find('#confirmBoxUrl').attr("href", url);
    $('#confirmBox').modal('show');
});

$('#confirmBox').on('hidden.bs.modal', function (e) {
    $('#confirmBox').find('.modal-body').html('');
    $('#confirmBox').find('#confirmBoxUrl').attr("href", '#!');
});

function initForm() {
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
}
initForm();


