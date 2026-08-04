<div id="loginPage">
    <div class="lcard">
        <div class="llogo">
            <div class="llogo-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
            <div class="llogo-text"><strong>Kenya Railways Corporation</strong><span>Crew Booking System - Live</span></div>
        </div>
        <div class="lh1">Sign in</div>
        <div class="lsub">Access your depot's crew booking board</div>
        <div class="lerr" id="loginErr"></div>
        <div class="frow"><label>Username</label><input type="text" id="lUser" placeholder="e.g. hq_admin" autocomplete="username"></div>
        <div class="frow"><label>Password</label><input type="password" id="lPass" placeholder="Password" autocomplete="current-password"></div>
        <button class="btn-login" onclick="doLogin()">Sign in →</button>
        <div class="lhint" id="loginHint"></div>
    </div>
</div>
