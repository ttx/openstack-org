<?php
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/


final class SummitAdminUI extends DataExtension
{
    private static $better_buttons_actions = array
    (
        'forcephase',
        'setasactive',
        'resetvotes',
        'handlevotinglists'
    );

    /**
     * @var array
     */
    private static $summary_fields = array
    (
        'Title'                   => 'Title',
        'Status'                  => 'Status',
        'FriendlyType'            => 'Type',
        'FriendlyApiAvailability' => 'Is Available through API ?'
    );

    public function getFriendlyType(){
        return $this->owner->TypeID > 0 ? $this->owner->Type()->Type : 'NOT SET';
    }

    public function getFriendlyApiAvailability(){
        return $this->owner->AvailableOnApi == true ? 'Yes' : 'No';
    }

    public function updateCMSFields(FieldList $f) {
        //clear all fields
        $oldFields = $f->toArray();
        foreach($oldFields as $field){
            $f->remove($field);
        }

        $_REQUEST['SummitID'] = $this->owner->ID;

        $f->add($rootTab = new TabSet("Root", $tabMain = new Tab('Main')));

        $summit_time_zone = null;
        if($this->owner->TimeZone) {
            $time_zone_list = timezone_identifiers_list();
            $summit_time_zone = $time_zone_list[$this->owner->TimeZone];
        }

        if ($this->owner->RandomVotingLists()->exists()) {
            $f->addFieldToTab('Root.Main',
                HeaderField::create('The presentations in this summit have been randomised for voting', 4));
        }

        $f->addFieldToTab('Root.Main', new TextField('Title', 'Title'));
        $f->addFieldToTab('Root.Main', $link = new TextField('Link', 'Summit Page Link'));

        $f->addFieldsToTab('Root.Main', $ddl_type = new DropdownField('TypeID', 'Type', SummitType::get()->map('ID', 'FriendlyName')));
        $ddl_type->setEmptyString('--SELECT A SUMMIT TYPE --');

        $link->setDescription('The link to the site page for this summit. Eg: <em>/summit/vancouver-2015/</em>');
        $f->addFieldToTab('Root.Main', new CheckboxField('Active', 'This is the active summit'));
        $f->addFieldToTab('Root.Main', new CheckboxField('AvailableOnApi', 'Is this Summit available through API? (If True this Summit will be available to Mobile Apps)'));

        $f->addFieldToTab('Root.Main', $date_label = new TextField('DateLabel', 'Date label'));
        $date_label->setDescription('A readable piece of text representing the date, e.g. <em>May 12-20, 2015</em> or <em>December 2016</em>');

        $f->addFieldToTab('Root.Main', $registration_link = new TextField('RegistrationLink', 'Registration Link'));
        $registration_link->setDescription('Link to the site where tickets can be purchased.');

        $f->addFieldToTab('Root.Main', $secondary_registration_link = new TextField('SecondaryRegistrationLink', 'Secondary Registration Link'));
        $f->addFieldToTab('Root.Main', $secondary_registration_btn_txt = new TextField('SecondaryRegistrationBtnText', 'Secondary Registration Label'));
        $secondary_registration_link->setDescription('Link to the site where you book a hotel.');

        $f->addFieldsToTab('Root.Dates',
            $ddl_timezone = new DropdownField('TimeZone', 'Time Zone', DateTimeZone::listIdentifiers()));
        $ddl_timezone->setEmptyString('-- Select a Timezone --');

        if($summit_time_zone) {
            $f->addFieldToTab('Root.Dates', new HeaderField("All dates below are in <span style='color:red;'>$summit_time_zone</span> time."));
        }
        else {
            $f->addFieldToTab('Root.Dates', new HeaderField("All dates below in the timezone of the summit's venue."));
        }

        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SummitBeginDate', "When does the summit begin?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SummitEndDate', "When does the summit end?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('StartShowingVenuesDate', "When do you begin showing venues?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');

        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SubmissionBeginDate', "When do submissions begin?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SubmissionEndDate', "When do submissions end?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');

        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('VotingBeginDate', "When does voting begin?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('VotingEndDate', "When does voting end?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SelectionBeginDate', "When do selections begin?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('SelectionEndDate', "When do selections end?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('RegistrationBeginDate', "When does registration begin?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DatetimeField('RegistrationEndDate', "When does registration end?"));
        $date->getDateField()->setConfig('showcalendar', true);
        $date->getDateField()->setConfig('dateformat', 'dd/MM/yyyy');
        $f->addFieldToTab('Root.Dates', $date = new DateField('ScheduleDefaultStartDate', "Default Start Date to show on schedule page?"));
        $date->setConfig('showcalendar', true);
        $date->setConfig('dateformat', 'dd/MM/yyyy');

        $f->addFieldsToTab('Root.Main', new NumericField('MaxSubmissionAllowedPerUser', 'Max. Submission Allowed Per User'));

        $logo_field = new UploadField('Logo', 'Logo');
        $logo_field->setAllowedMaxFileNumber(1);
        $logo_field->setAllowedFileCategories('image');
        $logo_field->setFolderName('summits/logos/');
        $logo_field->getValidator()->setAllowedMaxFileSize(1024*1024*1);
        $f->addFieldToTab('Root.Main', $logo_field);

        $f->addFieldToTab('Root.Main', new TextField('ComingSoonBtnText', 'Coming Soon Btn Text'));
        $f->addFieldToTab('Root.Main', new TextField('ExternalEventId', 'Eventbrite Event Id'));

        $f->addFieldsToTab("Root.ExternalCalendarSync", new TextField("CalendarSyncName", "External Calendar Display Name"));
        $f->addFieldsToTab("Root.ExternalCalendarSync", new TextareaField("CalendarSyncDescription", "External Calendar Description"));
        if ($this->owner->ID > 0) {
            $summit_id = $this->owner->ID;
            // tracks
            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldCopyTracksAction($summit_id));
            $categories = new GridField('Categories', 'Presentation Categories', $this->owner->getCategories(), $config);
            $f->addFieldToTab('Root.Presentation Categories', $categories);

            // track tag groups
            $config = GridFieldConfig_RecordEditor::create(100);
            $track_tag_groups = new GridField('TrackTagGroups', 'Track Tag Groups', $this->owner->TrackTagGroups(), $config);
            $config->addComponent($sort = new GridFieldSortableRows('Order'));
            $config->addComponent(new GridFieldSeedDefaultTrackTagGroupsAction);
            $f->addFieldToTab('Root.Track Tag Groups', $track_tag_groups);

            // track groups
            $config = GridFieldConfig_RecordEditor::create(50);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $multi_class_selector = new GridFieldAddNewMultiClass();
            $multi_class_selector->setClasses
            (
                array
                (
                    'PresentationCategoryGroup'        => 'Category Group',
                    'PrivatePresentationCategoryGroup' => 'Private Category Group',
                )
            );
            $config->addComponent($multi_class_selector);
            $categories = new GridField('CategoryGroups', 'Category Groups', $this->owner->CategoryGroups(), $config);
            $f->addFieldToTab('Root.Category Groups', $categories);

            // locations
            $config = GridFieldConfig_RecordEditor::create(50);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $multi_class_selector = new GridFieldAddNewMultiClass();
            $multi_class_selector->setClasses
            (
                array
                (
                    'SummitVenue'            => 'Venue',
                    'SummitHotel'            => 'Hotel',
                    'SummitAirport'          => 'Airport',
                    'SummitExternalLocation' => 'External Location',
                )
            );
            $config->addComponent($multi_class_selector);
            $config->addComponent($sort = new GridFieldSortableRows('Order'));
            $gridField = new GridField('Locations', 'Locations',
                $this->owner->Locations()->where("ClassName <> 'SummitVenueRoom' "), $config);
            $f->addFieldToTab('Root.Locations', $gridField);

            // event types
            $config = GridFieldConfig_RecordEditor::create(100);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $config->addComponent(new GridFieldAddDefaultEventTypes);
            $multi_class_selector = new GridFieldAddNewMultiClass();

            $multi_class_selector->setClasses
            (
                array
                (
                    'SummitEventType' => 'Event Type',
                    'PresentationType' => 'Presentation Type',
                )
            );

            $config->addComponent($multi_class_selector);
            $gridField = new GridField('EventTypes', 'EventTypes', $this->owner->EventTypes(), $config);
            $f->addFieldToTab('Root.EventTypes', $gridField);

            //schedule

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));
            $config->removeComponentsByType('GridFieldDeleteAction');
            $gridField = new GridField('Schedule', 'Schedule', $this->owner->Events()->filter('Published', true)->sort
            (
                array
                (
                    'StartDate' => 'ASC',
                    'EndDate'   => 'ASC'
                )
            ), $config);
            $config->getComponentByType("GridFieldDataColumns")->setFieldCasting(array("Description" => "HTMLText->BigSummary"));
            $f->addFieldToTab('Root.Schedule', $gridField);
            $config->addComponent(new GridFieldPublishSummitEventAction);

