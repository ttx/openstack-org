<style>
    .hero-survey {
        background-color: transparent;
        background-image: url('assets/survey/Crowd-General-Session-3.jpg');
        background-repeat: no-repeat;
        background-position: left center;
        background-attachment: scroll;
        background-size: 100%;
        height: 300px;
        border-radius: 15px;
        -webkit-box-shadow: 8px 10px 5px 1px rgba(0, 0, 0, 0.4);
        -moz-box-shadow: 8px 10px 5px 1px rgba(0, 0, 0, 0.4);
        box-shadow: 8px 10px 5px 1px rgba(0, 0, 0, 0.4);
        margin-bottom: 25px;
    }
</style>
<div class="container">
    <h1>$_T("survey_ui", $LoginPageTitle)</h1>
    <!-- user survey report -->
    <h1>$_T("survey_ui", "Get Started")</h1>
    <% if not $Top.SurveyTemplate.isVoid && not $CurrentMember %>
        <div class="row">
            <div class="col-lg-6">
                <h3>$_T("survey_ui", "Already have an OpenStack Foundation login?")</h3>
                <div class="survey-login-wrapper">
                    <form id="MemberLoginForm_LoginForm" action="Security/login?BackURL={$Link}" method="post"
                          enctype="application/x-www-form-urlencoded">
                        <div class="Actions">
                            <input class="action" id="MemberLoginForm_LoginForm_action_dologin" type="submit"
                                   name="action_dologin" value="{$_T("survey_ui", "Log In")}" title="{$_T("survey_ui", "Log In")}"/>
                            <p id="ForgotPassword"><a href="Security/lostpassword">$_T("survey_ui", "I've lost my password")</a></p>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <h3>$_T("survey_ui", "Don't have a login? Start here.")</h3>
                <div class="survey-login-wrapper">
                    $RegisterForm
                </div>
            </div>
        </div>
    <% else %>
     <div class="row">
            <div class="col-lg-12" style="text-align: center">
                <a href="$Top.Link" title="Start Survey!" class="roundedButton">$_T("survey_ui", "Start Survey!")</a>
            </div>
        </div>
    <% end_if %>
    <hr />
    <div class="row">
        <div class="col-lg-12">
            <div class="condensed hero-survey">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <h1 style="color: white">$_T("survey_ui", "See the results from the latest User Survey")</h1>
                        </div>
                    </div>
                    <div title="Photo by the OpenStack Foundation" data-placement="left" data-toggle="tooltip"
                         class="hero-credit" data-original-title="Photo by the OpenStack Foundation">
                        <a target="_blank" href="#"><i class="fa fa-info-circle"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <h3>$_T("survey_ui", "See the OpenStack community’s opinions, adoption and technology choices")</h3>
            <p>
                $_T("survey_ui", "Read more from the eighth survey of OpenStack users since April 2013, with a goal of better understanding attitudes, organizational profiles, use cases, and technology choices across the community’s various deployment stages and sizes. This round of the survey offers highlights only, including a selection of charts most widely used by the community, and focuses only on deployments.")
            </p>
            <p>
                <a class="roundedButton" href="/assets/survey/October2016SurveyReport.pdf" target="_blank">$_T("survey_ui", "Download the October 2016 highlights report")</a>
                <a class="roundedButton" href="/assets/survey/April-2016-User-Survey-Report.pdf" target="_blank">$_T("survey_ui", "Download the full April 2016 Report")</a>
            </p>
            <h3>$_T("survey_ui", "Be your own data scientist")</h3>
            <p>
                $_T("survey_ui", "Uncover your own findings by digging into the User Survey data from the past year with a <a href=\"%s\">new analysis tool</a> available to the OpenStack community. Apply multiple filters to virtually every quantitative question from the user survey.", "/analytics")
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <h3>$_T("survey_ui", "Survey Assets")</h3>
            <p>
            <ul>
                <li><a href="https://www.youtube.com/watch?v=m9p8NUMs_PM&feature=youtu.be" target="_blank">$_T("survey_ui", "See a video overview of the October 2016 report")</a></li>
                <li><a href="/assets/survey/October2016-UserSurvey-script-charts.pdf" target="_blank">$_T("survey_ui", "Slide deck of the October 2016 highlights report")</a></li>
                <li><a href="http://www.amazon.com/dp/1532707053/" target="_blank">$_T("survey_ui", "Order a print copy of the April 2016 full report")</a></li>
                <li><a href="https://www.youtube.com/watch?v=lmu5r7BCY_U&feature=youtu.be" target="_blank">$_T("survey_ui", "Video overview of the April 2016 full report")</a></li>
                <li><a href="https://www.dropbox.com/s/8sxfm5bt57kgeys/User%20Survey%20overview%20web.pptx?dl=0" target="_blank">$_T("survey_ui", "Slide deck of the April 2016 full report")</a></li>
            </ul>
            </p>
            <h3>$_T("survey_ui", "See prior surveys")</h3>
            <p>
                $_T("survey_ui", "Learn more about past User Survey data to see how OpenStack is growing and maturing.")
            </p>
            <ul class="list-unstyled">
                <li>
                    <a href="http://www.openstack.org/assets/survey/Public-User-Survey-Report.pdf">$_T("survey_ui", "October 2015 Full report")</a>
                <li>
                    <a href="http://superuser.openstack.org/articles/user-survey-identifies-leading-industries-and-business-drivers-for-openstack-adoption"
                       target="_blank">$_T("survey_ui", "May 2015 Demographics")</a></li>
                <li>
                    <a href="http://superuser.openstack.org/articles/user-survey-identifies-leading-industries-and-business-drivers-for-openstack-adoption"
                       target="_blank">$_T("survey_ui", "May 2015 Business drivers")</a></li>
                <li>
                    <a href="http://superuser.openstack.org/articles/openstack-users-share-how-their-deployments-stack-up"
                       target="_blank">$_T("survey_ui", "May 2015 Deployments")</a></li>
                <li><a href="http://superuser.openstack.org/articles/openstack-user-survey-insights-november-2014"
                       target="_blank">$_T("survey_ui", "November 2014 Full report")</a></li>
            </ul>
        </div>
    </div>
    <!-- end - user survey report -->
    <% if $LoginPageSlide1Content && $LoginPageSlide2Content && $LoginPageSlide3Content %>
    <hr/>
    <div class="row">

        <div class="col-lg-4">
            <div id="user">
                <p>$_T("survey_ui", $LoginPageSlide1Content)</p>
            </div>
        </div>

        <div class="col-lg-4">
            <div id="time">
                <p>$_T("survey_ui", $LoginPageSlide2Content)</p>
            </div>
        </div>

        <div class="survey-box col-lg-4">
            <div id="private">
                <p>$_T("survey_ui", $LoginPageSlide3Content)</p>
            </div>
        </div>
    </div>
    <% end_if %>
    <% if  $LoginPageContent %>
     $LoginPageContent
    <% end_if %>
    <script>
        $(function () {
            var param = $('#fragment');
            param.val(window.location.hash);
        });
    </script>
</div>
