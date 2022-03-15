<header>
    <div class="topbar-left">
        <button class="menu-bar" ng-click="toggleMenu()"><i class="fas fa-bars"></i></button>
        <p class="logo">
            <i class="fas fa-angle-left"></i><i class="fas fa-angle-left"></i>
            <span> Renonights</span> <i class="fas fa-angle-right"></i><i class="fas fa-angle-right"></i>
        </p>
        <p>Reports</p>
    </div>
    <div class="topbar-right">
        <div class="logindetails">
            You logged in as
        </div>
        <div class="user">
            <div style=" margin-right:50px"><%uname%></div>
        </div>
        <i class="fas fa-sort-down"></i>
    </div>
</header>
<div class="main-wrapper">
    <div class="sidebar" ng-style="myStyle">
        <div class="close" ng-click="toggleMenu()">
            <button><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-top shadow">
            Your Balance: <b> $<%c.amount%> usd</b>
        </div>
        <div class="sidebar-bottom shadow">
            <ul>
                <li>
                    <a href="#!">
                        <i class="fas fa-plus"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#!accounthistory">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Player history</span>
                    </a>
                </li>
                <li>
                    <a href="#!transctionhistory">
                        <i class="fas fa-search"></i>
                        <span>Vendor history</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-book"></i>
                        <span>Download Link</span>
                    </a>
                </li>
                <li>
                    <a href="#!SendSmsLink">
                        <i class="fas fa-envelope"></i>
                        <span>SMS Link</span>
                    </a>
                </li>
                <li>
                    <a href="#!accountdispute">
                        <i class="fas fa-envelope"></i>
                        <span>Dispute</span>
                    </a>
                </li>
                <hr />
                <li>
                    <a title="Logout" style=" margin-right:30px" href="logoutself" style="font-size:15px; color:black; float:right; margin-right: 60px">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>
                            Logout
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="content">
        <div class="content-top shadow">
            <i class="fa fa-home"></i>Home / Send link in sms
        </div>

        <div class="data-table">
            <div class="info">
                Please Select the platform, indicate the phone number of your customer</br>
                a text message with the app link will be sent to spceified number
            </div>
            <div class="message"><%sms%></div>
            <div class="platform">
                <div class="select">Select the required platform</div>
                <div class="android">
                    <img src="media/google.png" ng-click="selectplatform('android')" alt="">
                </div>
                <div class="ios">
                    <img src="media/appstore.png" ng-click="selectplatform('ios')" alt="">
                </div>
            </div>
            <div class="number">
                <span>Phone Number</span>
                <input type="number" ng-model="number" placeholder="999999">
            </div>
            <div class="button">
                <button ng-click="sendsms()">Send</button>
            </div>
            <div class="free">
                *SMS sending if free
            </div>
        </div>
    </div>
</div>