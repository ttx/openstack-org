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
class AbstractStepForm extends HoneyPotForm
{

    function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null)
    {
        parent::__construct($controller, $name, $fields, $actions, $validator);
        // to prevent Security token doesn't match, possible CSRF attack
        $this->disableSecurityToken();
    }
    /**
     * @param $default_action
     */
    public function setDefaultAction($default_action)
    {
        $this->default_action = $default_action;
    }


    public function defaultAction()
    {
        return $this->default_action;
    }

    /**
     * @var FormAction
     */
    protected $default_action;
}