            // events

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $multi_class_selector = new GridFieldAddNewMultiClass();
            $multi_class_selector->setClasses
            (
                array
                (
                    'SummitEvent'           => 'Event',
                    'SummitEventWithFile'   => 'Event with attachment',
                    'SummitGroupEvent'      => 'Group Event',
                )
            );
            $config->addComponent($multi_class_selector);
            $config->addComponent(new GridFieldPublishSummitEventAction);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));

            $gridField = new GridField
            (
                'Events',
                'Events',
                $this->owner->Events()->filter('ClassName:ExactMatch:not', 'Presentation'),
                $config
            );

            $config
            ->getComponentByType("GridFieldDataColumns")
            ->setFieldCasting
            (
                ["Description" => "HTMLText->BigSummary"]
            );

            $f->addFieldToTab('Root.Events', $gridField);

            //track selection list presentations

            $config    = GridFieldConfig_RecordEditor::create(50);
            $gridField = new GridField('TrackChairsSelectionLists', 'TrackChairs Selection Lists',
                SummitSelectedPresentationList::get()->filter('ListType', 'Group')
                    ->where(' CategoryID IN ( SELECT ID FROM PresentationCategory WHERE SummitID = ' . $summit_id . ')')
                , $config);
            $f->addFieldToTab('Root.TrackChairs Selection Lists', $gridField);


            // attendees

            $config = GridFieldConfig_RecordEditor::create(50);
            $gridField = new GridField('Attendees', 'Attendees', $this->owner->Attendees(), $config);
            $f->addFieldToTab('Root.Attendees', $gridField);

            // tickets types

            $config = GridFieldConfig_RecordEditor::create(100);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $config->addComponent(new GridFieldAddTicketTypesFromEventbrite);
            $gridField = new GridField('SummitTicketTypes', 'Ticket Types', $this->owner->SummitTicketTypes(), $config);
            $f->addFieldToTab('Root.TicketTypes', $gridField);

            // promo codes

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $multi_class_selector = new GridFieldAddNewMultiClass();


            $multi_class_selector->setClasses
            (
                array
                (
                    'SpeakerSummitRegistrationPromoCode' => 'Speaker Promo Code',
                )
            );

            $config->addComponent($multi_class_selector);

            $promo_codes = new GridField('SummitRegistrationPromoCodes', 'Registration Promo Codes',
                $this->owner->SummitRegistrationPromoCodes(), $config);
            $f->addFieldToTab('Root.RegistrationPromoCodes', $promo_codes);

            // speakers

            $config = GridFieldConfig_RecordEditor::create(50);
            $gridField = new GridField('Speakers', 'Speakers', $this->owner->Speakers(false), $config);
            $config->getComponentByType("GridFieldDataColumns")->setFieldCasting(array("Bio" => "HTMLText->BigSummary"));
            $f->addFieldToTab('Root.Speakers', $gridField);

            // presentations

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldPublishSummitEventAction);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));

            $gridField = new GridField('Presentations', 'Presentations',
                $this->owner->Presentations()->where(" Title IS NOT NULL AND Title <>'' "), $config);
            $config->getComponentByType("GridFieldDataColumns")->setFieldCasting(array("Description" => "HTMLText->BigSummary"));
            $f->addFieldToTab('Root.Presentations', $gridField);

            // push notifications
            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));
            $config->getComponentByType('GridFieldDataColumns')->setDisplayFields
            (
                array(
                    'Created'        => 'Created (LOCAL)',
                    'Channel'        => 'Channel',
                    'Message'        => 'Message',
                    'Owner.FullName' => 'Owner',
                    'Approved'            => 'Approved',
                    'ApprovedBy.FullName' => 'Approved By',
                    'IsSent'              => 'Is Sent?',
                    'SentDate'            => 'Sent Date (UTC)',
                )
            );
            $config->getComponentByType('GridFieldDetailForm')->setItemRequestClass('GridFieldDetailFormPushNotification');
            $config->addComponent(new GridFieldApprovePushNotificationAction);
            $gridField = new GridField('Notifications', 'Notifications', $this->owner->Notifications(), $config);

            $f->addFieldToTab('Root.PushNotifications', $gridField);

            // entity events

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));
            $config->addComponent(new GridFieldWipeDevicesDataAction);
            $config->addComponent(new GridFieldDeleteAllSummitEntityEventsAction);
            $config->removeComponentsByType('GridFieldAddNewButton');
            $gridField = new GridField('EntityEvents', 'EntityEvents', $this->owner->EntityEvents(), $config);
            $f->addFieldToTab('Root.EntityEvents', $gridField);

            //TrackChairs
            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));
            $gridField = new GridField('TrackChairs', 'TrackChairs', $this->owner->TrackChairs(), $config);
            $f->addFieldToTab('Root.TrackChairs', $gridField);

            //RSVP templates

            $config = GridFieldConfig_RecordEditor::create(50);
            $config->addComponent(new GridFieldAjaxRefresh(1000, false));
            $gridField = new GridField('RSVPTemplates', 'RSVPTemplates', $this->owner->RSVPTemplates(), $config);
            $f->addFieldToTab('Root.RSVPTemplates', $gridField);

            // Summit Packages
            $config = GridFieldConfig_RecordEditor::create(PHP_INT_MAX);
            $config->addComponent(new GridFieldSortableRows('Order'));
            $gridField = new GridField('SummitPackages', 'Sponsor Packages', $this->owner->SummitPackages(), $config);
            $f->addFieldToTab('Root.Sponsor Packages', $gridField);

            // Summit Add Ons

            $config = GridFieldConfig_RecordEditor::create(PHP_INT_MAX);
            $config->addComponent(new GridFieldSortableRows('Order'));

            // Remove pagination so that you can sort all add-ons collectively
            $config->removeComponentsByType('GridFieldPaginator');
            $config->removeComponentsByType('GridFieldPageCount');

            $gridField = new GridField('SummitAddOn', 'Sponsor Add Ons', $this->owner->SummitAddOns(), $config);
            $f->addFieldToTab('Root.Sponsor Add Ons', $gridField);

            // Summit WIFI Connections

            $config = GridFieldConfig_RecordEditor::create(PHP_INT_MAX);

            $gridField = new GridField('WIFIConnections', 'WIFI Connections', $this->owner->WIFIConnections(), $config);
            $f->addFieldToTab('Root.WIFI Connections', $gridField);

            // summit speaker announcement email

            // ExcludedCategoriesForAcceptedPresentations
            $config = GridFieldConfig_RelationEditor::create(50);
            $completer = $config->getComponentByType('GridFieldAddExistingAutocompleter');
            $completer->setResultsFormat('$Title ($ID)');
            $completer->setSearchFields(array('Title', 'ID'));
            $completer->setSearchList($this->owner->Categories());
            $categories = new GridField('ExcludedCategoriesForAcceptedPresentations', 'Excluded Categories For Accepted Presentations (Announcement/Second Break Out Email)', $this->owner->ExcludedCategoriesForAcceptedPresentations(), $config);
            $f->addFieldToTab('Root.Speakers Emails', $categories);
            // ExcludedCategoriesForAlternatePresentations
            $config = GridFieldConfig_RelationEditor::create(50);
            $completer = $config->getComponentByType('GridFieldAddExistingAutocompleter');
            $completer->setResultsFormat('$Title ($ID)');
            $completer->setSearchFields(array('Title', 'ID'));
            $completer->setSearchList($this->owner->Categories());
            $categories = new GridField('ExcludedCategoriesForAlternatePresentations', 'Excluded Categories For Alternate Presentations (Announcement Email)', $this->owner->ExcludedCategoriesForAlternatePresentations(), $config);
            $f->addFieldToTab('Root.Speakers Emails', $categories);
            // ExcludedCategoriesForRejectedPresentations
            $config = GridFieldConfig_RelationEditor::create(50);
            $completer = $config->getComponentByType('GridFieldAddExistingAutocompleter');
            $completer->setResultsFormat('$Title ($ID)');
            $completer->setSearchFields(array('Title', 'ID'));
            $completer->setSearchList($this->owner->Categories());
            $categories = new GridField('ExcludedCategoriesForRejectedPresentations', 'Excluded Categories For Rejected Presentations (Announcement Email)', $this->owner->ExcludedCategoriesForRejectedPresentations(), $config);
            $f->addFieldToTab('Root.Speakers Emails', $categories);
            // ExcludedTracksForUploadPresentationSlideDeck
            $config = GridFieldConfig_RelationEditor::create(50);
            $completer = $config->getComponentByType('GridFieldAddExistingAutocompleter');
            $completer->setResultsFormat('$Title ($ID)');
            $completer->setSearchFields(array('Title', 'ID'));
            $completer->setSearchList($this->owner->Categories());
            $categories = new GridField('ExcludedTracksForUploadPresentationSlideDeck', 'Excluded Tracks For Upload Presentation Slide Deck Email', $this->owner->ExcludedTracksForUploadPresentationSlideDeck(), $config);
            $f->addFieldToTab('Root.Speakers Emails', $categories);
        }
    }


    public function getBetterButtonsActions()
    {
        $extension        = $this->owner->getExtensionInstance("BetterButtonDataObject");
        if(is_null($extension)) return;
        $extension->owner = $this->owner;
        $f                = $extension->getBetterButtonsActions();
        if (Director::isDev() && Permission::check('ADMIN')) {
            $f->push(new DropdownFormAction('Dev tools', [
                new BetterButtonNestedForm('forcephase', 'Force phase...', FieldList::create(
                    DropdownField::create('Phase', 'Choose a phase', [
                        0 => 'ACCEPTING SUBMISSIONS',
                        1 => 'COMMUNITY VOTING',
                        2 => 'TRACK CHAIR SELECTION',
                        3 => 'REGISTRATION',
                        4 => 'SUMMIT IS ON',
                    ])
                )),
                BetterButtonCustomAction::create('resetvotes', 'Reset presentation votes')
                    ->setRedirectType(BetterButtonCustomAction::REFRESH),
                BetterButtonCustomAction::create('setasactive', 'Set as active')
                    ->setRedirectType(BetterButtonCustomAction::REFRESH)
            ]));
        }

        $text = $this->owner->RandomVotingLists()->exists() ? "Regenerate random voting order" : "Generate random voting order";
        $f->push($random = BetterButtonCustomAction::create(
            'handlevotinglists',
            $text
        )
            ->setRedirectType(BetterButtonCustomAction::REFRESH)
        );
        if (!$this->owner->checkRange("Voting")) {
            $random->setConfirmation('You are randomising the presentations outside of the voting phase. If there are more presentations coming, this could cause errors. Are you sure you want to do this?');
        }
        return $f;
    }

    public function forcephase($data, $form)
    {
        $span = 10;
        $subtractor = (($data['Phase'] * $span) * -1);
        foreach (['Submission', 'Voting', 'Selection', 'Registration'] as $period) {
            $date = (new DateTime(null, new DateTimeZone('UTC')))->modify("$subtractor days");
            $this->owner->{"set" . $period . "BeginDate"}($date->format("Y-m-d"));
            $subtractor += $span;
            $date->add(DateInterval::createFromDateString("$span days"));
            $this->owner->{"set" . $period . "EndDate"}($date->format("Y-m-d"));
        }

        $this->owner->write();
        $form->sessionMessage('Phase updated', 'good');
    }

    public function resetvotes()
    {
        DB::query(sprintf(
            "DELETE FROM PresentationVote WHERE PresentationID IN (%s)",
            implode(',', $this->owner->Presentations()->column('ID'))
        ));

        return 'All votes have been reset';
    }

    public function setasactive()
    {
        DB::query("UPDATE Summit SET Active = 0");
        $this->owner->Active = 1;
        $this->owner->write();

        return 'Summit is now active';
    }

    public function handlevotinglists()
    {
        $this->owner->generateVotingLists();

        return Summit::config()->random_voting_list_count . " random incarnations created";
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        return Permission::check("ADMIN") || Permission::check("ADMIN_SUMMIT_APP") || Permission::check("ADMIN_SUMMIT_APP_SCHEDULE");
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return Permission::check("ADMIN") || Permission::check("ADMIN_SUMMIT_APP") || Permission::check("ADMIN_SUMMIT_APP_SCHEDULE");
    }

}