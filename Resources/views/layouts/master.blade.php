<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Module TelegramBot</title>

       {{-- Laravel Mix - CSS File --}}
       {{-- <link rel="stylesheet" href="{{ mix('css/telegrambot.css') }}"> --}}

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.14/css/bootstrap-select.css" />
        <link rel= "stylesheet" href = "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css"/>

        <style>
            .multiselect-container>li>a>label {
                width: 100%;
                padding-left: 10px;
            }

            .multiselect.dropdown-toggle.btn.btn-default {
                border: 1px solid #ced4da;
                border-radius: .25rem;
            }
        </style>

    </head>
    <body>
        @yield('content')

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
        <script src = "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.min.js"></script>

        <script>
            $('#settings_roles').multiselect({
                selectAllValue: 'multiselect-all',
                enableCaseInsensitiveFiltering: true,
                enableFiltering: true,
                onChange: function(element, checked) {
                    var brands = $('#settings_roles option:selected');
                    var selected = [];
                    $(brands).each(function(index, brand){
                        selected.push([$(this).val()]);
                    });

                    console.log(selected);
                }
            });
        </script>
        {{-- Laravel Mix - JS File --}}
        {{-- <script src="{{ mix('js/telegrambot.js') }}"></script> --}}
    </body>
</html>
