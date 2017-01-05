<?php
/**
 * Copyright 2015 OpenStack Foundation
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

class ThankYouStepForm extends AbstractStepForm {

    /**
     * @var ISurveyRegularStep
     */
    protected $step;

    /**
     * @param Controller $controller
     * @param String $name
     * @param FieldList $fields
     * @param FieldList $actions
     * @param ISurveyRegularStep $step
     * @param null $validator
     */
    function __construct($controller, $name, FieldList $fields, FieldList $actions, ISurveyRegularStep $step, $validator = null) {
        parent::__construct($controller, $name, $fields, $actions, $validator);
        $this->step = $step;
    }

    /**
     * @return ISurveyRegularStep
     */
    public function CurrentStep(){
        return $this->step;
    }

    public function SavePasswordForm(){
        return new DeploymentSurveySavePasswordForm($this, 'SavePasswordForm');
    }
}