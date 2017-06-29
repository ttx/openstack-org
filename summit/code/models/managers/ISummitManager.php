<?php

/**
 * Copyright 2017 OpenStack Foundation
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
interface ISummitManager
{

    /**
     * @param $id
     * @return ISummit
     */
    public function deleteSummit($id);

    /**
     * @param $summit
     * @return ISummit
     */
    public function updateSummit($summit);

    /**
     * @param $summit_id
     * @param $summit_dates
     * @return ISummit
     */
    public function updateSummitDates($summit_id, $summit_dates);

    /**
     * @param $summit_id
     * @param $wifi_data
     * @return ISummit
     */
    public function updateSummitWifi($summit_id, $wifi_data);
}