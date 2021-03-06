<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <title>Latitude </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css" />

    <link href="css/main.css" rel="stylesheet">
    <link href="css/topbar.css" rel="stylesheet">
    <link rel="stylesheet" href="css/vendor/vendorhometable.css">
    <link rel="stylesheet" href="css/vendor/responsiveview.css">
    <link rel="stylesheet" href="./css/vendor/modal.css">

    <!-- for maps -->
    <style>
        .btn {
            color: black;
            padding: 5px 15px;
            border: none;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-blue {
            background: rgb(21, 148, 200);
            background: linear-gradient(180deg, rgba(21, 148, 200, 1) 0%, rgba(9, 42, 110, 1) 100%);
        }

        .btn-gray {
            color: #010101;
            background: rgb(255, 255, 255);
            background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(177, 177, 177, 1) 100%);
            box-shadow: 0 0 1px rgba(0, 0, 0, 0.8);
        }
    </style>
</head>

<body ng-app="myApp">
    @php
    $name=Auth::user()->name;
    @endphp
    <main>
        <!-- @include('includes.vendortop') -->
        <div ng-view class="mainbody"></div>
    </main>


    <!-- Js -->
    <script>
        var global_sequence = 0;
        var uname = '<?php echo $name; ?>';
    </script>
    <script src="./js/jquery.min.js"></script>
    <script src="./js/bootstrap.bundle.min.js"></script>
    <script src="./js/bootstrap-progressbar.min.js"></script>
    <script src="./js/angular-1.7.9/angular.min.js"></script>
    <script src="./js/angular-1.7.9/angular-route.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular-animate.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script src="<?= asset('Subadminjs/routes.js') ?>"></script>
    <script src="<?= asset('Subadminjs/VendorHome.js') ?>"></script>
    <script src="<?= asset('Subadminjs/TransctionHistory.js') ?>"></script>
    <script src="<?= asset('Subadminjs/AccountHistory.js') ?>"></script>
    <script src="<?= asset('Subadminjs/AccountReport.js') ?>"></script>
    <script src="<?= asset('Subadminjs/AccountDisputes.js') ?>"></script>


</body>

</html>