<?php

/**
 * Copyright 2014 Openstack Foundation
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
class Summit extends DataObject implements ISummit
{

    private static $db =
    [
        'Title' => 'Varchar',
        'SummitBeginDate' => 'SS_Datetime',
        'SummitEndDate' => 'SS_Datetime',
        'SubmissionBeginDate' => 'SS_Datetime',
        'SubmissionEndDate' => 'SS_Datetime',
        'VotingBeginDate' => 'SS_Datetime',
        'VotingEndDate' => 'SS_Datetime',
        'SelectionBeginDate' => 'SS_Datetime',
        'SelectionEndDate' => 'SS_Datetime',
        'RegistrationBeginDate' => 'SS_Datetime',
        'RegistrationEndDate' => 'SS_Datetime',
        'Active' => 'Boolean',
        'DateLabel' => 'Varchar',
        'Link' => 'Varchar',
        'Slug' => 'Varchar',
        'RegistrationLink' => 'Text',
        'ComingSoonBtnText' => 'Text',
        'SecondaryRegistrationLink' => 'Text',
        'SecondaryRegistrationBtnText' => 'Text',
        // https://www.eventbrite.com
        'ExternalEventId' => 'Text',
        'TimeZone' => 'Text',
        'StartShowingVenuesDate' => 'SS_Datetime',
        'MaxSubmissionAllowedPerUser' => 'Int',
        'ScheduleDefaultStartDate' => 'SS_Datetime',
        'AvailableOnApi' => 'Boolean',
        'CalendarSyncName'        => 'Varchar(255)',
        'CalendarSyncDescription' => 'Text',
    ];

    private static $defaults =
    [
        'MaxSubmissionAllowedPerUser'  => 3,
        'SecondaryRegistrationBtnText' => 'Book Hotel',
    ];

    private static $has_one = [

        'Logo' => 'BetterImage',
        'Type' => 'SummitType',
    ];

    private static $has_many = [
        'Presentations'                => 'Presentation',
        'Categories'                   => 'PresentationCategory',
        'CategoryGroups'               => 'PresentationCategoryGroup',
        'Locations'                    => 'SummitAbstractLocation',
        'EventTypes'                   => 'SummitEventType',
        'Events'                       => 'SummitEvent',
        'Attendees'                    => 'SummitAttendee',
        'SummitTicketTypes'            => 'SummitTicketType',
        'SummitRegistrationPromoCodes' => 'SummitRegistrationPromoCode',
        'Notifications'                => 'SummitPushNotification',
        'EntityEvents'                 => 'SummitEntityEvent',
        'TrackChairs'                  => 'SummitTrackChair',
        'RandomVotingLists'            => 'PresentationRandomVotingList',
        'SummitAssistances'            => 'PresentationSpeakerSummitAssistanceConfirmationRequest',
        'RSVPTemplates'                => 'RSVPTemplate',
        'SpeakerAnnouncementEmails'    => 'SpeakerAnnouncementSummitEmail',
        'SummitPackages'               => 'SummitPackage',
        'SummitAddOns'                 => 'SummitAddOn',
        'WIFIConnections'              => 'SummitWIFIConnection',
        'Sponsors'                     => 'Sponsor',
        'TrackTagGroups'               => 'TrackTagGroup'
    ];

    /**
     * @var array
     */
    private static $many_many = [
        // summit speaker announcement emails
        'ExcludedCategoriesForAcceptedPresentations'   => 'PresentationCategory',
        'ExcludedCategoriesForAlternatePresentations'  => 'PresentationCategory',
        'ExcludedCategoriesForRejectedPresentations'   => 'PresentationCategory',
        // summit speaker upload slide deck email
        'ExcludedTracksForUploadPresentationSlideDeck' => 'PresentationCategory',
    ];

    private static $many_many_extraFields = [];

    public static function get_active()
    {
        $summit = Summit::get()->filter([
            'Active' => true
        ])
            ->first();

        return $summit ?: Summit::create();
    }

    public static function get_most_recent()
    {
        return Summit::get()
            ->where('SummitEndDate < DATE(NOW())')
            ->sort('SummitEndDate DESC')
            ->first();
    }

    /**
     * @return Summit[]
     */
    public static function getNotFinished(){
        return Summit::get()
            ->where(' SummitEndDate > UTC_TIMESTAMP() ')
            ->sort('SummitEndDate ASC');
    }

    public function checkRange($key)
    {
        $start_date = $this->getField("{$key}BeginDate");
        $end_date = $this->getField("{$key}EndDate");

        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        $start_date = new DateTime($start_date, new DateTimeZone('UTC'));
        $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return ($now >= $start_date && $now <= $end_date);
    }

    public function getStatus()
    {
        if (!$this->Active) {
            return "INACTIVE";
        }
        if ($this->checkRange("Submission")) {
            return "ACCEPTING SUBMISSIONS";
        }
        if ($this->checkRange("Voting")) {
            return "COMMUNITY VOTING";
        }
        if ($this->checkRange("Selection")) {
            return "TRACK CHAIR SELECTION";
        }
        if ($this->checkRange("Registration")) {
            return "REGISTRATION";
        }
        if ($this->checkRange("Summit")) {
            return "SUMMIT IS ON";
        }

        return "DRAFT";
    }

    public function getNext()
    {
        $end_date = $this->getField('SummitEndDate');

        return Summit::get()->filter(array
        (
            'SummitEndDate:GreaterThan' => $end_date,
            'Active' => 1,
        ))->sort('SummitEndDate', 'ASC')->first();
    }

    public function getTitle()
    {
        $title = $this->getField('Title');
        $name = $this->getField('Name');

        return empty($title) ? $name : $title;
    }

    public function getSummitYear()
    {
        return date('Y', strtotime($this->getField('SummitBeginDate')));
    }

    // dates

    private function setDateTimeFromLocalToUTC($value, $field)
    {
        if (!empty($value)) {
            $value = $this->convertDateFromTimeZone2UTC($value);
            $this->setField($field, $value);
        }
    }

    private function getFromUTCtoLocal($field, $format = "Y-m-d H:i:s")
    {
        return $this->convertDateFromUTC2TimeZone($this->getField($field), $format);
    }

    public function setStartShowingVenuesDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'StartShowingVenuesDate');
    }

    public function getStartShowingVenuesDate()
    {
        return $this->getFromUTCtoLocal('StartShowingVenuesDate');
    }

    public function setSummitBeginDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SummitBeginDate');
    }

    public function getSummitBeginDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SummitBeginDate', $format);
    }

    public function setSummitEndDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SummitEndDate');
    }

    public function getSummitEndDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SummitEndDate', $format);
    }

    public function setSubmissionBeginDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SubmissionBeginDate');
    }

    public function getSubmissionBeginDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SubmissionBeginDate', $format);
    }

    public function setSubmissionEndDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SubmissionEndDate');
    }

    public function getSubmissionEndDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SubmissionEndDate', $format);
    }

    public function setVotingBeginDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'VotingBeginDate');
    }

    public function getVotingBeginDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('VotingBeginDate', $format);
    }

    public function setVotingEndDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'VotingEndDate');
    }

    public function getVotingEndDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('VotingEndDate', $format);
    }

    public function setSelectionBeginDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SelectionBeginDate');
    }

    public function getSelectionBeginDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SelectionBeginDate', $format);
    }

    public function setSelectionEndDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'SelectionEndDate');
    }

    public function getSelectionEndDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('SelectionEndDate', $format);
    }

    public function setRegistrationBeginDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'RegistrationBeginDate');
    }

    public function getRegistrationBeginDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('RegistrationBeginDate', $format);
    }

    public function setRegistrationEndDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'RegistrationEndDate');
    }

    public function getRegistrationEndDate($format = "Y-m-d H:i:s")
    {
        return $this->getFromUTCtoLocal('RegistrationEndDate', $format);
    }

    /**
     * @return string past,present,future
     */
    public function isStageTime($stage)
    {

        $start = $stage . 'BeginDate';
        $end = $stage . 'EndDate';
        $start_date = strtotime($this->convertDateFromUTC2TimeZone($this->getField($start)));
        $end_date = strtotime($this->convertDateFromUTC2TimeZone($this->getField($end)));
        $local_date = strtotime($this->getLocalTime());

        if ($local_date > $end_date) {
            return 'past';
        } else if ($local_date < $start_date) {
            return 'future';
        } else {
            return 'present';
        }
    }

    // date helper functions

    public function getBeginDateYMD()
    {
        $date = new DateTime($this->getSummitBeginDate());

        return $date->format('Y-m-d');
    }

    public function getEndDateYMD()
    {
        $date = new DateTime($this->getSummitEndDate());

        return $date->format('Y-m-d');
    }

    public function getBeginDateDMY()
    {
        $date = new DateTime($this->getSummitBeginDate());

        return $date->format('d/m/Y');
    }

    public function getEndDateDMY()
    {
        $date = new DateTime($this->getSummitEndDate());

        return $date->format('d/m/Y');
    }

    public function getBeginTime()
    {
        $date = new DateTime($this->getSummitBeginDate());

        return $date->format('H:i:s');
    }

    function TalksByMemberID($memberID)
    {

        $SpeakerList = new ArrayList();

        // Pull any talks that belong to this Summit and are owned by member
        $talksMemberOwns = $this->Talks("`OwnerID` = " . $memberID . " AND `SummitID` = " . $this->ID);
        $SpeakerList->merge($talksMemberOwns);

        // Now pull any talks that belong to this Summit and the member is listed as a speaker
        $speaker = Speaker::get()->filter('memberID', $memberID)->first();
        if ($speaker) {
            $talksMemberIsASpeaker = $speaker->TalksBySummitID($this->ID);

            // Now merge and de-dupe the lists
            $SpeakerList->merge($talksMemberIsASpeaker);
            $SpeakerList->removeDuplicates('ID');
        }

        return $SpeakerList;
    }

    /*
     * @return int
     */
    public static function CurrentSummitID()
    {
        $current = self::CurrentSummit();

        return is_null($current) ? 0 : $current->ID;
    }


    public static function ActiveSummit()
    {
        $summit = self::CurrentSummit();
        if (is_null($summit)) {
            $summit = self::GetUpcoming();
        }

        return $summit;
    }

    public static function ActiveSummitID()
    {
        $current = self::ActiveSummit();

        return is_null($current) ? 0 : $current->ID;
    }

    /**
     * @return ISummit
     */
    public static function CurrentSummit()
    {
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return Summit::get()->filter(array(
            'SummitBeginDate:LessThanOrEqual' => $now->format('Y-m-d H:i:s'),
            'SummitEndDate:GreaterThanOrEqual' => $now->format('Y-m-d H:i:s'),
            'Active' => 1
        ))->first();
    }

    /**
     * @return bool
     */
    public function IsCurrent()
    {
        $now = new \DateTime('now', new DateTimeZone('UTC'));
        $start = new \DateTime($this->SummitBeginDate, new DateTimeZone('UTC'));
        $end = new \DateTime($this->SummitEndDate, new DateTimeZone('UTC'));

        return $this->Active && $start <= $now && $end >= $now;
    }

    public function IsUpComing()
    {
        $now = new \DateTime('now', new DateTimeZone('UTC'));
        $start = new \DateTime($this->SummitBeginDate, new DateTimeZone('UTC'));
        $end = new \DateTime($this->SummitEndDate, new DateTimeZone('UTC'));

        return $this->Active && $start >= $now && $end >= $now;
    }

    public static function GetUpcoming()
    {
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return Summit::get()->filter([
            'SummitBeginDate:GreaterThanOrEqual' => $now->format('Y-m-d H:i:s'),
            'SummitEndDate:GreaterThanOrEqual' => $now->format('Y-m-d H:i:s'),
            'Active' => 1
        ])->first();
    }

    private $must_seed = false;

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->ID === 0) {
            $this->must_seed = true;
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->must_seed) {
            self::seedBasicEventTypes($this->ID);
        }
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return (int)$this->getField('ID');
    }

    /**
     * @return string
     */
    public function getName()
    {
        $value = $this->getField('Name');
        if (empty($value)) {
            $value = $this->getField('Title');
        }

        return $value;
    }

    /**
     * @return DateTime
     */
    public function getBeginDate()
    {
        return $this->getSummitBeginDate();
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->getSummitEndDate();
    }

    /**
     * @return string
     */
    public function getScheduleLink()
    {
        $page = SummitAppSchedPage::getBy($this);
        return ($page) ? $page->getAbsoluteLiveLink(false) : '#';
    }

    /**
     * @return string
     */
    public function getTrackListLink()
    {
        $page = SummitStaticCategoriesPage::get()->filter('SummitID', $this->getIdentifier())->first();
        if(!$page) $page = SummitCategoriesPage::get()->filter('SummitID', $this->getIdentifier())->first();
        return ($page) ? $page->getAbsoluteLiveLink(false) : '#';
    }

    /**
     * @return string
     */
    public function getCallForPresentationsLink()
    {
        $page = PresentationPage::get()->filter('SummitID', $this->getIdentifier())->first();
        return ($page) ? $page->getAbsoluteLiveLink(false) : '#';
    }

    /**
     * @param mixed|null $day
     * @param int|null $location
     * @return SummitEvent[]
     * @throws Exception
     */
    public function getSchedule($day = null, $location = null)
    {
        $query = new QueryObject();
        $query->addAndCondition(QueryCriteria::equal('Published', 1));
        if (!is_null($day)) {
            if (!$day instanceof DateTime) {
                $day = new DateTime($day);
            }

            $start = $day->setTime(0, 0, 0)->format("Y-m-d H:i:s");
            $end = $day->add(new DateInterval('PT23H59M59S'))->format("Y-m-d H:i:s");

            $query->addAndCondition(QueryCriteria::greaterOrEqual('StartDate',
                $this->convertDateFromTimeZone2UTC($start)));
            $query->addAndCondition(QueryCriteria::lowerOrEqual('EndDate', $this->convertDateFromTimeZone2UTC($end)));
        }
        if (!is_null($location)) {
            $query->addAndCondition(QueryCriteria::equal('LocationID', intval($location)));
        }
        $query
            ->addOrder(QueryOrder::asc('StartDate'))
            ->addOrder(QueryOrder::asc('EndDate'))
            ->addOrder(QueryOrder::asc('Title'));

        $raw_schedule      = AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Events', $query)->toArray();
        $filtered_schedule = [];
        foreach($raw_schedule as $event){
            if(!ScheduleManager::allowToSee($event)) continue;
            $filtered_schedule[] = $event;
        }
        return new ArrayList($filtered_schedule);
    }

    public function getScheduleByLevel($level = null)
    {
        $query = new QueryObject();
        $query->addAndCondition(QueryCriteria::equal('Published', 1));
        if (!is_null($level)) {
            $query->addAndCondition(QueryCriteria::equal('Level', $level));
        }

        $query
            ->addOrder(QueryOrder::asc('StartDate'))
            ->addOrder(QueryOrder::asc('EndDate'))
            ->addOrder(QueryOrder::asc('Title'));

        $raw_schedule      = AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Presentations', $query)->toArray();
        $filtered_schedule = [];
        foreach($raw_schedule as $event){
            if(!ScheduleManager::allowToSee($event)) continue;
            $filtered_schedule[] = $event;
        }
        return new ArrayList($filtered_schedule);
    }

    public function getScheduleByTrack($track = null)
    {
        $query = new QueryObject();
        $query->addAndCondition(QueryCriteria::equal('Published', 1));
        if (!is_null($track)) {
            $query->addAndCondition(QueryCriteria::equal('CategoryID', $track));
        }

        $query
            ->addOrder(QueryOrder::asc('StartDate'))
            ->addOrder(QueryOrder::asc('EndDate'))
            ->addOrder(QueryOrder::asc('Title'));

        $raw_schedule      = AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Presentations', $query)->toArray();
        $filtered_schedule = [];
        foreach($raw_schedule as $event){
            if(!ScheduleManager::allowToSee($event)) continue;
            $filtered_schedule[] = $event;
        }
        return new ArrayList($filtered_schedule);
    }

    /**
     * @param mixed $day
     * @param int $location_id
     * @return SummitEvent[]
     * @throws Exception
     */
    public function getBlackouts($day, $location_id)
    {
        $blackouts = new ArrayList();
        $location = SummitAbstractLocation::get()->byID($location_id);
        if (!is_null($location) && !$location->overridesBlackouts()) {
            $event_repository = new SapphireSummitEventRepository();
            $blackouts = $event_repository->getOtherBlackoutsByDay($this, $day, $location_id);
        }
        return $blackouts;
    }

    /**
     * @param $value
     * @param $format
     * @return null|string
     */
    public function convertDateFromTimeZone2UTC($value, $format = "Y-m-d H:i:s")
    {
        $time_zone_id = $this->TimeZone;
        if (empty($time_zone_id)) {
            return $value;
        }
        $time_zone_list = timezone_identifiers_list();

        if (isset($time_zone_list[$time_zone_id]) && !empty($value)) {
            $utc_timezone = new DateTimeZone("UTC");
            $time_zone_name = $time_zone_list[$time_zone_id];
            $time_zone = new \DateTimeZone($time_zone_name);
            $date = new \DateTime($value, $time_zone);
            $date->setTimezone($utc_timezone);

            return $date->format($format);
        }

        return null;
    }

    /**
     * @param $value
     * @param $format
     * @return null|string
     */
    public function convertDateFromUTC2TimeZone($value, $format = "Y-m-d H:i:s")
    {
        $time_zone_id = $this->TimeZone;
        if (empty($time_zone_id)) {
            return $value;
        }
        $time_zone_list = timezone_identifiers_list();

        if (isset($time_zone_list[$time_zone_id]) && !empty($value)) {
            $utc_timezone = new DateTimeZone("UTC");
            $time_zone_name = $time_zone_list[$time_zone_id];
            $time_zone = new \DateTimeZone($time_zone_name);
            $date = new \DateTime($value, $utc_timezone);

            $date->setTimezone($time_zone);

            return $date->format($format);
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getTimeZoneName()
    {
        $time_zone_id = $this->TimeZone;
        if (empty($time_zone_id)) {
            return null;
        }
        $time_zone_list = timezone_identifiers_list();
        return isset($time_zone_list[$time_zone_id]) ? $time_zone_list[$time_zone_id] : null;
    }

    /**
     * @return null|string
     */
    public function getLocalTime($format = "Y-m-d H:i:s")
    {
        $time_zone_id = $this->TimeZone;
        if (empty($time_zone_id)) {
            return 'Timezone not set';
        }
        $time_zone_list = timezone_identifiers_list();

        if (isset($time_zone_list[$time_zone_id])) {
            $utc_timezone = new DateTimeZone("UTC");
            $time_zone_name = $time_zone_list[$time_zone_id];
            $time_zone = new \DateTimeZone($time_zone_name);
            $date = new \DateTime('now', $utc_timezone);

            $date->setTimezone($time_zone);

            return $date->format($format);
        }

        return null;
    }

    /**
     * @return ISummitEventType[]
     */
    public function getEventTypes()
    {
        return AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'EventTypes');
    }

    /**
     * @param ISummitEventType $event_type
     * @throws Exception
     */
    public function addEventType(ISummitEventType $event_type)
    {
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'EventTypes')->add($event_type);
    }

    /**
     * @return ISummitAirport[]
     */
    public function getAirports()
    {
        $query = new QueryObject(new SummitAirport);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitAirport'));
        $query->addOrder(QueryOrder::asc('Order'));

        return AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->toArray();
    }

    /**
     * @param ISummitAirport $airport
     * @return void
     */
    public function addAirport(ISummitAirport $airport)
    {
        $query = new QueryObject(new SummitAirport);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitAirport'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->add($airport);
    }

    /**
     * @return void
     */
    public function clearAllAirports()
    {
        $query = new QueryObject(new SummitAirport);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitAirport'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->removeAll();
    }

    /**
     * @param bool|false $show_all
     * @param string $hotel_type
     * @return array
     * @throws Exception
     */
    public function getHotels($show_all = false, $hotel_type = 'Primary')
    {
        $filters = array
        (
            'Type' => $hotel_type,
            'SummitID' => $this->ID
        );

        if (!$show_all) {
            $filters['DisplayOnSite'] = true;
        }

        return SummitHotel::get()->filter($filters)->sort('Order', 'ASC')->toArray();
    }

    /**
     * @param ISummitHotel $hotel
     * @return void
     */
    public function addHotel(ISummitHotel $hotel)
    {
        $query = new QueryObject(new SummitHotel);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitHotel'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->add($hotel);
    }

    /**
     * @return void
     */
    public function clearAllHotels()
    {
        $query = new QueryObject(new SummitHotel);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitHotel'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->removeAll();
    }

    /**
     * @return ISummitVenue[]
     */
    public function getVenues()
    {
        $venues = $this->Locations()->where("ClassName IN ('SummitVenue','SummitExternalLocation')")->sort("Order");
        return $venues;
    }

    /**
     * @return ISummitVenue[]
     */
    public function getPrimaryVenues()
    {
        $venues = $this->Locations()->where("ClassName = 'SummitVenue'")->sort("Order");
        return $venues;
    }

    /**
     * @return int|void
     */
    public function getVenuesCount()
    {
        return count($this->getVenues());
    }

    /**
     * @return ISummitVenue
     */
    public function getMainVenue()
    {
        return SummitVenue::get()->filter(array('SummitID' => $this->ID, 'IsMain' => true))->first();
    }

    /**
     * @param ISummitVenue $venue
     * @return void
     */
    public function addVenue(ISummitVenue $venue)
    {
        $query = new QueryObject(new SummitVenue);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitVenue'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->add($venue);
    }

    /**
     * @return void
     */
    public function clearAllVenues()
    {
        $query = new QueryObject(new SummitVenue);
        $query->addAndCondition(QueryCriteria::equal('ClassName', 'SummitVenue'));
        AssociationFactory::getInstance()->getOne2ManyAssociation($this, 'Locations', $query)->removeAll();
    }

    public function getScheduleDefaultDate()
    {
        //'2016-10-26'
        $date = $this->getScheduleDefaultStartDate();
        if (empty($date)) return $this->getBeginDateYMD();
        $date = new DateTime($date);
        return $date->format('Y-m-d');
    }

    public function setScheduleDefaultStartDate($value)
    {
        $this->setDateTimeFromLocalToUTC($value, 'ScheduleDefaultStartDate');
    }

    public function getScheduleDefaultStartDate()
    {
        return $this->getFromUTCtoLocal('ScheduleDefaultStartDate');
    }

    public static $validation_enabled = true;

    protected function validate()
    {
        if (!self::$validation_enabled) return ValidationResult::create();

        $valid = parent::validate();
        if (!$valid->valid()) {
            return $valid;
        }
        $name = trim($this->Title);
        if (empty($name)) {
            return $valid->error('Title is required!');
        }

        $count = intval(Summit::get()->filter(array('Title' => $name, "ID:ExactMatch:not" => $this->ID))->count());

        if ($count > 0) {
            return $valid->error(sprintf('Summit Title %s already exists!. please set another one', $this->Title));
        }

        $time_zone = $this->TimeZone;
        if (empty($time_zone)) {
            return $valid->error('Time Zone is required!');
        }

        $start_date = $this->SummitBeginDate;
        $end_date = $this->SummitEndDate;
        $start_showing_venues_date = $this->StartShowingVenuesDate;
        $default_schedule_date = $this->ScheduleDefaultStartDate;

        if (!is_null($start_date) && !is_null($end_date)) {
            $start_date = new DateTime($start_date);
            $end_date = new DateTime($end_date);
            $start_showing_venues_date = new DateTime($start_showing_venues_date);
            $default_schedule_date = new DateTime($default_schedule_date);

            if ($start_date > $end_date) {
                return $valid->error('End Date must be greather than Start Date');
            }

            if (!is_null($start_showing_venues_date)) {
                if (!($start_showing_venues_date <= $start_date)) {
                    return $valid->error('StartShowingVenuesDate should be lower than SummitBeginDate');
                }
            }

            if (!is_null($default_schedule_date)) {
                if ($default_schedule_date < $start_date || $default_schedule_date > $end_date) {
                    return $valid->error('ScheduleDefaultStartDate should be between Summit Start/End Date');
                }
            }
        }

        $start_date = $this->RegistrationBeginDate;
        $end_date = $this->RegistrationEndDate;

        if (!is_null($start_date) && !is_null($end_date)) {
            $start_date = new DateTime($start_date);
            $end_date = new DateTime($end_date);
            if ($start_date > $end_date) {
                return $valid->error('Registration End Date must be greather than Registration Start Date');
            }
        }

        return $valid;
    }

    /**
     * @param SummitMainInfo $info
     * @return void
     */
    function registerMainInfo(SummitMainInfo $info)
    {
        $this->Name = $info->getName();
        $this->SummitBeginDate = $info->getStartDate();
        $this->SummitEndDate = $info->getEndDate();
    }

    public function isEventInsideSummitDuration(ISummitEvent $summit_event)
    {
        $event_start_date  = new DateTime($summit_event->getStartDate());
        $event_end_date    = new DateTime($summit_event->getEndDate());
        $summit_start_date = new DateTime($this->getBeginDate());
        $summit_end_date   = new DateTime($this->getEndDate());

        return $event_start_date >= $summit_start_date && $event_start_date <= $summit_end_date &&
            $event_end_date <= $summit_end_date && $event_end_date >= $event_start_date;
    }

    public function isAttendeesRegistrationOpened()
    {
        $registration_begin_date = $this->RegistrationBeginDate;
        $registration_end_date   = $this->RegistrationEndDate;

        if (is_null($registration_begin_date) || is_null($registration_end_date)) {
            return false;
        }
        $time_zone_list = timezone_identifiers_list();
        $summit_time_zone = new DateTimeZone($time_zone_list[$this->TimeZone]);

        $registration_begin_date = new DateTime($registration_begin_date, $summit_time_zone);
        $registration_end_date = new DateTime($registration_end_date, $summit_time_zone);
        $now = new DateTime("now", $summit_time_zone);

        return $now >= $registration_begin_date && $now <= $registration_end_date;
    }

    /**
     * @param string $ticket_external_id
     * @return ISummitTicketType
     */
    public function findTicketTypeByExternalId($ticket_external_id)
    {
        return $this->SummitTicketTypes()->filter('ExternalId', $ticket_external_id)->first();
    }

    /**
     * @param int $summit_id
     * @throws ValidationException
     * @throws null
     */
    public static function seedBasicEventTypes($summit_id)
    {
        foreach(DefaultSummitEventType::get() as $default_event_type){
            if(SummitEventType::get()->filter([
                'Type' => $default_event_type->Type,
                'SummitID' => $summit_id
            ])->count() > 0 ) continue;
            $event_type = $default_event_type->buildEventType($summit_id);
            $event_type->write();
        }
    }

    /**
     * @return bool
     */
    public function isAttendee()
    {
        $current_user = Member::currentUser();

        return ($current_user) ? $current_user->isAttendee($this->getIdentifier()) : false;
    }

    public function getDates()
    {
        $start_date = $this->getBeginDate();
        $end_date = $this->getEndDate();
        $res = array();
        foreach ($this->getDatesFromRange($start_date, $end_date) as $date) {
            $is_weekday = ($date->format('N') < 6) ? 1 : 0;
            $date_array = array('Label' => $date->format('l j'), 'Date' => $date->format('Y-m-d'), 'IsWeekday' => $is_weekday);
            array_push($res, new ArrayData($date_array));
        }

        return new ArrayList($res);
    }

    public function getDatesWithEvents()
    {
        $list = array();
        foreach ($this->getDates() as $day) {
            $day->Has_Published_Events = $this->hasPublishedEventOn($day->Date);
            array_push($list, $day);
        }

        return new ArrayList($list);
    }

    public function hasPublishedEventOn($day)
    {
        if (!$day instanceof DateTime) {
            $day = new DateTime($day);
        }
        $day->setTime(0, 0, 0);
        $start_date = $day->format('Y-m-d H:i:s');
        $end_date = $day->add(new DateInterval('PT23H59M59S'))->format('Y-m-d H:i:s');
        $id = $this->ID;
        $sql = <<<SQL
SELECT COUNT(E.ID) FROM SummitEvent E
WHERE E.SummitID ={$id}
AND StartDate >= '{$start_date}' AND EndDate <= '{$end_date}' AND Published = 1;
SQL;

        return (intval(DB::query($sql)->value()) > 0) ? 1 : 0;
    }

    private function getDatesFromRange($start, $end)
    {

        $start = new DateTime($start);
        $start = $start->setTime(0, 0, 0);
        $end = new DateTime($end);
        $end = $end->setTime(0, 0, 0);
        $interval = new DateInterval('P1D');
        $array[] = $start;
        $aux = clone $start;
        do {
            $aux = $aux->add($interval);
            $aux = $aux->setTime(0, 0, 0);
            $array[] = clone $aux;
        } while ($aux < $end);
        $array[] = $end;

        return $array;
    }

    /**
     * @param $event_id
     * @return SummitEvent
     * @throws Exception
     */
    public function getEventFromSchedule($event_id)
    {
        $event = $this->Events()->filter(array('Published' => 1, 'ID' => $event_id))->first();
        if ($event->ClassName == 'Presentation') {
            $event = Presentation::get()->byID($event_id);
        }

        return $event;
    }

    public function Speakers($only_published = true)
    {
        $id     = $this->ID;
        $filter = intval($only_published) ? "AND E.Published = 1 " : "";
        $dl     = new DataList('PresentationSpeaker');

        $dl = $dl->leftJoin('Member', ' Member.ID = PresentationSpeaker.MemberID')
            ->where("EXISTS
            (
                SELECT 1 FROM SummitEvent E
                INNER JOIN Presentation P ON E.ID = P.ID
                INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
                WHERE E.SummitID = {$id}
                {$filter}
                AND PS.PresentationSpeakerID = PresentationSpeaker.ID
            )
            OR
            EXISTS 
            (
               SELECT 1 FROM SummitEvent E
               INNER JOIN Presentation ON Presentation.ID = E.ID
               WHERE E.SummitID   = {$id}
               AND Presentation.ModeratorID = PresentationSpeaker.ID
               {$filter}
            )
            ");

        return $dl;
    }

    public function Tags()
    {
        $id = $this->ID;

        $sql = <<<SQL

SELECT distinct T.* FROM Tag T
INNER JOIN SummitEvent_Tags ET ON ET.TagID = T.ID
INNER JOIN SummitEvent E ON E.ID = ET.SummitEventID
WHERE E.SummitID = {$id}
SQL;

        $list = array();
        $res = DB::query($sql);
        foreach ($res as $row) {

            $class = $row['ClassName'];
            array_push($list, new $class($row));
        }

        return new ArrayList($list);
    }

    public function generateVotingLists()
    {
        DB::query("DELETE FROM PresentationRandomVotingList");
        $i = 0;
        while ($i < self::config()->random_voting_list_count) {
            $list = PresentationRandomVotingList::create([
                'SummitID' => $this->ID,
            ]);
            $list->setSequence(
                $this->VoteablePresentations()
                    ->sort('RAND()')
                    ->column('ID')
            );
            $list->write();
            $i++;
        }
    }

    public function VoteablePresentations()
    {
        return $this->Presentations()
            ->where("SummitEvent.Title IS NOT NULL")
            ->where("SummitEvent.Title <> '' ")
            ->filter('Presentation.Status', Presentation::STATUS_RECEIVED)
            ->filter('Category.VotingVisible', true);
    }

    /**
     * @return bool
     */
    public function isCallForSpeakersOpen()
    {
        $start_date = $this->getField('SubmissionBeginDate');
        $end_date = $this->getField('SubmissionEndDate');

        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        $start_date = new DateTime($start_date, new DateTimeZone('UTC'));
        $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return ($now >= $start_date && $now <= $end_date);
    }

    /**
     * @return bool
     */
    public function isVotingOpen()
    {
        return $this->checkRange('Voting');
    }

    /**
     * @return ICompany[]
     */
    public function EventSponsors()
    {
        $query = <<<SQL
SELECT DISTINCT C.* FROM SummitEvent_Sponsors S
INNER JOIN SummitEvent E ON E.ID = S.SummitEventID AND E.SummitID = {$this->ID}
INNER JOIN Company C ON C.ID = S.CompanyID
SQL;

        $list = array();
        $res = DB::query($query);
        foreach ($res as $row) {

            $class = $row['ClassName'];
            array_push($list, new $class($row));
        }

        return new ArrayList($list);

    }

    /**
     * @param $date
     * @return bool
     */
    public function belongsToDuration($date)
    {
        if (is_string($date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date);
        }
        if ($date === false) {
            return false;
        }


        $begin = new DateTime($this->getBeginDate());
        $end = new DateTime($this->getEndDate());
        $date = $date->setTime(0, 0, 0);
        $begin = $begin->setTime(0, 0, 0);
        $end = $end->setTime(0, 0, 0);

        return $begin <= $date && $date <= $end;
    }

    public function TrackGroupLists()
    {
        return SummitSelectedPresentationList::get()
            ->filter('ListType', 'Group')
            ->innerJoin('PresentationCategory', 'PresentationCategory.ID = SummitSelectedPresentationList.CategoryID')
            ->where('PresentationCategory.SummitID = ' . $this->ID)
            ->sort('PresentationCategory.Title', 'ASC');
    }

    /**
     * @return bool
     */
    public function ShouldShowVenues()
    {
        $start_showing_venue_date = $this->getField('StartShowingVenuesDate');
        if (empty($start_showing_venue_date)) return true;
        $now = new \DateTime('now', new DateTimeZone('UTC'));
        $start_showing_venue_date = new \DateTime($start_showing_venue_date, new DateTimeZone('UTC'));
        return $start_showing_venue_date <= $now;
    }

    public function isPresentationEditionAllowed()
    {
        return $this->isCallForSpeakersOpen() || $this->isVotingOpen();
    }

    public function PublishedEvents()
    {
        return $this->Events()->filter('Published', 1);
    }

    /**
     * @return string
     */
    public function Month()
    {
        $begin = new DateTime($this->getBeginDate());

        return $begin->format('F');
    }

    /**
     * @return bool
     */
    public function isSelectionOpen()
    {
        $start_date = $this->getField('SelectionBeginDate');
        $end_date = $this->getField('SelectionEndDate');

        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        $start_date = new DateTime($start_date, new DateTimeZone('UTC'));
        $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return ($now >= $start_date && $now <= $end_date);
    }

    /**
     * @return bool
     */
    public function isSelectionOver()
    {
        $start_date = $this->getField('SelectionBeginDate');
        $end_date = $this->getField('SelectionEndDate');

        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        $end_date = new DateTime($end_date, new DateTimeZone('UTC'));
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return ($now > $end_date);
    }

    /**
     * @return bool
     */
    public function isScheduleDisplayed()
    {
        $display_date = $this->getField('ScheduleDefaultStartDate');

        if (empty($display_date)) {
            return false;
        }
        $now = new \DateTime('now', new DateTimeZone('UTC'));

        return ($now >= $display_date);
    }

    public function getTopVenues()
    {
        return $this->Locations()->where("ClassName='SummitVenue' OR ClassName='SummitExternalLocation' ")->sort('Name', 'ASC');
    }

    public function getSummitDateRange()
    {
        $start = $this->obj('SummitBeginDate');
        $end = $this->obj('SummitEndDate');

        $d1 = $start->DayOfMonth();
        $d2 = $end->DayOfMonth();
        $m1 = $start->ShortMonth();
        $m2 = $end->ShortMonth();
        $y1 = $start->Year();
        $y2 = $end->Year();

        if ($y1 != $y2) return "$m1 $d1, $y1 - $m2 $d2, $y2";
        else if ($m1 != $m2) return "$m1 $d1 - $m2 $d2, $y1";
        else return "$m1 $d1 - $d2, $y1";
    }

    /**
     * @param string $day
     * @return bool
     */
    public function isDayBelongs($day)
    {
        return true;
    }

    /**
     * @param string $day
     * @param SummitAbstractLocation $location
     * @return int
     */
    public function getPublishedEventsCountByDateLocation($day, SummitAbstractLocation $location)
    {

        if (!$day instanceof DateTime) {
            $day = new DateTime($day);
        }

        $start = $day->setTime(0, 0, 0)->format("Y-m-d H:i:s");
        $end = $day->add(new DateInterval('PT23H59M59S'))->format("Y-m-d H:i:s");
        $start = $this->convertDateFromTimeZone2UTC($start);
        $end = $this->convertDateFromTimeZone2UTC($end);
        $location_id = $location->ID;

        return intval($this->Events()->where(" LocationID = {$location_id} AND Published = 1 AND StartDate >= '{$start}' AND EndDate <= '{$end}'")->count());
    }

    /**
     * @return PresentationCategory[]
     */
    public function getCategories()
    {
        return $this->Categories()->sort('Title');
    }

    /**
     * @return PresentationCategoryGroup[]
     */
    public function getPublicCategoryGroups()
    {
        return $this->CategoryGroups()->filter('ClassName', 'PresentationCategoryGroup');
    }

    /**
     * @return PrivatePresentationCategoryGroup[]
     */
    public function getPrivateCategoryGroups()
    {
        return $this->CategoryGroups()->filter('ClassName', 'PrivatePresentationCategoryGroup');
    }

    /**
     * @return PresentationCategory[]
     */
    public function getPublicCategories()
    {
        $categories = new ArrayList();
        $private_groups = $this->getPrivateCategoryGroups();

        foreach ($this->getCategories()->sort('Title') as $cat) {
            $is_private = false;
            foreach ($private_groups as $private_group) {
                if ($private_group->hasCategory($cat)) {
                    $is_private = true;
                    break;
                }
            }
            if (!$is_private)
                $categories->push($cat);
        }
        return $categories;
    }

    /**
     * @param PresentationCategory $category
     * @return bool
     */
    public function isPublicCategory(PresentationCategory $category)
    {
        return !$this->isPrivateCategory($category);
    }

    /**
     * @param PresentationCategory $category
     * @return bool
     */
    public function isPrivateCategory(PresentationCategory $category)
    {
        $res = false;
        $private_groups = $this->getPrivateCategoryGroups();
        foreach ($private_groups as $private_group) {
            if ($private_group->hasCategory($category)) {
                $res = true;
                break;
            }
        }
        return $res;
    }

    /**
     * @param PresentationCategory $category
     * @return null|PrivatePresentationCategoryGroup
     */
    public function getPrivateGroupFor(PresentationCategory $category)
    {
        $private_groups = $this->getPrivateCategoryGroups();
        foreach ($private_groups as $private_group) {
            if ($private_group->hasCategory($category)) {
                return $private_group;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getExternalEventId()
    {
        return $this->getField("ExternalEventId");
    }

    /**
     * @param string $timezone_name
     * @return string
     */
    public function getNiceVotingEnd($timezone_name = 'UTC'){
        $dt = new DateTime($this->getField('VotingEndDate'), new DateTimeZone('UTC'));
        $timezone = new DateTimeZone($timezone_name);
        $dt->setTimezone($timezone);
        return sprintf("%s at %s %s", $dt->format('l, F d'), $dt->format('H:i A'), $dt->format('T'));
    }

    /**
     * @return int[]
     */
    public function getExcludedTracksForPublishedPresentations()
    {
        return  array_values($this->ExcludedCategoriesForAcceptedPresentations()->getIDList());
    }

    /**
     * @return int[]
     */
    public function getExcludedTracksForRejectedPresentations()
    {
        return  array_values($this->ExcludedCategoriesForRejectedPresentations()->getIDList());
    }

    /**
     * @return int[]
     */
    public function getExcludedTracksForAlternatePresentations()
    {
        return  array_values($this->ExcludedCategoriesForAlternatePresentations()->getIDList());
    }

    /**
     * @return int[]
     */
    public function getExcludedTracksForUploadPresentationSlideDeck()
    {
        return array_values($this->ExcludedTracksForUploadPresentationSlideDeck()->getIDList());
    }

    /**
     * @return string
     */
    public function getCalendarSyncId(){
        $res  = "openstack-".$this->Title.'-';
        $begin_date = new DateTime($this->getSummitBeginDate());
        $res .= $begin_date->format('Y').'-summit';
        $res  = strtolower(  preg_replace('/[^a-zA-Z0-9\-]/', '',$res));
        return $res;
    }

    /**
     * @return string
     */
    public function getCalendarSyncName(){
        $val = $this->getField("CalendarSyncName");
        if(empty($val))
            $val =  $this->Title .' Calendar';
        return $val;
    }

    /**
     * @return string
     */
    public function getCalendarSyncDescription(){
        $val = $this->getField("CalendarSyncDescription");
        if(empty($val))
            $val =  'Calendar to hold Summit Events';
        return $val;
    }

    /**
     * @return int
     */
    public function getOffset(){
        $time_zone_id = $this->TimeZone;
        if (empty($time_zone_id)) {
            return 0;
        }
        $time_zone_list = timezone_identifiers_list();

        if (isset($time_zone_list[$time_zone_id])) {
            $time_zone_name = $time_zone_list[$time_zone_id];
            $time_zone      = new \DateTimeZone($time_zone_name);
            $now            = new \DateTime($this->getSummitBeginDate(), $time_zone);
            return $time_zone->getOffset($now);
        }

        return 0;
    }

    /**
     * @param Summit $summit
     * @return ArrayList
     */
    public static function getAllowedTagsForTracksBy(Summit $summit){
        $list = new ArrayList();
        $map  = [];
        foreach($summit->TrackTagGroups() as $trackTagGroup){
            foreach($trackTagGroup->AllowedTags() as $tag){
                if(isset($map[$tag->ID])) continue;
                $map[$tag->ID] = $tag->ID;
                $list->add($tag);
            }
        }
        return $list;
    }

    /**
     * @param Summit $summit
     * @param Tag $tag
     */
    public static function seedTagOnAllTracksAllowedTags(Summit $summit, Tag $tag){
        foreach($summit->Categories() as $track){
            $track->AllowedTags()->add($tag);
        }
    }

    /**
     * @param Summit $summit
     * @param Tag $tag
     */
    public static function unSeedTagOnAllTracksAllowedTags(Summit $summit, Tag $tag){
        foreach($summit->Categories() as $track){
            $track->AllowedTags()->remove($tag);
        }
    }

    public static function seedDefaultTrackTagGroups(Summit $summit){
        foreach(DefaultTrackTagGroup::get() as $default_track_tag_group){
            $new_group = new TrackTagGroup();
            // if already exists ...
            if($summit->TrackTagGroups()->where(sprintf("Label = '%s'", $default_track_tag_group->Label))->count() > 0)
                continue;
            $new_group->Name      = $default_track_tag_group->Name;
            $new_group->Label     = $default_track_tag_group->Label;
            $new_group->Order     = $default_track_tag_group->Order;
            $new_group->Mandatory = $default_track_tag_group->Mandatory;
            $summit->TrackTagGroups()->add($new_group);

            $new_group->SummitID = $summit->ID;
            $new_group->write();
            foreach ($default_track_tag_group->AllowedTags() as $tag){
                $new_group->AllowedTags()->add($tag, ['IsDefault' => true]);
            }
        }
    }

    /**
     * @param Tag $tag
     * @return TrackTagGroup|null
     */
    public function getTagGroupFor(Tag $tag){
        $res = DB::query(sprintf("SELECT TrackTagGroup.* from TrackTagGroup 
INNER JOIN TrackTagGroup_AllowedTags ON TrackTagGroup_AllowedTags.TrackTagGroupID = TrackTagGroup.ID
INNER JOIN Tag ON Tag.ID = TrackTagGroup_AllowedTags.TagID
WHERE TrackTagGroup.SummitID = %s AND Tag.ID = %s; 
", $this->ID, $tag->ID))->first();
        return $res ? new TrackTagGroup($res): null;
    }
}
