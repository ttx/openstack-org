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
class EdgeComputingPage extends Page {
   static $db = array(
	);
}
 
class EdgeComputingPage_Controller extends Page_Controller {

    function init()
    {
        parent::init();

        Requirements::css("themes/openstack/css/sanfrancisco-font.css");
        Requirements::CSS('themes/openstack/css/edgecomputing.css');
        Requirements::CSS('themes/openstack/css/enterprise.css');
    }

}