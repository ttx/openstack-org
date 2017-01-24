<div class="container-fluid">
    <% cached 'frontend_schedule_speaker_page_page', $Speaker.ID, $Speaker.LastEdited %>
    <% with Speaker %>
    <div class="container">
        <div class="row schedule-title-wrapper">
            <div class="col-sm-6 col-main-title">
                <h1 style="text-align:left;">Speaker Details</h1>
                <% if $goback %>
                <div class="go-back">
                    <a href="#" onclick="window.history.back(); return false;"><< Go back </a>
                </div>
                <% end_if %>
            </div>
        </div>
        <hr/>
        <div class="speaker-photo-left">
            <a class="profile-pic-wrapper big-pic" target="_blank" href="/community/speakers/profile/{$ID}/{$NameSlug}" style="background-image: url('$ProfilePhoto(120)')"></a>
        </div>
        <div class="speaker-info">
            <div class="speaker_name row">$FirstName $LastName</div>
            <div class="speaker_job_title row"> $getTitleNice() </div>
        </div>
        <div class="row section1">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 col-md-10">
                        <div class="row speaker_bio">$Bio</div>
                    </div>
                    <div class="col-xs-12 col-md-2">
                        <div class="row social-row">
                            <div class="col-md-2 col-xs-1 social_icon">
                                <span class="info_item_icon"><i class="fa fa-2x fa-map-marker"></i></span>
                            </div>
                            <div class="col-md-10 col-xs-10 social-item">
                                <span class="info_item_text">$CountryName()</span>
                            </div>
                        </div>
                        <% if IRCHandle %>
                        <div class="row social-row">
                            <div class="col-md-2 col-xs-1 social_icon">
                                <span class="irc_icon">IRC</span>
                            </div>
                            <div class="col-md-10 col-xs-10 social-item">
                                <span class="info_item_text">$IRCHandle</span>
                            </div>
                        </div>
                        <% end_if %>
                        <% if TwitterName %>
                        <div class="row social-row">
                            <div class="col-md-2 col-xs-1 social_icon">
                                <span class="info_item_icon"><i class="fa fa-2x fa-twitter"></i></span>
                            </div>
                            <div class="col-md-10 col-xs-10 social-item">
                                <span class="info_item_text"> <a href="https://twitter.com/{$TwitterName}">@{$TwitterName}</a></span>
                            </div>

                        </div>
                        <% end_if %>
                    </div>
                </div>
            </div>
        </div>
        <div class="row sessions">
            <div class="col-md-12 col-xs-12">
                <div class="row">
                    <div class="col-md-12 col-xs-12 sessions_title">
                        Sessions
                    </div>
                </div>
                <div class="row">
                    <script type="application/javascript">

                        var summit =
                        {
                            id:   $Summit.ID,
                            link: "{$Summit.Link.JS}",
                            schedule_link: "{$Summit.getScheduleLink.JS}",
                            track_list_link: "{$Summit.getTrackListLink.JS}",
                            title: "{$Summit.Title.JS}",
                            year: "{$Summit.getSummitYear().JS}",
                            dates : [],
                            events: [],
                            speakers : {},
                            sponsors : {},
                            event_types:{},
                            locations : {},
                            tags: {},
                            tracks : {},
                            category_groups: {},
                            presentation_levels: {},
                            current_user: null,
                            should_show_venues: <% if $Summit.ShouldShowVenues %>true<% else %>false<% end_if %>
                        };

                            <% if CurrentMember && CurrentMember.isAttendee($Top.Summit.ID) %>
                                <% with CurrentMember %>
                                summit.current_user = { id: {$ID}, first_name: '{$FirstName.JS}', last_name: '{$Surname.JS}' };
                                <% end_with %>
                            <% end_if %>

                            <% loop $Top.Summit.Speakers %>
                            summit.speakers[{$ID}] =
                            {
                                id: {$ID},
                                name : "{$Name.JS}",
                                profile_pic : "{$ProfilePhoto(60).JS}",
                                position : "{$TitleNice.JS}",
                            };
                            <% end_loop %>

                            <% loop $Top.Summit.Sponsors %>
                            summit.sponsors[{$ID}] =
                            {
                                id: {$ID},
                                name : "{$Name.JS}",
                            };
                            <% end_loop %>

                            <% loop $Top.Summit.Tags %>
                            summit.tags[{$ID}] =
                            {
                                id: {$ID},
                                name : "{$Tag.JS}",
                            };
                            <% end_loop %>

                            <% loop $Top.Summit.getCategories %>
                            summit.tracks[{$ID}] =
                            {
                                id: {$ID},
                                name : "{$Title.JS}",
                            };
                            <% end_loop %>

                            <% loop $Top.getPresentationLevels %>
                            summit.presentation_levels['{$Level}'] =
                            {
                                level : "{$Level}",
                            };
                            <% end_loop %>

                            <% loop $Top.Summit.EventTypes %>
                            summit.event_types[{$ID}] =
                            {
                                type : "{$Type.JS}",
                                color : "{$Color}",
                            };
                            <% end_loop %>

                            <% loop $Top.Summit.Locations %>
                                <% if ClassName == SummitVenue || ClassName == SummitExternalLocation %>
                                summit.locations[{$ID}] =
                                {
                                    class_name  : "{$ClassName}",
                                    name        : "{$Name.JS}",
                                    name_nice   : "{$Name.JS}",
                                    description : "{$Description.JS}",
                                    address_1   : "{$Address1.JS}",
                                    address_2   : "{$Address2.JS}",
                                    city        : "{$City}",
                                    state       : "{$State}",
                                    country     : "{$Country}",
                                    lng         : '{$Lng}',
                                    lat         : '{$Lat}',
                                    venue_id    : {$Venue.ID},
                                    link        : "{$Link.JS}",
                                };
                                    <% if ClassName == SummitVenue %>
                                        <% loop Rooms %>
                                        summit.locations[{$ID}] =
                                        {
                                            class_name : "{$ClassName}",
                                            name       : "{$Name.JS}",
                                            name_nice  : "{$FullName.JS}",
                                            capacity   : {$Capacity},
                                            venue_id   : {$VenueID},
                                            link       : "{$Link.JS}",
                                        };
                                        <% end_loop %>
                                        <% loop Floors %>
                                            <% loop Rooms %>
                                            summit.locations[{$ID}] =
                                            {
                                                class_name : "{$ClassName}",
                                                name       : "{$Name.JS}",
                                                name_nice  : "{$FullName.JS}",
                                                capacity   : {$Capacity},
                                                venue_id   : {$Up.VenueID},
                                                link       : "{$Link.JS}",
                                            };
                                            <% end_loop %>
                                        <% end_loop %>
                                    <% end_if %>
                                <% end_if %>
                            <% end_loop %>

                            <% loop PublishedPresentations($Top.Summit.ID, 'MODERATOR') %>

                                    summit.events.push(
                                            {
                                                id              :  {$ID},
                                                title           : "{$Title.JS}",
                                                abstract        : "{$Abstract.JS}",
                                                date_nice       : "{$StartDate().Format(D j)}",
                                                start_datetime  : "{$StartDate}",
                                                end_datetime    : "{$EndDate}",
                                                start_time      : "{$StartTime}",
                                                end_time        : "{$EndTime}",
                                                allow_feedback  : {$AllowFeedBack},
                                                location_id     : {$LocationID},
                                                type_id         : {$TypeID},
                                                sponsors_id     : [<% loop Sponsors %>{$ID},<% end_loop %>],
                                                tags_id         : [<% loop Tags %>{$ID},<% end_loop %>],
                                                track_id        : {$CategoryID},
                                                <% if ClassName == Presentation %>
                                                    moderator_id: {$ModeratorID},
                                                    speakers_id : [<% loop Speakers %>{$ID},<% end_loop %>],
                                                    level : '{$Level}',
                                                <% end_if %>
                                                <% if CurrentMember && CurrentMember.isOnMySchedule($ID) %>
                                                    own      : true,
                                                <% else %>
                                                    own      : false,
                                                <% end_if %>
                                                favorite : false,
                                                show : true
                                            }
                                    );

                            <% end_loop %>
                            <% loop PublishedPresentations($Top.Summit.ID, 'SPEAKER') %>
                            <% if not $isModeratorByID($Up.ID) %>
                            summit.events.push(
                                    {
                                        id              :  {$ID},
                                        title           : "{$Title.JS}",
                                        abstract        : "{$Abstract.JS}",
                                        date_nice       : "{$StartDate().Format(D j)}",
                                        start_datetime  : "{$StartDate}",
                                        end_datetime    : "{$EndDate}",
                                        start_time      : "{$StartTime}",
                                        end_time        : "{$EndTime}",
                                        allow_feedback  : {$AllowFeedBack},
                                        location_id     : {$LocationID},
                                        type_id         : {$TypeID},
                                        sponsors_id     : [<% loop Sponsors %>{$ID},<% end_loop %>],
                                        tags_id         : [<% loop Tags %>{$ID},<% end_loop %>],
                                        track_id        : {$CategoryID},
                                        <% if ClassName == Presentation %>
                                            moderator_id: {$ModeratorID},
                                            speakers_id : [<% loop Speakers %>{$ID},<% end_loop %>],
                                            level       : '{$Level}',
                                        <% end_if %>
                                        <% if CurrentMember && CurrentMember.isOnMySchedule($ID) %>
                                            own      : true,
                                        <% else %>
                                            own      : false,
                                        <% end_if %>
                                        favorite : false,
                                        show : true
                                    }
                            );
                            <% end_if %>
                            <% end_loop %>
                    </script>
                    <event-list summit="{ summit }" default_event_color={'#757575'} search_url="{$Top.Link(global-search)}" base_url="{$Top.Link}" ></event-list>
                </div>
            </div>
        </div>
    </div>
    <% end_with %>
    <% end_cached %>
</div>

$ModuleJS('schedule')
$ModuleJS('event-list')
